<?php
include '../config/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    // Send message via WebSocket
    $ws_data = json_encode([
        'type' => 'chat',
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'message' => $message
    ]);

    $ws = stream_socket_client("tcp://localhost:8080", $errno, $errstr, 30);
    fwrite($ws, $ws_data);
    fclose($ws);

    header("Location: messages.php?friend_id=" . $receiver_id);
    exit();
}
?>
