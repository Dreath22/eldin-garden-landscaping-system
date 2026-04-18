<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

// $pdo is already created by config.php (PDO::ERRMODE_EXCEPTION, FETCH_ASSOC, no emulated prepares)
require_once '../config/config.php';

// ─── ROUTER ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleList($pdo);
        break;
    case 'get':
        handleGet($pdo);
        break;
    case 'add':
        handleAdd($pdo);
        break;
    case 'ban':
        handleBan($pdo);
        break;
    case 'update':
        handleUpdate($pdo);
        break;
    case 'delete':
        handleDelete($pdo);
        break;
    case 'getClients':
        include __DIR__ . '/Users/get_clients.php';
        break;
    default:
        jsonError("Unknown action. Valid actions: list, get, add, ban, update, delete.", 400);
}


// ─── HELPERS ──────────────────────────────────────────────────────────────────

/**
 * Enforce that the current request is POST.
 * Exits with 405 JSON if it is not.
 */
function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method Not Allowed. Use POST."]);
        exit;
    }
}

/**
 * Decode the JSON request body. Exits with 400 if missing or malformed.
 */
function getJsonInput(): array
{
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        jsonError("Invalid or empty JSON body.", 400);
    }
    return $input;
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


// ─── HANDLER: add ─────────────────────────────────────────────────────────────
// Source: add_user.php (exclusive — all sanitization, validation, and SQL from that file verbatim)

function handleAdd(PDO $pdo): void
{
    requirePost();
    $input = getJsonInput();

    // --- 1. INPUT SANITIZATION & NORMALIZATION ---
    // strip_tags prevents basic HTML injection (XSS)
    $firstName = trim(strip_tags($input['firstName'] ?? ''));
    $lastName = trim(strip_tags($input['lastName'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);

    // filter_var removes illegal characters from email
    $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    $password = $input['temporaryPassword'] ?? '';

    // --- 2. STRICT VALIDATION (Whitelisting) ---
    $allowedRoles = ['customer', 'editor', 'admin'];
    $allowedStatuses = ['active', 'pending', 'inactive'];

    $role = in_array(strtolower($input['role'] ?? ''), $allowedRoles)
        ? strtolower($input['role'])
        : 'customer';

    $status = in_array(strtolower($input['status'] ?? ''), $allowedStatuses)
        ? strtolower($input['status'])
        : 'active';

    // Basic phone sanitization (keep digits, +, -, and spaces)
    $phone_number = isset($input['phone_number'])
        ? preg_replace('/[^\d+ \-]/', '', $input['phone_number'])
        : null;

    // --- 3. ERROR CHECKING ---
    if (empty($firstName) || empty($lastName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError("Valid first name, last name, and email are required.", 422);
    }

    if (strlen($password) < 8) {
        jsonError("Password must be at least 8 characters.", 422);
    }

    // --- 4. DATABASE INSERT ---
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, role, status, password, phone_number, notes)
                VALUES (:name, :email, :role, :status, :password, :phone_number, :notes)";

        $stmt = $pdo->prepare($sql);

        $creationNote = "Added by admin on " . date("Y-m-d H:i:s");

        $stmt->execute([
            ':name' => $fullName,
            ':email' => $email,
            ':role' => ucfirst($role),
            ':status' => $status,
            ':password' => $hashedPassword,
            ':phone_number' => $phone_number,
            ':notes' => $creationNote,
        ]);

        jsonResponse([
            "status" => "success",
            "message" => "User " . htmlspecialchars($fullName) . " created successfully.",
        ], 201);

    }
    catch (PDOException $e) {
        // 23000 = ANSI SQL integrity constraint violation (duplicate unique key)
        if ($e->getCode() == 23000) {
            jsonError("That email is already registered.", 409);
        }
        error_log("DB Error [handleAdd]: " . $e->getMessage());
        jsonError("An internal error occurred.", 500);
    }
}


// ─── HANDLER: ban ─────────────────────────────────────────────────────────────
// Source: ban_user.php (verbatim logic — strip_tags on reason/notes, rowCount check)

function handleBan(PDO $pdo): void
{
    requirePost();
    $input = getJsonInput();

    if (!isset($input['id'])) {
        jsonError("Missing User ID.", 400);
    }

    $id = (int)$input['id'];
    $reason = trim(strip_tags($input['reason'] ?? 'Other'));
    $notes = trim(strip_tags($input['notes'] ?? ''));

    try {
        // Ensure your 'users' table has ban_reason and ban_notes columns.
        $sql = "UPDATE users
                SET status     = 'banned',
                    ban_reason = :reason,
                    ban_notes  = :notes
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':reason' => $reason,
            ':notes' => $notes,
            ':id' => $id,
        ]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(["status" => "success", "message" => "User has been banned."]);
        }
        else {
            jsonError("User not found or already banned.", 404);
        }

    }
    catch (PDOException $e) {
        error_log("DB Error [handleBan]: " . $e->getMessage());
        jsonError("Internal server error.", 500);
    }
}


