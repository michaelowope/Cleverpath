<?php
include '../config/connect.php';

// Always initialize an array to hold any messages
$message = [];

// Check if user is logged in
if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

// Grab the content ID from URL
if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];
} else {
    $get_id = '';
    header('location:home.php');
    exit;
}

// 1) Handle Like/Unlike logic
if (isset($_POST['like_btn'])) {
    // Must be logged in to Like
    if ($user_id === '') {
        $message[] = 'Please log in to like content!';
    } else {
        // Check if user already liked this content
        $check_like = $conn->prepare("SELECT * FROM `likes` WHERE user_id = ? AND content_id = ?");
        $check_like->execute([$user_id, $get_id]);

        if ($check_like->rowCount() > 0) {
            // If there is already a row, remove it => "Unlike"
            $remove_like = $conn->prepare("DELETE FROM `likes` WHERE user_id = ? AND content_id = ?");
            $remove_like->execute([$user_id, $get_id]);
            $message[] = 'You unliked this content.';
        } else {
            // Otherwise, add a new like => "Like"
            // We also need the tutor_id from this content, so let's fetch it
            $get_tutor = $conn->prepare("SELECT tutor_id FROM `content` WHERE id = ? LIMIT 1");
            $get_tutor->execute([$get_id]);
            $fetch_tutor = $get_tutor->fetch(PDO::FETCH_ASSOC);
            $tutor_id = $fetch_tutor ? $fetch_tutor['tutor_id'] : '';

            $add_like = $conn->prepare("INSERT INTO `likes` (user_id, tutor_id, content_id) VALUES (?,?,?)");
            $add_like->execute([$user_id, $tutor_id, $get_id]);
            $message[] = 'You liked this content!';
        }
    }
}

