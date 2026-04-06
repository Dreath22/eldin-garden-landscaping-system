<?php

class BookingCriteria {
    public function __construct(
        public readonly ?int $page = 1,
        public readonly ?string $status = 'all',
        public readonly ?string $category = '0',
        public readonly ?string $order = 'newest',
        public readonly ?string $fromDate = null,
        public readonly ?string $search = null
    ) {}
    
    public function getOffset(): int {
        $limit = BookingConfig::DEFAULT_LIMIT;
        $page = max(1, min($this->page ?? 1, BookingConfig::MAX_PAGE_NUMBER));
        return ($page - 1) * $limit;
    }
    
    public function getLimit(): int {
        return BookingConfig::DEFAULT_LIMIT;
    }
    
    public function getValidatedStatus(): string {
        return BookingConfig::STATUS_MAPPING[$this->status] ?? null;
    }
    
    public function getValidatedOrder(): string {
        return BookingConfig::SORT_MAPPING[$this->order] ?? BookingConfig::SORT_MAPPING['newest'];
    }
}
