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
        
        // Perform delete
        $deleteSuccess = $this->repository->delete($validatedId);
        
        if ($deleteSuccess) {
            return ServiceUpdateResult::success('Service deleted successfully');
        } else {
            return ServiceUpdateResult::failure('Failed to delete service');
        }
    }

    public function createService(array $requestData): ServiceUpdateResult {
        // Validate create data
        $dataValidation = ServiceValidator::validateCreateData($requestData);
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
        
        // Validate update data
        $dataValidation = ServiceValidator::validateUpdateData($requestData);
        if (!$dataValidation['valid']) {
            return ServiceUpdateResult::failure('Validation failed', $dataValidation['errors']);
        }
        
        // Check if there's anything to update
        if (empty($dataValidation['data'])) {
            return ServiceUpdateResult::failure('No valid fields to update');
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
