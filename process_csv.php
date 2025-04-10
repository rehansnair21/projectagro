<?php
session_start();
require_once 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in and is a seller
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$seller_id = $_SESSION['id'];

// Check if file was uploaded
if (!isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit();
}

// Check for upload errors
if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    $error_message = isset($upload_errors[$_FILES['csv_file']['error']]) 
        ? $upload_errors[$_FILES['csv_file']['error']] 
        : 'Unknown upload error';
    echo json_encode(['success' => false, 'error' => $error_message]);
    exit();
}

$tmp_name = $_FILES['csv_file']['tmp_name'];

// Check if the file exists and is readable
if (!is_readable($tmp_name)) {
    echo json_encode(['success' => false, 'error' => 'File is not readable']);
    exit();
}

// Try to open the file
$handle = fopen($tmp_name, "r");
if ($handle === FALSE) {
    echo json_encode(['success' => false, 'error' => 'Could not open file']);
    exit();
}

try {
    // Read the header row
    $header = fgetcsv($handle);
    if ($header === FALSE) {
        throw new Exception('Could not read CSV header');
    }

    // Expected headers
    $expected_headers = ['product_name', 'price', 'category', 'stock'];
    if ($header !== $expected_headers) {
        throw new Exception('Invalid CSV format. Expected headers: ' . implode(', ', $expected_headers));
    }

    $success_count = 0;
    $error_count = 0;
    $errors = [];

    // Prepare the SQL statement
    $stmt = $conn->prepare("
        INSERT INTO products (seller_id, name, price, category, stock) 
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    // Start transaction
    $conn->begin_transaction();

    // Process each row
    while (($data = fgetcsv($handle)) !== FALSE) {
        // Skip empty rows
        if (count($data) < 4 || empty($data[0])) {
            continue;
        }

        $name = trim($data[0]);
        $price = floatval($data[1]);
        $category = trim($data[2]);
        $stock = intval($data[3]);

        // Validate data
        if (empty($name)) {
            $errors[] = "Empty product name found";
            continue;
        }

        if ($price <= 0) {
            $errors[] = "Invalid price ($price) for product: $name";
            continue;
        }

        if ($stock < 0) {
            $errors[] = "Invalid stock quantity ($stock) for product: $name";
            continue;
        }

        // Bind and execute
        if (!$stmt->bind_param("isdsi", $seller_id, $name, $price, $category, $stock)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $success_count++;
    }

    // If we have successful insertions, commit the transaction
    if ($success_count > 0) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "Successfully added $success_count products. Failed: $error_count",
            'errors' => $errors
        ]);
    } else {
        throw new Exception("No products were added successfully");
    }

} catch (Exception $e) {
    // Rollback the transaction if there was an error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'errors' => $errors ?? []
    ]);
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($handle)) {
        fclose($handle);
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 