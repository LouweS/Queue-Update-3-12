<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $customerId = (int)($data['customer_id'] ?? 0);
    $isPriority = !empty($data['is_priority']) ? 1 : 0;

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Backward-compatible schema migration.
    $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");

    $stmt = $conn->prepare("SELECT id, status FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }

    if ($customer['status'] !== 'waiting') {
        echo json_encode(['success' => false, 'message' => 'Only waiting customers can be prioritized']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE customers SET is_priority = ? WHERE id = ?");
    $stmt->execute([$isPriority, $customerId]);

    echo json_encode([
        'success' => true,
        'message' => $isPriority ? 'Customer marked as priority' : 'Priority removed'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
