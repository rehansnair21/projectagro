<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $user_id = $_SESSION['id'];
    $full_name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            throw new Exception('Invalid file type');
        }
        
        $upload_dir = 'uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
            $photo_url = $destination;
            
            // Update database with new photo URL
            $stmt = $conn->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
            $stmt->bind_param("si", $photo_url, $user_id);
            $stmt->execute();
        }
    }
    
    // Update user details
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, mobile = ? WHERE id = ?");
    $stmt->bind_param("ssi", $full_name, $mobile, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update profile');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 