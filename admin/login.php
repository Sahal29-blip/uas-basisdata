<?php
session_start();
include '../includes/db_config.php'; // Menghubungkan ke database

// Handle login
$login_error = '';
if (isset($_POST['login'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];
    if (empty($username) || empty($password)) {
        $login_error = 'Username dan password harus diisi!';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header("Location: index.php");
                exit();
            } else {
                $login_error = 'Username atau password salah!';
            }
        } else {
            $login_error = 'Username atau password salah!';
        }
        $stmt->close();
    }
}

// Handle register
$register_error = '';
$register_success = '';
if (isset($_POST['register'])) {
    $username = $_POST['register_username'];
    $password = $_POST['register_password'];
    $confirm = $_POST['register_confirm'];
    if (empty($username) || empty($password) || empty($confirm)) {
        $register_error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm) {
        $register_error = 'Password tidak sama!';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?"); // Mengecek apakah username sudah ada
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $register_error = 'Username sudah terdaftar!';
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)"); // Menambahkan username dan password pada database
            $stmt->bind_param("ss", $username, $hash);
            if ($stmt->execute()) {
                $register_success = 'Registrasi berhasil! Silakan login.';
            } else {
                $register_error = 'Registrasi gagal!';
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Monitor Login - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../static/css/admin.css">
    <link rel="stylesheet" href="../static/css/login.css">
    <link rel="icon" type="image/png" href="../static/images/EspressifLogo.png">
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="auth-glass-container" id="authContainer">
        <div class="auth-left">
            <div class="auth-title">SENSOR MONITORING</div>
            <div class="auth-forms-wrapper">
                <!-- Login Form -->
                <div id="loginPanel" class="auth-form-panel login-panel active">
                    <?php if (!empty($login_error)): ?>
                        <div class="alert alert-danger py-2" role="alert" style="border-radius: 16px;">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($login_error) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" class="auth-form">
                        <label for="login_username" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control input-with-icon" id="login_username" name="login_username" required autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        
                        <label for="login_password" class="form-label">Password</label>
                        <div class="password-wrapper mb-2">
                            <input type="password" class="form-control password-input" id="login_password" name="login_password" required autocomplete="current-password">
                            <i class="fas fa-eye eye-icon" data-target="login_password"></i>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-auth">Masuk</button>
                        <span class="auth-link" id="toRegister">Belum memiliki akun?</span>
                    </form>
                </div>
                <!-- Register Form -->
                <div id="registerPanel" class="auth-form-panel register-panel">
                    <?php if (!empty($register_error)): ?>
                        <div class="alert alert-danger py-2" role="alert" style="border-radius: 16px;">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($register_error) ?>
                        </div>
                    <?php elseif (!empty($register_success)): ?>
                        <div class="alert alert-success py-2" role="alert" style="border-radius: 16px;">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($register_success) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" class="auth-form">
                        <label for="register_username" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control input-with-icon" id="register_username" name="register_username" required autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        
                        <label for="register_password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control password-input" id="register_password" name="register_password" required autocomplete="new-password">
                            <i class="fas fa-eye eye-icon" data-target="register_password"></i>
                        </div>
                        
                        <label for="register_confirm" class="form-label">Confirm Password</label>
                        <div class="password-wrapper mb-2">
                            <input type="password" class="form-control password-input" id="register_confirm" name="register_confirm" required autocomplete="new-password">
                            <i class="fas fa-eye eye-icon" data-target="register_confirm"></i>
                        </div>
                        
                        <button type="submit" name="register" class="btn btn-auth">Daftar</button>
                        <span class="auth-link" id="toLogin">Sudah memiliki akun?</span>
                    </form>
                </div>
            </div>
        </div>
        <div class="auth-right">
            <img id="loginImage" src="../static/images/login.png" class="auth-image login-image active">
            <img id="registerImage" src="../static/images/registrasi.png" class="auth-image register-image">
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Element references
    const loginPanel = document.getElementById('loginPanel');
    const registerPanel = document.getElementById('registerPanel');
    const toRegister = document.getElementById('toRegister');
    const toLogin = document.getElementById('toLogin');
    const authContainer = document.getElementById('authContainer');
    const loginImage = document.getElementById('loginImage');
    const registerImage = document.getElementById('registerImage');

    // Switch to Register Panel
    toRegister.addEventListener('click', function() {
        // Animate out login image
        loginImage.classList.remove('active');
        
        setTimeout(() => {
            // Animate in register image
            registerImage.classList.add('active');
            
            // Animate panels
            loginPanel.classList.remove('active');
            loginPanel.classList.add('to-register');
            
            setTimeout(() => {
                loginPanel.classList.remove('to-register');
                registerPanel.classList.add('active');
                authContainer.classList.add('swap');
            }, 600);
        }, 300);
    });

    // Switch to Login Panel
    toLogin.addEventListener('click', function() {
        // Animate out register image
        registerImage.classList.remove('active');
        
        setTimeout(() => {
            // Animate in login image
            loginImage.classList.add('active');
            
            // Animate panels
            registerPanel.classList.remove('active');
            registerPanel.classList.add('to-login');
            
            setTimeout(() => {
                registerPanel.classList.remove('to-login');
                loginPanel.classList.add('active');
                authContainer.classList.remove('swap');
            }, 600);
        }, 300);
    });

    // Password toggle functionality
    document.querySelectorAll('.eye-icon').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    // Preload images to avoid flickering
    window.addEventListener('load', function() {
        const img1 = new Image();
        img1.src = loginImage.src;
        
        const img2 = new Image();
        img2.src = registerImage.src;
    });
    </script>
</body>
</html>