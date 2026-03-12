<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $alerts = [];

    // Check for long queues (more than 5 waiting)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting'");
    $waitingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($waitingCount > 5) {
        $alerts[] = [
            'type' => 'long_queue',
            'severity' => $waitingCount > 10 ? 'critical' : 'warning',
            'message' => "Long queue detected: {$waitingCount} customers waiting",
            'count' => $waitingCount
        ];
    }

    // Check for customers waiting too long (more than 15 minutes)
    $stmt = $conn->query("
        SELECT id, queue_number, service_type, created_at,
               TIMESTAMPDIFF(MINUTE, created_at, NOW()) as wait_minutes
        FROM customers
        WHERE status = 'waiting'
        AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 15
        ORDER BY created_at ASC
    ");
    $longWaiters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($longWaiters)) {
        $alerts[] = [
            'type' => 'customer_waiting_long',
            'severity' => count($longWaiters) > 3 ? 'critical' : 'warning',
            'message' => count($longWaiters) . " customer(s) waiting longer than 15 minutes",
            'customers' => $longWaiters
        ];
    }

    // Check for idle counters (online but no customer for more than 2 minutes)
    $stmt = $conn->query("
        SELECT c.id, c.name,
               COALESCE(TIMESTAMPDIFF(MINUTE, cust.completed_at, NOW()), NULL) as idle_minutes
        FROM counters c
        LEFT JOIN customers cust ON c.current_customer_id = cust.id
        WHERE c.is_online = 1 AND c.current_customer_id IS NULL
        AND NOT EXISTS (
            SELECT 1 FROM customers
            WHERE status = 'waiting'
        )
    ");
    $idleCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($idleCounters) && $waitingCount > 0) {
        $alerts[] = [
            'type' => 'idle_counters',
            'severity' => 'info',
            'message' => count($idleCounters) . " counter(s) are idle while customers are waiting",
            'counters' => $idleCounters
        ];
    }

    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'alert_count' => count($alerts),
        'has_critical' => !empty(array_filter($alerts, fn($a) => $a['severity'] === 'critical'))
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
