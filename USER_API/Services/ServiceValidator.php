<?php

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/ServiceConfig.php';

class ServiceValidator {
    
    private const ALLOWED_CREATE_FIELDS = ['name', 'description', 'baseprice', 'duration', 'status'];
    private const ALLOWED_UPDATE_FIELDS = ['name', 'description', 'baseprice', 'duration', 'status'];
    
    public static function validateId($id): array {
        $id = sanitizeInput($id, 'int');
        if (!$id) {
            return ['valid' => false, 'value' => null, 'error' => 'Invalid service ID'];
        }
        return ['valid' => true, 'value' => $id, 'error' => null];
    }
    
    public static function validateCreateData(array $data): array {
        $errors = [];
        $validated = [];
        
        // FIELD WHITELISTING - Prevent injection via field names
        $data = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));
        
        // Validate required fields
        if (!isset($data['name']) || $data['name'] === null) {
            $errors['name'] = 'Name is required';
        } else {
            $name = sanitizeInput($data['name']);
            if ($name === null || $name === '') {
                $errors['name'] = 'Name cannot be empty';
            } elseif (strlen($name) > 255) {
                $errors['name'] = 'Name must be less than 255 characters';
            } else {
                $validated['name'] = $name;
            }
        }
        
        // Validate description (optional)
        if (isset($data['description'])) {
            if ($data['description'] === null || $data['description'] === '') {
                $validated['description'] = null; // Allow empty description
            } else {
                $description = sanitizeInput($data['description']);
                if ($description !== null && strlen($description) <= 1000) {
                    $validated['description'] = $description;
                } else {
                    $errors['description'] = 'Description must be less than 1000 characters';
                }
            }
        }
        
        // Validate baseprice (required) - Enhanced validation
        if (!isset($data['baseprice']) || $data['baseprice'] === null) {
            $errors['baseprice'] = 'Base price is required';
        } else {
            $baseprice = sanitizeInput($data['baseprice'], 'int');
            if ($baseprice === null || $baseprice === false) {
                $errors['baseprice'] = 'Invalid price format';
            } elseif (!is_numeric($baseprice) || $baseprice < ServiceConfig::MIN_PRICE || $baseprice > ServiceConfig::MAX_PRICE) {
                $errors['baseprice'] = 'Base price must be between ' . ServiceConfig::MIN_PRICE . ' and ' . ServiceConfig::MAX_PRICE;
            } else {
                $validated['baseprice'] = (float)$baseprice; // Ensure proper numeric type
            }
        }
        
        // Validate features (optional)
        if (isset($data['features'])) {
            if ($data['features'] === null || $data['features'] === '') {
                $validated['features'] = null; // Allow empty features
            } else {
                $features = sanitizeInput($data['features']);
                if ($features !== null && strlen($features) <= 2000) { // Allow longer text for features
                    $validated['features'] = $features;
                } else {
                    $errors['features'] = 'Features must be less than 2000 characters';
                }
            }
        }
        
        // Validate duration (optional)
        if (isset($data['duration'])) {
            if ($data['duration'] === null || $data['duration'] === '') {
                $validated['duration'] = null; // Allow empty duration
            } else {
                $duration = sanitizeInput($data['duration']);
                if ($duration !== null && strlen($duration) <= 100) {
                    $validated['duration'] = $duration;
                } else {
                    $errors['duration'] = 'Duration must be less than 100 characters';
                }
            }
        }
        
        // Validate status (optional, defaults to active)
        if (isset($data['status'])) {
            if (in_array($data['status'], ServiceConfig::VALID_STATUSES)) {
                $validated['status'] = $data['status'];
            } else {
                $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', ServiceConfig::VALID_STATUSES);
            }
        } else {
            $validated['status'] = 'active'; // Default status
        }
        
        return [
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        ];
    }
    
    public static function validateUpdateData(array $data): array {
        $errors = [];
        $validated = [];
        
        // Validate name (always process if provided)
        if (array_key_exists('name', $data)) {
            if ($data['name'] === null || $data['name'] === '') {
                $errors['name'] = 'Name cannot be empty';
            } else {
                $name = sanitizeInput($data['name']);
                if ($name !== null && strlen($name) <= 255) {
                    $validated['name'] = $name;
                } else {
                    $errors['name'] = 'Name must be less than 255 characters';
                }
            }
        }
        
        // Validate description (always process if provided)
        if (array_key_exists('description', $data)) {
            if ($data['description'] === null || $data['description'] === '') {
                $validated['description'] = null; // Allow empty description
            } else {
                $description = sanitizeInput($data['description']);
                if ($description !== null && strlen($description) <= 1000) {
                    $validated['description'] = $description;
                } else {
                    $errors['description'] = 'Description must be less than 1000 characters';
                }
            }
        }
        
        // Validate baseprice (always process if provided)
        if (array_key_exists('baseprice', $data)) {
            if ($data['baseprice'] === null || $data['baseprice'] === '') {
                $errors['baseprice'] = 'Base price is required';
            } else {
                $baseprice = sanitizeInput($data['baseprice'], 'float');
                if ($baseprice === null || $baseprice === false) {
                    $errors['baseprice'] = 'Invalid price format';
                } elseif (!is_numeric($baseprice) || $baseprice < ServiceConfig::MIN_PRICE || $baseprice > ServiceConfig::MAX_PRICE) {
                    $errors['baseprice'] = 'Base price must be between ' . ServiceConfig::MIN_PRICE . ' and ' . ServiceConfig::MAX_PRICE;
                } else {
                    $validated['baseprice'] = (float)$baseprice;
                }
            }
        }
        
        // Validate duration (always process if provided)
        if (array_key_exists('duration', $data)) {
            if ($data['duration'] === null || $data['duration'] === '') {
                $validated['duration'] = null; // Allow empty duration
            } else {
                $duration = sanitizeInput($data['duration']);
                if ($duration !== null && strlen($duration) <= 100) {
                    $validated['duration'] = $duration;
                } else {
                    $errors['duration'] = 'Duration must be less than 100 characters';
                }
            }
        }
        
        // Validate status (always process if provided)
        if (array_key_exists('status', $data)) {
            if ($data['status'] === null || $data['status'] === '') {
                $validated['status'] = 'active'; // Default status
            } else {
                if (in_array($data['status'], ServiceConfig::VALID_STATUSES)) {
                    $validated['status'] = $data['status'];
                } else {
                    $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', ServiceConfig::VALID_STATUSES);
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        ];
    }
}
