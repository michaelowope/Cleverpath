<?php
include '../config/connect.php';

if (!isset($_COOKIE['user_id'])) {
    exit();
}

$user_id = $_COOKIE['user_id'];
$friend_id = $_GET['friend_id'] ?? null;

if (!$friend_id) {
    exit();
}

// Fetch messages from DB
$messages_query = $conn->prepare("
    SELECT * FROM `messages` 
    WHERE (sender_id = :user_id AND receiver_id = :friend_id) 
       OR (sender_id = :friend_id AND receiver_id = :user_id)
    ORDER BY timestamp ASC
");
$messages_query->execute(['user_id' => $user_id, 'friend_id' => $friend_id]);
$messages = $messages_query->fetchAll(PDO::FETCH_ASSOC);

// Display messages
foreach ($messages as $message) {
    $is_sent = ($message['sender_id'] == $user_id);
    $message_class = $is_sent ? 'sent' : 'received';
    ?>
    <div class="message-pvp <?= $message_class; ?>">
        <?php if (!empty($message['message'])): ?>
            <span><?= htmlspecialchars($message['message']); ?></span>
        <?php endif; ?>
        
        <?php if (!empty($message['file'])): ?>
            <br>
            <?php 
            $file_extension = pathinfo($message['file'], PATHINFO_EXTENSION);
            $file_path = "uploads/" . htmlspecialchars($message['file']);
            
            if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <img src="<?= $file_path; ?>" alt="Image" class="chat-file-preview">
            <?php else: ?>
                <a href="<?= $file_path; ?>" target="_blank" download>
                    <i class="fas fa-file"></i> <?= htmlspecialchars($message['file']); ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
?>
