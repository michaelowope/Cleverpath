<?php
include '../config/connect.php';

if (!isset($_COOKIE['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_COOKIE['user_id'];

// Default pagination settings
$items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$selected_level = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($current_page - 1) * $items_per_page;

// Friend Request Handling
if (isset($_POST['send_request'])) {
    $receiver_id = $_POST['receiver_id'];

    // Ensure the user exists before sending request
    $check_user = $conn->prepare("SELECT id FROM `users` WHERE id = :receiver_id");
    $check_user->execute(['receiver_id' => $receiver_id]);

    if ($check_user->rowCount() > 0) {
        // Check if the request already exists
        $check_request = $conn->prepare("SELECT * FROM `friend_requests` WHERE 
            (sender_id = :user_id AND receiver_id = :receiver_id) OR 
            (sender_id = :receiver_id AND receiver_id = :user_id)");
        $check_request->execute(['user_id' => $user_id, 'receiver_id' => $receiver_id]);

        if ($check_request->rowCount() == 0) {
            $send_request = $conn->prepare("INSERT INTO `friend_requests` (sender_id, receiver_id, status) VALUES (:user_id, :receiver_id, 'pending')");
            $send_request->execute(['user_id' => $user_id, 'receiver_id' => $receiver_id]);
            $message = "Friend request sent!";
        } else {
            $message = "Request already sent or you are already friends!";
        }
    } else {
        $message = "User does not exist!";
    }
}

// Accept or Decline Friend Request
if (isset($_POST['accept_request'])) {
    $request_id = $_POST['request_id'];
    $update_request = $conn->prepare("UPDATE `friend_requests` SET status = 'accepted' WHERE id = :request_id");
    $update_request->execute(['request_id' => $request_id]);
    $message = "Friend request accepted!";
}

if (isset($_POST['decline_request'])) {
    $request_id = $_POST['request_id'];
    $delete_request = $conn->prepare("DELETE FROM `friend_requests` WHERE id = :request_id");
    $delete_request->execute(['request_id' => $request_id]);
    $message = "Friend request declined!";
}

// Fetch Pending Friend Requests
$friend_requests_query = $conn->prepare("
    SELECT fr.id AS request_id, u.id AS sender_id, u.name, u.image, u.course, u.level 
    FROM `friend_requests` fr 
    JOIN `users` u ON fr.sender_id = u.id 
    WHERE fr.receiver_id = :user_id AND fr.status = 'pending'
");
$friend_requests_query->execute(['user_id' => $user_id]);
$friend_requests = $friend_requests_query->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Fetch IDs of existing friends
$existing_friends_query = $conn->prepare("
    SELECT DISTINCT CASE 
        WHEN sender_id = :user_id THEN receiver_id 
        ELSE sender_id 
    END AS friend_id
    FROM `friend_requests`
    WHERE (sender_id = :user_id OR receiver_id = :user_id) AND status = 'accepted'
");
$existing_friends_query->execute(['user_id' => $user_id]);
$existing_friends = array_column($existing_friends_query->fetchAll(PDO::FETCH_ASSOC), 'friend_id');

// Build query with filtering and search options
$query = "SELECT * FROM `users` WHERE id != :user_id";
$params = ['user_id' => $user_id];

if (!empty($existing_friends)) {
    $friend_placeholders = [];
    foreach ($existing_friends as $index => $friend_id) {
        $param_name = ":friend_id_$index";
        $friend_placeholders[] = $param_name;
        $params[$param_name] = $friend_id;
    }
    $query .= " AND id NOT IN (" . implode(',', $friend_placeholders) . ")";
}

if ($selected_level > 0) {
    $query .= " AND level = :level";
    $params['level'] = $selected_level;
}

if (!empty($selected_course)) {
    $query .= " AND course = :course";
    $params['course'] = $selected_course;
}

if (!empty($search_query)) {
    $query .= " AND (name LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search_query%";
}

// 🚀 **Fixed: MariaDB Limit/Offset Syntax Issue**
$query .= " ORDER BY name ASC LIMIT $items_per_page OFFSET $offset";

// Fetch filtered users
$select_users = $conn->prepare($query);
$select_users->execute($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friends</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="friends-container">
    <h1 class="heading"><i class="fas fa-user-friends"></i> Add Friends</h1>

    <?php if (!empty($message)): ?>
        <div class="message-box">
            <p><?= htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Pending Friend Requests -->
    <h2>Friend Requests</h2>
    <div class="box-container">
        <?php foreach ($friend_requests as $request): ?>
            <div class="box">
                <img src="uploads/<?= $request['image']; ?>" class="friend-img">
                <h3><?= htmlspecialchars($request['name']); ?></h3>
                <p><?= htmlspecialchars($request['course']); ?> - <?= $request['level']; ?> Level</p>
                <form action="" method="post">
                    <input type="hidden" name="request_id" value="<?= $request['request_id']; ?>">
                    <button type="submit" name="accept_request" class="btn accept-btn"><i class="fas fa-check"></i> Accept</button>
                    <button type="submit" name="decline_request" class="btn decline-btn"><i class="fas fa-times"></i> Decline</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>All Students</h2>
    <div class="box-container">
        <?php foreach ($select_users->fetchAll(PDO::FETCH_ASSOC) as $student): ?>
            <div class="box">
                <img src="uploads/<?= $student['image']; ?>" class="friend-img">
                <h3><?= htmlspecialchars($student['name']); ?></h3>
                <p><?= htmlspecialchars($student['course']); ?> - <?= $student['level']; ?> Level</p>
                <form action="" method="post">
                    <input type="hidden" name="receiver_id" value="<?= $student['id']; ?>">
                    <button type="submit" name="send_request" class="btn"><i class="fas fa-user-plus"></i> Add Friend</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
