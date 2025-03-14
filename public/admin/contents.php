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

   $verify_video = $conn->prepare("SELECT * FROM `content` WHERE id = ? LIMIT 1");
   $verify_video->execute([$delete_id]);

   if($verify_video->rowCount() > 0){
      $fetch_file = $verify_video->fetch(PDO::FETCH_ASSOC);
      $file_path = '../uploads/'.$fetch_file['file'];

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="contents">
   <h1 class="heading">Your Contents</h1>
   <div class="box-container">
      <div class="box" style="text-align: center;">
         <h3 class="title" style="margin-bottom: .5rem;">Create New Content</h3>
         <a href="add_content.php" class="btn">Add Content</a>
      </div>

      <?php
      $select_files = $conn->prepare("SELECT * FROM `content` WHERE tutor_id = ? ORDER BY date DESC");
      $select_files->execute([$tutor_id]);
      if($select_files->rowCount() > 0){
         while($fetch_files = $select_files->fetch(PDO::FETCH_ASSOC)){ 
            $file_id = $fetch_files['id'];
            $file_name = $fetch_files['file'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $playlist_id = $fetch_files['playlist_id'];

            // Fetch the playlist thumbnail
            $select_playlist_thumb = $conn->prepare("SELECT thumb FROM `playlist` WHERE id = ? LIMIT 1");
            $select_playlist_thumb->execute([$playlist_id]);
            $fetch_playlist_thumb = $select_playlist_thumb->fetch(PDO::FETCH_ASSOC);
            $thumbnail = $fetch_playlist_thumb ? $fetch_playlist_thumb['thumb'] : "default-thumb.jpg";
      ?>
      <div class="box">
         <div class="flex">
            <div>
               <i class="fas fa-dot-circle" style="<?= ($fetch_files['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"></i>
               <span style="<?= ($fetch_files['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"><?= $fetch_files['status']; ?></span>
            </div>
            <div><i class="fas fa-calendar"></i><span><?= $fetch_files['date']; ?></span></div>
         </div>

         <div class="thumb">
            <?php if(in_array($file_ext, ['mp4', 'avi', 'mov'])): ?>
               <video src="../uploads/<?= $file_name; ?>" class="thumb" controls></video>
            <?php elseif($file_ext === 'pdf'): ?>
               <a href="../uploads/<?= $file_name; ?>" target="_blank">
                  <img src="../uploads/<?= $thumbnail; ?>" class="thumb" alt="PDF File">
               </a>
            <?php endif; ?>
         </div>

         <h3 class="title"><?= $fetch_files['title']; ?></h3>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="video_id" value="<?= $file_id; ?>">
            <a href="update_content.php?get_id=<?= $file_id; ?>" class="option-btn">Update</a>
            <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this file?');" name="delete_video">
         </form>
         <a href="view_content.php?get_id=<?= $file_id; ?>" class="btn">View Content</a>
      </div>
      <?php
         }
      } else {
         echo '<p class="empty">No contents added yet!</p>';
      }
      ?>
   </div>
</section>

<?php include '../components/footer.php'; ?>
<script src="../js/admin_script.js"></script>
</body>
</html>
