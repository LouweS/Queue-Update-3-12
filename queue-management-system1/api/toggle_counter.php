<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
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
    
    $counterId = $data['counter_id'] ?? 0;
    $isOnline = isset($data['is_online']) ? (bool)$data['is_online'] : null;
    
    if (!$counterId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Counter ID is required']);
        exit;
    }
    
    if ($isOnline === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'is_online parameter is required']);
        exit;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Update counter status
    $stmt = $conn->prepare("UPDATE counters SET is_online = ? WHERE id = ?");
    $stmt->execute([$isOnline ? 1 : 0, $counterId]);
    
    // Get updated counter info
    $stmt = $conn->prepare("SELECT id, name, is_online, service_types, current_customer_id FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Counter ' . ($isOnline ? 'activated' : 'deactivated') . ' successfully',
        'counter' => $counter
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
