<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ebella Management - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .login-container {
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        .login-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-card-body {
            padding: 2rem;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            width: 120px;
            height: auto;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-login {
            font-size: 0.9rem;
            letter-spacing: 0.05rem;
            padding: 0.75rem 1rem;
            background-color: #6f42c1;
            border: none;
        }
        .btn-login:hover {
            background-color: #5a32a3;
        }
        .alert {
            margin-bottom: 1.5rem;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-card-body">
                <div class="login-logo">
                    <h3 class="fw-bold">Ebella Management</h3>
                    <p class="text-muted">Sign in to your account</p>
                </div>

                <?php
                session_start();
                require_once 'db_config.php'; // Database connection

                $error = '';
                $email = '';

                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $email = trim($_POST['email']);
                    $password = $_POST['password'];
                    
                    // Validate inputs
                    if (empty($email) || empty($password)) {
                        $error = 'Please enter both email and password.';
                    } else {
                        // Check user in database
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Verify password (assuming plain text for demo - in production use password_verify with hashed passwords)
                            if ($password === $user['password_hash']) {
                                // Check if account is active
                                if ($user['activity_status'] === 'active') {
                                    // Reset failed login attempts on successful login
                                    $resetStmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE user_id = ?");
                                    $resetStmt->execute([$user['user_id']]);
                                    
                                    // Set session variables
                                    $_SESSION['user_id'] = $user['user_id'];
                                    $_SESSION['username'] = $user['username'];
                                    $_SESSION['user_role'] = $user['user_role'];
                                    $_SESSION['loggedin'] = true;
                                    
                                    // Redirect based on user role
                                    header("Location: dashboard.php");
                                    exit;
                                } else {
                                    $error = 'Your account is ' . $user['activity_status'] . '. Please contact support.';
                                }
                            } else {
                                // Increment failed login attempts
                                $failedAttempts = $user['failed_login_attempts'] + 1;
                                $updateStmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, last_failed_login = NOW() WHERE user_id = ?");
                                $updateStmt->execute([$failedAttempts, $user['user_id']]);
                                
                                if ($failedAttempts >= 5) {
                                    // Suspend account after 5 failed attempts
                                    $suspendStmt = $pdo->prepare("UPDATE users SET activity_status = 'suspended' WHERE user_id = ?");
                                    $suspendStmt->execute([$user['user_id']]);
                                    $error = 'Too many failed login attempts. Your account has been suspended.';
                                } else {
                                    $error = 'Invalid password. You have ' . (5 - $failedAttempts) . ' attempts remaining.';
                                }
                            }
                        } else {
                            $error = 'No account found with that email address.';
                        }
                    }
                }
                ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        <label for="email">Email address</label>
                    </div>
                    <div class="form-floating position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <button class="w-100 btn btn-lg btn-login text-white" type="submit">Sign in</button>
                </form>

                <div class="text-center mt-3">
                    <a href="#" class="text-decoration-none">Forgot password?</a>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="text-muted">Don't have an account? <a href="#" class="text-decoration-none">Contact administrator</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>