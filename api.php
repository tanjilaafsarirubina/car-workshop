<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');

$pdo = getDBConnection();

switch ($action) {
    case 'get_slots':
        getSlots($pdo);
        break;
    case 'book':
        bookAppointment($pdo);
        break;
    case 'get_appointments':
        getAppointments($pdo);
        break;
    case 'update_appointment':
        updateAppointment($pdo);
        break;
    case 'cancel_appointment':
        cancelAppointment($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getSlots($pdo) {
    $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $stmt = $pdo->query("SELECT * FROM mechanics ORDER BY id ASC");
    $mechanics = $stmt->fetchAll();

    $result = [];
    foreach ($mechanics as $m) {
        $mId = $m['id'];
        $countStmt = $pdo->prepare("SELECT COUNT(*) as booked FROM appointments WHERE mechanic_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $countStmt->execute([$mId, $date]);
        $row = $countStmt->fetch();
        $booked = (int)$row['booked'];
        $maxSlots = (int)$m['max_daily_slots'];
        $available = max(0, $maxSlots - $booked);

        $result[] = [
            'id' => (int)$m['id'],
            'name' => $m['name'],
            'specialty' => $m['specialty'],
            'phone' => $m['phone'],
            'max_daily_slots' => $maxSlots,
            'booked_count' => $booked,
            'available_slots' => $available,
            'is_full' => ($available <= 0)
        ];
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'mechanics' => $result
    ]);
    exit;
}

function bookAppointment($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $client_name = trim($_POST['client_name'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $car_license = trim($_POST['car_license'] ?? '');
    $car_engine  = trim($_POST['car_engine'] ?? '');
    $date        = trim($_POST['appointment_date'] ?? '');
    $mechanic_id = (int)($_POST['mechanic_id'] ?? 0);

    $errors = [];

    if (empty($client_name)) {
        $errors[] = 'Client Name is required.';
    }
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }
    if (empty($phone) || !preg_match('/^[0-9+\s\-()]{6,20}$/', $phone)) {
        $errors[] = 'Valid Phone number is required (must contain only numbers/phone symbols).';
    }
    if (empty($car_license)) {
        $errors[] = 'Car License Number is required.';
    }
    if (empty($car_engine) || !preg_match('/^[a-zA-Z0-9\s\-]+$/', $car_engine)) {
        $errors[] = 'Car Engine Number is required (alphanumeric).';
    }
    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = 'A valid Appointment Date (YYYY-MM-DD) is required.';
    } else {
        $today = date('Y-m-d');
        if ($date < $today) {
            $errors[] = 'Appointment date cannot be in the past.';
        }
    }
    if ($mechanic_id <= 0) {
        $errors[] = 'Please select a desired mechanic from the available list.';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    $mStmt = $pdo->prepare("SELECT * FROM mechanics WHERE id = ?");
    $mStmt->execute([$mechanic_id]);
    $mechanic = $mStmt->fetch();
    if (!$mechanic) {
        echo json_encode(['success' => false, 'message' => 'Selected mechanic does not exist.']);
        exit;
    }

    $dupStmt = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE appointment_date = ? 
          AND (phone = ? OR car_license = ?) 
          AND status != 'cancelled'
    ");
    $dupStmt->execute([$date, $phone, $car_license]);
    if ($dupStmt->fetch()) {
        echo json_encode([
            'success' => false,
            'error_type' => 'DUPLICATE_CLIENT',
            'message' => "You have already taken an appointment on " . date('F j, Y', strtotime($date)) . ". Duplicate appointments on the same date are not allowed."
        ]);
        exit;
    }

    $capStmt = $pdo->prepare("
        SELECT COUNT(*) as booked FROM appointments 
        WHERE mechanic_id = ? AND appointment_date = ? AND status != 'cancelled'
    ");
    $capStmt->execute([$mechanic_id, $date]);
    $bookedRow = $capStmt->fetch();
    $bookedCount = (int)$bookedRow['booked'];
    $maxSlots = (int)$mechanic['max_daily_slots'];

    if ($bookedCount >= $maxSlots) {
        echo json_encode([
            'success' => false,
            'error_type' => 'MECHANIC_FULL',
            'message' => "Mechanic " . $mechanic['name'] . " is fully occupied (" . $bookedCount . "/" . $maxSlots . " appointments) on " . date('F j, Y', strtotime($date)) . ". Please select another free mechanic."
        ]);
        exit;
    }

    $insStmt = $pdo->prepare("
        INSERT INTO appointments (client_name, address, phone, car_license, car_engine, appointment_date, mechanic_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $success = $insStmt->execute([$client_name, $address, $phone, $car_license, $car_engine, $date, $mechanic_id]);

    if ($success) {
        $insertId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'appointment_id' => $insertId,
            'message' => "Appointment approved! You are booked with " . $mechanic['name'] . " on " . date('F j, Y', strtotime($date)) . "."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save appointment. Please try again.']);
    }
    exit;
}

function getAppointments($pdo) {
    $search = trim($_GET['search'] ?? '');
    $date = trim($_GET['date'] ?? '');
    $mechanic_id = (int)($_GET['mechanic_id'] ?? 0);

    $sql = "
        SELECT a.*, m.name as mechanic_name, m.specialty as mechanic_specialty
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($date)) {
        $sql .= " AND a.appointment_date = ?";
        $params[] = $date;
    }

    if ($mechanic_id > 0) {
        $sql .= " AND a.mechanic_id = ?";
        $params[] = $mechanic_id;
    }

    if (!empty($search)) {
        $sql .= " AND (a.client_name LIKE ? OR a.phone LIKE ? OR a.car_license LIKE ? OR a.car_engine LIKE ?)";
        $term = "%" . $search . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    exit;
}

function updateAppointment($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $id          = (int)($_POST['id'] ?? 0);
    $new_date    = trim($_POST['appointment_date'] ?? '');
    $mechanic_id = (int)($_POST['mechanic_id'] ?? 0);

    if ($id <= 0 || empty($new_date) || $mechanic_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
        exit;
    }

    $appStmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $appStmt->execute([$id]);
    $appointment = $appStmt->fetch();

    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
        exit;
    }

    $mStmt = $pdo->prepare("SELECT * FROM mechanics WHERE id = ?");
    $mStmt->execute([$mechanic_id]);
    $mechanic = $mStmt->fetch();

    if (!$mechanic) {
        echo json_encode(['success' => false, 'message' => 'Target mechanic does not exist.']);
        exit;
    }

    $dupCheck = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE appointment_date = ? 
          AND (phone = ? OR car_license = ?) 
          AND id != ? 
          AND status != 'cancelled'
    ");
    $dupCheck->execute([$new_date, $appointment['phone'], $appointment['car_license'], $id]);
    if ($dupCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot change date: Client already has another appointment on " . $new_date . "."
        ]);
        exit;
    }

    $capCheck = $pdo->prepare("
        SELECT COUNT(*) as booked FROM appointments 
        WHERE mechanic_id = ? AND appointment_date = ? AND id != ? AND status != 'cancelled'
    ");
    $capCheck->execute([$mechanic_id, $new_date, $id]);
    $bookedRow = $capCheck->fetch();
    $bookedCount = (int)$bookedRow['booked'];
    $maxSlots = (int)$mechanic['max_daily_slots'];

    if ($bookedCount >= $maxSlots) {
        echo json_encode([
            'success' => false,
            'message' => "Mechanic " . $mechanic['name'] . " is fully occupied (" . $bookedCount . "/" . $maxSlots . ") on " . $new_date . ". Choose another mechanic."
        ]);
        exit;
    }

    $upStmt = $pdo->prepare("
        UPDATE appointments 
        SET appointment_date = ?, mechanic_id = ? 
        WHERE id = ?
    ");
    $success = $upStmt->execute([$new_date, $mechanic_id, $id]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => "Appointment updated successfully to " . $new_date . " with " . $mechanic['name'] . "."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update appointment.']);
    }
    exit;
}

function cancelAppointment($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled/deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete appointment.']);
    }
    exit;
}
