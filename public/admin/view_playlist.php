<?php

include '../../config/connect.php';

if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    $tutor_id = '';
    header('location:login.php');
    exit();
}

if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];
} else {
    $get_id = '';
    header('location:playlist.php');
    exit();
}

// Delete playlist
if (isset($_POST['delete_playlist'])) {
    $delete_id = $_POST['playlist_id'];
    $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);
    $delete_playlist_thumb = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? LIMIT 1");
    $delete_playlist_thumb->execute([$delete_id]);
    $fetch_thumb = $delete_playlist_thumb->fetch(PDO::FETCH_ASSOC);

    if (!empty($fetch_thumb['thumb'])) {
        unlink('../uploads/'.$fetch_thumb['thumb']);
    }

    $delete_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE playlist_id = ?");
    $delete_bookmark->execute([$delete_id]);

    $delete_playlist = $conn->prepare("DELETE FROM `playlist` WHERE id = ?");
    $delete_playlist->execute([$delete_id]);

    header('location:playlists.php');
    exit();
}

// Delete video/content from playlist
if (isset($_POST['delete_video'])) {
    $delete_id = $_POST['video_id'];
    $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);
    $verify_video = $conn->prepare("SELECT * FROM `content` WHERE id = ? LIMIT 1");
    $verify_video->execute([$delete_id]);

    if ($verify_video->rowCount() > 0) {
        $fetch_video = $verify_video->fetch(PDO::FETCH_ASSOC);
        $file_path = '../uploads/'.$fetch_video['file'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE content_id = ?");
        $delete_likes->execute([$delete_id]);

        $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE content_id = ?");
        $delete_comments->execute([$delete_id]);

        $delete_content = $conn->prepare("DELETE FROM `content` WHERE id = ?");
        $delete_content->execute([$delete_id]);

        $message[] = 'Content deleted!';
    } else {
        $message[] = 'Content already deleted!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Playlist Details</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="playlist-details">
   <h1 class="heading">Playlist Details</h1>

   <?php
      $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? AND tutor_id = ?");
$select_playlist->execute([$get_id, $tutor_id]);
if ($select_playlist->rowCount() > 0) {
    while ($fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC)) {
        $playlist_id = $fetch_playlist['id'];
        $playlist_thumb = $fetch_playlist['thumb']; // Get playlist thumbnail
        $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
        $count_videos->execute([$playlist_id]);
        $total_videos = $count_videos->rowCount();
        ?>
   <div class="row">
      <div class="thumb">
         <span><?= $total_videos; ?></span>
         <img src="../uploads/<?= $playlist_thumb; ?>" alt="Playlist Thumbnail">
      </div>
      <div class="details">
         <h3 class="title"><?= $fetch_playlist['title']; ?></h3>
         <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
         <div class="description"><?= $fetch_playlist['description']; ?></div>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="playlist_id" value="<?= $playlist_id; ?>">
            <a href="update_playlist.php?get_id=<?= $playlist_id; ?>" class="option-btn">Update Playlist</a>
            <input type="submit" value="Delete Playlist" class="delete-btn" onclick="return confirm('Delete this playlist?');" name="delete_playlist">
         </form>
      </div>
   </div>
   <?php
    }
} else {
    echo '<p class="empty">No playlist found!</p>';
}
?>

</section>

<section class="contents">
   <h1 class="heading">Playlist Contents</h1>
   <div class="box-container">

   <?php
   $select_videos = $conn->prepare("SELECT * FROM `content` WHERE tutor_id = ? AND playlist_id = ?");
$select_videos->execute([$tutor_id, $playlist_id]);
if ($select_videos->rowCount() > 0) {
    while ($fetch_videos = $select_videos->fetch(PDO::FETCH_ASSOC)) {
        $file_id = $fetch_videos['id'];
        $file_name = $fetch_videos['file'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        ?>
      <div class="box">
         <div class="flex">
            <div>
               <i class="fas fa-dot-circle" style="<?= ($fetch_videos['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"></i>
               <span style="<?= ($fetch_videos['status'] == 'active') ? 'color:limegreen' : 'color:red'; ?>"><?= $fetch_videos['status']; ?></span>
            </div>
            <div><i class="fas fa-calendar"></i><span><?= $fetch_videos['date']; ?></span></div>
         </div>

         <div class="thumb">
            <?php if (in_array($file_ext, ['mp4', 'avi', 'mov'])): ?>
               <video src="../uploads/<?= $file_name; ?>" class="thumb" controls></video>
            <?php elseif ($file_ext === 'pdf'): ?>
               <a href="../uploads/<?= $file_name; ?>" target="_blank">
                  <img src="../uploads/<?= $playlist_thumb; ?>" class="thumb" alt="PDF File">
               </a>
            <?php endif; ?>
         </div>

         <h3 class="title"><?= $fetch_videos['title']; ?></h3>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="video_id" value="<?= $file_id; ?>">
            <a href="update_content.php?get_id=<?= $file_id; ?>" class="option-btn">Update</a>
            <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this content?');" name="delete_video">
         </form>
         <a href="view_content.php?get_id=<?= $file_id; ?>" class="btn">View Content</a>
      </div>
   <?php
    }
} else {
    echo '<p class="empty">No content added yet! <a href="add_content.php" class="btn" style="margin-top: 1.5rem;">Add Content</a></p>';
}
?>

   </div>
</section>

<?php include '../components/footer.php'; ?>
<script src="../js/admin_script.js"></script>

</body>
</html>
