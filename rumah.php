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

// ---------- TAMBAH / EDIT ----------
if (isset($_POST['simpan'])) {
    $id_rumah   = $_POST['id_rumah'];
    $no_rumah   = mysqli_real_escape_string($conn, $_POST['no_rumah']);
    $harga_sewa = $_POST['harga_sewa'];
    $status     = $_POST['status'];

    if (empty($id_rumah)) {
        // Tambah rumah baru
        $sql = "INSERT INTO rumah (no_rumah, harga_sewa, status) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sds", $no_rumah, $harga_sewa, $status);
    } else {
        // Kemaskini rumah sedia ada
        $sql = "UPDATE rumah SET no_rumah=?, harga_sewa=?, status=? WHERE id_rumah=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sdsi", $no_rumah, $harga_sewa, $status, $id_rumah);
    }
    mysqli_stmt_execute($stmt);
    header("Location: rumah.php");
    exit();
}

// ---------- PADAM ----------
if (isset($_GET['padam'])) {
    $id_rumah = $_GET['padam'];
    $sql = "DELETE FROM rumah WHERE id_rumah=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_rumah);
    mysqli_stmt_execute($stmt);
    header("Location: rumah.php");
    exit();
}

// ---------- SENARAI ----------
$result = mysqli_query($conn, "SELECT * FROM rumah ORDER BY id_rumah DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengurusan Rumah</title>
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
        
        .btn-padam-action {
            background: #dc3545;
            border: none;
            border-radius: 6px;
            padding: 5px 15px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-padam-action:hover {
            background: #c82333;
            color: white;
        }
        
        .badge-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-status.disewa {
            background: #fce4ec;
            color: #c62828;
        }
        
        .badge-status.kosong {
            background: #e8f5e9;
            color: #2e7d32;
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
                <i class="fas fa-home me-2"></i>
                Pengurusan Rumah
            </h4>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalRumah" onclick="tambahRumah()">
                <i class="fas fa-plus"></i> Tambah Rumah
            </button>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <!-- Toolbar -->
            <div class="table-toolbar">
                <div class="nav-links">
                    <a href="#"><i class="fas fa-home"></i> rumah</a>
                    <span class="separator">></span>
                    <a href="#" style="color: #1a1a2e; cursor: default;">rumah</a>
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
                            <th>ID Rumah</th>
                            <th>No Rumah</th>
                            <th>Harga Sewa</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?= $row['id_rumah'] ?></strong>
                                </td>
                                <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                                <td>RM <?= number_format($row['harga_sewa'], 2) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Disewa'): ?>
                                        <span class="badge-status disewa">
                                            <i class="fas fa-circle me-1"></i> Disewa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status kosong">
                                            <i class="fas fa-circle me-1"></i> Kosong
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn-edit-action"
                                            onclick='editRumah(<?= json_encode($row) ?>)'
                                            data-bs-toggle="modal" data-bs-target="#modalRumah">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <a href="rumah.php?padam=<?= $row['id_rumah'] ?>"
                                           class="btn-padam-action"
                                           onclick="return confirm('Anda pasti mahu padam rumah ini?')">
                                            <i class="fas fa-times me-1"></i> Padam
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-home"></i>
                                        <h5>Tiada Data Rumah</h5>
                                        <p>Klik butang "Tambah Rumah" untuk menambah rumah baru.</p>
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
                    <i class="fas fa-rows me-1"></i>
                    Rows: <span><?= mysqli_num_rows($result) ?></span>
                </div>
                <div>
                    <i class="fas fa-columns me-1"></i>
                    Columns: <span>5</span>
                </div>
                <div>
                    <i class="fas fa-clock me-1"></i>
                    Last updated: <span><?= date('d/m/Y H:i:s') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Rumah -->
    <div class="modal fade" id="modalRumah" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-home me-2"></i> Tambah Rumah
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_rumah" id="id_rumah">
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag me-1" style="color: #e4b700;"></i> 
                                No Rumah <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="no_rumah" id="no_rumah" placeholder="Contoh: 44A" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill me-1" style="color: #e4b700;"></i> 
                                Harga Sewa (RM) <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" class="form-control" name="harga_sewa" id="harga_sewa" placeholder="0.00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-info-circle me-1" style="color: #e4b700;"></i> 
                                Status
                            </label>
                            <select class="form-select" name="status" id="status">
                                <option value="Kosong">Kosong</option>
                                <option value="Disewa">Disewa</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-batal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Batal
                        </button>
                        <button type="submit" name="simpan" class="btn-simpan" id="btnSimpan">
                            <i class="fas fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function tambahRumah() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-home me-2"></i> Tambah Rumah';
            document.getElementById('id_rumah').value = '';
            document.getElementById('no_rumah').value = '';
            document.getElementById('harga_sewa').value = '';
            document.getElementById('status').value = 'Kosong';
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save me-2"></i> Simpan';
        }

        function editRumah(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Rumah';
            document.getElementById('id_rumah').value = data.id_rumah;
            document.getElementById('no_rumah').value = data.no_rumah;
            document.getElementById('harga_sewa').value = data.harga_sewa;
            document.getElementById('status').value = data.status;
            document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-save me-2"></i> Kemaskini';
        }
    </script>
</body>
</html>