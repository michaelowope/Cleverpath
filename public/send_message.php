<?php

include '../config/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start transaction for data integrity
        $conn->beginTransaction();

        // Validate input
        $sender_id = $_POST['sender_id'] ?? null;
        $receiver_id = $_POST['receiver_id'] ?? null;
        $message = isset($_POST['message']) && !empty(trim($_POST['message'])) ? trim($_POST['message']) : null;
        $file_name = null; // Default: No file

        if (!$sender_id || !$receiver_id) {
            throw new Exception("Sender or receiver ID is missing.");
        }

        // Handle File Upload (if any)
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            $file_tmp = $file['tmp_name'];
            $file_name = time() . "_" . basename($file['name']);
            $file_destination = "uploads/" . $file_name;

            if (!move_uploaded_file($file_tmp, $file_destination)) {
                throw new Exception("File upload failed.");
            }
        }

        // Ensure at least a message or file is sent
        if (!$message && !$file_name) {
            throw new Exception("Error: Both message and file cannot be empty.");
        }

        // Insert message into database
        $insert_message = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, file) VALUES (?, ?, ?, ?)");
        $insert_message->execute([$sender_id, $receiver_id, $message, $file_name]);

        // Commit transaction
        $conn->commit();

        // Send WebSocket message
        $ws_data = json_encode([
            'type' => 'chat',
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message,
            'file' => $file_name
        ]);

        $ws = @stream_socket_client("tcp://localhost:8080", $errno, $errstr, 30);
        if ($ws) {
            fwrite($ws, $ws_data);
            fclose($ws);
        }

        // Redirect back to chat
        header("Location: messages.php?friend_id=" . $receiver_id);
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaction on error
        error_log("Transaction Failed: " . $e->getMessage());
        die("Error: " . $e->getMessage());
    }
}
