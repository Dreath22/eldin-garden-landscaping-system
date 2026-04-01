<?php
// Simple test to check POST size limits
header("Content-Type: application/json");

// Check PHP POST limits
echo json_encode([
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_input_vars' => ini_get('max_input_vars'),
    'memory_limit' => ini_get('memory_limit'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set'
]);
?>
