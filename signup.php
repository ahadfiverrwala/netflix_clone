<?php
// Completely suppress all warnings and notices
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

session_start();
include 'db_connect.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// Process signup form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if (!$check_stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            
            $check_stmt->bind_param("s", $email);
            
            if (!$check_stmt->execute()) {
                throw new Exception("Execute failed: " . $check_stmt->error);
            }
            
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if (!$insert_stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                
                $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($insert_stmt->execute()) {
                    $success = "Registration successful! Redirecting to login...";
                    // Redirect to login page after 2 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000);
                    </script>";
                } else {
                    throw new Exception("Insert failed: " . $insert_stmt->error);
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Netflix Clone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1489599832529-2ea8c3d2b5f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .signup-container {
            width: 100%;
            max-width: 500px;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 12px;
            padding: 60px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            color: #e50914;
            font-size: 3rem;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 20px;
        }
        
        .welcome-text {
            font-size: 1.2rem;
            color: #ccc;
            margin-bottom: 10px;
        }
        
        .signup-text {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-control {
            width: 100%;
            height: 55px;
            border: 2px solid #333;
            border-radius: 8px;
            background: rgba(51, 51, 51, 0.8);
            color: #fff;
            padding: 0 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #e50914;
            background: rgba(68, 68, 68, 0.8);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }
        
        .form-control::placeholder {
            color: #999;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #999;
        }
        
        .strength-bar {
            height: 4px;
            background: #333;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #ff6b6b; }
        .strength-medium { background: #ffa726; }
        .strength-strong { background: #51cf66; }
        
        .btn {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #e50914 0%, #f40612 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #f40612 0%, #e50914 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(229, 9, 20, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: rgba(255, 0, 0, 0.1);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success {
            background: rgba(0, 255, 0, 0.1);
            color: #51cf66;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 255, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .login-link {
            text-align: center;
            color: #999;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #e50914;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #f40612;
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #666;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #333;
        }
        
        .divider span {
            background: rgba(0, 0, 0, 0.85);
            padding: 0 20px;
            font-size: 14px;
        }
        
        .social-signup {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-btn {
            flex: 1;
            height: 45px;
            border: 2px solid #333;
            background: transparent;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .social-btn:hover {
            border-color: #e50914;
            background: rgba(229, 9, 20, 0.1);
        }
        
        .footer {
            text-align: center;
            padding: 40px 0 20px;
            color: #666;
            font-size: 14px;
        }
        
        .footer a {
            color: #999;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer a:hover {
            color: #e50914;
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .signup-container {
                margin: 20px;
                padding: 40px 30px;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            .signup-text {
                font-size: 1.8rem;
            }
            
            .social-signup {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .signup-text {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="header">
            <a href="index.php" class="logo">NETFLIX</a>
            <div class="welcome-text">Join Netflix today</div>
            <div class="signup-text">Sign Up</div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="signupForm">
            <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                <i class="fas fa-user input-icon"></i>
            </div>
            
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required id="password">
                <i class="fas fa-lock input-icon" id="passwordIcon"></i>
                <div class="password-strength">
                    <div>Password strength: <span id="strengthText">Weak</span></div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required id="confirmPassword">
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <button type="submit" class="btn" id="signupBtn">
                <i class="fas fa-user-plus"></i>
                Sign Up
            </button>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in now</a>
            </div>
        </form>
        
        <div class="divider">
            <span>or continue with</span>
        </div>
        
        <div class="social-signup">
            <button type="button" class="social-btn" onclick="alert('Google signup coming soon!')">
                <i class="fab fa-google"></i>
                Google
            </button>
            <button type="button" class="social-btn" onclick="alert('Facebook signup coming soon!')">
                <i class="fab fa-facebook-f"></i>
                Facebook
            </button>
        </div>
    </div>
    
    <div class="footer">
        <p>
            <a href="#">Help</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Contact</a>
        </p>
        <p>&copy; 2025 Netflix Clone. All rights reserved.</p>
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthText = document.getElementById('strengthText');
        const strengthFill = document.getElementById('strengthFill');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            if (strength <= 2) {
                strengthText.textContent = 'Weak';
                strengthFill.style.width = '33%';
                strengthFill.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthText.textContent = 'Medium';
                strengthFill.style.width = '66%';
                strengthFill.classList.add('strength-medium');
            } else {
                strengthText.textContent = 'Strong';
                strengthFill.style.width = '100%';
                strengthFill.classList.add('strength-strong');
            }
        });
        
        // Password visibility toggle
        const passwordIcon = document.getElementById('passwordIcon');
        passwordIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.className = 'fas fa-eye input-icon';
            } else {
                passwordInput.type = 'password';
                this.className = 'fas fa-lock input-icon';
            }
        });
        passwordIcon.style.cursor = 'pointer';
        
        // Confirm password validation
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.style.borderColor = '#ff6b6b';
            } else {
                this.style.borderColor = '#51cf66';
            }
        });
        
        // Form submission with loading state
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('signupBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<div class="loading"></div> Creating Account...';
            btn.disabled = true;
            
            // Re-enable after 3 seconds if no redirect
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        });
        
        // Input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#e50914';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#999';
            });
        });
    </script>
</body>
</html>
