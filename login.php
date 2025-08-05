<?php
// Completely suppress all warnings and notices
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

session_start();
include 'db_connect.php';

$error = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Check user credentials
        $sql = "SELECT id, email, password, name FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['name'];
                
                echo "<script>window.location.href = 'index.php';</script>";
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Netflix Clone</title>
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
                        url('https://techovedas.com/wp-content/uploads/2024/04/netflix-octobre-contenus-2022.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 12px;
            padding: 60px 40px 20px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            margin: 20px;
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
        
        .signin-text {
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
        
        .signup-link {
            text-align: center;
            color: #999;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .signup-link a {
            color: #e50914;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .signup-link a:hover {
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
        
        .social-login {
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
            padding: 30px 0 0;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
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
            .login-container {
                margin: 20px;
                padding: 40px 30px 20px 30px;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            .signin-text {
                font-size: 1.8rem;
            }
            
            .social-login {
                flex-direction: column;
            }
            
            .footer {
                padding: 20px 0 0;
            }
            
            .footer p {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .signin-text {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <a href="index.php" class="logo">NETFLIX</a>
            <div class="welcome-text">Welcome back</div>
            <div class="signin-text">Sign In</div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email or phone number" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
            
            <div class="signup-link">
                New to Netflix? <a href="signup.php">Sign up now</a>
            </div>
        </form>
        
        <div class="divider">
            <span>or continue with</span>
        </div>
        
        <div class="social-login">
            <button type="button" class="social-btn" onclick="alert('Google login coming soon!')">
                <i class="fab fa-google"></i>
                Google
            </button>
            <button type="button" class="social-btn" onclick="alert('Facebook login coming soon!')">
                <i class="fab fa-facebook-f"></i>
                Facebook
            </button>
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
    </div>
    
    <script>
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<div class="loading"></div> Signing In...';
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
        
        // Password visibility toggle
        const passwordInput = document.querySelector('input[type="password"]');
        const lockIcon = passwordInput.parentElement.querySelector('.input-icon');
        
        lockIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.className = 'fas fa-eye input-icon';
            } else {
                passwordInput.type = 'password';
                this.className = 'fas fa-lock input-icon';
            }
        });
        
        lockIcon.style.cursor = 'pointer';
    </script>
</body>
</html>

