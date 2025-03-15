<?php

include '../../config/connect.php';

// Always initialize $message as an array so it's iterable
$message = [];

// Check for a valid tutor cookie, otherwise redirect
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    $tutor_id = '';
    header('location:login.php');
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {

    // Example limit of 8MB (adjust as needed)
    $maxSize = 8 * 1024 * 1024; // 8 MB

    // Collect form data
    $id          = unique_id();
    $status      = filter_var($_POST['status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $title       = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $playlist    = filter_var($_POST['playlist'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $level       = filter_var($_POST['level'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // File info
    $file          = $_FILES['file']['name'];
    $file          = filter_var($file, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $file_ext      = pathinfo($file, PATHINFO_EXTENSION);
    $rename_file   = unique_id() . '.' . $file_ext;
    $file_tmp_name = $_FILES['file']['tmp_name'];
    $file_folder   = '../uploads/' . $rename_file;
    $file_size     = $_FILES['file']['size'];

    // Check if file size exceeds limit
    if ($file_size > $maxSize) {
        $message[] = 'Your file is too large. The maximum file size allowed is 8MB.';
    } else {
        // Insert data into database and move file to uploads folder
        $add_playlist = $conn->prepare("INSERT INTO `content` (id, tutor_id, playlist_id, title, description, file, status, level)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $add_playlist->execute([
            $id,
            $tutor_id,
            $playlist,
            $title,
            $description,
            $rename_file,
            $status,
            $level
        ]);

        move_uploaded_file($file_tmp_name, $file_folder);
        $message[] = 'New course uploaded successfully!';
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

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<?php
// Display any messages
if (!empty($message)) {
    foreach ($message as $msg) {
        echo '<div class="message">' . $msg . '</div>';
    }
}
?>

<section class="video-form">
    <div class="back-btn-container">
       <button onclick='window.history.back()' class="btn"><i class="fa-solid fa-arrow-left"></i>Go back</a>
   </div>

   <h1 class="heading">upload content</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <p>Content status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="" selected disabled>-- Select status</option>
         <option value="active">active</option>
         <option value="deactive">deactive</option>
      </select>
      <p>Content title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Enter Content title" class="box">
      <p>Content description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="Write Description" maxlength="1000" cols="30" rows="10"></textarea>
      <p>Course Level <span>*</span></p>
      <select name="level" class="box" required>
         <option value="" disabled selected>--Select Level</option>
         <option value="100">100 level</option>
         <option value="200">200 level</option>
         <option value="300">300 level</option>
         <option value="400">400 level</option>
      </select>
      <p>File playlist <span>*</span></p>
      <select name="playlist" class="box" required>
         <option value="" disabled selected>--Select playlist</option>
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
             echo '<option value="" disabled>no playlist created yet!</option>';
         }
         ?>
      </select>
      <p>select file <span>*</span></p>
      <input type="file" name="file" accept="video/*,.pdf" required class="box">
      <input type="submit" value="Upload Content" name="submit" class="btn">
   </form>
</section>

<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>
</body>
</html>
