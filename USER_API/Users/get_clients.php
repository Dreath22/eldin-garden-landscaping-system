<?php
function getClient($pdo){
    try {
        // Select the fields you need for the dropdown
        $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC");
        $customers = $stmt->fetchAll();

        echo json_encode(["customers" => $customers]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch customers data"]);
    }
}
getClient($pdo)

?>