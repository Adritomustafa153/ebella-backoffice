<?php
require_once 'auth_checker.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    // Get and sanitize input
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: dashboard.php?error=invalid_email");
        exit();
    }
    
    // Check if email already exists
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        header("Location: dashboard.php?error=email_exists");
        exit();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password_hash, user_role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $email, $password_hash, $role);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?message=User added successfully");
    } else {
        header("Location: dashboard.php?error=Error adding user: " . urlencode($conn->error));
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>