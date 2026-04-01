<?php
// Function to sanitize input
function sanitizeInput($input, $type = 'string') {
    if ($input === null || $input === '') {
        return null;
    }
    
    switch($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 1000
                ]
            ]);
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'alpha':
            return preg_match('/^[a-zA-Z_]+$/', $input) ? $input : null;
        case 'order':
            return in_array($input, ['newest', 'oldest', 'name_asc', 'name_desc']) ? $input : 'newest';
        case 'status':
            return in_array($input, ['all', 'active', 'inactive', 'pending']) ? $input : 'all';
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>