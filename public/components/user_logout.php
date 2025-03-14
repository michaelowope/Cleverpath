<?php

   include 'connect.php';

   session_start();

   // Clear all session variables
   $_SESSION = [];

   // Destroy the session
   session_destroy();

   // Clear user_id cookie properly
   if (isset($_COOKIE['user_id'])) {
      setcookie('user_id', '', time() - 3600, '/'); // Expire the cookie
   }

   
   header('Location:../public/index.php');
   exit;
?>