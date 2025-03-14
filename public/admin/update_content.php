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
    header('location:dashboard.php');
    exit();
}

if (isset($_POST['update'])) {
    if (isset($_POST['file_id'])) {
        $file_id = $_POST['file_id'];
        $file_id = filter_var($file_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    } else {
        $file_id = '';
    }

    $status = $_POST['status'];
    $status = filter_var($status, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $title = $_POST['title'];
    $title = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = $_POST['description'];
    $description = filter_var($description, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $playlist = $_POST['playlist'];
    $playlist = filter_var($playlist, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $update_content = $conn->prepare("UPDATE `content` SET title = ?, description = ?, status = ? WHERE id = ?");
    $update_content->execute([$title, $description, $status, $file_id]);

    if (!empty($playlist)) {
        $update_playlist = $conn->prepare("UPDATE `content` SET playlist_id = ? WHERE id = ?");
        $update_playlist->execute([$playlist, $file_id]);
    }

    $old_file = isset($_POST['old_file']) ? $_POST['old_file'] : ''; // Ensure old_file exists
    $old_file = filter_var($old_file, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
        $file = $_FILES['file']['name'];
        $file = filter_var($file, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $file_ext = pathinfo($file, PATHINFO_EXTENSION);
        $allowed_ext = ['mp4', 'avi', 'mov', 'pdf']; // Allowed file types

        if (in_array($file_ext, $allowed_ext)) {
            $rename_file = uniqid().'.'.$file_ext;
            $file_tmp_name = $_FILES['file']['tmp_name'];
            $file_folder = '../uploads/'.$rename_file;

            // Update the file path in database
            $update_file = $conn->prepare("UPDATE `content` SET file = ? WHERE id = ?");
            $update_file->execute([$rename_file, $file_id]);

            move_uploaded_file($file_tmp_name, $file_folder);

            // Remove old file only if different from the new file
            if (!empty($old_file) && $old_file != $rename_file) {
                unlink('../uploads/'.$old_file);
            }
        } else {
            $message[] = "Invalid file type! Only MP4, AVI, MOV, and PDF are allowed.";
        }
    }

    $message[] = 'Content updated successfully!';
}

if (isset($_POST['delete_file'])) {
    if (isset($_POST['file_id'])) {
        $delete_id = $_POST['file_id'];
        $delete_id = filter_var($delete_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Fetch and delete the associated file
        $delete_file_query = $conn->prepare("SELECT file FROM `content` WHERE id = ? LIMIT 1");
        $delete_file_query->execute([$delete_id]);
        $fetch_file = $delete_file_query->fetch(PDO::FETCH_ASSOC);

        if ($fetch_file && !empty($fetch_file['file'])) {
            unlink('../uploads/'.$fetch_file['file']);
        }

        // Delete related records
        $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE content_id = ?");
        $delete_likes->execute([$delete_id]);

        $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE content_id = ?");
        $delete_comments->execute([$delete_id]);

        // Delete the content
        $delete_content = $conn->prepare("DELETE FROM `content` WHERE id = ?");
        $delete_content->execute([$delete_id]);

        header('location:contents.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update File</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="video-form">

   <h1 class="heading">Update Content</h1>

   <?php
      $select_videos = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND tutor_id = ?");
$select_videos->execute([$get_id, $tutor_id]);
if ($select_videos->rowCount() > 0) {
    while ($fetch_videos = $select_videos->fetch(PDO::FETCH_ASSOC)) {
        $file_id = $fetch_videos['id'];
        ?>
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="file_id" value="<?= $fetch_videos['id']; ?>">
      <input type="hidden" name="old_file" value="<?= $fetch_videos['file']; ?>">
      
      <p>Update Status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="<?= $fetch_videos['status']; ?>" selected><?= $fetch_videos['status']; ?></option>
         <option value="active">Active</option>
         <option value="deactive">Deactive</option>
      </select>
      
      <p>Update Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Enter title" class="box" value="<?= $fetch_videos['title']; ?>">
      
      <p>Update Description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="Write description" maxlength="1000" cols="30" rows="10"><?= $fetch_videos['description']; ?></textarea>
      
      <p>Update Playlist</p>
      <select name="playlist" class="box">
         <option value="<?= $fetch_videos['playlist_id']; ?>" selected>-- Select Playlist --</option>
         <?php
              $select_playlists = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ?");
        $select_playlists->execute([$tutor_id]);
        if ($select_playlists->rowCount() > 0) {
            while ($fetch_playlist = $select_playlists->fetch(PDO::FETCH_ASSOC)) {
                ?>
         <option value="<?= $fetch_playlist['id']; ?>"><?= $fetch_playlist['title']; ?></option>
         <?php
            }
        } else {
            echo '<option value="" disabled>No playlist created yet!</option>';
        }
        ?>
      </select>

      <p>Update File</p>
      <input type="file" name="file" accept="video/*,.pdf" class="box">

      <input type="submit" value="Update File" name="update" class="btn">
   </form>
   <?php
    }
} else {
    echo '<p class="empty">File not found!</p>';
}
?>

</section>

<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>
