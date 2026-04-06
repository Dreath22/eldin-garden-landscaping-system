<?php

class RateLimiter {
    private const MAX_REQUESTS_PER_MINUTE = 60;
    private const CACHE_FILE = __DIR__ . '/../../cache/rate_limit.json';
    
    public static function checkLimit(string $identifier): bool {
        $cacheData = self::getCacheData();
        $now = time();
        $windowStart = $now - 60; // 1 minute window
        
        // Clean old entries
        $cacheData = array_filter($cacheData, fn($timestamp) => $timestamp > $windowStart);
        
        // Check current requests
        $recentRequests = array_filter($cacheData, fn($timestamp) => $timestamp >= $windowStart);
        
        if (count($recentRequests) >= self::MAX_REQUESTS_PER_MINUTE) {
            return false; // Rate limit exceeded
        }
        
        // Add current request
        $cacheData[] = $now;
        self::saveCacheData($cacheData);
        
        return true;
    }
    
    private static function getCacheData(): array {
        if (!file_exists(self::CACHE_FILE)) {
            return [];
        }
        
        $data = file_get_contents(self::CACHE_FILE);
        return $data ? json_decode($data, true) : [];
    }
    
    private static function saveCacheData(array $data): void {
        $cacheDir = dirname(self::CACHE_FILE);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents(self::CACHE_FILE, json_encode($data));
    }
    
    public static function getClientIdentifier(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
