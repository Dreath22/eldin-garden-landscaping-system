<?php
// Start session for user authentication
session_start();

header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

require_once '../config/config.php';

class InvoiceController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Emit a successful JSON response and exit.
     */
    function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    /**
     * Emit an error JSON response and exit.
     */
    function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Y');
        $year = date('Y');
        
        // Get the last invoice number for this year
        $stmt = $this->pdo->prepare("
            SELECT invoice_number 
            FROM invoices 
            WHERE invoice_number LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute(["$prefix%"]);
        $lastInvoice = $stmt->fetch();
        
        if ($lastInvoice) {
            // Extract the sequence number and increment
            $lastSequence = (int)substr($lastInvoice['invoice_number'], -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        return $prefix . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create invoice from booking
     */
    public function createFromBooking($data)
    {
        try {
            $bookingId = (int)($data['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                $this->jsonError("Valid booking ID is required", 400);
            }

            // Get booking details
            $stmt = $this->pdo->prepare("
                SELECT b.*, u.id as user_id, s.service_name, s.base_price
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN services s ON b.service_id = s.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $this->jsonError("Booking not found", 404);
            }

            // Check if invoice already exists for this booking
            $stmt = $this->pdo->prepare("
                SELECT id FROM invoices WHERE booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            if ($stmt->fetch()) {
                $this->jsonError("Invoice already exists for this booking", 409);
            }

            // Generate invoice number and dates
            $invoiceNumber = $this->generateInvoiceNumber();
            $invoiceDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+30 days'));

            // Calculate amounts (you can modify this logic based on your pricing)
            $subtotal = $booking['base_price'] ?? 0.00;
            $taxRate = 0.12; // 12% tax
            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount;

            // Create invoice
            $stmt = $this->pdo->prepare("
                INSERT INTO invoices (
                    invoice_number, booking_id, user_id, invoice_date, due_date,
                    subtotal, tax_amount, total_amount, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
            ");

            $notes = "Auto-generated invoice for booking: " . $booking['service_name'];
            $stmt->execute([
                $invoiceNumber,
                $bookingId,
                $booking['user_id'],
                $invoiceDate,
                $dueDate,
                $subtotal,
                $taxAmount,
                $totalAmount,
                $notes
            ]);

            $invoiceId = $this->pdo->lastInsertId();

            // Create corresponding transaction record
            $transactionCode = 'TXN-' . strtoupper(uniqid());
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (
                    transaction_code, booking_id, invoice_id, description, type, status, amount, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $transactionCode,
                $bookingId,
                $invoiceId,
                "Invoice: $invoiceNumber",
                'Payment',
                'Pending',
                $totalAmount,
                "Transaction for invoice: $invoiceNumber"
            ]);

            $transactionId = $this->pdo->lastInsertId();

            // Update invoice with transaction ID
            $stmt = $this->pdo->prepare("
                UPDATE invoices SET transaction_id = ? WHERE id = ?
            ");
            $stmt->execute([$transactionId, $invoiceId]);

            $this->jsonResponse([
                "status" => "success",
                "message" => "Invoice created successfully",
                "invoice_id" => $invoiceId,
                "invoice_number" => $invoiceNumber,
                "transaction_id" => $transactionId,
                "transaction_code" => $transactionCode
            ]);

        } catch (PDOException $e) {
            error_log("Create invoice error: " . $e->getMessage());
            $this->jsonError("Failed to create invoice: " . $e->getMessage(), 500);
        }
    }

    /**
     * Get invoice by ID with transaction history
     */
    public function getInvoice($data)
    {
        try {
            $invoiceId = (int)($data['id'] ?? 0);
            if ($invoiceId <= 0) {
                $this->jsonError("Valid invoice ID is required", 400);
            }

            // Get invoice details
            $stmt = $this->pdo->prepare("
                SELECT i.*, b.service_name, b.booking_date, b.address,
                       u.name as customer_name, u.email as customer_email
                FROM invoices i
                LEFT JOIN bookings b ON i.booking_id = b.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.id = ?
            ");
            $stmt->execute([$invoiceId]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                $this->jsonError("Invoice not found", 404);
            }

            // Get transaction history for this invoice
            $stmt = $this->pdo->prepare("
                SELECT id, transaction_code, description, type, status, amount, 
                       transaction_date, notes
                FROM transactions 
                WHERE invoice_id = ? OR booking_id = ?
                ORDER BY transaction_date DESC, id DESC
            ");
            $stmt->execute([$invoiceId, $invoice['booking_id']]);
            $transactions = $stmt->fetchAll();

            $this->jsonResponse([
                "status" => "success",
                "invoice" => $invoice,
                "transactions" => $transactions
            ]);

        } catch (PDOException $e) {
            error_log("Get invoice error: " . $e->getMessage());
            $this->jsonError("Failed to retrieve invoice: " . $e->getMessage(), 500);
        }
    }

    /**
     * Get invoices for a booking
     */
    public function getBookingInvoices($data)
    {
        try {
            $bookingId = (int)($data['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                $this->jsonError("Valid booking ID is required", 400);
            }

            $stmt = $this->pdo->prepare("
                SELECT i.*, t.transaction_code, t.status as transaction_status
                FROM invoices i
                LEFT JOIN transactions t ON i.transaction_id = t.id
                WHERE i.booking_id = ?
                ORDER BY i.created_at DESC
            ");
            $stmt->execute([$bookingId]);
            $invoices = $stmt->fetchAll();

            $this->jsonResponse([
                "status" => "success",
                "invoices" => $invoices
            ]);

        } catch (PDOException $e) {
            error_log("Get booking invoices error: " . $e->getMessage());
            $this->jsonError("Failed to retrieve invoices: " . $e->getMessage(), 500);
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus($data)
    {
        try {
            $invoiceId = (int)($data['invoice_id'] ?? 0);
            $status = $data['status'] ?? '';

            if ($invoiceId <= 0) {
                $this->jsonError("Valid invoice ID is required", 400);
            }

            $validStatuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                $this->jsonError("Invalid status. Valid statuses: " . implode(', ', $validStatuses), 400);
            }

            // Get current invoice
            $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = ?");
            $stmt->execute([$invoiceId]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                $this->jsonError("Invoice not found", 404);
            }

            // Update invoice status
            $stmt = $this->pdo->prepare("
                UPDATE invoices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?
            ");
            $stmt->execute([$status, $invoiceId]);

            // If status is 'paid', update corresponding transaction
            if ($status === 'paid' && $invoice['transaction_id']) {
                $stmt = $this->pdo->prepare("
                    UPDATE transactions 
                    SET status = 'Completed', transaction_date = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$invoice['transaction_id']]);
            }

            $this->jsonResponse([
                "status" => "success",
                "message" => "Invoice status updated successfully"
            ]);

        } catch (PDOException $e) {
            error_log("Update invoice status error: " . $e->getMessage());
            $this->jsonError("Failed to update invoice status: " . $e->getMessage(), 500);
        }
    }

    /**
     * List all invoices with pagination and filters
     */
    public function list($params)
    {
        try {
            $page = (int)($params['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $status = $params['status'] ?? 'all';
            $userId = $params['user_id'] ?? null;

            $whereConditions = [];
            $bindings = [];

            if ($status !== 'all') {
                $whereConditions[] = "i.status = ?";
                $bindings[] = $status;
            }

            if ($userId) {
                $whereConditions[] = "i.user_id = ?";
                $bindings[] = (int)$userId;
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM invoices i 
                $whereClause
            ");
            $stmt->execute($bindings);
            $total = $stmt->fetch()['total'];

            // Get invoices
            $stmt = $this->pdo->prepare("
                SELECT i.*, b.service_name, u.name as customer_name, u.email as customer_email,
                       t.transaction_code, t.status as transaction_status
                FROM invoices i
                LEFT JOIN bookings b ON i.booking_id = b.id
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN transactions t ON i.transaction_id = t.id
                $whereClause
                ORDER BY i.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([...$bindings, $limit, $offset]);
            $invoices = $stmt->fetchAll();

            $this->jsonResponse([
                "status" => "success",
                "invoices" => $invoices,
                "pagination" => [
                    "current_page" => $page,
                    "total_pages" => ceil($total / $limit),
                    "total_records" => $total,
                    "limit" => $limit
                ]
            ]);

        } catch (PDOException $e) {
            error_log("List invoices error: " . $e->getMessage());
            $this->jsonError("Failed to retrieve invoices: " . $e->getMessage(), 500);
        }
    }
}

// Router
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

$controller = new InvoiceController($pdo);

switch ($action) {
    case 'create_from_booking':
        $controller->createFromBooking($data);
        break;
    case 'get':
        $controller->getInvoice($data);
        break;
    case 'booking_invoices':
        $controller->getBookingInvoices($data);
        break;
    case 'update_status':
        $controller->updateStatus($data);
        break;
    case 'list':
        $controller->list($_GET);
        break;
    default:
        $controller->jsonError("Unknown action. Valid actions: create_from_booking, get, booking_invoices, update_status, list", 400);
}
?>
