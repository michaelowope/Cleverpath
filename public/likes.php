<?php
include '../config/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
    header('location:index.php');
    exit();
}

// Pagination parameters
$items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total likes for the user
$count_query = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE user_id = ?");
$count_query->execute([$user_id]);
$total_likes_total = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_likes_total / $items_per_page);

// Fetch liked content with pagination
$select_likes = $conn->prepare("SELECT * FROM likes WHERE user_id = ? LIMIT ? OFFSET ?");
$select_likes->bindParam(1, $user_id, PDO::PARAM_STR);
$select_likes->bindParam(2, $items_per_page, PDO::PARAM_INT);
$select_likes->bindParam(3, $offset, PDO::PARAM_INT);
$select_likes->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Liked Courses</title>
   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="liked-videos">
   <div class="back-btn-container">
      <button onclick="window.history.back()" class="btn"><i class="fa-solid fa-arrow-left"></i> Go back</button>
   </div>

   <h1 class="heading">Liked Courses</h1>

   <!-- Items Per Page Selection -->
   <form method="GET" action="likes.php" class="pagination-form">
      <label for="limit">Show:</label>
      <select name="limit" id="limit" onchange="this.form.submit()">
         <option value="10" <?= ($items_per_page == 10) ? 'selected' : ''; ?>>10 per page</option>
         <option value="20" <?= ($items_per_page == 20) ? 'selected' : ''; ?>>20 per page</option>
         <option value="30" <?= ($items_per_page == 30) ? 'selected' : ''; ?>>30 per page</option>
      </select>
      <input type="hidden" name="page" value="1">
   </form>

   <div class="box-container">
   <?php
   if ($select_likes->rowCount() > 0) {
       while ($fetch_like = $select_likes->fetch(PDO::FETCH_ASSOC)) {
           $content_id = $fetch_like['content_id'];

           // Fetch content details
           $select_contents = $conn->prepare("SELECT * FROM content WHERE id = ? ORDER BY date DESC");
           $select_contents->execute([$content_id]);
           if ($select_contents->rowCount() > 0) {
               while ($fetch_content = $select_contents->fetch(PDO::FETCH_ASSOC)) {
                   // Fetch tutor details
                   $select_tutors = $conn->prepare("SELECT * FROM tutors WHERE id = ?");
                   $select_tutors->execute([$fetch_content['tutor_id']]);
                   $fetch_tutor = $select_tutors->fetch(PDO::FETCH_ASSOC);

                   // Get playlist thumbnail using playlist_id from content
                   $playlist_id = $fetch_content['playlist_id'];
                   $select_playlist = $conn->prepare("SELECT thumb FROM playlist WHERE id = ? LIMIT 1");
                   $select_playlist->execute([$playlist_id]);
                   $fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC);
                   $display_thumb = $fetch_playlist ? $fetch_playlist['thumb'] : $fetch_content['thumb'];
                   ?>
                   <div class="box">
                      <div class="tutor">
                         <img src="uploads/<?= htmlspecialchars($fetch_tutor['image']); ?>" alt="">
                         <div>
                            <h3><?= htmlspecialchars($fetch_tutor['name']); ?></h3>
                            <span><?= htmlspecialchars($fetch_content['date']); ?></span>
                         </div>
                      </div>
                      <img src="uploads/<?= htmlspecialchars($display_thumb); ?>" alt="" class="thumb">
                      <h3 class="title"><?= htmlspecialchars($fetch_content['title']); ?></h3>
                      <form action="" method="post" class="flex-btn">
                         <input type="hidden" name="content_id" value="<?= htmlspecialchars($fetch_content['id']); ?>">
                         <a href="view_content.php?get_id=<?= htmlspecialchars($fetch_content['id']); ?>" class="inline-btn">View Content</a>
                         <input type="submit" value="Unlike" class="inline-delete-btn" name="remove">
                      </form>
                   </div>
                   <?php
               }
           } else {
               echo '<p class="empty">Content not found!</p>';
           }
       }
   } else {
       echo '<p class="empty">Nothing added to likes yet!</p>';
   }
   ?>
   </div>

   <!-- Pagination Navigation -->
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
