<?php

// 1) Require the DB connection
require_once __DIR__ . '../config/connect.php';

// For demonstration, let’s define some test data:
$testId       = 'testUser123';
$testName     = 'Test User';
$testEmail    = 'test@example.com';
$testPassword = 'secret123'; // Plain text in this example, not recommended in production
$testImage    = 'default.png';
$testLevel    = 100;
$testCourse   = 'Computer Science';

try {
    // 2) Insert (Register) a test user
    echo "Registering user...\n";
    $registerStmt = $conn->prepare("
        INSERT INTO users (id, name, email, password, image, level, course)
        VALUES (:id, :name, :email, :password, :image, :level, :course)
    ");

    $registerStmt->execute([
        ':id'       => $testId,
        ':name'     => $testName,
        ':email'    => $testEmail,
        ':password' => $testPassword,
        ':image'    => $testImage,
        ':level'    => $testLevel,
        ':course'   => $testCourse
    ]);

    echo "User registered successfully.\n";

    // 3) Attempt Login
    echo "Attempting to login...\n";
    $loginStmt = $conn->prepare("
        SELECT * FROM users
        WHERE email = :email
          AND password = :password
        LIMIT 1
    ");
    $loginStmt->execute([
        ':email'    => $testEmail,
        ':password' => $testPassword
    ]);

    $user = $loginStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Login successful! Found user: " . $user['name'] . "\n";
    } else {
        echo "Login failed. No matching user found.\n";
    }

    // 4) Clean up (optional, but recommended)
    echo "Cleaning up test data...\n";
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $deleteStmt->execute([':id' => $testId]);

    echo "Test user deleted.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
