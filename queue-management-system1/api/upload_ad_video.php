<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($_FILES['ad_video'])) {
        throw new Exception('No video file uploaded');
    }

    $file = $_FILES['ad_video'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed with error code: ' . $file['error']);
    }

    $maxBytes = 100 * 1024 * 1024; // 100MB
    if ($file['size'] > $maxBytes) {
        throw new Exception('File is too large. Maximum size is 100MB.');
    }

    $allowedMime = ['video/mp4', 'video/webm', 'video/ogg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMime, true)) {
        throw new Exception('Invalid file type. Allowed formats: MP4, WebM, OGG.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['mp4', 'webm', 'ogg'], true)) {
        throw new Exception('Invalid file extension. Allowed: mp4, webm, ogg.');
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new Exception('Failed to create upload directory.');
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    $safeName = $safeName !== '' ? $safeName : 'ad_video';
    $newFileName = $safeName . '_' . date('Ymd_His') . '.' . $extension;

    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file.');
    }

    $relativePath = 'uploads/ads/' . $newFileName;

    echo json_encode([
        'success' => true,
        'message' => 'Video uploaded successfully',
        'video_path' => $relativePath
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>