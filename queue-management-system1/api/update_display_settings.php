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

    $adVideoUrl = trim($data['ad_video_url'] ?? '');
    $announcementText = trim($data['announcement_text'] ?? '');

    $db = new Database();
    $conn = $db->getConnection();

    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS ad_video_url TEXT NULL");
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS announcement_text TEXT NULL");

    $stmt = $conn->prepare('SELECT id FROM display_settings WHERE id = 1 LIMIT 1');
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $stmt = $conn->prepare('UPDATE display_settings SET ad_video_url = ?, announcement_text = ? WHERE id = 1');
        $stmt->execute([$adVideoUrl, $announcementText]);
    } else {
        $stmt = $conn->prepare("INSERT INTO display_settings (id, company_name, welcome_message, refresh_interval, ad_video_url, announcement_text) VALUES (1, 'Customer Service', 'Welcome to our Service Center', 10, ?, ?)");
        $stmt->execute([$adVideoUrl, $announcementText]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Display settings saved successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>