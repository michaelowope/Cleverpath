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

    $check_user = $conn->prepare("SELECT id FROM `users` WHERE id = :receiver_id");
    $check_user->execute(['receiver_id' => $receiver_id]);

    if ($check_user->rowCount() > 0) {
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
    $placeholders = implode(',', array_map(fn($i) => ":friend_id_$i", array_keys($existing_friends)));
    $query .= " AND id NOT IN ($placeholders)";
    foreach ($existing_friends as $index => $friend_id) {
        $params["friend_id_$index"] = $friend_id;
    }
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

// Count total students for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $items_per_page);

// Fetch students with pagination
$query .= " ORDER BY name ASC LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;
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
    <style>
        .filter-form {
            width: 100%;
            display: flex;
            gap: 1rem;
            align-items: center;
        }   

        .filter-form input {
            width: 100%;
            padding: 1.5rem;
        }

        .filter-form select {
            padding: 1rem;
        }

        .filter-form button {
            width: fit-content;
        }
    </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="friends-container">
    <!-- <!-- <div class="back-btn-container">
       <button onclick='window.history.back()' class="btn"><i class="fa-solid fa-arrow-left"></i>Go back</a>
   </div> --> -->
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

    <!-- Filters + All Students -->
    <h2>All Students</h2>
    <form method="GET" action="add_friends.php" class="filter-form">
        <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>" class='box'>
        <select name="course" class='box'>
            <option value="">All Courses</option>
            <option value="Computer Science" <?= ($selected_course === "Computer Science") ? "selected" : "" ?>>Computer Science</option>
            <option value="Computer Information Systems" <?= ($selected_course === "Computer Information Systems") ? "selected" : "" ?>>Computer Information Systems</option>
            <option value="Computer Technology" <?= ($selected_course === "Computer Technology") ? "selected" : "" ?>>Computer Technology</option>
            <option value="Software Engineering" <?= ($selected_course === "Software Engineering") ? "selected" : "" ?>>Software Engineering</option>
            <option value="Information Technology" <?= ($selected_course === "Information Technology") ? "selected" : "" ?>>Information Technology</option>
        </select>
        <select name="level" class='box'>
            <option value="0">All Levels</option>
            <option value="100" <?= ($selected_level == 100) ? "selected" : "" ?>>100 Level</option>
            <option value="200" <?= ($selected_level == 200) ? "selected" : "" ?>>200 Level</option>
            <option value="300" <?= ($selected_level == 300) ? "selected" : "" ?>>300 Level</option>
            <option value="400" <?= ($selected_level == 400) ? "selected" : "" ?>>400 Level</option>
        </select>
        <button type="submit" class="btn">Filter</button>
    </form>

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

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?= $current_page - 1; ?>&limit=<?= $items_per_page; ?>" class="btn">Previous</a>
        <?php endif; ?>

        <span>Page <?= $current_page; ?> of <?= $total_pages; ?></span>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?= $current_page + 1; ?>&limit=<?= $items_per_page; ?>" class="btn">Next</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
