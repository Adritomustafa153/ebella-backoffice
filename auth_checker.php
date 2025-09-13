<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if session has expired (15 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Verify user still exists in database
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT user_id, username, email, user_role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit();
}

$current_user = $result->fetch_assoc();
?>