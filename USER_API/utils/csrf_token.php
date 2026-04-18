<?php
// CSRF Token Generator
require_once 'SecurityMiddleware.php';

session_start();

$security = new SecurityMiddleware();
$token = $security->generateCsrfToken();

header('Content-Type: application/json');
echo json_encode(['token' => $token]);
?>
