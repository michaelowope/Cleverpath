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
        .message-pvp {
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
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">
        <img src="uploads/<?= $friend['image']; ?>" alt="Friend">
        <h2><?= htmlspecialchars($friend['name']); ?></h2>
    </div>

    <div class="chat-messages" id="chat-messages">
        <?php include 'fetch_messages.php'; ?>
    </div>

    <form id="chat-form" class="chat-input">
        <input type="hidden" name="sender_id" id="sender_id" value="<?= htmlspecialchars($user_id); ?>">
        <input type="hidden" name="receiver_id" id="receiver_id" value="<?= htmlspecialchars($friend_id); ?>">
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
        displayMessage(data.sender_id, data.message, data.file);
    };

    function displayMessage(senderId, message, file) {
        const chatMessages = document.getElementById("chat-messages");
        const messageDiv = document.createElement("div");
        messageDiv.classList.add("message-pvp", senderId == <?= json_encode($user_id); ?> ? "sent" : "received");
        
        // Display message text
        if (message) {
            messageDiv.innerHTML += `<span>${message}</span>`;
        }

        // Display file if exists
        if (file) {
            let filePreview = document.createElement("a");
            filePreview.href = "/uploads/" + file;
            filePreview.target = "_blank";
            filePreview.innerText = "📁 View File";
            filePreview.style.display = "block";
            messageDiv.appendChild(filePreview);
        }

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    document.getElementById("chat-form").addEventListener("submit", function(e) {
        e.preventDefault();
        const sender_id = document.getElementById("sender_id").value;
        const receiver_id = document.getElementById("receiver_id").value;
        const message = document.getElementById("message").value.trim();
        const fileInput = document.getElementById("file-input");

        // Ensure message or file exists
        if (message !== "" || fileInput.files.length > 0) {
            let fileName = fileInput.files.length > 0 ? fileInput.files[0].name : "";

            // Send WebSocket message (for real-time update)
            const messageData = { type: "chat", sender_id, receiver_id, message, file: fileName };
            ws.send(JSON.stringify(messageData));

            // Instantly show message for sender
            displayMessage(sender_id, message, fileName);

            // Send message to `send_message.php` via AJAX (to save in DB)
            let formData = new FormData();
            formData.append("sender_id", sender_id);
            formData.append("receiver_id", receiver_id);
            formData.append("message", message);
            if (fileInput.files.length > 0) {
                formData.append("file", fileInput.files[0]);
            }

            fetch("send_message.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(result => console.log("Message saved: " + result))
            .catch(error => console.error("Error saving message:", error));

            // Reset input fields
            document.getElementById("message").value = "";
            fileInput.value = "";
        }
    });
</script>

</body>
</html>
