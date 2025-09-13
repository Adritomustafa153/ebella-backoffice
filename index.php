<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ebella Management - User System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #20c997;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .main-container {
            flex: 1;
            padding: 2rem 0;
        }
        
        .auth-card {
            border-radius: 12px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .auth-body {
            padding: 2rem;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
        }
        
        .auth-switch {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 1rem 0;
            margin-top: auto;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
        }
        
        .input-group {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-store me-2"></i>Ebella Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" id="loginNavLink">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="registerNavLink">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <!-- Login Form -->
                    <div class="card auth-card mb-4" id="loginCard">
                        <div class="auth-header">
                            <h3><i class="fas fa-sign-in-alt me-2"></i>User Login</h3>
                            <p class="mb-0">Access your Ebella Management account</p>
                        </div>
                        <div class="auth-body">
                            <form id="loginForm" action="login.php" method="POST">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                                        <span class="password-toggle" id="loginPasswordToggle">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                                </div>
                            </form>
                            <div class="auth-switch">
                                <p class="mb-0">Don't have an account? <a href="#" id="switchToRegister">Register here</a></p>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <div class="card auth-card d-none" id="registerCard">
                        <div class="auth-header">
                            <h3><i class="fas fa-user-plus me-2"></i>Create Account</h3>
                            <p class="mb-0">Join Ebella Management system</p>
                        </div>
                        <div class="auth-body">
                            <form id="registerForm" action="register.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="registerUsername" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="registerUsername" name="username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="registerEmail" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="registerPassword" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="registerPassword" name="password" required>
                                            <span class="password-toggle" id="registerPasswordToggle">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                                </div>
                            </form>
                            <div class="auth-switch">
                                <p class="mb-0">Already have an account? <a href="#" id="switchToLogin">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 Ebella Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle between login and register forms
            const loginCard = document.getElementById('loginCard');
            const registerCard = document.getElementById('registerCard');
            const switchToRegister = document.getElementById('switchToRegister');
            const switchToLogin = document.getElementById('switchToLogin');
            const loginNavLink = document.getElementById('loginNavLink');
            const registerNavLink = document.getElementById('registerNavLink');
            
            switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                loginCard.classList.add('d-none');
                registerCard.classList.remove('d-none');
                loginNavLink.classList.remove('active');
                registerNavLink.classList.add('active');
            });
            
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                registerCard.classList.add('d-none');
                loginCard.classList.remove('d-none');
                registerNavLink.classList.remove('active');
                loginNavLink.classList.add('active');
            });
            
            // Password visibility toggles
            const setupPasswordToggle = (toggleId, inputId) => {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
                
                toggle.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        input.type = 'password';
                        toggle.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            };
            
            setupPasswordToggle('loginPasswordToggle', 'loginPassword');
            setupPasswordToggle('registerPasswordToggle', 'registerPassword');
            
            // Form validation
            const registerForm = document.getElementById('registerForm');
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('registerPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>