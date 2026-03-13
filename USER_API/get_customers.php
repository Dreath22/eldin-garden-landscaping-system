<?php
header("Content-Type: application/json");
require_once '../config/config.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Select the fields you need for the dropdown
    $stmt1 = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC");
    $customers = $stmt1->fetchAll();

    $stmt2 = $pdo->query("SELECT id, service_name, base_price AS basePrice FROM services where status = 'active' ORDER BY service_name ASC");
    $services = $stmt2->fetchAll();

    echo json_encode(["customers" => $customers, "services" => $services]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch form data"]);
}