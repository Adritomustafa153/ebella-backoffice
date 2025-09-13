<?php
require_once 'auth_checker.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_user"])) {
    // Get and sanitize input
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];
    
    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: dashboard.php?error=invalid_email");
        exit();
    }
    
    // Check if email already exists (excluding current user)
    $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        header("Location: dashboard.php?error=email_exists");
        exit();
    }
    
    // Update user
    $sql = "UPDATE users SET username = ?, email = ?, user_role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?message=User updated successfully");
    } else {
        header("Location: dashboard.php?error=Error updating user: " . urlencode($conn->error));
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>