// 2) Handle adding a comment
if (isset($_POST['add_comment'])) {
    if ($user_id === '') {
        $message[] = 'Please log in to comment!';
    } else {
        $comment_text = filter_var($_POST['comment_box'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Get tutor_id from the content for the comment row
        $get_tutor = $conn->prepare("SELECT tutor_id FROM `content` WHERE id = ? LIMIT 1");
        $get_tutor->execute([$get_id]);
        $fetch_tutor = $get_tutor->fetch(PDO::FETCH_ASSOC);
        $tutor_id = $fetch_tutor ? $fetch_tutor['tutor_id'] : '';

        // Create a unique ID for this comment
        $comment_id = unique_id();

        // Insert the comment into the table
        $add_comment = $conn->prepare("INSERT INTO `comments`
            (id, content_id, user_id, tutor_id, comment)
            VALUES (?,?,?,?,?)");
        $add_comment->execute([$comment_id, $get_id, $user_id, $tutor_id, $comment_text]);

        $message[] = 'Comment added successfully!';
    }
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

<?php 
   // user_header.php presumably displays the $message array,
   // so we do NOT display $message here again
   include 'components/user_header.php'; 
?>

<section class="watch-content">
   <?php
   // Fetch the content if it's active
   $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND status = ?");
   $select_content->execute([$get_id, 'active']);
   if ($select_content->rowCount() > 0) {
       while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
           $file_name = $fetch_content['file'];
           $file_ext  = pathinfo($file_name, PATHINFO_EXTENSION);

           // Count total likes for this content
           $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE content_id = ?");
           $count_likes->execute([$get_id]);
           $total_likes = $count_likes->rowCount();

           // Check if current user has liked this content
           $confirm_like = $conn->prepare("SELECT * FROM `likes` WHERE user_id = ? AND content_id = ?");
           $confirm_like->execute([$user_id, $get_id]);
           $is_liked = ($confirm_like->rowCount() > 0);

           // Count total comments for this content
           $count_comments = $conn->prepare("SELECT * FROM `comments` WHERE content_id = ?");
           $count_comments->execute([$get_id]);
           $total_comments = $count_comments->rowCount();
           ?>
           <div class="content-details">
               <!-- Display the video or PDF -->
               <?php if (in_array($file_ext, ['mp4', 'avi', 'mov'])): ?>
                   <video src="uploads/<?= htmlspecialchars($file_name); ?>" class="video" controls autoplay></video>
               <?php elseif ($file_ext === 'pdf'): ?>
                   <div id="pdfContainer">
                       <iframe id="pdfViewer" src="uploads/<?= htmlspecialchars($file_name); ?>" width="100%" height="600px"></iframe>
                   </div>
                   <button id="fullscreenBtn" class="btn view-fullscreen">View in Fullscreen</button>
                   <a href="quiz.php?pdf_id=<?= $get_id; ?>" class="btn" style="color: white;">Generate Quiz</a>
               <?php endif; ?>

                <h3 class="title"><?= htmlspecialchars($fetch_content['title']); ?></h3>
                <div class="description">
                    <p><?= htmlspecialchars($fetch_content['description']); ?></p>
                </div>
                <div class="info">
                    <p><i class="fas fa-calendar"></i><span><?= htmlspecialchars($fetch_content['date']); ?></span></p>
                </div>

                <!-- Display total likes, total comments, and Like button -->
                <div class="flex">
                    <div><i class="fas fa-heart"></i> <?= $total_likes; ?> likes</div>
                    <div><i class="fas fa-comment"></i> <?= $total_comments; ?> comments</div>
                </div>

                <!-- Like / Unlike form -->
                <form action="" method="post" style="display:inline;">
                    <button type="submit" name="like_btn" class="inline-btn">
                        <?= $is_liked ? 'Unlike' : 'Like'; ?>
                    </button>
                </form>
           </div>
           <?php
       }
   } else {
       echo '<p class="empty">No content found!</p>';
   }
   ?>
</section>

<!-- Comments section -->
<section class="comments">
   <?php if ($select_content->rowCount() > 0): ?>
      <h1 class="heading">Comments</h1>

      <!-- Form to add a comment -->
      <form action="" method="post" class="add-comment-form">
         <textarea name="comment_box" rows="3" placeholder="Write a comment..." required class="box"></textarea>
         <button type="submit" name="add_comment" class="btn">Post Comment</button>
      </form>

      <!-- Show existing comments -->
      <div class="show-comments">
         <?php
         // Re-run the total comment selection to display them
         $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE content_id = ? ORDER BY date DESC");
         $select_comments->execute([$get_id]);

         if ($select_comments->rowCount() > 0) {
             while ($fetch_comment = $select_comments->fetch(PDO::FETCH_ASSOC)) {
                 // Find the user who wrote this comment
                 $select_commentor = $conn->prepare("SELECT * FROM `users` WHERE id = ? LIMIT 1");
                 $select_commentor->execute([$fetch_comment['user_id']]);
                 $fetch_commentor = $select_commentor->fetch(PDO::FETCH_ASSOC);

                 // If user data is found, show it
                 $commentor_name  = $fetch_commentor ? $fetch_commentor['name'] : 'Unknown User';
                 $commentor_image = $fetch_commentor ? $fetch_commentor['image'] : 'default.png';
                 ?>
                 <div class="box">
                     <div class="user">
                         <img src="uploads/<?= htmlspecialchars($commentor_image); ?>" alt="">
                         <div>
                             <h3><?= htmlspecialchars($commentor_name); ?></h3>
                             <span><?= htmlspecialchars($fetch_comment['date']); ?></span>
                         </div>
                     </div>
                     <p class="text"><?= htmlspecialchars($fetch_comment['comment']); ?></p>
                 </div>
                 <?php
             }
         } else {
             echo '<p class="empty">No comments yet!</p>';
         }
         ?>
      </div>
   <?php endif; ?>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<!-- Fullscreen PDF logic (only shows if PDF) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const pdfViewer = document.getElementById("pdfViewer");

    if (fullscreenBtn && pdfViewer) {
        fullscreenBtn.addEventListener("click", function() {
            if (pdfViewer.requestFullscreen) {
                pdfViewer.requestFullscreen();
            } else if (pdfViewer.mozRequestFullScreen) {
                pdfViewer.mozRequestFullScreen();
            } else if (pdfViewer.webkitRequestFullscreen) {
                pdfViewer.webkitRequestFullscreen();
            } else if (pdfViewer.msRequestFullscreen) {
                pdfViewer.msRequestFullscreen();
            } else {
                alert("Fullscreen mode is not supported by your browser.");
            }
        });
    }
});
</script>
</body>
</html>
