<?php

include '../../config/connect.php';

if(isset($_COOKIE['tutor_id'])){
   $tutor_id = $_COOKIE['tutor_id'];
}else{
   $tutor_id = '';
   header('location:login.php');
}

if(isset($_POST['submit'])){

   $id = unique_id();
   $status = $_POST['status'];
   $status = filter_var($status, FILTER_SANITIZE_STRING);
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);
   $playlist = $_POST['playlist'];
   $playlist = filter_var($playlist, FILTER_SANITIZE_STRING);
   $level = $_POST['level'];
   $level = filter_var($level, FILTER_SANITIZE_STRING);

   $file = $_FILES['file']['name'];
   $file = filter_var($file, FILTER_SANITIZE_STRING);
   $file_ext = pathinfo($file, PATHINFO_EXTENSION);
   $rename_file = unique_id().'.'.$file_ext;
   $file_tmp_name = $_FILES['file']['tmp_name'];
   $file_folder = '../uploaded_files/'.$rename_file;

   $add_playlist = $conn->prepare("INSERT INTO `content`(id, tutor_id, playlist_id, title, description, file, status, level) VALUES(?,?,?,?,?,?,?,?)");
   $add_playlist->execute([$id, $tutor_id, $playlist, $title, $description, $rename_file, $status, $level]);
   move_uploaded_file($file_tmp_name, $file_folder);
   $message[] = 'new course uploaded!';
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
   
<section class="video-form">

   <h1 class="heading">upload content</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <p>File status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="" selected disabled>-- select status</option>
         <option value="active">active</option>
         <option value="deactive">deactive</option>
      </select>
      <p>File title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="enter File title" class="box">
      <p>File description <span>*</span></p>
      <textarea name="description" class="box" required placeholder="write description" maxlength="1000" cols="30" rows="10"></textarea>
      <p>Course Level <span>*</span></p>
      <select name="level" class='box' required>
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
         if($select_playlists->rowCount() > 0){
            while($fetch_playlist = $select_playlists->fetch(PDO::FETCH_ASSOC)){
         ?>
         <option value="<?= $fetch_playlist['id']; ?>"><?= $fetch_playlist['title']; ?></option>
         <?php
            }
         ?>
         <?php
         }else{
            echo '<option value="" disabled>no playlist created yet!</option>';
         }
         ?>
      </select>
      <p>select file <span>*</span></p>
      <input type="file" name="file" accept="video/*,.pdf" required class="box">
      <input type="submit" value="Upload File" name="submit" class="btn">
   </form>

</section>















<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>