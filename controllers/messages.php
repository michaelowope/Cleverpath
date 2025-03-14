<?php
include '../config/connect.php';

if (!isset($_COOKIE['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_COOKIE['user_id'];
$friend_id = isset($_GET['friend_id']) ? $_GET['friend_id'] : null;

if (!$friend_id) {
    header('location:chat.php');
    exit();
}

// Fetch friend details
$friend_query = $conn->prepare("SELECT id, name, image FROM `users` WHERE id = ?");
$friend_query->execute([$friend_id]);
$friend = $friend_query->fetch(PDO::FETCH_ASSOC);

if (!$friend) {
    header('location:chat.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?= htmlspecialchars($friend['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .chat-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 80vh;
        }
        .chat-header {
            background: #444ead;
            color: white;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
        }
        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat-messages {
            flex: 1;
            padding: 0.8rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .message {
            max-width: 80%;
            padding: 0.65rem 0.8rem;
            margin: 5px 0;
            border-radius: 1rem;
            word-wrap: break-word;
        }
        .sent {
            background: #444ead;
            color: white;
            align-self: flex-end;
        }
        .received {
            background: #ddd;
            color: black;
            align-self: flex-start;
        }
        .message img {
            max-width: 100px;
            border-radius: 5px;
        }
        .chat-input {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #fff;
            border-top: 1px solid #ddd;
        }
        .chat-input input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .chat-input input[type="file"] {
            display: none;
        }
        .chat-input label {
            cursor: pointer;
            font-size: 20px;
            color: #444ead;
            margin-right: 10px;
        }
        .chat-input button {
            background: #444ead;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-input button:hover {
            background: #363b96;
        }
        .file-preview {
            display: flex;
            align-items: center;
            padding: 5px;
            background: #eee;
            border-radius: 5px;
            display: none;
        }
        .file-preview img {
            max-width: 50px;
            border-radius: 5px;
            margin-right: 10px;
        }
        .file-preview span {
            font-size: 14px;
            color: #333;
        }
        .file-preview .remove-file {
            margin-left: 10px;
            cursor: pointer;
            color: red;
        }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">
        <img src="uploaded_files/<?= $friend['image']; ?>" alt="Friend">
        <h2><?= htmlspecialchars($friend['name']); ?></h2>
    </div>

    <div class="chat-messages" id="chat-messages">
        <?php include 'fetch_messages.php'; ?>
    </div>

    <form id="chat-form" class="chat-input">
        <input type="hidden" id="sender_id" value="<?= $user_id; ?>">
        <input type="hidden" id="receiver_id" value="<?= $friend_id; ?>">
        <input type="text" id="message" placeholder="Type a message...">
        <label for="file-input"><i class="fas fa-paperclip"></i></label>
        <input type="file" id="file-input">
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>

<script>
    const ws = new WebSocket("ws://localhost:8080");

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        const chatMessages = document.getElementById("chat-messages");
        const messageDiv = document.createElement("div");
        messageDiv.classList.add("message", data.sender_id == <?= $user_id; ?> ? "sent" : "received");
        messageDiv.innerHTML = `<p>${data.message}</p>`;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    document.getElementById("chat-form").addEventListener("submit", function(e) {
        e.preventDefault();
        const sender_id = document.getElementById("sender_id").value;
        const receiver_id = document.getElementById("receiver_id").value;
        const message = document.getElementById("message").value;

        if (message.trim() !== "") {
            ws.send(JSON.stringify({ type: "chat", sender_id, receiver_id, message }));
            document.getElementById("message").value = "";
        }
    });
</script>

</body>
</html>