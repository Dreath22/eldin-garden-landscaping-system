<?php

class BookingUpdateResult {
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly array $errors = [],
        public readonly ?array $pagination = null
    ) {}
    
    public static function success(string $message, ?array $data = null, ?array $pagination = null): self {
        return new self(true, $message, $data, [], $pagination);
    }
    
    public static function failure(string $message, array $errors = []): self {
        return new self(false, $message, null, $errors);
    }
    
    public function toArray(): array {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'pagination' => $this->pagination
        ];
    }
}
