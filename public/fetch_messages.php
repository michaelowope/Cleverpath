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
            <?php 
                $file_path = "uploads/" . htmlspecialchars($message['file']);
            ?>
            <a href="<?= $file_path; ?>" download class="download-file">
                Download File
            </a>
        <?php endif; ?>

    </div>
    <?php
}
?>
