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
    // Admin - ambil dari session (yang diisi dari database)
    $nama = $_SESSION['nama'] ?? 'Admin';
    $email = $_SESSION['email'] ?? 'admin@umahkakjum.com';
    $no_telefon = $_SESSION['no_telefon'] ?? '-';
    $no_ic = '-';
    $role_display = 'Tuan Rumah / Admin';
    $tarikh_daftar = '-';
    $username = $_SESSION['username'] ?? 'admin';
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
                <?php if ($role == 'admin'): ?>
                    <span class="badge-role">
                        <i class="fas fa-user-shield me-1"></i>
                        <?= $role_display ?>
                    </span>
                    <small style="display:block;font-size:11px;opacity:0.7;margin-top:3px;">
                        <i class="fas fa-user me-1"></i> @<?= htmlspecialchars($username) ?>
                    </small>
                <?php else: ?>
                    <span class="badge-role">
                        <i class="fas fa-user me-1"></i>
                        <?= $role_display ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Body -->
        <div class="profile-body">
            <!-- Maklumat Profile -->
            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2" style="color: var(--primary);"></i> Maklumat Profile</h6>
            
            <div class="info-row">
                <span class="label"><i class="fas fa-user me-2"></i> Nama</span>
                <span class="value"><?= htmlspecialchars($nama) ?></span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-envelope me-2"></i> Email</span>
                <span class="value"><?= htmlspecialchars($email) ?></span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-phone me-2"></i> No Telefon</span>
                <span class="value"><?= htmlspecialchars($no_telefon) ?></span>
            </div>
            
            <?php if ($role == 'admin'): ?>
                <div class="info-row">
                    <span class="label"><i class="fas fa-user-tag me-2"></i> Username</span>
                    <span class="value"><?= htmlspecialchars($username) ?></span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-tag me-2"></i> ID Admin</span>
                    <span class="value">#<?= $user_id ?></span>
                </div>
            <?php else: ?>
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
            <?php else: ?>
            <!-- Admin Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number gold"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rumah")) ?></div>
                    <div class="label">Jumlah Rumah</div>
                </div>
                <div class="stat-item">
                    <div class="number green"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rumah WHERE status='Disewa'")) ?></div>
                    <div class="label">Rumah Disewa</div>
                </div>
                <div class="stat-item">
                    <div class="number blue"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM penyewa")) ?></div>
                    <div class="label">Jumlah Penyewa</div>
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

<?php
if ($role == 'admin') {
    include("footer.php");
} else {
    include("footer_penyewa.php");
}
?>
