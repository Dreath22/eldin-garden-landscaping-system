<?php

class ServiceRepository {
    
    private PDO $pdo;
    
    // FIELD WHITELISTING - Prevent SQL injection via field names
    private const ALLOWED_CREATE_FIELDS = ['service_name', 'description', 'base_price', 'status', 'features', 'duration'];
    private const ALLOWED_UPDATE_FIELDS = ['service_name', 'description', 'base_price', 'status', 'features', 'duration'];
    
    // FIELD MAPPING - Map frontend field names to database column names
    private const FIELD_MAPPING = [
        'name' => 'service_name',
        'baseprice' => 'base_price',
        'duration' => 'duration' // Map duration to duration column
    ];
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function findById(int $id): ?array {
        $sql = "SELECT id, service_name as name, description, base_price as baseprice, duration, status, features, rating, review_count, created_at FROM services WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM services WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $id]) && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Repository delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function create(array $data): bool {
        if (empty($data)) {
            return false;
        }
        
        // Map frontend field names to database column names
        $mappedData = [];
        foreach ($data as $field => $value) {
            $dbField = self::FIELD_MAPPING[$field] ?? $field;
            $mappedData[$dbField] = $value;
        }
        
        // FIELD WHITELISTING - Prevent SQL injection via field names
        $filteredData = array_intersect_key($mappedData, array_flip(self::ALLOWED_CREATE_FIELDS));
        
        if (empty($filteredData)) {
            return false;
        }
        
        // Add created_at timestamp safely
        $filteredData['created_at'] = date('Y-m-d H:i:s');
        
        $fields = array_keys($filteredData);
        $placeholders = array_map(fn($field) => ":$field", $fields);
        
        $sql = "INSERT INTO services (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($filteredData) && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Repository create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update(int $id, array $data): bool {
        if (empty($data)) {
            return false;
        }
        
        // Map frontend field names to database column names
        $mappedData = [];
        foreach ($data as $field => $value) {
            $dbField = self::FIELD_MAPPING[$field] ?? $field;
            $mappedData[$dbField] = $value;
        }
        
        // FIELD WHITELISTING - Prevent SQL injection via field names
        $filteredData = array_intersect_key($mappedData, array_flip(self::ALLOWED_UPDATE_FIELDS));
        
        if (empty($filteredData)) {
            return false;
        }
        
        $updateCondition = [];
        $sqlParams = [':id' => $id];
        
        foreach ($filteredData as $field => $value) {
            $updateCondition[] = "$field = :$field";
            $sqlParams[":$field"] = $value;
        }
        
        $updateClause = implode(', ', $updateCondition);
        $sql = "UPDATE services SET $updateClause WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($sqlParams);
            
            // Consider it successful if:
            // 1. The query executed successfully AND
            // 2. Either rows were updated OR the data was the same (rowCount = 0)
            return $result;
        } catch (PDOException $e) {
            error_log("Repository update error: " . $e->getMessage());
            return false;
        }
    }
}
