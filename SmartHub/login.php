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
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Rent Hub — Log Masuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0b1220;
        }
        .auth-split { display: flex; width: 100%; min-height: 100vh; }
        .auth-visual {
            flex: 1.1;
            position: relative;
            background: linear-gradient(160deg, #0f172a 0%, #16213a 55%, #1d2b4f 100%);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            color: #fff;
        }
        .auth-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('RumahSewa.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.16;
        }
        .auth-visual::after {
            content: '';
            position: absolute;
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(37,99,235,0.35), transparent 70%);
            top: -120px; right: -120px;
            border-radius: 50%;
        }
        .auth-visual .vbrand { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; }
        .auth-visual .vbrand .brand-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            display: flex; align-items: center; justify-content: center; font-size: 19px;
            box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        }
        .auth-visual .vbrand .vtext { font-weight: 700; font-size: 19px; letter-spacing: -0.3px; }
        .auth-visual .vbrand .vtext span { color: #60a5fa; }
        .auth-visual .headline {
            position: relative; z-index: 1;
            font-size: 34px; font-weight: 800; line-height: 1.25; letter-spacing: -0.6px; max-width: 440px;
        }
        .auth-visual .headline span { color: #60a5fa; }
        .auth-visual .sub { position: relative; z-index: 1; color: #a9b4cc; font-size: 14.5px; max-width: 420px; margin-top: 12px; }
        .auth-visual .vfoot { position: relative; z-index: 1; color: #6b7794; font-size: 12.5px; }
        .stat-chips { position: relative; z-index: 1; display: flex; gap: 14px; margin-top: 30px; flex-wrap: wrap; }
        .stat-chips .chip {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            min-width: 120px;
        }
        .stat-chips .chip .n { font-size: 19px; font-weight: 800; color: #fff; }
        .stat-chips .chip .l { font-size: 11.5px; color: #93a0bd; margin-top: 2px; }

        .auth-form-side {
            flex: 1;
            background: #f5f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }
        .login-container { width: 100%; max-width: 400px; }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 20px 60px rgba(16,24,40,0.08);
            border: 1px solid #eef0f6;
        }
        .brand-header { text-align: left; margin-bottom: 30px; }
        .brand-header h2 { font-size: 22px; font-weight: 800; color: #101828; letter-spacing: -0.4px; margin: 0 0 6px; }
        .brand-header p { color: #6b7280; font-size: 14px; margin: 0; }

        .input-group-custom { position: relative; margin-bottom: 18px; }
        .input-group-custom .form-control {
            width: 100%; padding: 13px 16px 13px 44px; border: 1.5px solid #e7eaf3; border-radius: 12px;
            font-size: 14.5px; background: #fbfcfe; transition: all 0.2s ease; height: 50px;
        }
        .input-group-custom .form-control:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .input-group-custom .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9aa3b2; font-size: 15px; z-index: 2; }

        .options-row { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 22px; font-size: 13.5px; }
        .forgot-password { color: #6b7280; font-weight: 500; }
        .forgot-password:hover { text-decoration: underline; }

        .btn-login {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;
            border-radius: 12px; color: #fff; font-size: 15.5px; font-weight: 700; cursor: pointer;
            transition: all 0.2s ease; box-shadow: 0 8px 20px rgba(37,99,235,0.28);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(37,99,235,0.35); }

        .alert-custom { border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 13.5px; border: none; }

        @media (max-width: 900px) { .auth-visual { display: none; } }
        @media (max-width: 480px) { .login-card { padding: 32px 24px; } }
    </style>
</head>
<body>
    <div class="auth-split">
        <div class="auth-visual">
            <div class="vbrand">
                <div class="brand-icon"><i class="fas fa-home"></i></div>
                <div class="vtext">Smart<span>Rent</span> Hub</div>
            </div>
            <div>
                <div class="headline">Urus hartanah sewa anda dengan <span>lebih bijak.</span></div>
                <p class="sub">Satu platform untuk tuan rumah dan penyewa — pengurusan sewaan, bayaran dan komunikasi dalam satu tempat.</p>
                <div class="stat-chips">
                    <div class="chip"><div class="n">100%</div><div class="l">Digital &amp; selamat</div></div>
                    <div class="chip"><div class="n">24/7</div><div class="l">Akses bila-bila masa</div></div>
                    <div class="chip"><div class="n">RM</div><div class="l">Bayaran dalam talian</div></div>
                </div>
            </div>
            <div class="vfoot">&copy; <?= date('Y') ?> Smart Rent Hub. Semua hak terpelihara.</div>
        </div>

        <div class="auth-form-side">
            <div class="login-container">
                <div class="login-card">
                    <div class="brand-header">
                        <h2>Selamat Kembali</h2>
                        <p>Log masuk untuk teruskan ke akaun anda</p>
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
                            <input class="form-control" type="password" name="password" placeholder="Kata laluan" required>
                        </div>

                        <div class="options-row">
                            <a href="#" class="forgot-password">Lupa kata laluan?</a>
                        </div>

                        <button class="btn-login" name="login">
                            <i class="fas fa-sign-in-alt me-2"></i> Log Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
