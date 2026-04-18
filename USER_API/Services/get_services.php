<?php

function getServices($pdo) {
    try{
        $stmt = $pdo->query("SELECT id, service_name, base_price AS basePrice FROM services where status = 'Active' ORDER BY service_name ASC");
        $services = $stmt->fetchAll();
        echo json_encode(["services" => $services]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch services"]);
    }
}
getServices($pdo)
?>