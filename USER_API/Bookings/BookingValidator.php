<?php

require_once __DIR__ . '/../utils/InputValidator.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/BookingConfig.php';
require_once __DIR__ . '/BookingCriteria.php';

class BookingValidator {
    
    public static function validateListParams(array $params): array {
        $errors = [];
        $validated = [];
        
        // Validate page
        if (isset($params['page'])) {
            $validator = new InputValidator();
            $validator->validate(['page' => $params['page']], ['page' => ['int' => ['min' => 1, 'max' => BookingConfig::MAX_PAGE_NUMBER]]]);
            
            if ($validator->hasErrors()) {
                $errors['page'] = 'Page must be between 1 and ' . BookingConfig::MAX_PAGE_NUMBER;
            } else {
                $sanitized = $validator->getSanitized();
                $validated['page'] = $sanitized['page'];
            }
        } else {
            $validated['page'] = 1;
        }
        
        // Validate status
        if (isset($params['status'])) {
            $validator = new InputValidator();
            $validator->validate(['status' => $params['status']], ['status' => ['enum' => ['values' => BookingConfig::VALID_STATUSES]]]);
            
            if ($validator->hasErrors()) {
                $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', BookingConfig::VALID_STATUSES);
            } else {
                $sanitized = $validator->getSanitized();
                $validated['status'] = $sanitized['status'];
            }
        } else {
            $validated['status'] = 'all';
        }
        
        // Validate category
        if (isset($params['category'])) {
            $validator = new InputValidator();
            $validator->validate(['category' => $params['category']], ['category' => ['int' => ['min' => 0]]]);
            
            if ($validator->hasErrors()) {
                $errors['category'] = 'Category must be a valid positive integer';
            } else {
                $sanitized = $validator->getSanitized();
                $validated['category'] = $sanitized['category'];
            }
        } else {
            $validated['category'] = '0';
        }
        
        // Validate order
        if (isset($params['order'])) {
            $validator = new InputValidator();
            $validator->validate(['order' => $params['order']], ['order' => ['enum' => ['values' => BookingConfig::VALID_SORT_OPTIONS]]]);
            
            if ($validator->hasErrors()) {
                $errors['order'] = 'Invalid order. Must be one of: ' . implode(', ', BookingConfig::VALID_SORT_OPTIONS);
            } else {
                $sanitized = $validator->getSanitized();
                $validated['order'] = $sanitized['order'];
            }
        } else {
            $validated['order'] = 'newest';
        }
        
        // Validate fromDate
        if (isset($params['from']) && !empty($params['from'])) {
            $fromDate = sanitizeInput($params['from']);
            if ($fromDate === null || !strtotime($fromDate)) {
                $errors['from'] = 'Invalid date format';
            } else {
                $validated['from'] = $fromDate;
            }
        }
        
        // Validate search
        if (isset($params['search']) && !empty($params['search'])) {
            $search = sanitizeInput($params['search']);
            if ($search === null || strlen($search) > BookingConfig::MAX_SEARCH_LENGTH) {
                $errors['search'] = 'Search term must be less than ' . BookingConfig::MAX_SEARCH_LENGTH . ' characters';
            } else {
                $validated['search'] = trim($search);
            }
        }
        
        return [
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        ];
    }
    
    public static function createCriteria(array $params): BookingCriteria {
        $validation = self::validateListParams($params);
        
        if (!$validation['valid']) {
            throw new InvalidArgumentException('Invalid parameters: ' . implode(', ', $validation['errors']));
        }
        
        $data = $validation['data'];
        
        return new BookingCriteria(
            page: $data['page'],
            status: $data['status'],
            category: $data['category'],
            order: $data['order'],
            fromDate: $data['from'] ?? null,
            search: $data['search'] ?? null
        );
    }
}
