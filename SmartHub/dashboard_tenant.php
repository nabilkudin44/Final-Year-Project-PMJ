<?php
include("db.php");
include("header_penyewa.php");

// ============================================
// CHECK: HANYA PENYEWA SAHAJA
// ============================================
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penyewa') {
    header("Location: login.php");
    exit();
}

$id_penyewa = $_SESSION['user_id'];

// ============================================
// AMBIL MAKLUMAT PENYEWA
// ============================================
$sql_penyewa = "SELECT * FROM penyewa WHERE id_penyewa = ?";
$stmt_penyewa = mysqli_prepare($conn, $sql_penyewa);
mysqli_stmt_bind_param($stmt_penyewa, "i", $id_penyewa);
mysqli_stmt_execute($stmt_penyewa);
$result_penyewa = mysqli_stmt_get_result($stmt_penyewa);
$penyewa = mysqli_fetch_assoc($result_penyewa);

// ============================================
// AMBIL MAKLUMAT SEWAAN AKTIF - GUNA STATUS RUMAH
// ============================================
$sql_sewa = "SELECT 
                s.id_sewa,
                s.tarikh_masuk,
                s.deposit,
                r.id_rumah,
                r.no_rumah,
                r.harga_sewa,
                r.status AS status_rumah
            FROM sewa s
            LEFT JOIN rumah r ON s.id_rumah = r.id_rumah
            WHERE s.id_penyewa = ? AND r.status = 'Disewa'
            ORDER BY s.id_sewa DESC
            LIMIT 1";

$stmt_sewa = mysqli_prepare($conn, $sql_sewa);
mysqli_stmt_bind_param($stmt_sewa, "i", $id_penyewa);
mysqli_stmt_execute($stmt_sewa);
$result_sewa = mysqli_stmt_get_result($stmt_sewa);
$sewa = mysqli_fetch_assoc($result_sewa);

// ============================================
// AMBIL STATUS BAYARAN TERKINI - FIXED QUERY
// ============================================
$status_bayaran = 'Tiada Bayaran';
$jumlah_bayaran = 0;
$bil_bayaran = 0;

if ($sewa) {
    // Query untuk total dan bilangan
    $sql_bayaran = "SELECT 
                        SUM(jumlah) AS total_bayaran,
                        COUNT(*) AS bil_bayaran
                    FROM bayaran 
                    WHERE id_sewa = ?";
    
    $stmt_bayaran = mysqli_prepare($conn, $sql_bayaran);
    mysqli_stmt_bind_param($stmt_bayaran, "i", $sewa['id_sewa']);
    mysqli_stmt_execute($stmt_bayaran);
    $result_bayaran = mysqli_stmt_get_result($stmt_bayaran);
    $bayaran = mysqli_fetch_assoc($result_bayaran);
    
    if ($bayaran) {
        $jumlah_bayaran = $bayaran['total_bayaran'] ?? 0;
        $bil_bayaran = $bayaran['bil_bayaran'] ?? 0;
    }
    
    // Query untuk status terkini (separate query)
    $sql_status = "SELECT status FROM bayaran WHERE id_sewa = ? ORDER BY id_bayaran DESC LIMIT 1";
    $stmt_status = mysqli_prepare($conn, $sql_status);
    mysqli_stmt_bind_param($stmt_status, "i", $sewa['id_sewa']);
    mysqli_stmt_execute($stmt_status);
    $result_status = mysqli_stmt_get_result($stmt_status);
    $status_row = mysqli_fetch_assoc($result_status);
    
    if ($status_row) {
        $status_bayaran = $status_row['status'] ?? 'Tiada Bayaran';
    }
}

// ============================================
// AMBIL SEJARAH BAYARAN
// ============================================
$sql_histori = "SELECT 
                    b.id_bayaran,
                    b.tarikh_bayar,
                    b.jumlah,
                    b.status,
                    b.bulan,
                    b.tahun,
                    r.no_rumah
                FROM bayaran b
                INNER JOIN sewa s ON b.id_sewa = s.id_sewa
                INNER JOIN rumah r ON s.id_rumah = r.id_rumah
                WHERE s.id_penyewa = ?
                ORDER BY b.id_bayaran DESC
                LIMIT 5";

$stmt_histori = mysqli_prepare($conn, $sql_histori);
mysqli_stmt_bind_param($stmt_histori, "i", $id_penyewa);
mysqli_stmt_execute($stmt_histori);
$result_histori = mysqli_stmt_get_result($stmt_histori);

