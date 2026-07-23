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

$error = "";
$success = "";
$edit_data = null;

// Get list of houses that are empty (Kosong)
$rumah_kosong_sql = "SELECT * FROM rumah WHERE status = 'Kosong' ORDER BY no_rumah";
$rumah_kosong_result = mysqli_query($conn, $rumah_kosong_sql);

// Get list of all tenants
$penyewa_sql = "SELECT * FROM penyewa ORDER BY nama";
$penyewa_result = mysqli_query($conn, $penyewa_sql);

// ============================================
// PROSES TAMBAH SEWAAN
// ============================================
if (isset($_POST['sewa'])) {
    $id_rumah = mysqli_real_escape_string($conn, $_POST['id_rumah']);
    $id_penyewa = mysqli_real_escape_string($conn, $_POST['id_penyewa']);
    $tarikh_masuk = mysqli_real_escape_string($conn, $_POST['tarikh_masuk']);
    $deposit = mysqli_real_escape_string($conn, $_POST['deposit']);

    if (empty($id_rumah) || empty($id_penyewa) || empty($tarikh_masuk)) {
        $error = "Sila lengkapkan semua maklumat penting!";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // Insert into sewa table
            $sql = "INSERT INTO sewa (id_rumah, id_penyewa, tarikh_masuk, deposit) 
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisd", $id_rumah, $id_penyewa, $tarikh_masuk, $deposit);
            mysqli_stmt_execute($stmt);
            $id_sewa = mysqli_insert_id($conn);

            // Update rumah status to 'Disewa'
            $sql = "UPDATE rumah SET status = 'Disewa' WHERE id_rumah = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id_rumah);
            mysqli_stmt_execute($stmt);

            // Create initial payment record
            $harga_sql = "SELECT harga_sewa FROM rumah WHERE id_rumah = ?";
            $harga_stmt = mysqli_prepare($conn, $harga_sql);
            mysqli_stmt_bind_param($harga_stmt, "i", $id_rumah);
            mysqli_stmt_execute($harga_stmt);
            $harga_result = mysqli_stmt_get_result($harga_stmt);
            $harga_row = mysqli_fetch_assoc($harga_result);
            
            if ($harga_row) {
                $jumlah_bayaran = $harga_row['harga_sewa'];
                $sql = "INSERT INTO bayaran (id_sewa, jumlah, status, tarikh_bayar) VALUES (?, ?, 'Belum Lunas', NULL)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "id", $id_sewa, $jumlah_bayaran);
                mysqli_stmt_execute($stmt);
            }

            mysqli_commit($conn);
            $success = "Penyewa berjaya dimasukkan ke rumah!";
            
            // Refresh data
            $rumah_kosong_result = mysqli_query($conn, $rumah_kosong_sql);
            $penyewa_result = mysqli_query($conn, $penyewa_sql);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Ralat: " . $e->getMessage();
        }
    }
}

// ============================================
// PROSES EDIT SEWAAN
// ============================================
if (isset($_POST['update_sewa'])) {
    $id_sewa = mysqli_real_escape_string($conn, $_POST['id_sewa']);
    $id_rumah = mysqli_real_escape_string($conn, $_POST['id_rumah']);
    $id_penyewa = mysqli_real_escape_string($conn, $_POST['id_penyewa']);
    $tarikh_masuk = mysqli_real_escape_string($conn, $_POST['tarikh_masuk']);
    $deposit = mysqli_real_escape_string($conn, $_POST['deposit']);

    if (empty($id_rumah) || empty($id_penyewa) || empty($tarikh_masuk)) {
        $error = "Sila lengkapkan semua maklumat penting!";
    } else {
        $sql = "UPDATE sewa SET id_rumah=?, id_penyewa=?, tarikh_masuk=?, deposit=? WHERE id_sewa=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisdi", $id_rumah, $id_penyewa, $tarikh_masuk, $deposit, $id_sewa);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Sewaan berjaya dikemaskini!";
        } else {
            $error = "Ralat: " . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES TAMAT SEWAAN (DELETE)
// ============================================
if (isset($_GET['tamat'])) {
    $id_sewa = mysqli_real_escape_string($conn, $_GET['tamat']);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Get id_rumah from sewa
        $sql = "SELECT id_rumah FROM sewa WHERE id_sewa = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_sewa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $id_rumah = $row['id_rumah'];
        
        // Delete from sewa
        $sql = "DELETE FROM sewa WHERE id_sewa = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_sewa);
        mysqli_stmt_execute($stmt);
        
        // Update rumah status to 'Kosong'
        $sql = "UPDATE rumah SET status = 'Kosong' WHERE id_rumah = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_rumah);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $success = "Sewaan berjaya ditamatkan!";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Ralat: " . $e->getMessage();
    }
}

