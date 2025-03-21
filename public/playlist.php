<?php

include '../config/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];
} else {
    $get_id = '';
    header('location:index.php');
    exit();
}

if (isset($_POST['save_list'])) {
    if ($user_id != '') {
        $list_id = $_POST['list_id'];
        $list_id = filter_var($list_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $select_list = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
        $select_list->execute([$user_id, $list_id]);

        if ($select_list->rowCount() > 0) {
            $remove_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
            $remove_bookmark->execute([$user_id, $list_id]);
            $message[] = 'Playlist removed!';
        } else {
            $insert_bookmark = $conn->prepare("INSERT INTO `bookmark`(user_id, playlist_id) VALUES(?,?)");
            $insert_bookmark->execute([$user_id, $list_id]);
            $message[] = 'Playlist saved!';
        }
    } else {
        $message[] = 'Please login first!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Playlist</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- Playlist Section -->
<section class="playlist">
   <!-- <div class="back-btn-container">
       <button onclick='window.history.back()' class="btn"><i class="fa-solid fa-arrow-left"></i>Go back</a>
   </div> -->
   <h1 class="heading">Playlist Details</h1>
   <div class="row">

      <?php
         $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? AND status = ? LIMIT 1");
$select_playlist->execute([$get_id, 'active']);
if ($select_playlist->rowCount() > 0) {
    $fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC);
    $playlist_id = $fetch_playlist['id'];
    $playlist_thumb = $fetch_playlist['thumb']; // Fetch playlist thumbnail

    $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
    $count_videos->execute([$playlist_id]);
    $total_videos = $count_videos->rowCount();

    $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ? LIMIT 1");
    $select_tutor->execute([$fetch_playlist['tutor_id']]);
    $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

    $select_bookmark = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
    $select_bookmark->execute([$user_id, $playlist_id]);
    ?>

      <div class="col">
         <form action="" method="post" class="save-list">
            <input type="hidden" name="list_id" value="<?= $playlist_id; ?>">
            <?php if ($select_bookmark->rowCount() > 0): ?>
               <button type="submit" name="save_list"><i class="fas fa-bookmark"></i><span>Saved</span></button>
            <?php else: ?>
               <button type="submit" name="save_list"><i class="far fa-bookmark"></i><span>Save Playlist</span></button>
            <?php endif; ?>
         </form>
         <div class="thumb">
            <span><?= $total_videos; ?> File<?= $total_videos > 1 ? 's' : ''; ?></span>
            <img src="uploads/<?= $playlist_thumb; ?>" alt="Playlist Thumbnail">
         </div>
      </div>

      <div class="col">
         <div class="tutor">
            <img src="uploads/<?= $fetch_tutor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_tutor['name']; ?></h3>
               <span><?= $fetch_tutor['department']; ?></span>
            </div>
         </div>
         <div class="details">
            <h3><?= $fetch_playlist['title']; ?></h3>
            <p><?= $fetch_playlist['description']; ?></p>
            <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
         </div>
      </div>

      <?php
} else {
    echo '<p class="empty">This playlist was not found!</p>';
}
?>

   </div>
</section>

<!-- Videos Container Section -->
<section class="videos-container">
   <h1 class="heading">Playlist Content</h1>
   <div class="box-container">

      <?php
   $select_content = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ? AND status = ? ORDER BY date DESC");
$select_content->execute([$get_id, 'active']);
if ($select_content->rowCount() > 0) {
    while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
        $file_name = $fetch_content['file'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        ?>
      <a href="view_content.php?get_id=<?= $fetch_content['id']; ?>" class="box">
         <i class="fas fa-play"></i>
         <div class="thumb">
            <?php if (in_array($file_ext, ['mp4', 'avi', 'mov'])): ?>
               <video src="uploads/<?= $file_name; ?>" class="thumb" controls></video>
            <?php elseif ($file_ext === 'pdf'): ?>
               <img src="uploads/<?= $playlist_thumb; ?>" class="thumb" alt="PDF File">
               <div class="pdf-overlay">
                  <i class="fas fa-file-pdf"></i>
               </div>
            <?php endif; ?>
         </div>
         <h3><?= $fetch_content['title']; ?></h3>
      </a>
      <?php
    }
} else {
    echo '<p class="empty">No content added yet!</p>';
}
?>

   </div>
</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
