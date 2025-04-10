<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit();
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'File size too large']);
        exit();
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/profile_photos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $_SESSION['id'] . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old profile photo if exists
        $stmt = $conn->prepare("SELECT photo_url FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_photo = $result->fetch_assoc()['photo_url'];
        
        if ($old_photo && $old_photo !== 'default-avatar.png' && file_exists($old_photo)) {
            unlink($old_photo);
        }

        // Update database
        $stmt = $conn->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
        $stmt->bind_param("si", $filepath, $_SESSION['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'photo_url' => $filepath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?> 