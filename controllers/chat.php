<?php
include '../config/connect.php';

if (!isset($_COOKIE['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_COOKIE['user_id'];

// Handle Unfriend Request
if (isset($_POST['unfriend'])) {
    $friend_id = $_POST['friend_id'];

    $delete_friend = $conn->prepare("
        DELETE FROM `friend_requests` 
        WHERE ((sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)) 
        AND status = 'accepted'
    ");
    $delete_friend->execute([$user_id, $friend_id, $friend_id, $user_id]);

    $message = "Friend removed successfully!";
}

// Fetch Friends (Ensure No Duplicates & Prevent Errors)
$friends_query = $conn->prepare("
    SELECT DISTINCT u.id, u.name, u.image 
    FROM `users` u 
    JOIN `friend_requests` f 
    ON (u.id = f.sender_id OR u.id = f.receiver_id) 
    WHERE f.status = 'accepted' 
    AND (f.sender_id = ? OR f.receiver_id = ?) 
    AND u.id != ?
");
$friends_query->execute([$user_id, $user_id, $user_id]);
$friends = $friends_query->fetchAll(PDO::FETCH_ASSOC) ?: []; // Ensures it's always an array
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .friend-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--white);
            padding: 1rem;
            border-radius: .5rem;
            transition: 0.3s;
            cursor: pointer;
            text-decoration: none;
        }

        .friend-box div {
            display:flex;
            align-items:center;
            gap: 2rem;
        }

        .friend-box div h3 {
            font-size: 2rem;
        }

        .friend-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .friend-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .friends-container .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .friends-container .box {
            background: var(--white);
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .friends-container .btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<?php include '../components/user_header.php'; ?>

<section class="chat-container">
    <h1 class="heading"><i class="fas fa-comments"></i> Your Friends</h1>

    <!-- Add Friend Button -->
    <div class="add-friend-container">
        <a href="add_friends.php" class="btn add-friend-btn">
            <i class="fas fa-user-plus"></i> Add Friend
        </a>
    </div>

    <!-- Display Messages -->
    <?php if (!empty($message)): ?>
        <div class="message-box">
            <p><?= htmlspecialchars($message ?? ''); ?></p>
        </div>
    <?php endif; ?>

    <div class="friend-list">
        <?php if (!empty($friends) && is_array($friends)): ?>
            <?php foreach ($friends as $friend): ?>
                <a class="friend-box" href="messages.php?friend_id=<?= $friend['id']; ?>">
                    <div>
                        <img src="uploaded_files/<?= $friend['image']; ?>" class="friend-img" onerror="this.src='default-avatar.png';">
                        <h3><?= htmlspecialchars($friend['name']); ?></h3>
                    </div>
                    <form action="" method="post" class="unfriend-form">
                        <input type="hidden" name="friend_id" value="<?= $friend['id']; ?>">
                        <button type="submit" name="unfriend" class="btn unfriend-btn">
                            <i class="fas fa-user-times"></i>
                        </button>
                    </form>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No friends found!</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