// Get data from sewa table with join - TANPA ALAMAT
$sql = "SELECT 
            s.id_sewa,
            s.id_penyewa,
            p.nama as nama_penyewa,
            s.id_rumah,
            r.no_rumah,
            s.tarikh_masuk,
            s.deposit
        FROM sewa s
        LEFT JOIN penyewa p ON s.id_penyewa = p.id_penyewa
        LEFT JOIN rumah r ON s.id_rumah = r.id_rumah
        ORDER BY s.id_sewa DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Sewaan</title>
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
        
        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
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
        
        .text-ellipsis {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
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
        
        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1a1a2e, #2d2d44);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
            padding: 20px 25px;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-header h5 {
            font-weight: 700;
        }
        
        .modal-body {
            padding: 25px 30px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 15px 30px 25px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
        }
        
        .form-label .required {
            color: #dc3545;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #e8ecf1;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #e4b700;
            box-shadow: 0 0 0 0.2rem rgba(228, 183, 0, 0.15);
        }
        
        .form-text {
            font-size: 12px;
            color: #888;
        }
        
        .btn-simpan {
            background: #e4b700;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 35px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-simpan:hover {
            background: #d4a800;
            color: white;
        }
        
        .btn-batal {
            background: #e8ecf1;
            color: #666;
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-batal:hover {
            background: #d5d9e0;
            color: #444;
        }
        
        .btn-edit-action {
            background: #ffc107;
            border: none;
            border-radius: 6px;
            padding: 5px 15px;
            font-weight: 600;
            color: #1a1a2e;
            transition: all 0.2s ease;
        }
        
        .btn-edit-action:hover {
            background: #e0a800;
            color: #1a1a2e;
        }
        
        .btn-tamat-action {
            background: #dc3545;
            border: none;
            border-radius: 6px;
            padding: 5px 15px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-tamat-action:hover {
            background: #c82333;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .alert-danger {
            background: #fce4ec;
            color: #c62828;
        }
        
        @media (max-width: 768px) {
            .header-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
            .modal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header -->
        <div class="header-card">
            <h4>
                <i class="fas fa-table me-2"></i>
                Data Sewaan
            </h4>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
        
        <!-- Alerts -->
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Table -->
        <div class="table-container">
            <!-- Toolbar -->
            <div class="table-toolbar">
                <div class="nav-links">
                    <a href="#"><i class="fas fa-home"></i> rumah</a>
                    <span class="separator">></span>
                    <a href="#" style="color: #1a1a2e; cursor: default;">sewa</a>
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
                            <th>Nama Penyewa</th>
                            <th>No Rumah</th>
                            <th>Tarikh Masuk</th>
                            <th>Deposit</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <span class="text-ellipsis" title="<?= htmlspecialchars($row['nama_penyewa']) ?>">
                                        <?= htmlspecialchars($row['nama_penyewa'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($row['no_rumah'] ?? '-') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($row['tarikh_masuk'])) ?></td>
                                <td>
                                    RM <?= number_format($row['deposit'] ?? 0, 2) ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn-edit-action" 
                                                onclick="editSewa(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <a href="?tamat=<?= $row['id_sewa'] ?>" 
                                           class="btn-tamat-action"
                                           onclick="return confirm('Anda pasti mahu tamatkan sewaan ini?')">
                                            <i class="fas fa-times me-1"></i> Tamat
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h5>Tiada Data Sewaan</h5>
                                        <p>Klik butang "Add" untuk menambah sewaan baru.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL TAMBAH SEWAAN -->
    <!-- ============================================ -->
    <div class="modal fade" id="modalTambah" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-handshake me-2"></i> Sewa Rumah kepada Penyewa
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Pilih Rumah -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-home me-1" style="color: #e4b700;"></i> 
                                Pilih Rumah <span class="required">*</span>
                            </label>
                            <select class="form-select" name="id_rumah" required>
                                <option value="">-- Sila Pilih Rumah Kosong --</option>
                                <?php 
                                mysqli_data_seek($rumah_kosong_result, 0);
                                while ($row = mysqli_fetch_assoc($rumah_kosong_result)): 
                                ?>
                                    <option value="<?= $row['id_rumah'] ?>">
                                        <?= htmlspecialchars($row['no_rumah']) ?> - 
                                        RM <?= number_format($row['harga_sewa'], 2) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (mysqli_num_rows($rumah_kosong_result) == 0): ?>
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tiada rumah kosong. Sila tambah rumah baru.
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Pilih Penyewa -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user me-1" style="color: #e4b700;"></i> 
                                Pilih Penyewa <span class="required">*</span>
                            </label>
                            <select class="form-select" name="id_penyewa" required>
                                <option value="">-- Sila Pilih Penyewa --</option>
                                <?php 
                                mysqli_data_seek($penyewa_result, 0);
                                while ($row = mysqli_fetch_assoc($penyewa_result)): 
                                ?>
                                    <option value="<?= $row['id_penyewa'] ?>">
                                        <?= htmlspecialchars($row['nama']) ?> 
                                        (<?= htmlspecialchars($row['no_ic'] ?? '-') ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (mysqli_num_rows($penyewa_result) == 0): ?>
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tiada penyewa. Sila tambah penyewa baru.
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Tarikh Masuk -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-plus me-1" style="color: #e4b700;"></i> 
                                Tarikh Masuk <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" name="tarikh_masuk" required>
                        </div>

                        <!-- Deposit -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill me-1" style="color: #e4b700;"></i> 
                                Deposit (RM)
                            </label>
                            <input type="number" step="0.01" class="form-control" name="deposit" placeholder="0.00" value="0.00">
                            <div class="form-text">Jumlah deposit yang dibayar oleh penyewa</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-batal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Batal
                        </button>
                        <button type="submit" name="sewa" class="btn-simpan">
                            <i class="fas fa-handshake me-2"></i> Sewa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL EDIT SEWAAN -->
    <!-- ============================================ -->
    <div class="modal fade" id="modalEdit" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i> Edit Sewaan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_sewa" id="edit_id_sewa">
                        
                        <!-- Pilih Rumah -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-home me-1" style="color: #e4b700;"></i> 
                                Pilih Rumah <span class="required">*</span>
                            </label>
                            <select class="form-select" name="id_rumah" id="edit_id_rumah" required>
                                <option value="">-- Sila Pilih Rumah --</option>
                                <?php 
                                $all_rumah_sql = "SELECT * FROM rumah ORDER BY no_rumah";
                                $all_rumah_result = mysqli_query($conn, $all_rumah_sql);
                                while ($row = mysqli_fetch_assoc($all_rumah_result)): 
                                ?>
                                    <option value="<?= $row['id_rumah'] ?>">
                                        <?= htmlspecialchars($row['no_rumah']) ?> - 
                                        RM <?= number_format($row['harga_sewa'], 2) ?>
                                        <?= $row['status'] == 'Disewa' ? '🔴' : '🟢' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Pilih Penyewa -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user me-1" style="color: #e4b700;"></i> 
                                Pilih Penyewa <span class="required">*</span>
                            </label>
                            <select class="form-select" name="id_penyewa" id="edit_id_penyewa" required>
                                <option value="">-- Sila Pilih Penyewa --</option>
                                <?php 
                                $all_penyewa_sql = "SELECT * FROM penyewa ORDER BY nama";
                                $all_penyewa_result = mysqli_query($conn, $all_penyewa_sql);
                                while ($row = mysqli_fetch_assoc($all_penyewa_result)): 
                                ?>
                                    <option value="<?= $row['id_penyewa'] ?>">
                                        <?= htmlspecialchars($row['nama']) ?> 
                                        (<?= htmlspecialchars($row['no_ic'] ?? '-') ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Tarikh Masuk -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-plus me-1" style="color: #e4b700;"></i> 
                                Tarikh Masuk <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" name="tarikh_masuk" id="edit_tarikh_masuk" required>
                        </div>

                        <!-- Deposit -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill me-1" style="color: #e4b700;"></i> 
                                Deposit (RM)
                            </label>
                            <input type="number" step="0.01" class="form-control" name="deposit" id="edit_deposit" placeholder="0.00">
                            <div class="form-text">Jumlah deposit yang dibayar oleh penyewa</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-batal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Batal
                        </button>
                        <button type="submit" name="update_sewa" class="btn-simpan">
                            <i class="fas fa-save me-2"></i> Kemaskini
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSewa(data) {
            document.getElementById('edit_id_sewa').value = data.id_sewa;
            document.getElementById('edit_id_rumah').value = data.id_rumah;
            document.getElementById('edit_id_penyewa').value = data.id_penyewa;
            document.getElementById('edit_tarikh_masuk').value = data.tarikh_masuk;
            document.getElementById('edit_deposit').value = data.deposit || 0;
        }
    </script>
</body>
</html>
