<?php

require_once __DIR__ . '/ServiceValidator.php';
require_once __DIR__ . '/ServiceRepository.php';
require_once __DIR__ . '/ServiceUpdateResult.php';

class ServiceService {
    
    private ServiceRepository $repository;
    
    public function __construct(ServiceRepository $repository) {
        $this->repository = $repository;
    }
    
    public function deleteService($id): ServiceUpdateResult {
        // Validate ID
        $idValidation = ServiceValidator::validateId($id);
        if (!$idValidation['valid']) {
            return ServiceUpdateResult::failure($idValidation['error']);
        }
        
        $validatedId = $idValidation['value'];
        
        // Check if service exists
        $existingService = $this->repository->findById($validatedId);
        if (!$existingService) {
            return ServiceUpdateResult::failure('Service not found');
        }
        
        // Check if service has associated bookings
        try {
            $pdo = $this->repository->getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE service_id = :id");
            $stmt->execute([':id' => $validatedId]);
            $bookingCount = (int)$stmt->fetchColumn();
            
            if ($bookingCount > 0) {
                return ServiceUpdateResult::failure("Cannot delete service: {$bookingCount} booking(s) are associated with this service. Please delete or reassign the bookings first.");
            }
        } catch (PDOException $e) {
            error_log("Booking check error: " . $e->getMessage());
            return ServiceUpdateResult::failure('Error checking service dependencies');
        }
        
        // Perform delete
        $deleteSuccess = $this->repository->delete($validatedId);
        
        if ($deleteSuccess) {
            return ServiceUpdateResult::success('Service deleted successfully');
        } else {
            return ServiceUpdateResult::failure('Failed to delete service');
        }
    }

    public function createService(array $requestData): ServiceUpdateResult {
        // Validate create data with duplicate check
        $dataValidation = ServiceValidator::validateCreateDataWithDuplicateCheck($requestData, $this->repository->getPdo());
        if (!$dataValidation['valid']) {
            return ServiceUpdateResult::failure('Validation failed', $dataValidation['errors']);
        }
        
        // Perform create
        $createSuccess = $this->repository->create($dataValidation['data']);
        
        if ($createSuccess) {
            return ServiceUpdateResult::success('Service created successfully');
        } else {
            return ServiceUpdateResult::failure('Failed to create service');
        }
    }

    public function updateService($id, array $requestData): ServiceUpdateResult {
        // Validate ID
        $idValidation = ServiceValidator::validateId($id);
        if (!$idValidation['valid']) {
            return ServiceUpdateResult::failure($idValidation['error']);
        }
        
        $validatedId = $idValidation['value'];
        
        // Check if service exists
        $existingService = $this->repository->findById($validatedId);
        if (!$existingService) {
            return ServiceUpdateResult::failure('Service not found');
        }
        
        // Validate update data with duplicate check
        error_log("Update request data: " . json_encode($requestData));
        $dataValidation = ServiceValidator::validateUpdateDataWithDuplicateCheck($requestData, $validatedId, $this->repository->getPdo());
        error_log("Update validation result: " . json_encode($dataValidation));
        
        if (!$dataValidation['valid']) {
            error_log("Update validation failed: " . json_encode($dataValidation['errors']));
            return ServiceUpdateResult::failure('Validation failed', $dataValidation['errors']);
        }
        
        error_log("Validated data: " . json_encode($dataValidation['data']));
        
        // Check if there's anything to update
        if (empty($dataValidation['data'])) {
            error_log("No data to update - returning 'No changes made'");
            return ServiceUpdateResult::failure('No changes made to service');
        }
        
        // Perform update
        $updateSuccess = $this->repository->update($validatedId, $dataValidation['data']);
        
        if ($updateSuccess) {
            // Get updated service data
            $updatedService = $this->repository->findById($validatedId);
            return ServiceUpdateResult::success('Service updated successfully', $updatedService);
        } else {
            return ServiceUpdateResult::failure('No changes made to service');
        }
    }
}
