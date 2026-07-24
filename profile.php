<?php
include("db.php");

// Semak sama ada user dah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// ============================================
// AMBIL MAKLUMAT USER
// ============================================
if ($role == 'admin') {
    // Admin - guna hardcoded data dari session
    $nama = $_SESSION['username'] ?? 'Admin';
    $email = 'admin@umahkakjum.com';
    $no_telefon = '-';
    $no_ic = '-';
    $role_display = 'Tuan Rumah / Admin';
    $tarikh_daftar = '-';
} else {
    // Penyewa - ambil dari database
    $sql = "SELECT * FROM penyewa WHERE id_penyewa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penyewa = mysqli_fetch_assoc($result);
    
    if ($penyewa) {
        $nama = $penyewa['nama'];
        $email = $penyewa['email'];
        $no_telefon = $penyewa['no_telefon'] ?? '-';
        $no_ic = $penyewa['no_ic'] ?? '-';
        $role_display = 'Penyewa';
        $tarikh_daftar = '-';
    } else {
        header("Location: logout.php");
        exit();
    }
}

// ============================================
// AMBIL STATISTIK (UNTUK PENYEWA)
// ============================================
$bil_sewaan = 0;
$bil_bayaran = 0;
$total_bayaran = 0;

if ($role == 'penyewa') {
    // Bilangan sewaan aktif
    $sql = "SELECT COUNT(*) AS total FROM sewa WHERE id_penyewa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $bil_sewaan = $row['total'] ?? 0;
    
    // Bilangan bayaran & total
    $sql = "SELECT COUNT(*) AS bil, SUM(jumlah) AS total FROM bayaran b 
            JOIN sewa s ON b.id_sewa = s.id_sewa 
            WHERE s.id_penyewa = ? AND b.status = 'Lunas'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $bil_bayaran = $row['bil'] ?? 0;
    $total_bayaran = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #1a1a2e, #2d2d44);
            padding: 30px 30px 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e4b700, #c49b00);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            color: white;
            flex-shrink: 0;
        }
        .profile-title h4 {
            font-weight: 700;
            margin: 0;
        }
        .profile-title .badge-role {
            background: rgba(255,255,255,0.15);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 5px;
        }
        .profile-body {
            padding: 30px;
        }
        .info-row {
            display: flex;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row .label {
            width: 150px;
            color: #888;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-row .value {
            font-weight: 600;
            color: #1a1a2e;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e8ecf1;
        }
        .stat-item {
            text-align: center;
            background: #f8f9fc;
            padding: 15px;
            border-radius: 10px;
        }
        .stat-item .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .stat-item .label {
            font-size: 13px;
            color: #888;
        }
        .stat-item .number.gold {
            color: #e4b700;
        }
        .stat-item .number.green {
            color: #2e7d32;
        }
        .stat-item .number.blue {
            color: #1565c0;
        }
        .btn-edit {
            background: #e4b700;
            border: none;
            border-radius: 8px;
            padding: 12px 35px;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-edit:hover {
            background: #c49b00;
            color: white;
            transform: translateY(-2px);
        }
        .btn-back {
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
            gap: 10px;
        }
        .btn-back:hover {
            background: #d5d9e0;
            color: #444;
        }
        .action-buttons {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        @media (max-width: 600px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            .info-row .label {
                width: 100%;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 400px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
    <div class="profile-card">
        <!-- Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-title">
                <h4><?= htmlspecialchars($nama) ?></h4>
                <span class="badge-role">
                    <i class="fas fa-<?= $role == 'admin' ? 'user-shield' : 'user' ?> me-1"></i>
                    <?= $role_display ?>
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="profile-body">
            <!-- Maklumat Profile -->
            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2" style="color: #e4b700;"></i> Maklumat Profile</h6>
            
            <div class="info-row">
                <span class="label"><i class="fas fa-user me-2"></i> Nama</span>
                <span class="value"><?= htmlspecialchars($nama) ?></span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-envelope me-2"></i> Email</span>
                <span class="value"><?= htmlspecialchars($email) ?></span>
            </div>
            <?php if ($role == 'penyewa'): ?>
            <div class="info-row">
                <span class="label"><i class="fas fa-phone me-2"></i> No Telefon</span>
                <span class="value"><?= htmlspecialchars($no_telefon) ?></span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-id-card me-2"></i> No Kad Pengenalan</span>
                <span class="value"><?= htmlspecialchars($no_ic) ?></span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-tag me-2"></i> ID Penyewa</span>
                <span class="value">#<?= $user_id ?></span>
            </div>
            <?php endif; ?>

            <!-- Statistik (untuk penyewa sahaja) -->
            <?php if ($role == 'penyewa'): ?>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number gold"><?= $bil_sewaan ?></div>
                    <div class="label">Sewaan Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="number green"><?= $bil_bayaran ?></div>
                    <div class="label">Bayaran Lunas</div>
                </div>
                <div class="stat-item">
                    <div class="number blue">RM <?= number_format($total_bayaran, 2) ?></div>
                    <div class="label">Total Bayaran</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="edit_profile.php" class="btn-edit">
                    <i class="fas fa-edit"></i> Kemaskini Profile
                </a>
                <a href="<?= $role == 'admin' ? 'dashboard.php' : 'dashboard_tenant.php' ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>