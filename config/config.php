<?php
// config.php

function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

$envPath = realpath(__DIR__ . '/../.env');
if ($envPath) {
    loadEnv($envPath);
}

// Use getenv() as a backup to $_ENV
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$db   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ''); // <--- MUST MATCH .ENV KEY
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$charset = 'utf8mb4';
$port = '3306'; 
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Connection logic
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // If connection fails here, we MUST return JSON or the frontend crashes
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Database Connection Failed: " . $e->getMessage()
    ]);
    exit;
}