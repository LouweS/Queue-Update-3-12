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
    
    $customerId = $data['customer_id'] ?? 0;
    $counterId = $data['counter_id'] ?? 0;

    // New flow: Call next customer for a specific counter
    if ($counterId > 0 && $customerId === 0) {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Backward-compatible schema migration.
        $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");
        
        $conn->beginTransaction();
        
        // Get counter details
        $stmt = $conn->prepare("SELECT id, name, service_types, current_customer_id FROM counters WHERE id = ? AND is_online = 1 FOR UPDATE");
        $stmt->execute([$counterId]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$counter) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Counter not available']);
            exit;
        }
        
        if ($counter['current_customer_id']) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Counter is currently serving another customer']);
            exit;
        }
        
        // Get service types for this counter
        $serviceTypes = json_decode($counter['service_types'], true);
        if (empty($serviceTypes)) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Counter has no service types configured']);
            exit;
        }
        
        // Find next waiting customer matching counter's service types (priority first)
        $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
        $stmt = $conn->prepare("
            SELECT id 
            FROM customers 
            WHERE status = 'waiting' 
            AND DATE(created_at) = CURDATE()
            AND service_type IN ($placeholders)
            ORDER BY is_priority DESC, created_at ASC 
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute($serviceTypes);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'No waiting customers for this counter\'s services']);
            exit;
        }
        
        // Call the customer
        $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW(), is_priority = 0 WHERE id = ?");
        $stmt->execute([$customer['id']]);
        
        $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
        $stmt->execute([$customer['id'], $counterId]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Customer called successfully',
            'counter_id' => $counterId,
            'counter_name' => $counter['name']
        ]);
        exit;
    }

    // Legacy flow: Call specific customer (auto-assign to counter)
    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Backward-compatible schema migration.
    $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");

    $conn->beginTransaction();

    // Ensure customer is still waiting before calling.
    $stmt = $conn->prepare("SELECT id, status, service_type FROM customers WHERE id = ? FOR UPDATE");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }

    if ($customer['status'] !== 'waiting') {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Customer is not waiting']);
        exit;
    }

        // Prefer counters that support this customer's service type.
        $serviceAliases = [
                'bills' => ['bills', 'payment'],
                'complaints' => ['complaints', 'technical', 'support'],
                'customer_service' => ['customer_service', 'general', 'inquiry'],
        ];
        $candidateTypes = $serviceAliases[$customer['service_type']] ?? [$customer['service_type']];

        $conditions = [];
        $params = [];
        foreach ($candidateTypes as $type) {
            $conditions[] = 'service_types LIKE CONCAT(\'%"\', ?, \'"%\')';
            $params[] = $type;
        }

        $sql = "SELECT id, name
                        FROM counters
                        WHERE is_online = 1
                            AND current_customer_id IS NULL
                            AND (" . implode(' OR ', $conditions) . ")
                        ORDER BY id ASC
                        LIMIT 1
                        FOR UPDATE";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);

        $assignmentType = 'service_match';

        // Fallback to any available online counter.
        if (!$counter) {
                $stmt = $conn->query("SELECT id, name FROM counters WHERE is_online = 1 AND current_customer_id IS NULL ORDER BY id ASC LIMIT 1 FOR UPDATE");
                $counter = $stmt->fetch(PDO::FETCH_ASSOC);
                $assignmentType = 'fallback';
        }

    if (!$counter) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'No available counter']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW(), is_priority = 0 WHERE id = ?");
    $stmt->execute([$customerId]);

    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $counter['id']]);

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer called successfully',
        'counter_name' => $counter['name'],
        'assignment_type' => $assignmentType,
        'service_type' => $customer['service_type']
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>