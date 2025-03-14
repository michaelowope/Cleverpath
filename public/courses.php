<?php

include '../config/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

// Default pagination settings
$items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$selected_level = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$offset = ($current_page - 1) * $items_per_page;

// Build dynamic query with optional level filtering
$query = "SELECT * FROM `playlist` WHERE status = :status";
$params = ['status' => 'active'];

if ($selected_level > 0) {
    $query .= " AND id IN (SELECT DISTINCT playlist_id FROM `content` WHERE level = :level)";
    $params['level'] = $selected_level;
}

$query .= " ORDER BY date DESC LIMIT :limit OFFSET :offset";

// Count total courses with filtering
$count_query = "SELECT COUNT(*) FROM `playlist` WHERE status = :status";
if ($selected_level > 0) {
    $count_query .= " AND id IN (SELECT DISTINCT playlist_id FROM `content` WHERE level = :level)";
}

$count_courses = $conn->prepare($count_query);
$count_courses->execute($params);
$total_courses = $count_courses->fetchColumn();

// Calculate total pages
$total_pages = ($total_courses > 0) ? ceil($total_courses / $items_per_page) : 1;

// Fetch courses
$select_courses = $conn->prepare($query);
$select_courses->bindParam(':status', $params['status'], PDO::PARAM_STR);
if ($selected_level > 0) {
    $select_courses->bindParam(':level', $params['level'], PDO::PARAM_INT);
}
$select_courses->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
$select_courses->bindParam(':offset', $offset, PDO::PARAM_INT);
$select_courses->execute();

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Courses</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="courses">

   <h1 class="heading">All Courses</h1>

   <!-- Filtering and Pagination Selection -->
   <form action="" method="GET" class="filter-form">
      <div class="filter">
         <label for="level">Filter by Level:</label>
         <select name="level" id="level" onchange="this.form.submit()">
            <option value="0" <?= ($selected_level == 0) ? 'selected' : ''; ?>>All Levels</option>
            <option value="100" <?= ($selected_level == 100) ? 'selected' : ''; ?>>100 Level</option>
            <option value="200" <?= ($selected_level == 200) ? 'selected' : ''; ?>>200 Level</option>
            <option value="300" <?= ($selected_level == 300) ? 'selected' : ''; ?>>300 Level</option>
            <option value="400" <?= ($selected_level == 400) ? 'selected' : ''; ?>>400 Level</option>
         </select>
      </div>

      <div>
         <label for="limit">Show:</label>
         <select name="limit" id="limit" onchange="this.form.submit()">
            <option value="10" <?= ($items_per_page == 10) ? 'selected' : ''; ?>>10 per page</option>
            <option value="20" <?= ($items_per_page == 20) ? 'selected' : ''; ?>>20 per page</option>
            <option value="30" <?= ($items_per_page == 30) ? 'selected' : ''; ?>>30 per page</option>
         </select>
   
         <input type="hidden" name="page" value="1">
      </div>

   </form>

   <div class="box-container">
      <?php
         if($select_courses->rowCount() > 0){
            while($fetch_course = $select_courses->fetch(PDO::FETCH_ASSOC)){
               $course_id = $fetch_course['id'];

               $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = :tutor_id");
               $select_tutor->execute(['tutor_id' => $fetch_course['tutor_id']]);
               $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);
      ?>
      <div class="box">
         <div class="tutor">
            <img src="uploaded_files/<?= $fetch_tutor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_tutor['name']; ?></h3>
               <span><?= $fetch_course['date']; ?></span>
            </div>
         </div>
         <img src="uploaded_files/<?= $fetch_course['thumb']; ?>" class="thumb" alt="">
         <h3 class="title"><?= $fetch_course['title']; ?></h3>
         <?php if($user_id != ''){ ?>
            <a href="playlist.php?get_id=<?= $course_id; ?>" class="inline-btn home-btn">View Course</a>
         <?php } else { ?>
            <a href="login.php" class="inline-btn home-btn">Login to view</a>
         <?php } ?>
      </div>
      <?php
            }
         }else{
            echo '<p class="empty">No courses found!</p>';
         }
      ?>
   </div>

   <!-- Pagination Navigation -->
   <div class="pagination">
      <?php if ($current_page > 1): ?>
         <a href="?page=<?= $current_page - 1; ?>&limit=<?= $items_per_page; ?>&level=<?= $selected_level; ?>" class="prev">Previous</a>
      <?php endif; ?>

      <span>Page <?= $current_page; ?> of <?= $total_pages; ?></span>

      <?php if ($current_page < $total_pages): ?>
         <a href="?page=<?= $current_page + 1; ?>&limit=<?= $items_per_page; ?>&level=<?= $selected_level; ?>" class="next">Next</a>
      <?php endif; ?>
   </div>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>
