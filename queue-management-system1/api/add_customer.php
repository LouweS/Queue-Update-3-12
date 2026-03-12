<?php
header('Content-Type: application/json');
include '../config.php';

// Check if it's a POST request
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
    
    $name = trim((string)($data['name'] ?? ''));
    $serviceType = $data['service_type'] ?? '';
    $isPriority = !empty($data['is_priority']) ? 1 : 0;

    if (empty($serviceType)) {
        echo json_encode(['success' => false, 'message' => 'Service type is required']);
        exit;
    }

    // Name is optional for dashboard intake.
    if ($name === '') {
        $name = 'Walk-in';
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Backward-compatible schema migration.
    $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");
    
    // Generate queue number with explicit prefixes per service type.
    $prefixMap = [
        'bills' => 'B',
        'complaints' => 'C',
        'customer_service' => 'S',
    ];
    $prefix = $prefixMap[$serviceType] ?? strtoupper(substr($serviceType, 0, 1));
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)) as last_num 
                           FROM customers WHERE queue_number LIKE ? AND DATE(created_at) = CURDATE()");
    $likePattern = $prefix . '%';
    $stmt->execute([$likePattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = ($result['last_num'] ?? 0) + 1;
    $queueNumber = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    
    // Insert customer
    $stmt = $conn->prepare("INSERT INTO customers (queue_number, name, service_type, is_priority) VALUES (?, ?, ?, ?)");
    $stmt->execute([$queueNumber, $name, $serviceType, $isPriority]);
    
    echo json_encode([
        'success' => true, 
        'queue_number' => $queueNumber,
        'message' => 'Customer added successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>