// ─── HANDLER: update ──────────────────────────────────────────────────────────
// Keys standardized to match add_user.php convention:
//   firstName, lastName, email, phone_number, role, status, notes, id (all from JSON body)

function handleUpdate(PDO $pdo): void
{
    requirePost();
    $input = getJsonInput();

    if (!isset($input['id'])) {
        jsonError("Missing User ID.", 400);
    }

    // --- 1. SANITIZE (same level as handleAdd) ---
    $id = (int)$input['id'];
    $firstName = trim(strip_tags($input['firstName'] ?? ''));
    $lastName = trim(strip_tags($input['lastName'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);
    $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone_number = isset($input['phone_number'])
        ? preg_replace('/[^\d+ \-]/', '', $input['phone_number'])
        : null;
    $notes = trim(strip_tags($input['notes'] ?? ''));

    // --- 2. WHITELIST VALIDATION ---
    // Status whitelist merges add_user.php ('inactive') and ban_user.php ('banned') sets
    $allowedRoles = ['customer', 'editor', 'admin'];
    $allowedStatuses = ['active', 'pending', 'inactive', 'banned'];

    $role = strtolower($input['role'] ?? '');
    $status = strtolower($input['status'] ?? '');

    if (!in_array($role, $allowedRoles)) {
        jsonError("Invalid role. Allowed: " . implode(', ', $allowedRoles), 422);
    }
    if (!in_array($status, $allowedStatuses)) {
        jsonError("Invalid status. Allowed: " . implode(', ', $allowedStatuses), 422);
    }
    if (empty($firstName) || empty($lastName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError("Valid first name, last name, and email are required.", 422);
    }

    // --- 3. DATABASE UPDATE ---
    try {
        $sql = "UPDATE users
                SET name         = :name,
                    email        = :email,
                    role         = :role,
                    status       = :status,
                    phone_number = :phone_number,
                    notes        = :notes
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $fullName,
            ':email' => $email,
            ':role' => ucfirst($role),
            ':status' => $status,
            ':phone_number' => $phone_number,
            ':notes' => $notes,
            ':id' => $id, // ← comma present (bug from update_user.php L56 is fixed here)
        ]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(["status" => "success", "message" => "No changes were made."]);
        }
        else {
            jsonResponse(["status" => "success", "message" => "User updated successfully."]);
        }

    }
    catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonError("Email already in use by another user.", 409);
        }
        error_log("DB Error [handleUpdate]: " . $e->getMessage());
        jsonError("Internal server error.", 500);
    }
}


// ─── HANDLER: list ────────────────────────────────────────────────────────────
// Source: users.php (verbatim — pagination, filtering, date range, summary stats, array_map formatter)

