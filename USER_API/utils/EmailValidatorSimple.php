<?php
/**
 * Email Validation System
 * Comprehensive validation for email creation and updates
 */

class EmailValidatorSimple {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Validate email creation data
     */
    public function validateEmailData($data, $userId) {
        $errors = [];
        $validated = [];
        
        // Validate subject
        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        } elseif (strlen($data['subject']) > 500) {
            $errors['subject'] = 'Subject must be less than 500 characters';
        } elseif (!$this->isValidSubject($data['subject'])) {
            $errors['subject'] = 'Subject contains invalid characters';
        } else {
            $validated['subject'] = $this->sanitizeInput($data['subject']);
        }
        
        // Validate content
        if (empty($data['content'])) {
            $errors['content'] = 'Email content is required';
        } elseif (strlen($data['content']) < 10) {
            $errors['content'] = 'Email content must be at least 10 characters';
        } elseif (strlen($data['content']) > 50000) {
            $errors['content'] = 'Email content must be less than 50,000 characters';
        } elseif (!$this->isValidContent($data['content'])) {
            $errors['content'] = 'Email content contains potentially unsafe elements';
        } else {
            $validated['content'] = $this->sanitizeInput($data['content'], 'html');
        }
        
        // Validate preview (optional)
        if (isset($data['preview'])) {
            if (strlen($data['preview']) > 1000) {
                $errors['preview'] = 'Preview must be less than 1000 characters';
            } else {
                $validated['preview'] = $this->sanitizeInput($data['preview']);
            }
        }
        
