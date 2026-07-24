<?php
include("db.php");

// Semak sama ada user dah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// ============================================
// AMBIL MAKLUMAT USER
// ============================================
if ($role == 'admin') {
    $nama = $_SESSION['username'] ?? 'Admin';
    $email = 'admin@umahkakjum.com';
    $no_telefon = '-';
    $no_ic = '-';
    $role_display = 'Tuan Rumah / Admin';
} else {
    $sql = "SELECT * FROM penyewa WHERE id_penyewa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penyewa = mysqli_fetch_assoc($result);
    
    if ($penyewa) {
        $nama = $penyewa['nama'];
        $email = $penyewa['email'];
        $no_telefon = $penyewa['no_telefon'] ?? '';
        $no_ic = $penyewa['no_ic'] ?? '';
        $role_display = 'Penyewa';
    } else {
        header("Location: logout.php");
        exit();
    }
}

// ============================================
// PROSES UPDATE PROFILE
// ============================================
if (isset($_POST['update'])) {
    if ($role == 'penyewa') {
        $nama_baru = mysqli_real_escape_string($conn, $_POST['nama']);
        $email_baru = mysqli_real_escape_string($conn, $_POST['email']);
        $no_telefon_baru = mysqli_real_escape_string($conn, $_POST['no_telefon']);
        $no_ic_baru = mysqli_real_escape_string($conn, $_POST['no_ic']);
        $password_baru = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($nama_baru) || empty($email_baru)) {
            $error = "Nama dan email wajib diisi!";
        } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
            $error = "Kata laluan tidak sepadan!";
        } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
            $error = "Kata laluan mesti sekurang-kurangnya 6 aksara!";
        } else {
            // Update tanpa password
            if (empty($password_baru)) {
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $user_id);
            } else {
                // Update dengan password
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=?, password=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $hashed_password, $user_id);
            }

            if (mysqli_stmt_execute($stmt)) {
                // Update session
                $_SESSION['username'] = $nama_baru;
                $_SESSION['email'] = $email_baru;
                
                $success = "Profile berjaya dikemaskini!";
                
                // Refresh data
                $nama = $nama_baru;
                $email = $email_baru;
                $no_telefon = $no_telefon_baru;
                $no_ic = $no_ic_baru;
            } else {
                $error = "Ralat: " . mysqli_error($conn);
            }
        }
    } else {
        // Admin - hanya update nama
        $nama_baru = mysqli_real_escape_string($conn, $_POST['nama']);
        if (!empty($nama_baru)) {
            $_SESSION['username'] = $nama_baru;
            $nama = $nama_baru;
            $success = "Profile berjaya dikemaskini!";
        } else {
            $error = "Nama tidak boleh kosong!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kemaskini Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-wrapper {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .edit-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .edit-header {
            background: linear-gradient(135deg, #1a1a2e, #2d2d44);
            padding: 25px 30px;
            color: white;
        }
        .edit-header h5 {
            font-weight: 700;
            margin: 0;
        }
        .edit-header h5 i {
            color: #e4b700;
            margin-right: 10px;
        }
        .edit-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #555;
        }
        .form-label .required {
            color: #dc3545;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #e8ecf1;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #e4b700;
            box-shadow: 0 0 0 0.2rem rgba(228, 183, 0, 0.15);
        }
        .form-control:disabled {
            background: #f8f9fc;
            cursor: not-allowed;
        }
        .form-text {
            font-size: 12px;
            color: #888;
        }
        .btn-simpan {
            background: #e4b700;
            border: none;
            border-radius: 8px;
            padding: 12px 40px;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-simpan:hover {
            background: #c49b00;
            color: white;
            transform: translateY(-2px);
        }
        .btn-batal {
            background: #e8ecf1;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-batal:hover {
            background: #d5d9e0;
            color: #444;
        }
        .alert-custom {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-custom.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .alert-custom.danger {
            background: #fce4ec;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        .alert-custom i {
            margin-right: 10px;
        }
        .info-badge {
            background: #fff8e1;
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #e4b700;
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }
        .info-badge i {
            color: #e4b700;
            margin-right: 8px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        @media (max-width: 600px) {
            .edit-body {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-buttons .btn-simpan,
            .action-buttons .btn-batal {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php
if ($role == 'admin') {
    include("header.php");
} else {
    include("header_penyewa.php");
}
?>

<div class="page-wrapper">
    <div class="edit-card">
        <!-- Header -->
        <div class="edit-header">
            <h5><i class="fas fa-edit"></i> Kemaskini Profile</h5>
        </div>

        <!-- Body -->
        <div class="edit-body">
            <!-- Mesej -->
            <?php if ($error): ?>
                <div class="alert-custom danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-custom success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <div class="info-badge">
                <i class="fas fa-info-circle"></i>
                Kemaskini maklumat profile anda di bawah.
            </div>

            <form method="POST">
                <?php if ($role == 'admin'): ?>
                    <!-- Admin Form -->
                    <div class="mb-3">
                        <label class="form-label">Nama <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
                        <small class="form-text">Email admin tidak boleh diubah</small>
                    </div>
                <?php else: ?>
                    <!-- Penyewa Form -->
                    <div class="mb-3">
                        <label class="form-label">Nama Penuh <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No Telefon</label>
                            <input type="text" class="form-control" name="no_telefon" value="<?= htmlspecialchars($no_telefon) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No Kad Pengenalan</label>
                            <input type="text" class="form-control" name="no_ic" value="<?= htmlspecialchars($no_ic) ?>">
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="fw-bold"><i class="fas fa-lock me-2" style="color: #e4b700;"></i> Tukar Kata Laluan</h6>
                    <div class="mb-3">
                        <label class="form-label">Kata Laluan Baru</label>
                        <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak mahu tukar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Taip semula kata laluan">
                        <small class="form-text">Minimum 6 aksara</small>
                    </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <button type="submit" name="update" class="btn-simpan">
                        <i class="fas fa-save me-2"></i> Simpan
                    </button>
                    <a href="profile.php" class="btn-batal">
                        <i class="fas fa-arrow-left"></i> Kembali ke Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>