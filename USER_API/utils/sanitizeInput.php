<?php
// Your sanitization utility
function sanitizeInput($input, string $type = 'string', ?array $options = null) {
    if ($input === null || $input === '') return null;

    return match ($type) {
        'int' => filter_var($input, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000]
        ]) ?: null,
        
        default => htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8'),
    };
}

// Your allow-list utility
function validateInput($input, array $validValues) {
    return in_array($input, $validValues, true) ? $input : $validValues[0];
}
?>