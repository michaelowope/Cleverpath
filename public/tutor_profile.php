<?php
include '../config/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

if (isset($_POST['tutor_fetch'])) {
    $tutor_email = $_POST['tutor_email'];
    $tutor_email = filter_var($tutor_email, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $select_tutor = $conn->prepare('SELECT * FROM `tutors` WHERE email = ?');
    $select_tutor->execute([$tutor_email]);

    $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);
    $tutor_id = $fetch_tutor['id'];

    $count_playlists = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ?");
    $count_playlists->execute([$tutor_id]);
    $total_playlists = $count_playlists->rowCount();

    $count_contents = $conn->prepare("SELECT * FROM `content` WHERE tutor_id = ?");
    $count_contents->execute([$tutor_id]);
    $total_contents = $count_contents->rowCount();

    $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE tutor_id = ?");
    $count_likes->execute([$tutor_id]);
    $total_likes = $count_likes->rowCount();

    $count_comments = $conn->prepare("SELECT * FROM `comments` WHERE tutor_id = ?");
    $count_comments->execute([$tutor_id]);
    $total_comments = $count_comments->rowCount();
} else {
    header('location:teachers.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Teacher Profile</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- Teacher Profile Section Starts -->
<section class="tutor-profile">
   <!-- Back to Teachers Button -->
   <div class="back-btn-container">
       <a href="teachers.php" class="btn"><i class="fa-solid fa-arrow-left"></i>Go back</a>
   </div>

   <h1 class="heading">Profile Details</h1>
   <div class="details">
      <div class="tutor">
         <img src="uploads/<?= $fetch_tutor['image']; ?>" alt="">
         <h3><?= $fetch_tutor['name']; ?></h3>
         <span><?= $fetch_tutor['department']; ?></span>
      </div>
      <div class="flex">
         <p>Total Playlists : <span><?= $total_playlists; ?></span></p>
         <p>Total File : <span><?= $total_contents; ?></span></p>
         <p>Total Likes : <span><?= $total_likes; ?></span></p>
      </div>
   </div>
   
</section>
<!-- Teacher Profile Section Ends -->

<!-- Latest Courses Section Starts -->
<section class="courses">
   <h1 class="heading">Latest Course</h1>
   <div class="box-container">
      <?php
         $select_courses = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ? AND status = ?");
         $select_courses->execute([$tutor_id, 'active']);
         if ($select_courses->rowCount() > 0) {
             while ($fetch_course = $select_courses->fetch(PDO::FETCH_ASSOC)) {
                 $course_id = $fetch_course['id'];
                 
                 $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ?");
                 $select_tutor->execute([$fetch_course['tutor_id']]);
                 $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);
                 ?>
      <div class="box">
         <div class="tutor">
            <img src="uploads/<?= $fetch_tutor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_tutor['name']; ?></h3>
               <span><?= $fetch_course['date']; ?></span>
            </div>
         </div>
         <img src="uploads/<?= $fetch_course['thumb']; ?>" class="thumb" alt="">
         <h3 class="title"><?= $fetch_course['title']; ?></h3>
         <?php if ($user_id != '') { ?>
            <a href="playlist.php?get_id=<?= $course_id; ?>" class="inline-btn home-btn">View Playlist</a>
         <?php } else { ?>
            <a href="login.php" class="inline-btn home-btn">Login to view</a>
         <?php } ?>
      </div>
      <?php
             }
         } else {
             echo '<p class="empty">No courses added yet!</p>';
         }
      ?>
   </div>
</section>
<!-- Latest Courses Section Ends -->

<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
   
</body>
</html>
