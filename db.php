<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ebella_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create a default admin user if no users exist
$check_sql = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_sql = "INSERT INTO users (username, email, password_hash, user_role) 
                   VALUES ('admin', 'admin@ebella.com', '$default_password', 'admin')";
    
    if (!$conn->query($insert_sql)) {
        error_log("Error creating default user: " . $conn->error);
    }
}
?>