?>

    <div class="page-wrapper">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info">
                <h4><?= htmlspecialchars($penyewa['nama'] ?? 'Penyewa') ?></h4>
                <div class="subtitle">
                    <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($penyewa['email'] ?? '-') ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-phone me-1"></i> <?= htmlspecialchars($penyewa['no_telefon'] ?? '-') ?>
                </div>
            </div>
            <div class="profile-badge">
                <span class="badge-custom tenant">
                    <i class="fas fa-user me-1"></i> Penyewa
                </span>
                <?php if ($sewa): ?>
                    <span class="badge-custom active">
                        <i class="fas fa-check-circle me-1"></i> Sewaan Aktif
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon gold">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-content">
                    <div class="number"><?= $sewa ? htmlspecialchars($sewa['no_rumah']) : '-' ?></div>
                    <div class="label">Rumah Disewa</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-money-bill"></i>
                </div>
                <div class="stat-content">
                    <div class="number">RM <?= number_format($sewa['harga_sewa'] ?? 0, 2) ?></div>
                    <div class="label">Sewa Bulanan</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="number">
                        <?php if ($status_bayaran == 'Lunas'): ?>
                            ✅ Lunas
                        <?php elseif ($status_bayaran == 'Pending'): ?>
                            ⏳ Pending
                        <?php elseif ($status_bayaran == 'Belum Lunas'): ?>
                            ⏳ Belum Lunas
                        <?php else: ?>
                            Tiada
                        <?php endif; ?>
                    </div>
                    <div class="label">Status Bayaran</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="number"><?= $sewa ? date('d/m/Y', strtotime($sewa['tarikh_masuk'])) : '-' ?></div>
                    <div class="label">Tarikh Masuk</div>
                </div>
            </div>
        </div>
        
        <!-- Two Column: Detail Sewaan & Status Bayaran -->
        <div class="two-col">
            <!-- Detail Sewaan -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <h6><i class="fas fa-info-circle"></i> Detail Sewaan</h6>
                    <?php if ($sewa): ?>
                        <span class="badge-status <?= strtolower($sewa['status_rumah']) == 'disewa' ? 'disewa' : 'kosong' ?>">
                            <?= $sewa['status_rumah'] ?? 'Tiada' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body-custom">
                    <?php if ($sewa): ?>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-tag me-1"></i> No Rumah</span>
                            <span class="value"><?= htmlspecialchars($sewa['no_rumah']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-money-bill me-1"></i> Harga Sewa</span>
                            <span class="value amount">RM <?= number_format($sewa['harga_sewa'], 2) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-calendar-plus me-1"></i> Tarikh Masuk</span>
                            <span class="value"><?= date('d/m/Y', strtotime($sewa['tarikh_masuk'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-coins me-1"></i> Deposit</span>
                            <span class="value amount">RM <?= number_format($sewa['deposit'] ?? 0, 2) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-home"></i>
                            <h6>Tiada Sewaan Aktif</h6>
                            <p>Anda belum menyewa sebarang rumah.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status Bayaran -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <h6><i class="fas fa-credit-card"></i> Status Bayaran</h6>
                </div>
                <div class="card-body-custom">
                    <?php if ($sewa): ?>
                        <div class="detail-row">
                            <span class="label">Status Terkini</span>
                            <span class="value">
                                <?php if ($status_bayaran == 'Lunas'): ?>
                                    <span class="badge-status lunas"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                <?php elseif ($status_bayaran == 'Pending'): ?>
                                    <span class="badge-status pending"><i class="fas fa-clock me-1"></i> Pending</span>
                                <?php elseif ($status_bayaran == 'Belum Lunas'): ?>
                                    <span class="badge-status belum"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</span>
                                <?php else: ?>
                                    <span class="badge-status tiada"><i class="fas fa-minus-circle me-1"></i> Tiada Bayaran</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Jumlah Bayaran</span>
                            <span class="value amount">RM <?= number_format($jumlah_bayaran ?? 0, 2) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Bilangan Bayaran</span>
                            <span class="value"><?= $bil_bayaran ?? 0 ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status Sewaan</span>
                            <span class="value">
                                <?php if ($sewa['status_rumah'] == 'Disewa'): ?>
                                    <span class="status-indicator">
                                        <span class="status-dot hijau"></span> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="status-indicator">
                                        <span class="status-dot merah"></span> Tamat
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h6>Tiada Maklumat Bayaran</h6>
                            <p>Anda belum mempunyai sebarang rekod bayaran.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- History Bayaran -->
        <div class="card-custom">
            <div class="card-header-custom">
                <h6><i class="fas fa-history"></i> Sejarah Bayaran Terkini</h6>
                <span class="text-muted" style="font-size: 13px;">5 rekod terakhir</span>
            </div>
            <div class="card-body-custom" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-history">
                        <thead>
                            <tr>
                                <th>No Rumah</th>
                                <th>Bulan</th>
                                <th>Tahun</th>
                                <th>Jumlah</th>
                                <th>Tarikh Bayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_histori) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result_histori)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                                    <td><?= $row['bulan'] ?? '-' ?></td>
                                    <td><?= $row['tahun'] ?? '-' ?></td>
                                    <td class="amount">RM <?= number_format($row['jumlah'] ?? 0, 2) ?></td>
                                    <td><?= $row['tarikh_bayar'] ? date('d/m/Y H:i', strtotime($row['tarikh_bayar'])) : '-' ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'Lunas'): ?>
                                            <span class="badge-status lunas"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                        <?php elseif ($row['status'] == 'Pending'): ?>
                                            <span class="badge-status pending"><i class="fas fa-clock me-1"></i> Pending</span>
                                        <?php elseif ($row['status'] == 'Belum Lunas'): ?>
                                            <span class="badge-status belum"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</span>
                                        <?php else: ?>
                                            <span class="badge-status tiada">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state" style="padding: 30px 20px;">
                                            <i class="fas fa-receipt"></i>
                                            <h6>Tiada Sejarah Bayaran</h6>
                                            <p>Anda belum mempunyai sebarang rekod bayaran.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer" style="background: #f8f9fc; padding: 12px 20px; border-top: 1px solid #e8ecf1; font-size: 13px; color: #888;">
                <i class="fas fa-clock me-1"></i>
                Last updated: <span><?= date('d/m/Y H:i:s') ?></span>
            </div>
        </div>
    </div>

<?php include("footer_penyewa.php"); ?>
