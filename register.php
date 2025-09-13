<?php
// Database configuration
$servername = "127.0.0.1";
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "ebella_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    // Check if username or email already exists
    $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "Username or email already exists.";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    
    // Hash the password (using a simple hash for demonstration)
    // In production, use password_hash() with a strong algorithm
    $password_hash = md5($password); // This is just for demo - use stronger hashing in production
    
    // Prepare an insert statement
    $sql = "INSERT INTO users (username, email, password_hash, user_role, created_at) VALUES (?, ?, ?, 'customer', NOW())";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("sss", $username, $email, $password_hash);
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            echo "Registration successful. <a href='index.html'>Login here</a>";
        } else {
            echo "Something went wrong. Please try again later.";
        }
        
        // Close statement
        $stmt->close();
    }
    
    // Close connection
    $conn->close();
}
?>