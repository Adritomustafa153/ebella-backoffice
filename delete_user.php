<?php
require_once 'auth_checker.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_user"])) {
    // Get and sanitize input
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Prevent users from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        header("Location: dashboard.php?error=Cannot delete your own account");
        exit();
    }
    
    // Delete user
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?message=User deleted successfully");
    } else {
        header("Location: dashboard.php?error=Error deleting user: " . urlencode($conn->error));
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>