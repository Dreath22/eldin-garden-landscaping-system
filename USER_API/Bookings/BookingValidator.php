<?php

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/BookingConfig.php';
require_once __DIR__ . '/BookingCriteria.php';

class BookingValidator {
    
    public static function validateListParams(array $params): array {
        $errors = [];
        $validated = [];
        
        // Validate page
        if (isset($params['page'])) {
            $page = sanitizeInput($params['page'], 'int');
            if ($page === null || $page === false || $page < 1 || $page > BookingConfig::MAX_PAGE_NUMBER) {
                $errors['page'] = 'Page must be between 1 and ' . BookingConfig::MAX_PAGE_NUMBER;
            } else {
                $validated['page'] = $page;
            }
        } else {
            $validated['page'] = 1;
        }
        
        // Validate status
        if (isset($params['status'])) {
            $status = sanitizeInput($params['status']);
            if (!in_array($status, BookingConfig::VALID_STATUSES)) {
                $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', BookingConfig::VALID_STATUSES);
            } else {
                $validated['status'] = $status;
            }
        } else {
            $validated['status'] = 'all';
        }
        
        // Validate category
        if (isset($params['category'])) {
            $category = sanitizeInput($params['category'], 'int');
            if ($category === null || $category === false || $category < 0) {
                $errors['category'] = 'Category must be a valid positive integer';
            } else {
                $validated['category'] = $category;
            }
        } else {
            $validated['category'] = '0';
        }
        
        // Validate order
        if (isset($params['order'])) {
            $order = sanitizeInput($params['order']);
            if (!in_array($order, BookingConfig::VALID_SORT_OPTIONS)) {
                $errors['order'] = 'Invalid order. Must be one of: ' . implode(', ', BookingConfig::VALID_SORT_OPTIONS);
            } else {
                $validated['order'] = $order;
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
