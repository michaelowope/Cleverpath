<?php
include '../config/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

// Pagination settings
$items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;  // Default: 5 teachers per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total teachers
$count_query = $conn->prepare("SELECT COUNT(*) as total FROM tutors");
$count_query->execute();
$total_teachers = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_teachers / $items_per_page);

// Fetch teachers for current page
$select_tutors = $conn->prepare("SELECT * FROM tutors ORDER BY name ASC LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset);
$select_tutors->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Teachers</title>
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- teachers section starts  -->
<section class="teachers">
   <h1 class="heading">Expert Teachers</h1>

   <form action="search_tutor.php" method="post" class="search-tutor">
      <input type="text" name="search_tutor" maxlength="100" placeholder="Search teacher..." required>
      <button type="submit" name="search_tutor_btn" class="fas fa-search"></button>
   </form>

   <div class="box-container">
      <?php if (empty($user_id)) { ?>
         <div class="box offer">
            <h3>Inspire & Educate: Become a Teacher Today!</h3>
            <p>Empower students with your expertise. Join our platform and start teaching today!</p>
            <a href="admin/register.php" class="inline-btn">Get Started</a>
         </div>
      <?php } ?>

      <?php
      if ($select_tutors->rowCount() > 0) {
          while ($fetch_tutor = $select_tutors->fetch(PDO::FETCH_ASSOC)) {
              $tutor_id = $fetch_tutor['id'];

              // Count tutor's playlists, contents, likes, comments
              $count_playlists = $conn->prepare("SELECT * FROM playlist WHERE tutor_id = ?");
              $count_playlists->execute([$tutor_id]);
              $total_playlists = $count_playlists->rowCount();

              $count_contents = $conn->prepare("SELECT * FROM content WHERE tutor_id = ?");
              $count_contents->execute([$tutor_id]);
              $total_contents = $count_contents->rowCount();

              $count_likes = $conn->prepare("SELECT * FROM likes WHERE tutor_id = ?");
              $count_likes->execute([$tutor_id]);
              $total_likes = $count_likes->rowCount();

            //   $count_comments = $conn->prepare("SELECT * FROM comments WHERE tutor_id = ?");
            //   $count_comments->execute([$tutor_id]);
            //   $total_comments = $count_comments->rowCount();
              ?>
              <div class="box">
                 <div class="tutor">
                    <img src="uploads/<?= $fetch_tutor['image']; ?>" alt="">
                    <div>
                       <h3><?= $fetch_tutor['name']; ?></h3>
                       <span><?= $fetch_tutor['department']; ?></span>
                    </div>
                 </div>
                 <p>Playlists: <span><?= $total_playlists; ?></span></p>
                 <p>Total Content: <span><?= $total_contents; ?></span></p>
                 <p>Total Likes: <span><?= $total_likes; ?></span></p>
                 <form action="tutor_profile.php" method="post">
                    <input type="hidden" name="tutor_email" value="<?= $fetch_tutor['email']; ?>">
                    <input type="submit" value="View Profile" name="tutor_fetch" class="inline-btn">
                 </form>
              </div>
          <?php
          }
      } else {
          echo '<p class="empty">No teachers found!</p>';
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
<!-- teachers section ends  -->

<?php include 'components/footer.php'; ?>
<!-- custom js file link  -->
<script src="js/script.js"></script>
   
</body>
</html>