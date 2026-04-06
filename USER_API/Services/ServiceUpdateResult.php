<?php

class ServiceUpdateResult {
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $service = null,
        public readonly array $errors = []
    ) {}
    
    public static function success(string $message, ?array $service = null): self {
        return new self(true, $message, $service);
    }
    
    public static function failure(string $message, array $errors = []): self {
        return new self(false, $message, null, $errors);
    }
    
    public function toArray(): array {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'service' => $this->service,
            'errors' => $this->errors
        ];
    }
}
