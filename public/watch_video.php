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
    header('location:home.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Watch Content</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="watch-content">
   <?php
      $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND status = ?");
$select_content->execute([$get_id, 'active']);
if ($select_content->rowCount() > 0) {
    while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
        $file_name = $fetch_content['file'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        ?>
   <div class="content-details">
      <?php if (in_array($file_ext, ['mp4', 'avi', 'mov'])): ?>
         <video src="uploads/<?= $file_name; ?>" class="video" controls autoplay></video>
      <?php elseif ($file_ext === 'pdf'): ?>
         <div id="pdfContainer">
            <iframe id="pdfViewer" src="uploads/<?= $file_name; ?>" width="100%" height="600px"></iframe>
         </div>
         <button id="fullscreenBtn" class="btn view-fullscreen">View in Fullscreen</button>
         <a href="quiz.php?pdf_id=<?= $get_id; ?>" class='btn' style="color: white;">Generate Quiz</a>
      <?php endif; ?>

      <h3 class="title"><?= htmlspecialchars($fetch_content['title']); ?></h3>
      <div class="info">
         <p><i class="fas fa-calendar"></i><span><?= htmlspecialchars($fetch_content['date']); ?></span></p>
      </div>
      <div class="description"><p><?= htmlspecialchars($fetch_content['description']); ?></p></div>
   </div>
   <?php
    }
} else {
    echo '<p class="empty">No content found!</p>';
}
?>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const pdfViewer = document.getElementById("pdfViewer");

    fullscreenBtn.addEventListener("click", function() {
        if (pdfViewer.requestFullscreen) {
            pdfViewer.requestFullscreen();
        } else if (pdfViewer.mozRequestFullScreen) { // Firefox
            pdfViewer.mozRequestFullScreen();
        } else if (pdfViewer.webkitRequestFullscreen) { // Chrome, Safari, Opera
            pdfViewer.webkitRequestFullscreen();
        } else if (pdfViewer.msRequestFullscreen) { // IE/Edge
            pdfViewer.msRequestFullscreen();
        } else {
            alert("Fullscreen mode is not supported by your browser.");
        }
    });
});
</script>

</body>
</html>
