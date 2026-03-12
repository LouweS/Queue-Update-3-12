<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Backward-compatible schema migration.
    $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");
    
    // Get customers
    $stmt = $conn->query(" 
        SELECT *
        FROM customers
        WHERE DATE(created_at) = CURDATE()
        ORDER BY
            CASE status
                WHEN 'waiting' THEN 1
                WHEN 'serving' THEN 2
                WHEN 'completed' THEN 3
                WHEN 'cancelled' THEN 4
                ELSE 5
            END ASC,
            CASE WHEN status = 'waiting' THEN is_priority ELSE 0 END DESC,
            created_at ASC
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counters
    $stmt = $conn->query("
        SELECT c.*, cust.name as current_customer_name, cust.queue_number as current_queue_number
        FROM counters c 
        LEFT JOIN customers cust ON c.current_customer_id = cust.id
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counters as &$counter) {
        if (isset($counter['service_types']) && is_string($counter['service_types'])) {
            $decoded = json_decode($counter['service_types'], true);
            $counter['service_types'] = is_array($decoded) ? $decoded : [];
        }
        
        // Get waiting count for this counter's service types
        if (!empty($counter['service_types'])) {
            $placeholders = implode(',', array_fill(0, count($counter['service_types']), '?'));
            $stmt = $conn->prepare("
                SELECT COUNT(*) as waiting_count
                FROM customers 
                WHERE status = 'waiting' 
                AND DATE(created_at) = CURDATE()
                AND service_type IN ($placeholders)
            ");
            $stmt->execute($counter['service_types']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $counter['waiting_count'] = $result['waiting_count'] ?? 0;
        } else {
            $counter['waiting_count'] = 0;
        }
    }
    unset($counter);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'counters' => $counters
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>