function handleList(PDO $pdo): void
{
    // 1. Inputs & sanitization
    $limit = isset($_GET['limit']) ? max(1, min(10000, (int)$_GET['limit'])) : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $status = $_GET['status'] ?? 'all';
    $role = $_GET['role'] ?? 'all';
    $order = $_GET['order'] ?? 'newest';
    $fromDate = $_GET['from'] ?? null;
    $toDate = $_GET['to'] ?? null;
    $offset = ($page - 1) * $limit;

    // 2. Validation
    if (!in_array($status, ['active', 'pending', 'banned', 'all'])) {
        jsonError("Invalid status filter.", 400);
    }
    if (!in_array($role, ['admin', 'staff', 'customer', 'all'])) {
        jsonError("Invalid role filter.", 400);
    }
    if (!in_array($order, ['newest', 'oldest', 'name-az', 'name-za'])) {
        jsonError("Invalid order filter.", 400);
    }

    try {
        // 3. Global summary stats
        $stmtSummary = $pdo->query("
            SELECT
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active'  THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN status = 'banned'  THEN 1 ELSE 0 END) as banned_users
            FROM users
        ");
        $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

        // 4. Build filtered WHERE clause
        $where = [];
        $params = [];

        if ($status !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        if ($role !== 'all') {
            $where[] = "role = :role";
            $params[':role'] = ucfirst(strtolower($role)); // matches stored values: "Admin", "Customer", etc.
        }
        if (!empty($fromDate)) {
            $where[] = "joined_date >= :fromDate";
            $params[':fromDate'] = $fromDate;
        }
        if (!empty($toDate)) {
            $where[] = "joined_date <= :toDate";
            $params[':toDate'] = $toDate . " 23:59:59";
        }

        $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

        // 5. Total count of filtered results (for pagination)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
        $countStmt->execute($params);
        $totalFilteredCount = (int)$countStmt->fetchColumn();

        // 6. Sort options
        $sortOptions = [
            "newest" => "joined_date DESC",
            "oldest" => "joined_date ASC",
            "name-az" => "name ASC",
            "name-za" => "name DESC",
        ];
        $orderBy = $sortOptions[$order] ?? "joined_date DESC";

        // 7. Fetch paginated user rows
        $sql = "SELECT id, name, email, role, joined_date, last_login, status, phone_number, notes
                FROM users $whereSql
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $userStmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $userStmt->bindValue($key, $value);
        }
        // LIMIT and OFFSET must be bound as integers — PDO stringifies them otherwise
        $userStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $userStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $userStmt->execute();

        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        // 8. Shape each row for the frontend
        $formattedUsers = array_map(function ($u) {
            return [
            "id" => (int)$u['id'],
            "name" => $u['name'],
            "email" => $u['email'],
            "role" => $u['role'],
            "joined_date" => date("c", strtotime($u['joined_date'])),
            "last_active" => $u['last_login'] ? date("c", strtotime($u['last_login'])) : null,
            "status" => $u['status'],
            "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($u['name']),
            "phone_number" => $u['phone_number'] ?? null,
            "notes" => $u['notes'] ?? null,
            ];
        }, $users);

        // 9. Final response
        jsonResponse([
            "status" => "success",
            "summary" => [
                "total_users" => (int)$summaryData['total_users'],
                "active_users" => (int)($summaryData['active_users'] ?? 0),
                "pending_users" => (int)($summaryData['pending_users'] ?? 0),
                "banned_users" => (int)($summaryData['banned_users'] ?? 0),
                "roleFilterValue" => $totalFilteredCount,
                "total_pages" => (int)ceil($totalFilteredCount / $limit),
            ],
            "users" => $formattedUsers,
        ]);

    }
    catch (PDOException $e) {
        error_log("DB Error [handleList]: " . $e->getMessage());
        jsonError("Database error.", 500);
    }
}


// ─── HANDLER: get ─────────────────────────────────────────────────────────────
// Source: user-action.php 'get' case (L22-51).
// Column standardized to phone_number (not 'phone') to match the rest of this controller.

function handleGet(PDO $pdo): void
{
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$userId) {
        jsonError("Missing or invalid User ID.", 400);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, phone_number, joined_date, last_login, status, notes
            FROM users WHERE id = :id
        ");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            jsonError("User not found.", 404);
        }

        jsonResponse([
            "status" => "success",
            "user" => [
                "id" => (int)$userData['id'],
                "name" => $userData['name'],
                "email" => $userData['email'],
                "phone_number" => $userData['phone_number'],
                "role" => $userData['role'],
                "joined_date" => $userData['joined_date'],
                "last_login" => $userData['last_login'],
                "status" => $userData['status'],
                "notes" => $userData['notes'],
                "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($userData['name']),
            ],
        ]);

    }
    catch (PDOException $e) {
        error_log("DB Error [handleGet]: " . $e->getMessage());
        jsonError("Database error.", 500);
    }
}


// ─── HANDLER: delete ──────────────────────────────────────────────────────────
// Source: user-action.php 'delete' case (L100-111).
// Adds rowCount() 404 guard — user-action.php used execute() bool which
// swallowed the "user not found" case silently.

function handleDelete(PDO $pdo): void
{
    requirePost();

    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$userId) {
        jsonError("Missing or invalid User ID.", 400);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            jsonError("User not found.", 404);
        }

        jsonResponse(["status" => "success", "message" => "User deleted successfully."]);

    }
    catch (PDOException $e) {
        error_log("DB Error [handleDelete]: " . $e->getMessage());
        jsonError("Database error.", 500);
    }
}