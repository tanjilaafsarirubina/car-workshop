<?php
// Database settings come from config.local.php (git-ignored, copy
// config.local.example.php) or from environment variables. With neither,
// the app runs on a local SQLite file, so `php -S` works out of the box.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

function defineSetting($name, $default) {
    if (defined($name)) {
        return;
    }
    $value = getenv($name);
    define($name, ($value === false || $value === '') ? $default : $value);
}

defineSetting('DB_DRIVER', 'sqlite');
defineSetting('DB_HOST', 'localhost');
defineSetting('DB_PORT', '3306');
defineSetting('DB_NAME', 'car_workshop_db');
defineSetting('DB_USER', 'root');
defineSetting('DB_PASS', '');

defineSetting('SQLITE_FILE', __DIR__ . '/workshop.sqlite');

// "Today" decides which dates can be booked, so it has to be the workshop's
// today, not the server's.
defineSetting('APP_TIMEZONE', 'Asia/Dhaka');
date_default_timezone_set(APP_TIMEZONE);

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = DB_DRIVER;
    try {
        if ($driver === 'mysql') {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }
    } catch (PDOException $e) {
        try {
            $dsn = "sqlite:" . SQLITE_FILE;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            initSQLiteTables($pdo);
        } catch (PDOException $sqle) {
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $sqle->getMessage()
            ]));
        }
    }

    if (!$pdo && DB_DRIVER === 'sqlite') {
        $dsn = "sqlite:" . SQLITE_FILE;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        initSQLiteTables($pdo);
    }

    if ($pdo) {
        ensureSeeded($pdo);
    }

    return $pdo;
}

function initSQLiteTables($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mechanics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            specialty TEXT NOT NULL,
            phone TEXT NOT NULL,
            max_daily_slots INTEGER NOT NULL DEFAULT 4,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS appointments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_name TEXT NOT NULL,
            address TEXT NOT NULL,
            phone TEXT NOT NULL,
            car_license TEXT NOT NULL,
            car_engine TEXT NOT NULL,
            appointment_date TEXT NOT NULL,
            mechanic_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE
        );
    ");
}

function ensureSeeded($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM mechanics");
        $row = $stmt->fetch();
        if ($row && $row['cnt'] == 0) {
            $seed = [
                [1, 'Karim Rahman', 'Engine Overhaul & Diagnostics', '+880 1711-000001', 4],
                [2, 'Tanvir Ahmed', 'Transmission & Gearbox Expert', '+880 1711-000002', 4],
                [3, 'Rahim Uddin', 'Auto Electrical & ECU Tuning', '+880 1711-000003', 4],
                [4, 'Shafiqul Islam', 'Brake Systems & Suspension', '+880 1711-000004', 4],
                [5, 'Mahfuz Khan', 'Hybrid & AC Climate Control', '+880 1711-000005', 4]
            ];
            $insert = $pdo->prepare("INSERT INTO mechanics (id, name, specialty, phone, max_daily_slots) VALUES (?, ?, ?, ?, ?)");
            foreach ($seed as $m) {
                $insert->execute($m);
            }
        }
    } catch (Exception $e) {
    }
}
