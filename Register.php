<?php
include("db.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $nama       = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_ic      = mysqli_real_escape_string($conn, $_POST['no_ic']);
    $no_telefon = mysqli_real_escape_string($conn, $_POST['no_telefon']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Sila lengkapkan semua maklumat!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak sepadan!";
    } elseif (strlen($password) < 6) {
        $error = "Password mesti sekurang-kurangnya 6 aksara!";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id_penyewa FROM penyewa WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email sudah didaftarkan!";
        } else {
            // Check if IC already exists
            $check_sql = "SELECT id_penyewa FROM penyewa WHERE no_ic = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $no_ic);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "No Kad Pengenalan sudah didaftarkan!";
            } else {
                // Hash password dan simpan
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO penyewa (nama, no_ic, no_telefon, email, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $nama, $no_ic, $no_telefon, $email, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Pendaftaran berjaya! Sila login.";
                    // Redirect after 2 seconds
                    header("refresh:2; url=login_penyewa.php");
                } else {
                    $error = "Pendaftaran gagal. Sila cuba lagi.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Penyewa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* Cara 1: Guna gambar dari folder */
            background-image: url('RumahSewa2.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Layer gelap untuk teks lebih jelas */
            position: relative;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        .register-card h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .register-card .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .register-card .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .register-card .btn-register {
            background: linear-gradient(135deg, #c8d2e7 0%, #c6b116 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        .register-card .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .register-card .login-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .register-card .login-link:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h3 class="text-center">📝 Daftar Penyewa</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Penuh</label>
                <input type="text" class="form-control" name="nama" placeholder="Masukkan nama penuh" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">No Kad Pengenalan</label>
                <input type="text" class="form-control" name="no_ic" placeholder="Contoh: 010101-01-0101">
            </div>
            
            <div class="mb-3">
                <label class="form-label">No Telefon</label>
                <input type="text" class="form-control" name="no_telefon" placeholder="Contoh: 012-3456789">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" placeholder="Masukkan email" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Minimum 6 aksara" required>
                <div class="password-requirements">* Minimum 6 aksara untuk keselamatan</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Sahkan Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Taip semula password" required>
            </div>
            
            <button type="submit" name="register" class="btn-register">Daftar</button>
        </form>
        
        <div class="text-center mt-3">
            <p class="mb-0">Sudah ada akaun? <a href="login.php" class="login-link">Login di sini</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>