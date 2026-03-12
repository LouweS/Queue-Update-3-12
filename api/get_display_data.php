<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Backward-compatible schema migration.
    $conn->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_priority TINYINT(1) NOT NULL DEFAULT 0");

    // Ensure there are always 6 counters in the database.
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (1, 'CASHIER 1', '[\"bills\"]', 1, NULL)");
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (2, 'CASHIER 2', '[\"bills\"]', 1, NULL)");
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (3, 'CUSTOMER SERVICE 3', '[\"customer_service\"]', 1, NULL)");
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (4, 'CUSTOMER SERVICE 4', '[\"complaints\"]', 1, NULL)");
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (5, 'Counter 5', '[\"bills\", \"customer_service\", \"complaints\"]', 1, NULL)");
    $conn->exec("INSERT IGNORE INTO counters (id, name, service_types, is_online, current_customer_id) VALUES (6, 'Counter 6', '[\"bills\", \"customer_service\", \"complaints\"]', 1, NULL)");
    
    // Update existing counters to match the service type assignments
    $conn->exec("UPDATE counters SET service_types = '[\"bills\"]' WHERE id = 1");
    $conn->exec("UPDATE counters SET service_types = '[\"bills\"]' WHERE id = 2");
    $conn->exec("UPDATE counters SET service_types = '[\"customer_service\"]' WHERE id = 3");
    $conn->exec("UPDATE counters SET service_types = '[\"complaints\"]' WHERE id = 4");
    
    $data = [];
    
    // Get currently serving customer
    $stmt = $conn->query("SELECT c.*, cnt.name as counter_name FROM customers c LEFT JOIN counters cnt ON cnt.current_customer_id = c.id WHERE c.status = 'serving' ORDER BY c.called_at DESC LIMIT 1");
    $data['now_serving'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get next in line (priority first, then oldest waiting customer)
    $stmt = $conn->query("SELECT * FROM customers WHERE status = 'waiting' ORDER BY is_priority DESC, created_at ASC LIMIT 1");
    $data['next_in_line'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get waiting count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting'");
    $data['waiting_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recently called (last 6 completed or serving)
    $stmt = $conn->query("SELECT queue_number, called_at FROM customers WHERE (status = 'completed' OR status = 'serving') AND called_at IS NOT NULL ORDER BY called_at DESC LIMIT 6");
    $data['recent_called'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get waiting queue for ticker (priority first)
    $stmt = $conn->query("SELECT queue_number FROM customers WHERE status = 'waiting' ORDER BY is_priority DESC, created_at ASC LIMIT 4");
    $data['waiting_queue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all counters with current queue number and waiting list per counter
    $stmt = $conn->query("SELECT c.id, c.name, c.is_online, c.service_types, cust.queue_number AS current_queue_number FROM counters c LEFT JOIN customers cust ON cust.id = c.current_customer_id ORDER BY c.id ASC");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each counter, get waiting customers that match its service types
    foreach ($counters as &$counter) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (!empty($serviceTypes)) {
            // Build the query to find waiting customers for this counter
            $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
            $stmt = $conn->prepare("SELECT queue_number FROM customers WHERE status = 'waiting' AND service_type IN ($placeholders) ORDER BY is_priority DESC, created_at ASC LIMIT 5");
            $stmt->execute($serviceTypes);
            $counter['waiting_list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $counter['waiting_list'] = [];
        }
    }
    unset($counter);
    $data['counters'] = $counters;

    // Get announcement and ad video settings
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS ad_video_url TEXT NULL");
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS announcement_text TEXT NULL");

    $stmt = $conn->query("SELECT ad_video_url, announcement_text FROM display_settings WHERE id = 1 LIMIT 1");
    $displaySettings = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['ad_video_url'] = $displaySettings['ad_video_url'] ?? '';
    $data['announcement_text'] = $displaySettings['announcement_text'] ?? '';
    
    // Get counter information for currently serving
    if ($data['now_serving']) {
        $stmt = $conn->prepare("
            SELECT name FROM counters WHERE current_customer_id = ?
        ");
        $stmt->execute([$data['now_serving']['id']]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['now_serving']['counter_name'] = $counter ? $counter['name'] : 'Available Counter';
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>