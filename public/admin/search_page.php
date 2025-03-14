<?php

include '../../config/connect.php';

if(isset($_COOKIE['tutor_id'])){
   $tutor_id = $_COOKIE['tutor_id'];
}else{
   $tutor_id = '';
   header('location:login.php');
   exit();
}

if(isset($_POST['delete_video'])){
   $delete_id = $_POST['video_id'];
   $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);
   
   // Verify video existence
   $verify_video = $conn->prepare("SELECT * FROM `content` WHERE id = ? LIMIT 1");
   $verify_video->execute([$delete_id]);
   if($verify_video->rowCount() > 0){
      $fetch_file = $verify_video->fetch(PDO::FETCH_ASSOC);
      $file_path = '../uploaded_files/'.$fetch_file['file'];
      if(file_exists($file_path)){
         unlink($file_path);
      }
      $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE content_id = ?");
      $delete_likes->execute([$delete_id]);
      $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE content_id = ?");
      $delete_comments->execute([$delete_id]);
      $delete_content = $conn->prepare("DELETE FROM `content` WHERE id = ?");
      $delete_content->execute([$delete_id]);
      $message[] = 'File deleted!';
   } else {
      $message[] = 'File already deleted!';
   }
}

if(isset($_POST['delete_playlist'])){
   $delete_id = $_POST['playlist_id'];
   $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

   $verify_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? AND tutor_id = ? LIMIT 1");
   $verify_playlist->execute([$delete_id, $tutor_id]);

   if($verify_playlist->rowCount() > 0){
      $fetch_thumb = $verify_playlist->fetch(PDO::FETCH_ASSOC);
      unlink('../uploaded_files/'.$fetch_thumb['thumb']);
      $delete_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE playlist_id = ?");
      $delete_bookmark->execute([$delete_id]);
      $delete_playlist = $conn->prepare("DELETE FROM `playlist` WHERE id = ?");
      $delete_playlist->execute([$delete_id]);
      $message[] = 'Playlist deleted!';
   } else {
      $message[] = 'Playlist already deleted!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Results</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="contents">
   <h1 class="heading">Search Results</h1>
   <div class="box-container">
   <?php
      if(isset($_POST['search']) or isset($_POST['search_btn'])){
         $search = $_POST['search'];
         
         // Fetch content with playlist thumbnail
         $select_videos = $conn->prepare("
            SELECT content.*, playlist.thumb AS playlist_thumb 
            FROM `content` 
            LEFT JOIN `playlist` ON content.playlist_id = playlist.id 
            WHERE content.title LIKE ? AND content.tutor_id = ? 
            ORDER BY content.date DESC
         ");
         $select_videos->execute(["%{$search}%", $tutor_id]);

         if($select_videos->rowCount() > 0){
            while($fetch_content = $select_videos->fetch(PDO::FETCH_ASSOC)){ 
               $video_id = $fetch_content['id'];
               $thumbnail = !empty($fetch_content['playlist_thumb']) ? $fetch_content['playlist_thumb'] : 'course-default-images.jpg';
   ?>
      <div class="box">
         <div class="flex">
            <div>
               <i class="fas fa-dot-circle" style="<?= ($fetch_content['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"></i>
               <span style="<?= ($fetch_content['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"><?= $fetch_content['status']; ?></span>
            </div>
            <div><i class="fas fa-calendar"></i><span><?= $fetch_content['date']; ?></span></div>
         </div>
         <img src="../uploaded_files/<?= $thumbnail; ?>" class="thumb" alt="">
         <h3 class="title"><?= $fetch_content['title']; ?></h3>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="video_id" value="<?= $video_id; ?>">
            <a href="update_content.php?get_id=<?= $video_id; ?>" class="option-btn">Update</a>
            <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this video?');" name="delete_video">
         </form>
         <a href="view_content.php?get_id=<?= $video_id; ?>" class="btn">View Content</a>
      </div>
   <?php
            }
         } else {
            echo '<p class="empty">No contents found!</p>';
         }
      } else {
         echo '<p class="empty">Please search something!</p>';
      }
   ?>
   </div>
</section>

<section class="playlists">
   <h1 class="heading">Courses</h1>
   <div class="box-container">
   <?php
      if(isset($_POST['search']) or isset($_POST['search_btn'])){
         $search = $_POST['search'];
         $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE title LIKE ? AND tutor_id = ? ORDER BY date DESC");
         $select_playlist->execute(["%{$search}%", $tutor_id]);

         if($select_playlist->rowCount() > 0){
            while($fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC)){
               $playlist_id = $fetch_playlist['id'];
               $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
               $count_videos->execute([$playlist_id]);
               $total_videos = $count_videos->rowCount();
   ?>
      <div class="box">
         <div class="flex">
            <div>
               <i class="fas fa-circle-dot" style="<?= ($fetch_playlist['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"></i>
               <span style="<?= ($fetch_playlist['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"><?= $fetch_playlist['status']; ?></span>
            </div>
            <div><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
         </div>
         <div class="thumb">
            <span><?= $total_videos; ?></span>
            <img src="../uploaded_files/<?= $fetch_playlist['thumb']; ?>" alt="">
         </div>
         <h3 class="title"><?= $fetch_playlist['title']; ?></h3>
         <p class="description"><?= $fetch_playlist['description']; ?></p>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="playlist_id" value="<?= $playlist_id; ?>">
            <a href="update_playlist.php?get_id=<?= $playlist_id; ?>" class="option-btn">Update</a>
            <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this playlist?');" name="delete_playlist">
         </form>
         <a href="view_playlist.php?get_id=<?= $playlist_id; ?>" class="btn">View Courses</a>
      </div>
   <?php
            }
         } else {
            echo '<p class="empty">No courses found!</p>';
         }
      } else {
         echo '<p class="empty">Please search something!</p>';
      }
   ?>
   </div>
</section>

<?php include '../components/footer.php'; ?>
<script src="../js/admin_script.js"></script>
</body>
</html>
