<?php

include '../config/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
    header('location:login.php');
    exit();
}

$message = [];

// Fetch current user data
$select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ? LIMIT 1");
$select_user->execute([$user_id]);
$fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);

$prev_pass = $fetch_user['password'] ?? '';
$prev_image = $fetch_user['image'] ?? '';

if (isset($_POST['submit'])) {
    // Handle name update
    if (!empty($_POST['name']) && $_POST['name'] !== $fetch_user['name']) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $update_name = $conn->prepare("UPDATE `users` SET name = ? WHERE id = ?");
        $update_name->execute([$name, $user_id]);
        $message[] = 'Username updated successfully!';
    }

    // Handle email update (only if changed)
    if (!empty($_POST['email']) && $_POST['email'] !== $fetch_user['email']) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $select_email = $conn->prepare("SELECT email FROM `users` WHERE email = ? LIMIT 1");
        $select_email->execute([$email]);

        if ($select_email->rowCount() > 0) {
            $message[] = 'Email already taken!';
        } else {
            $update_email = $conn->prepare("UPDATE `users` SET email = ? WHERE id = ?");
            $update_email->execute([$email, $user_id]);
            $message[] = 'Email updated successfully!';
        }
    }

    // Handle profile image update
    if (!empty($_FILES['image']['name'])) {
        $image = filter_var($_FILES['image']['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $rename = uniqid() . '.' . $ext;
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = 'uploads/' . $rename;

        if ($image_size > 2000000) {
            $message[] = 'Image size too large!';
        } else {
            move_uploaded_file($image_tmp_name, $image_folder);
            $update_image = $conn->prepare("UPDATE `users` SET `image` = ? WHERE id = ?");
            $update_image->execute([$rename, $user_id]);

            // Delete old image if exists
            if (!empty($prev_image) && file_exists('uploads/' . $prev_image) && $prev_image !== $rename) {
                unlink('uploads/' . $prev_image);
            }

            $message[] = 'Profile image updated successfully!';
        }
    }

    // Handle password update only if user provides inputs
    if (!empty($_POST['old_pass']) || !empty($_POST['new_pass']) || !empty($_POST['cpass'])) {
        if (!empty($_POST['old_pass']) && !empty($_POST['new_pass']) && !empty($_POST['cpass'])) {
            $old_pass = sha1($_POST['old_pass']);
            $new_pass = sha1($_POST['new_pass']);
            $cpass = sha1($_POST['cpass']);

            if ($old_pass !== $prev_pass) {
                $message[] = 'Old password does not match!';
            } elseif ($new_pass !== $cpass) {
                $message[] = 'Confirm password does not match!';
            } elseif ($new_pass === $prev_pass) {
                $message[] = 'New password cannot be the same as the old password!';
            } else {
                $update_pass = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
                $update_pass->execute([$new_pass, $user_id]);
                $message[] = 'Password updated successfully!';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="form-container" style="min-height: calc(100vh - 19rem);">
   <form action="" method="post" enctype="multipart/form-data">
      <h3>Update Profile</h3>

      <!-- Display messages (Ensure $message is an array) -->
      <?php if (!empty($message) && is_array($message)): ?>
         <div class="message-box">
            <?php foreach ($message as $msg): ?>
               <div class="alert">
                  <p><?= htmlspecialchars($msg); ?></p>
                  <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
               </div>
            <?php endforeach; ?>
         </div>
      <?php endif; ?>

      <div class="flex">
         <div class="col">
            <p>Your Name</p>
            <input type="text" name="name" value="<?= htmlspecialchars($fetch_user['name'] ?? ''); ?>" maxlength="100" class="box">

            <p>Your Email</p>
            <input type="email" name="email" value="<?= htmlspecialchars($fetch_user['email'] ?? ''); ?>" maxlength="100" class="box">

            <p>Update Profile Picture</p>
            <input type="file" name="image" accept="image/*" class="box">
         </div>

         <div class="col">
            <p>Old Password</p>
            <input type="password" name="old_pass" placeholder="Enter your old password" maxlength="50" class="box">

            <p>New Password</p>
            <input type="password" name="new_pass" placeholder="Enter your new password" maxlength="50" class="box">

            <p>Confirm Password</p>
            <input type="password" name="cpass" placeholder="Confirm your new password" maxlength="50" class="box">
         </div>
      </div>

      <input type="submit" name="submit" value="Update Profile" class="btn">
   </form>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