        // Validate service ID (optional)
        if (isset($data['service_id']) && !empty($data['service_id'])) {
            if (!$this->validateServiceId($data['service_id'])) {
                $errors['service_id'] = 'Invalid service or no access to this service';
            } else {
                $validated['service_id'] = (int)$data['service_id'];
            }
        }
        
        
        // Business rule validations
        $this->validateBusinessRules($validated, $errors, $userId);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated
        ];
    }
    
    /**
     * Validate email update data
     */
    public function validateEmailUpdate($data, $emailId, $userId) {
        // First check if user can access this email
        if (!$this->canAccessEmail($emailId, $userId)) {
            return [
                'valid' => false,
                'errors' => ['access' => 'No access to this email'],
                'data' => []
            ];
        }
        
        // Get current email data
        $currentEmail = $this->getEmailById($emailId);
        if (!$currentEmail) {
            return [
                'valid' => false,
                'errors' => ['email' => 'Email not found'],
                'data' => []
            ];
        }
        
        // Check if email can be updated (sent emails cannot be modified)
        if ($currentEmail['status'] === 'sent') {
            return [
                'valid' => false,
                'errors' => ['status' => 'Sent emails cannot be modified'],
                'data' => []
            ];
        }
        
        // Validate the update data
        return $this->validateEmailData($data, $userId);
    }
    
    /**
     * Validate email send operation
     */
    public function validateEmailSend($emailId, $userId) {
        $errors = [];
        
        // Check if user can access this email
        if (!$this->canAccessEmail($emailId, $userId)) {
            $errors['access'] = 'No access to this email';
        }
        
        // Get email details
        $email = $this->getEmailById($emailId);
        if (!$email) {
            $errors['email'] = 'Email not found';
        } else {
            // Check if email is in correct status
            if ($email['status'] !== 'draft' && $email['status'] !== 'scheduled') {
                $errors['status'] = 'Email can only be sent from draft or scheduled status';
            }
            
            // Check if email has recipients
            if ($email['total_recipients'] === 0) {
                $errors['recipients'] = 'Email has no recipients';
            }
            
            // Check if email is approved (if required)
            if (!$email['is_approved']) {
                $errors['approval'] = 'Email requires approval before sending';
            }
            
            // Check daily send limit
            if (!$this->checkDailySendLimit($userId)) {
                $errors['limit'] = 'Daily send limit exceeded';
            }
            
            // Validate email content before sending
            if (!$this->isValidContent($email['full_content'])) {
                $errors['content'] = 'Email content failed validation';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate service ID
     */
    private function validateServiceId($serviceId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM services 
                WHERE id = :service_id 
                  AND status = 'active'
            ");
            $stmt->execute([':service_id' => $serviceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("Service validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate email list
     */
    private function validateEmailList($emailList) {
        $emails = [];
        
        if (is_string($emailList)) {
            // Split by comma, semicolon, or newline
            $emailArray = preg_split('/[,;\n\r]+/', $emailList);
        } elseif (is_array($emailList)) {
            $emailArray = $emailList;
        } else {
            return [];
        }
        
        foreach ($emailArray as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($email); // Normalize to lowercase
            }
        }
        
        return array_unique($emails);
    }
    
    /**
     * Validate date time
     */
    private function validateDateTime($dateTime) {
        try {
            return new DateTime($dateTime);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate subject
     */
    private function isValidSubject($subject) {
        // Check for potentially dangerous patterns
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/data:text\/html/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $subject)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate content security
     */
    private function isValidContent($content) {
        // Check for suspicious content
        $suspiciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<form\b[^>]*>/i',
            '/<input\b[^>]*>/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                error_log("Suspicious content detected: " . $pattern);
                return false;
            }
        }
        
        // Check for external links
        if (preg_match_all('/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                if (!$this->isSafeUrl($url)) {
                    error_log("Unsafe URL detected: " . $url);
                    return false;
                }
            }
        }
        
        // Check for spam indicators
        $spamKeywords = [
            'viagra', 'cialis', 'lottery', 'winner', 'congratulations', 
            'free money', 'click here', 'act now', 'limited time',
            'guaranteed', 'risk free', 'no cost', '100% free'
        ];
        
        $contentLower = strtolower($content);
        $spamScore = 0;
        foreach ($spamKeywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false) {
                $spamScore++;
            }
        }
        
        // Allow some spam keywords but flag if too many
        if ($spamScore > 3) {
            error_log("High spam score detected: " . $spamScore);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if URL is safe
     */
    private function isSafeUrl($url) {
        $allowedDomains = [
            'landscape.com',
            'localhost',
            '127.0.0.1',
            'greenscape.com'
        ];
        
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        foreach ($allowedDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate business rules
     */
    private function validateBusinessRules($data, &$errors, $userId) {
        // Rule 1: Promotional emails must have service
        if (isset($data['email_type']) && $data['email_type'] === 'promotion') {
            if (empty($data['service_id'])) {
                $errors['business_rule'] = 'Promotional emails must be linked to a service';
            }
        }
        
        // Rule 2: Service updates must have valid service
        if (isset($data['email_type']) && $data['email_type'] === 'service_update') {
            if (empty($data['service_id'])) {
                $errors['business_rule'] = 'Service update emails must be linked to a service';
            }
        }
        
        // Rule 3: Check send limits based on user role
        $userRole = $this->getUserRole($userId);
        if ($userRole === 'Staff') {
            $maxRecipients = 1000;
            if (isset($data['total_recipients']) && $data['total_recipients'] > $maxRecipients) {
                $errors['business_rule'] = "Staff users can send to maximum {$maxRecipients} recipients";
            }
        }
    }
    
    /**
     * Get recipient count
     */
    private function getRecipientCount($recipientType) {
        try {
            switch ($recipientType) {
                case 'subscribers':
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'");
                    break;
                case 'customers':
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'Customer'");
                    break;
                case 'all':
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM subscribers WHERE status = 'active'");
                    break;
                default:
                    return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Get recipient count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user role
     */
    private function getUserRole($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['role'] : 'Customer';
        } catch (PDOException $e) {
            error_log("Get user role error: " . $e->getMessage());
            return 'Customer';
        }
    }
    
    /**
     * Check if user can access email
     */
    private function canAccessEmail($emailId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM emails WHERE id = :email_id
            ");
            $stmt->execute([':email_id' => $emailId]);
            $email = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$email) {
                return false;
            }
            
            // Check if user is admin or email owner
            $userRole = $this->getUserRole($userId);
            if ($userRole === 'Admin') {
                return true;
            }
            
            return $email['user_id'] == $userId;
        } catch (PDOException $e) {
            error_log("Email access check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email by ID
     */
    private function getEmailById($emailId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM emails WHERE id = :email_id
            ");
            $stmt->execute([':email_id' => $emailId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check daily send limit
     */
    private function checkDailySendLimit($userId) {
        try {
            $userRole = $this->getUserRole($userId);
            
            // Set limits based on role
            $dailyLimit = 100; // Default for Staff
            if ($userRole === 'Manager') {
                $dailyLimit = 1000;
            } elseif ($userRole === 'Admin') {
                return true; // No limit for admin
            }
            
            // Check today's sent count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as sent_today
                FROM emails 
                WHERE user_id = :user_id 
                  AND DATE(sent_at) = CURDATE()
                  AND status = 'sent'
            ");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['sent_today'] < $dailyLimit;
            
        } catch (PDOException $e) {
            error_log("Check send limit error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitize input
     */
    private function sanitizeInput($input, $type = 'string') {
        if ($type === 'html') {
            // Allow basic HTML tags for email content
            $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><h1><h2><h3><h4><img><div><span><table><tr><td><th>';
            return strip_tags($input, $allowedTags);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email template
     */
    public function validateEmailTemplate($data, $userId) {
        $errors = [];
        $validated = [];
        
        // Validate template name
        if (empty($data['template_name'])) {
            $errors['template_name'] = 'Template name is required';
        } elseif (strlen($data['template_name']) > 255) {
            $errors['template_name'] = 'Template name must be less than 255 characters';
        } else {
            $validated['template_name'] = $this->sanitizeInput($data['template_name']);
        }
        
        // Validate subject template
        if (empty($data['subject_template'])) {
            $errors['subject_template'] = 'Subject template is required';
        } elseif (strlen($data['subject_template']) > 500) {
            $errors['subject_template'] = 'Subject template must be less than 500 characters';
        } else {
            $validated['subject_template'] = $this->sanitizeInput($data['subject_template']);
        }
        
        // Validate content template
        if (empty($data['content_template'])) {
            $errors['content_template'] = 'Content template is required';
        } elseif (strlen($data['content_template']) < 10) {
            $errors['content_template'] = 'Content template must be at least 10 characters';
        } else {
            $validated['content_template'] = $this->sanitizeInput($data['content_template'], 'html');
        }
        
        // Validate template type
        $validTypes = ['newsletter', 'promotion', 'announcement', 'service_update', 'reminder', 'welcome'];
        if (isset($data['template_type'])) {
            if (!in_array($data['template_type'], $validTypes)) {
                $errors['template_type'] = 'Invalid template type';
            } else {
                $validated['template_type'] = $data['template_type'];
            }
        }
        
        // Check for template variables
        if (isset($data['content_template'])) {
            $variables = $this->extractTemplateVariables($data['content_template']);
            $subjectVars = $this->extractTemplateVariables($data['subject_template'] ?? '');
            $validated['variables'] = array_unique(array_merge($variables, $subjectVars));
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated
        ];
    }
    
    /**
     * Extract template variables
     */
    private function extractTemplateVariables($template) {
        preg_match_all('/\{(\w+)\}/', $template, $matches);
        return $matches[1] ?? [];
    }
}
?>
