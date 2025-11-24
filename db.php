<?php
// db.php
$config = require __DIR__ . '/config.php';

function getPDO(){
    global $config;
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        if ($config->db_driver === 'sqlite') {
            $dsn = 'sqlite:' . $config->sqlite_path;
            $pdo = new PDO($dsn);
        } else {
            $pdo = new PDO($config->mysql_dsn, $config->mysql_user, $config->mysql_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        // common settings
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed', 'message' => $e->getMessage()]);
        exit;
    }
}
