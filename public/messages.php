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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'components/user_header.php'; ?>

<div class="chat-container">
    <div class="chat-header">
        <a href="chat.php" class="btn"><i class="fa-solid fa-arrow-left"></i></a>
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
        <div class="file-preview" id="file-preview" style="display: none;">
            <span id="file-name"></span>
            <span class="remove-file" onclick="removeFile()">✖</span>
        </div>
        <label for="file-input"><i class="fas fa-paperclip"></i></label>
        <input type="file" id="file-input">
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>

<script src="js/script.js"></script>

<script>
    const ws = new WebSocket("ws://localhost:3000");

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        displayMessage(data.sender_id, data.message, data.file);
    };

    document.getElementById("file-input").addEventListener("change", function(event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById("file-preview");
        const fileNameSpan = document.getElementById("file-name");

        if (file) {
            fileNameSpan.textContent = file.name;
            previewContainer.style.display = "flex";
        } else {
            previewContainer.style.display = "none";
        }
    });

    function removeFile() {
        const fileInput = document.getElementById("file-input");
        const previewContainer = document.getElementById("file-preview");
        const fileNameSpan = document.getElementById("file-name");

        fileInput.value = "";
        fileNameSpan.textContent = "";
        previewContainer.style.display = "none";
    }

    function displayMessage(senderId, message, file) {
        const chatMessages = document.getElementById("chat-messages");
        const messageDiv = document.createElement("div");
        messageDiv.classList.add("message", senderId == <?= json_encode($user_id); ?> ? "sent" : "received");
        
        // Display message text
        if (message) {
            messageDiv.innerHTML += `<p>${message}</p>`;
        }

        // Display file if exists
        if (file) {
            let fileDownload = document.createElement("a");
            fileDownload.classList.add('download-file');
            fileDownload.href = "/uploads/" + file;
            fileDownload.download = file;
            fileDownload.innerText = "Download File";
            messageDiv.appendChild(fileDownload);
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
        const previewContainer = document.getElementById("file-preview");

        // Ensure message or file exists
        if (message !== "" || fileInput.files.length > 0) {
            let fileName = fileInput.files.length > 0 ? fileInput.files[0].name : "";
            console.log(fileInput.file)

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
            previewContainer.style.display = "none";
        }
    });
</script>

</body>
</html>
