<?php
include("db.php");
include("header.php");

// ============================================
// CHECK: HANYA ADMIN / TUAN RUMAH SAHAJA
// ============================================
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get penyewa ID from URL
$id_penyewa = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_penyewa == 0) {
    header("Location: dashboard.php");
    exit();
}

// Get penyewa details
$sql = "SELECT * FROM penyewa WHERE id_penyewa = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$penyewa = mysqli_fetch_assoc($result);

if (!$penyewa) {
    header("Location: dashboard.php");
    exit();
}

// Get rental history for this tenant
$sewa_sql = "SELECT 
                s.id_sewa,
                s.tarikh_masuk,
                s.deposit,
                r.no_rumah,
                r.harga_sewa,
                r.status as rumah_status,
                b.jumlah as bayaran_terkini,
                b.status as status_bayaran,
                b.tarikh_bayar
            FROM sewa s
            LEFT JOIN rumah r ON s.id_rumah = r.id_rumah
            LEFT JOIN bayaran b ON s.id_sewa = b.id_sewa
            WHERE s.id_penyewa = ?
            ORDER BY s.id_sewa DESC";
$sewa_stmt = mysqli_prepare($conn, $sewa_sql);
mysqli_stmt_bind_param($sewa_stmt, "i", $id_penyewa);
mysqli_stmt_execute($sewa_stmt);
$sewa_result = mysqli_stmt_get_result($sewa_stmt);

// Get payment history
$bayaran_sql = "SELECT 
                    b.*,
                    s.tarikh_masuk,
                    r.no_rumah
                FROM bayaran b
                JOIN sewa s ON b.id_sewa = s.id_sewa
                JOIN rumah r ON s.id_rumah = r.id_rumah
                WHERE s.id_penyewa = ?
                ORDER BY b.tarikh_bayar DESC, b.id_bayaran DESC";
$bayaran_stmt = mysqli_prepare($conn, $bayaran_sql);
mysqli_stmt_bind_param($bayaran_stmt, "i", $id_penyewa);
mysqli_stmt_execute($bayaran_stmt);
$bayaran_result = mysqli_stmt_get_result($bayaran_stmt);

// Calculate total paid and total due
$total_bayar = 0;
$total_tunggak = 0;
while ($row = mysqli_fetch_assoc($bayaran_result)) {
    if ($row['status'] == 'Lunas') {
        $total_bayar += $row['jumlah'];
    } else {
        $total_tunggak += $row['jumlah'];
    }
}
// Reset pointer for display
mysqli_data_seek($bayaran_result, 0);
?>

    <div class="page-wrapper">
        <!-- Header -->
        <div class="header-card">
            <h4>
                <i class="fas fa-user me-2"></i>
                Profil Penyewa
            </h4>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($penyewa['nama'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h3><?= htmlspecialchars($penyewa['nama']) ?></h3>
                    <p class="email"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($penyewa['email']) ?></p>
                    <?php
                    // Check if tenant has active rental
                    $active_check = "SELECT id_sewa FROM sewa WHERE id_penyewa = ?";
                    $active_stmt = mysqli_prepare($conn, $active_check);
                    mysqli_stmt_bind_param($active_stmt, "i", $id_penyewa);
                    mysqli_stmt_execute($active_stmt);
                    mysqli_stmt_store_result($active_stmt);
                    $has_active = mysqli_stmt_num_rows($active_stmt) > 0;
                    ?>
                    <span class="badge-status <?= $has_active ? 'aktif' : 'tidak' ?>">
                        <?= $has_active ? '<i class="fas fa-check-circle me-1"></i> Aktif (Menyewa)' : '<i class="fas fa-times-circle me-1"></i> Tiada Sewaan Aktif' ?>
                    </span>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">No Kad Pengenalan</div>
                    <div class="value"><?= htmlspecialchars($penyewa['no_ic'] ?? '-') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">No Telefon</div>
                    <div class="value"><?= htmlspecialchars($penyewa['no_telefon'] ?? '-') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Email</div>
                    <div class="value"><?= htmlspecialchars($penyewa['email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">ID Penyewa</div>
                    <div class="value">#<?= $penyewa['id_penyewa'] ?></div>
                </div>
            </div>

            <!-- Mini Stats -->
            <div class="stats-mini">
                <div class="stat green">
                    <div class="number">RM <?= number_format($total_bayar, 2) ?></div>
                    <div class="label">Jumlah Dibayar</div>
                </div>
                <div class="stat red">
                    <div class="number">RM <?= number_format($total_tunggak, 2) ?></div>
                    <div class="label">Tunggakan</div>
                </div>
                <div class="stat">
                    <div class="number"><?= mysqli_num_rows($sewa_result) ?></div>
                    <div class="label">Bilangan Sewaan</div>
                </div>
            </div>
        </div>

        <!-- Rental History -->
        <div class="table-container">
            <div class="table-toolbar">
                <div class="title">
                    <i class="fas fa-history me-2"></i> Sejarah Sewaan
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Rumah</th>
                            <th>Tarikh Masuk</th>
                            <th>Deposit</th>
                            <th>Harga Sewa</th>
                            <th>Status Rumah</th>
                            <th>Status Bayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer
                        mysqli_data_seek($sewa_result, 0);
                        if (mysqli_num_rows($sewa_result) > 0): 
                        ?>
                            <?php while ($row = mysqli_fetch_assoc($sewa_result)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['no_rumah'] ?? '-') ?></strong></td>
                                <td><?= $row['tarikh_masuk'] ? date('d/m/Y', strtotime($row['tarikh_masuk'])) : '-' ?></td>
                                <td>RM <?= number_format($row['deposit'] ?? 0, 2) ?></td>
                                <td>RM <?= number_format($row['harga_sewa'] ?? 0, 2) ?></td>
                                <td>
                                    <span class="badge-status <?= ($row['rumah_status'] ?? 'Kosong') == 'Disewa' ? 'lunas' : 'kosong' ?>">
                                        <?= $row['rumah_status'] ?? 'Kosong' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status_bayaran'] == 'Lunas'): ?>
                                        <span class="badge-status lunas"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                    <?php elseif ($row['status_bayaran'] == 'Belum Lunas'): ?>
                                        <span class="badge-status belum"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</span>
                                    <?php else: ?>
                                        <span class="badge-status kosong">Tiada Data</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-home"></i>
                                        <h6>Tiada Sejarah Sewaan</h6>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment History -->
        <div class="table-container">
            <div class="table-toolbar">
                <div class="title">
                    <i class="fas fa-receipt me-2"></i> Sejarah Bayaran
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Bayaran</th>
                            <th>No Rumah</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tarikh Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer
                        mysqli_data_seek($bayaran_result, 0);
                        if (mysqli_num_rows($bayaran_result) > 0): 
                        ?>
                            <?php while ($row = mysqli_fetch_assoc($bayaran_result)): ?>
                            <tr>
                                <td>#<?= $row['id_bayaran'] ?></td>
                                <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                                <td>RM <?= number_format($row['jumlah'], 2) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Lunas'): ?>
                                        <span class="badge-status lunas"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                    <?php else: ?>
                                        <span class="badge-status belum"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['tarikh_bayar'] ? date('d/m/Y H:i', strtotime($row['tarikh_bayar'])) : '-' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <h6>Tiada Rekod Bayaran</h6>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include("footer.php"); ?>
