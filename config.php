<?php
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'sql208.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_42459246_car_workshop_db');
define('DB_USER', 'if0_42459246');
define('DB_PASS', '[REDACTED]');

define('SQLITE_FILE', __DIR__ . '/workshop.sqlite');

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
