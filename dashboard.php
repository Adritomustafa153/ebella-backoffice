<?php
require_once 'auth_checker.php';
$page_title = "Ebella Management - Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #d291bc;
            --secondary-color: #f7ebec;
            --accent-color: #a0d2db;
            --dark-color: #846267;
            --light-color: #fff9fb;
        }
        
        .brand-logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 60px;
        }
        
        .sidebar a {
            color: white;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar a.active {
            background-color: var(--primary-color);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 80px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: calc(100% - 250px);
            margin-left: 250px;
            z-index: 1000;
        }
        
        .user-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
        }
        
        .user-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .user-card-body {
            padding: 20px;
            background-color: white;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-top: 15px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php include "sidebar.php" ?>
    
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Users</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3">Welcome, <?php echo $_SESSION["username"]; ?></span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION["user_role"]; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">User Management</h2>
            
            <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header" style="background-color: var(--primary-color); color: white;">
                            <h5 class="mb-0">Add New User</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="insert_user.php">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-select" name="role">
                                                <option value="customer">Customer</option>
                                                <option value="admin">Admin</option>
                                                <option value="sales">Sales</option>
                                                <option value="manager">Manager</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header" style="background-color: var(--primary-color); color: white;">
                            <h5 class="mb-0">User List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch users from database
                                        $sql = "SELECT user_id, username, email, user_role, created_at, last_login, activity_status FROM users";
                                        $result = $conn->query($sql);
                                        
                                        if ($result->num_rows > 0) {
                                            while($user = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $user['user_role']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $user['activity_status']; ?></span></td>
                                            <td><?php echo $user['created_at']; ?></td>
                                            <td><?php echo $user['last_login'] ? $user['last_login'] : 'Never'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                    data-id="<?php echo $user['user_id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo $user['user_role']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" 
                                                    data-id="<?php echo $user['user_id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                            endwhile;
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="edit_user.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="user_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                                <option value="sales">Sales</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="delete_user.php">
                    <div class="modal-body">
                        <input type="hidden" id="delete_id" name="user_id">
                        <p>Are you sure you want to delete user: <strong id="delete_username"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal data
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var email = button.getAttribute('data-email');
            var role = button.getAttribute('data-role');
            
            var modal = this;
            modal.querySelector('#edit_id').value = id;
            modal.querySelector('#edit_username').value = username;
            modal.querySelector('#edit_email').value = email;
            modal.querySelector('#edit_role').value = role;
        });
        
        // Handle delete modal data
        var deleteUserModal = document.getElementById('deleteUserModal');
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            
            var modal = this;
            modal.querySelector('#delete_id').value = id;
            modal.querySelector('#delete_username').textContent = username;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>