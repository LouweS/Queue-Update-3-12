<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get current queue count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting'");
    $waitingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Simple average service time calculation (avoid subqueries)
    $stmt = $conn->query("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_service_time
        FROM customers
        WHERE status = 'completed' 
        AND called_at IS NOT NULL
        AND completed_at IS NOT NULL
        AND completed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $avgServiceTime = $result['avg_service_time'] ?? 3; // Default 3 minutes if no data

    // Get number of online counters
    $stmt = $conn->query("SELECT COUNT(*) as count FROM counters WHERE is_online = 1");
    $onlineCounters = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate estimated wait time
    $estimatedWaitTime = 0;
    if ($waitingCount > 0 && $onlineCounters > 0) {
        $estimatedWaitTime = ceil(($waitingCount * $avgServiceTime) / $onlineCounters);
    }

    echo json_encode([
        'success' => true,
        'waiting_count' => $waitingCount,
        'estimated_wait_time' => max(0, $estimatedWaitTime),
        'avg_service_time' => round($avgServiceTime, 1),
        'online_counters' => $onlineCounters
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
