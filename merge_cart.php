<?php
function mergeGuestCart($conn, $guest_id, $user_id) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Get all items from guest cart
        $query = "SELECT product_id, quantity FROM cart WHERE user_id = ? AND is_guest = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $guest_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            // Check if item exists in user's cart
            $check_query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = 0";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $user_id, $item['product_id']);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            if ($existing) {
                // Update existing item quantity
                $new_quantity = $existing['quantity'] + $item['quantity'];
                $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = 0";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("isi", $new_quantity, $user_id, $item['product_id']);
                $update_stmt->execute();
            } else {
                // Insert new item into user's cart
                $insert_query = "INSERT INTO cart (user_id, product_id, quantity, is_guest) 
                               VALUES (?, ?, ?, 0)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sii", $user_id, $item['product_id'], $item['quantity']);
                $insert_stmt->execute();
            }
        }

        // Delete guest cart
        $delete_query = "DELETE FROM cart WHERE user_id = ? AND is_guest = 1";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("s", $guest_id);
        $delete_stmt->execute();

        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}
?> 