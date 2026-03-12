<?php
header('Content-Type: application/json');
require_once '../config.php';

// Security: This is a destructive operation
// In production, you may want to add additional authentication/authorization checks

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    // Reset all counters FIRST to remove foreign key references
    $conn->prepare("UPDATE counters SET current_customer_id = NULL")->execute();
    
    // Delete all customers (clear all history)
    $conn->prepare("DELETE FROM customers")->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Queue has been reset. All customers and history have been removed.'
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to reset queue: ' . $e->getMessage()
    ]);
}
?>
