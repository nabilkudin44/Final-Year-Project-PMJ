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

// ============================================
// STATISTIK
// ============================================

// Jumlah Rumah
$rumah = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM rumah"));

// Rumah Disewa
$disewa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM rumah WHERE status='Disewa'"));

// Rumah Kosong
$kosong = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM rumah WHERE status='Kosong'"));

// Jumlah Penyewa
$penyewa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM penyewa"));

// Jumlah Kutipan (bayaran yang sudah lunas)
$kutipan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Lunas'"));

// Jumlah Tunggakan (bayaran yang belum lunas)
$tunggakan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Belum Lunas'"));

// ============================================
// SENARAI RUMAH DENGAN SEWAAN (BETUL)
// ============================================
$sql = "SELECT 
            r.id_rumah,
            r.no_rumah,
            r.harga_sewa,
            r.status,
            p.nama,
            p.id_penyewa,
            s.id_sewa,
            s.tarikh_masuk,
            (SELECT status FROM bayaran WHERE id_sewa = s.id_sewa ORDER BY id_bayaran DESC LIMIT 1) AS status_bayaran
        FROM rumah r
        LEFT JOIN sewa s ON r.id_rumah = s.id_rumah
        LEFT JOIN penyewa p ON p.id_penyewa = s.id_penyewa
        ORDER BY r.id_rumah ASC";

$result = mysqli_query($conn, $sql);
?>

    <div class="page-wrapper">
        <!-- Header -->
        <div class="header-card">
            <div>
                <h4>
                    <i class="fas fa-chart-pie me-2"></i>
                    Dashboard Tuan Rumah
                </h4>
                <p class="subtitle">Ringkasan maklumat keseluruhan sistem</p>
            </div>
            <div class="user-badge">
                <span class="badge bg-warning text-dark">
                    <i class="fas fa-user-shield me-1"></i> Tuan Rumah
                </span>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-user me-1"></i> <?= $_SESSION['username'] ?? 'Admin' ?>
                </span>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $rumah['total'] ?? 0 ?></div>
                    <div class="label">Jumlah Rumah</div>
                </div>
                <div class="icon blue">
                    <i class="fas fa-home"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $kosong['total'] ?? 0 ?></div>
                    <div class="label">Rumah Kosong</div>
                </div>
                <div class="icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $disewa['total'] ?? 0 ?></div>
                    <div class="label">Rumah Disewa</div>
                </div>
                <div class="icon red">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $penyewa['total'] ?? 0 ?></div>
                    <div class="label">Jumlah Penyewa</div>
                </div>
                <div class="icon orange">
                    <i class="fas fa-user-friends"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number">RM <?= number_format($kutipan['total'] ?? 0, 2) ?></div>
                    <div class="label">Jumlah Kutipan</div>
                </div>
                <div class="icon purple">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number">RM <?= number_format($tunggakan['total'] ?? 0, 2) ?></div>
                    <div class="label">Jumlah Tunggakan</div>
                </div>
                <div class="icon teal">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <!-- Toolbar -->
            <div class="table-toolbar">
                <div class="nav-links">
                    <a href="#"><i class="fas fa-home"></i> rumah</a>
                    <span class="separator">></span>
                    <a href="#" style="color: var(--ink); cursor: default;">dashboard</a>
                    <span class="separator">></span>
                    <span class="table-name"><span>Data</span></span>
                </div>
                <div class="actions">
                    <button class="btn-toolbar" onclick="location.reload();">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Table Content -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No Rumah</th>
                            <th>Penyewa</th>
                            <th>Tarikh Masuk</th>
                            <th>Harga Sewa</th>
                            <th>Status Rumah</th>
                            <th>Status Bayaran</th>
                            <th>Tindakan</th>
                            <th>Biodata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['no_rumah']) ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($row['nama'])): ?>
                                        <?= htmlspecialchars($row['nama']) ?>
                                    <?php else: ?>
                                        <span class="text-muted-custom">Tiada Penyewa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['tarikh_masuk'])): ?>
                                        <?= date('d/m/Y', strtotime($row['tarikh_masuk'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    RM <?= number_format($row['harga_sewa'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'Disewa'): ?>
                                        <span class="badge-status disewa">
                                            <i class="fas fa-check-circle me-1"></i> Disewa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status kosong">
                                            <i class="fas fa-circle me-1"></i> Kosong
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['status_bayaran'])): ?>
                                        <?php if ($row['status_bayaran'] == 'Lunas'): ?>
                                            <span class="badge-status lunas">
                                                <i class="fas fa-check-circle me-1"></i> Lunas
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status belum">
                                                <i class="fas fa-exclamation-circle me-1"></i> Belum Lunas
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-status tiada">Tiada Data</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="detailRumah.php?id=<?= $row['id_rumah'] ?>" 
                                       class="btn-lihat">
                                       <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                                <td>
                                    
                                        <?php if (!empty($row['id_penyewa'])): ?>
                                            <a href="detail.penyewa.php?id=<?= $row['id_penyewa'] ?>"
                                            class="btn-biodata">
                                            <i class="fas fa-user"></i> Biodata
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted-custom">-</span>
                                        <?php endif; ?>
                                    
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-home"></i>
                                        <h5>Tiada Data Rumah</h5>
                                        <p>Tiada rekod rumah dalam sistem.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer -->
            <div class="table-info-footer">
                <div>
                    <i class="fas fa-clock me-1"></i>
                    Last updated: <span><?= date('d/m/Y H:i:s') ?></span>
                </div>
                <div>
                    <i class="fas fa-database me-1"></i>
                    Jumlah rekod: <span><?= mysqli_num_rows($result) ?></span>
                </div>
            </div>
        </div>
    </div>

<?php include("footer.php"); ?>
