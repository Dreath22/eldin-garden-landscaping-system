<?php

class BookingConfig {
    // Pagination
    public const DEFAULT_LIMIT = 6;
    public const MAX_LIMIT = 100;
    
    // Validation
    public const MAX_SEARCH_LENGTH = 255;
    public const MAX_PAGE_NUMBER = 1000;
    
    // Status values
    public const VALID_STATUSES = ['all', 'pending', 'active', 'completed', 'cancelled'];
    public const STATUS_MAPPING = [
        'pending' => 'Pending',
        'active' => 'Active', 
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    
    // Sort options
    public const VALID_SORT_OPTIONS = ['newest', 'oldest'];
    public const SORT_MAPPING = [
        'newest' => 'B.appointment_date DESC',
        'oldest' => 'B.appointment_date ASC'
    ];
    
    // Rate limiting
    public const MAX_REQUESTS_PER_MINUTE = 60;
}
