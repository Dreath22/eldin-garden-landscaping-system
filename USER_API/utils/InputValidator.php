<?php
class InputValidator {
    private $errors = [];
    private $sanitized = [];
    
    // Main validation method
    public function validate(array $data, array $rules): self {
        $this->errors = [];
        $this->sanitized = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->sanitized[$field] = $this->applyRules($value, $fieldRules, $field);
        }
        
        return $this;
    }
    
    // Apply validation rules
    private function applyRules($value, array $rules, string $field): mixed {
        foreach ($rules as $rule => $params) {
            $method = 'validate' . ucfirst($rule);
            if (method_exists($this, $method)) {
                $value = $this->$method($value, $params, $field);
            }
        }
        return $value;
    }
    
    // Validation rule methods
    private function validateRequired($value, $params, string $field) {
        if ($value === null || $value === '') {
            $this->errors[$field] = ($params['message'] ?? ucfirst($field) . ' is required');
        }
        return $value;
    }
    
    private function validateString($value, $params, string $field) {
        if ($value !== null) {
            $maxLength = $params['max'] ?? 255;
            $minLength = $params['min'] ?? 1;
            
            $value = trim((string)$value);
            
            if (strlen($value) < $minLength) {
                $this->errors[$field] = ($params['message'] ?? ucfirst($field) . ' must be at least ' . $minLength . ' characters');
            }
            
            if (strlen($value) > $maxLength) {
                $this->errors[$field] = ($params['message'] ?? ucfirst($field) . ' must not exceed ' . $maxLength . ' characters');
            }
            
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    private function validateEmail($value, $params, string $field) {
        if ($value !== null) {
            $value = trim((string)$value);
            
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = ($params['message'] ?? 'Invalid email format');
            }
            
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    private function validateInt($value, $params, string $field) {
        if ($value !== null) {
            $options = ['options' => []];
            
            if (isset($params['min'])) $options['options']['min_range'] = $params['min'];
            if (isset($params['max'])) $options['options']['max_range'] = $params['max'];
            
            $sanitized = filter_var($value, FILTER_VALIDATE_INT, $options);
            
            if ($sanitized === false) {
                $this->errors[$field] = ($params['message'] ?? 'Invalid integer value');
            }
            
            return $sanitized;
        }
        return $value;
    }
    
    private function validateEnum($value, $params, string $field) {
        if ($value !== null) {
            $allowedValues = $params['values'] ?? [];
            
            if (!in_array($value, $allowedValues, true)) {
                $this->errors[$field] = ($params['message'] ?? 'Invalid value for ' . $field);
            }
            
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    // Get validation results
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getSanitized(): array {
        return $this->sanitized;
    }
    
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
}
?>
