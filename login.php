<?php
include "db.php";
//include "header.php";

$error = "";
$email = "";
$password = "";

// Hardcoded Admin Credentials
$admin_username = "nabil";
$admin_password = "44";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // ============================================
    // 1. CEK LOGIN ADMIN (HARDCODED)
    // ============================================
    if ($email == $admin_username && $password == $admin_password) {
        $_SESSION['user_id'] = 999;
        $_SESSION['username'] = $admin_username;
        $_SESSION['role'] = 'admin';
        $_SESSION['login_type'] = 'admin';
        header("Location: dashboard.php");
        exit();
    } 
    
    // ============================================
    // 2. CEK LOGIN PENYEWA (DATABASE)
    // ============================================
    else {
        $sql = "SELECT * FROM penyewa WHERE email = ? OR no_ic = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id_penyewa'];
                $_SESSION['username'] = $row['nama'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['no_ic'] = $row['no_ic'];
                $_SESSION['no_telefon'] = $row['no_telefon'];
                $_SESSION['role'] = 'penyewa';
                $_SESSION['login_type'] = 'penyewa';
                header("Location: dashboard_tenant.php");
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Pengguna tidak dijumpai!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Rent Hub - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-image: url('RumahSewa.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            padding: 45px 40px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .brand-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e4b700, #938e9f);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 32px;
            box-shadow: 0 8px 25px rgba(228, 183, 0, 0.3);
        }
        
        .brand-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -0.5px;
        }
        
        .brand-title span {
            color: #e4b700;
        }
        
        .brand-subtitle {
            font-size: 14px;
            color: #888;
            font-weight: 400;
            margin-top: 2px;
        }
        
        .role-badges {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .role-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }
        
        .role-badge.admin {
            background: #e4b700;
            color: white;
        }
        
        .role-badge.user {
            background: #4a6cf7;
            color: white;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom .form-control {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid #e8ecf1;
            border-radius: 12px;
            font-size: 15px;
            background: #f8f9fc;
            transition: all 0.3s ease;
            height: 52px;
        }
        
        .input-group-custom .form-control:focus {
            border-color: #e4b700;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(228, 183, 0, 0.1);
        }
        
        .input-group-custom .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 16px;
            z-index: 2;
        }
        
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #e4b700;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #969aa9;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e4b700 0%, #c49b00 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(228, 183, 0, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(228, 183, 0, 0.4);
            background: linear-gradient(135deg, #f0c800 0%, #d4a800 100%);
        }
        
        .login-info {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 15px;
            border: 1px dashed #e4b700;
            font-size: 13px;
            color: #666;
        }
        
        .login-info .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        
        .login-info .label {
            font-weight: 500;
            color: #888;
        }
        
        .login-info .value.admin {
            color: #e4b700;
            font-weight: 600;
        }
        
        .login-info .value.user {
            color: #4a6cf7;
            font-weight: 600;
        }
        
        .register-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf1;
            font-size: 14px;
            color: #666;
        }
        
        .register-section a {
            color: #4a6cf7;
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-section a:hover {
            text-decoration: underline;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            border: none;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px 25px;
            }
            .brand-title {
                font-size: 20px;
            }
            .options-row {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .login-container {
                padding: 10px;
            }
            .login-info .info-row {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand-header">
                <div class="brand-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="brand-title">Smart <span>Rent</span> Hub</div>
                <div class="brand-subtitle">Smart Rental Management System</div>
                
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group-custom">
                    <i class="fas fa-user input-icon"></i>
                    <input class="form-control" type="text" name="email" placeholder="Email / No IC / Username" required>
                </div>
                
                <div class="input-group-custom">
                    <i class="fas fa-lock input-icon"></i>
                    <input class="form-control" type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-password">Forget password?</a>
                </div>
                
                <button class="btn-login" name="login">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
            

            </div>
            

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>