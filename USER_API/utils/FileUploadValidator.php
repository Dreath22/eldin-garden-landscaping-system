<?php
class FileUploadValidator {
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    private $maxSize = 5 * 1024 * 1024; // 5MB
    private $uploadPath;
    
    public function __construct(string $uploadPath) {
        $this->uploadPath = $uploadPath;
        $this->ensureUploadDirectory();
    }
    
    public function validateFile(array $file): array {
        $errors = [];
        
        // Check if file was actually uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate file size
        if ($file['size'] > $this->maxSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($this->maxSize / 1024 / 1024) . 'MB';
        }
        
        // Validate file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes);
        }
        
        // Validate file content (additional security)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!$this->isAllowedMimeType($mimeType)) {
            $errors[] = 'File content type not allowed';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    public function getSafeFileName(string $originalName): string {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName);
        $safeName = substr($safeName, 0, 50); // Limit length
        
        return $safeName . '_' . uniqid() . '.' . $extension;
    }
    
    public function moveUploadedFile(array $file, string $destination = null): string|false {
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return false;
        }
        
        $safeFileName = $destination ?? $this->uploadPath . '/' . $this->getSafeFileName($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $safeFileName)) {
            return $safeFileName;
        }
        
        return false;
    }
    
    private function ensureUploadDirectory(): void {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    private function isAllowedMimeType(string $mimeType): bool {
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        return in_array($mimeType, $allowedMimes);
    }
    
    private function getUploadErrorMessage(int $errorCode): string {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
}
?>
