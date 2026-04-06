<?php

require_once __DIR__ . '/BookingValidator.php';
require_once __DIR__ . '/BookingRepository.php';
require_once __DIR__ . '/BookingUpdateResult.php';
require_once __DIR__ . '/BookingConfig.php';

class BookingService {
    
    private BookingRepository $repository;
    
    public function __construct(BookingRepository $repository) {
        $this->repository = $repository;
    }
    
    public function getList(array $params): BookingUpdateResult {
        try {
            // Validate and create criteria
            $criteria = BookingValidator::createCriteria($params);
            
            // Get summary data
            $summary = $this->repository->getSummary();
            
            // Get total count for pagination
            $totalRecords = $this->repository->countByCriteria($criteria);
            
            // Get paginated data
            $bookings = $this->repository->findByCriteria($criteria);
            
            // Calculate pagination
            $totalPages = ceil($totalRecords / $criteria->getLimit());
            $pagination = [
                'currentPage' => $criteria->page,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'limit' => $criteria->getLimit(),
                'hasNext' => $criteria->page < $totalPages,
                'hasPrev' => $criteria->page > 1
            ];
            
            $data = [
                'summary' => $summary,
                'bookings' => $bookings
            ];
            
            return BookingUpdateResult::success(
                'Bookings retrieved successfully',
                $data,
                $pagination
            );
            
        } catch (InvalidArgumentException $e) {
            return BookingUpdateResult::failure('Validation failed', [$e->getMessage()]);
        } catch (Exception $e) {
            error_log("BookingService getList error: " . $e->getMessage());
            return BookingUpdateResult::failure('Error retrieving bookings');
        }
    }
    
    public function create(array $data): BookingUpdateResult {
        // TODO: Implement create functionality
        return BookingUpdateResult::failure('Create functionality not yet implemented');
    }
    
    public function update(int $id, array $data): BookingUpdateResult {
        // TODO: Implement update functionality
        return BookingUpdateResult::failure('Update functionality not yet implemented');
    }
    
    public function delete(int $id): BookingUpdateResult {
        // TODO: Implement delete functionality
        return BookingUpdateResult::failure('Delete functionality not yet implemented');
    }
}
