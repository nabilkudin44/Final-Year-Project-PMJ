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

//Jumlah Rumah
$rumah = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah"));

//Rumah Disewa
$disewa = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah WHERE status='Disewa'"));

//Rumah Kosong
$kosong = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah WHERE status='Kosong'"));

//Jumlah Penyewa
$penyewa = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM penyewa"));

//Jumlah Kutipan
$kutipan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Lunas'"));

//Jumlah Tunggakan
$tunggakan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Belum Lunas'"));

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Tuan Rumah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-card h4 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        
        .header-card h4 i {
            color: #e4b700;
        }
        
        .header-card .subtitle {
            color: #888;
            font-size: 14px;
            margin: 0;
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .stats-card .info .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .stats-card .info .label {
            font-size: 13px;
            color: #888;
        }
        
        .stats-card .icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stats-card .icon.blue { background: #e3f2fd; color: #1565c0; }
        .stats-card .icon.green { background: #e8f5e9; color: #2e7d32; }
        .stats-card .icon.red { background: #fce4ec; color: #c62828; }
        .stats-card .icon.orange { background: #fff3e0; color: #e65100; }
        .stats-card .icon.purple { background: #f3e5f5; color: #6a1b9a; }
        .stats-card .icon.teal { background: #e0f7fa; color: #00695c; }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-toolbar {
            padding: 12px 20px;
            background: #f8f9fc;
            border-bottom: 1px solid #e8ecf1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .table-toolbar .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 14px;
        }
        
        .table-toolbar .nav-links a {
            color: #e4b700;
            text-decoration: none;
            font-weight: 600;
        }
        
        .table-toolbar .nav-links a:hover {
            text-decoration: underline;
        }
        
        .table-toolbar .nav-links .separator {
            color: #ddd;
        }
        
        .table-toolbar .table-name {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 16px;
        }
        
        .table-toolbar .table-name span {
            color: #e4b700;
        }
        
        .table-toolbar .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-toolbar {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 13px;
            color: #555;
            transition: all 0.2s ease;
        }
        
        .btn-toolbar:hover {
            background: #f0f0f0;
            border-color: #bbb;
        }
        
        .table {
            margin: 0;
            font-size: 14px;
        }
        
        .table thead th {
            background: #f8f9fc;
            color: #1a1a2e;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 15px;
            border-bottom: 2px solid #e8ecf1;
            border-top: none;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .table tbody tr:hover {
            background: #fafbfc;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-info-footer {
            padding: 12px 20px;
            background: #f8f9fc;
            border-top: 1px solid #e8ecf1;
            font-size: 13px;
            color: #888;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .table-info-footer span {
            color: #1a1a2e;
            font-weight: 600;
        }
        
        .badge-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-status.lunas {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-status.belum {
            background: #fce4ec;
            color: #c62828;
        }
        
        .badge-status.tiada {
            background: #f5f5f5;
            color: #888;
        }
        
        .btn-lihat {
            background: #4a6cf7;
            border: none;
            border-radius: 6px;
            padding: 5px 15px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }
        
        .btn-lihat:hover {
            background: #3a5cd7;
            color: white;
        }
        
        .btn-biodata {
            background: #17a2b8;
            border: none;
            border-radius: 6px;
            padding: 5px 15px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }
        
        .btn-biodata:hover {
            background: #138496;
            color: white;
        }
        
        .text-muted-custom {
            color: #999;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-state h5 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
        }
        
        @media (max-width: 768px) {
            .header-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .table-toolbar .nav-links {
                flex-wrap: wrap;
            }
            .table-info-footer {
                flex-direction: column;
                gap: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
            <div>
                <span class="badge bg-warning text-dark">
                    <i class="fas fa-user-shield me-1"></i> Tuan Rumah
                </span>
                <span class="badge bg-light text-dark ms-2">
                    <i class="fas fa-user me-1"></i> <?= $_SESSION['username'] ?>
                </span>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $rumah['total'] ?></div>
                    <div class="label">Jumlah Rumah</div>
                </div>
                <div class="icon blue">
                    <i class="fas fa-home"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $kosong['total'] ?></div>
                    <div class="label">Rumah Kosong</div>
                </div>
                <div class="icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $disewa['total'] ?></div>
                    <div class="label">Rumah Disewa</div>
                </div>
                <div class="icon red">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="info">
                    <div class="number"><?= $penyewa['total'] ?></div>
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
                    <a href="#" style="color: #1a1a2e; cursor: default;">dashboard</a>
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
                            <th>Status Bayaran</th>
                            <th>Tindakan</th>
                            <th>Biodata Penyewa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT
                            rumah.id_rumah,
                            rumah.no_rumah,
                            rumah.harga_sewa,
                            rumah.status,
                            penyewa.nama,
                            penyewa.id_penyewa,
                            sewa.id_sewa,
                            sewa.tarikh_masuk,
                            bayaran.status AS status_bayaran
                            FROM rumah
                            LEFT JOIN sewa ON rumah.id_rumah = sewa.id_rumah
                            LEFT JOIN penyewa ON penyewa.id_penyewa = sewa.id_penyewa
                            LEFT JOIN bayaran ON bayaran.id_sewa = sewa.id_sewa
                            ORDER BY rumah.id_rumah ASC";
                        
                        $result = mysqli_query($conn, $sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td>
                                <strong><?= $row['no_rumah']; ?></strong>
                            </td>
                            <td>
                                <?= empty($row['nama']) ? '<span class="text-muted-custom">Tiada Penyewa</span>' : htmlspecialchars($row['nama']); ?>
                            </td>
                            <td>
                                <?= empty($row['tarikh_masuk']) ? '-' : date('d/m/Y', strtotime($row['tarikh_masuk'])); ?>
                            </td>
                            <td>
                                RM <?= number_format($row['harga_sewa'],2); ?>
                            </td>
                            <td>
                                <?php
                                if($row['status_bayaran'] == "Lunas") {
                                    echo "<span class='badge-status lunas'><i class='fas fa-check-circle me-1'></i> Lunas</span>";
                                } else if($row['status_bayaran'] == "Belum Lunas") {
                                    echo "<span class='badge-status belum'><i class='fas fa-exclamation-circle me-1'></i> Belum Lunas</span>";
                                } else {
                                    echo "<span class='badge-status tiada'>Tiada Data</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <a href="detailRumah.php?id=<?= $row['id_rumah']; ?>" 
                                   class="btn-lihat">
                                   <i class="fas fa-eye"></i> Lihat
                                </a>
                            </td>
                            <td>
                                <?php if(!empty($row['id_penyewa'])): ?>
                                    <a href="detail_penyewa.php?id=<?= $row['id_penyewa']; ?>"
                                       class="btn-biodata">
                                       <i class="fas fa-user"></i> Lihat Biodata
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted-custom">Tiada Penyewa</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-home"></i>
                                    <h5>Tiada Data Rumah</h5>
                                    <p>Tiada rekod rumah dalam sistem.</p>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer -->
            <div class="table-info-footer">
                <div>
                    <i class="fas fa-clock me-1"></i>
                    Last updated: <span><?= date('d/m/Y H:i:s') ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
