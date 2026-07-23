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

// ---------- TAMBAH / EDIT ----------
if (isset($_POST['simpan'])) {
    $id_penyewa = $_POST['id_penyewa'];
    $nama       = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_ic      = mysqli_real_escape_string($conn, $_POST['no_ic']);
    $no_telefon = mysqli_real_escape_string($conn, $_POST['no_telefon']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = $_POST['password']; 
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($nama) || empty($email)) {
        $error = "Nama dan email wajib diisi!";
        $_SESSION['error'] = $error;
        header("Location: penyewa.php");
        exit();
    }

    if (!empty($password) && $password !== $confirm_password) {
        $error = "Kata laluan tidak sepadan!";
        $_SESSION['error'] = $error;
        header("Location: penyewa.php");
        exit();
    }

    if (!empty($password) && strlen($password) < 6) {
        $error = "Kata laluan mesti sekurang-kurangnya 6 aksara!";
        $_SESSION['error'] = $error;
        header("Location: penyewa.php");
        exit();
    }

    // Check if email already exists (untuk TAMBAH sahaja)
    if (empty($id_penyewa)) {
        $check_sql = "SELECT id_penyewa FROM penyewa WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email sudah didaftarkan!";
            $_SESSION['error'] = $error;
            header("Location: penyewa.php");
            exit();
        }

        // Check IC duplicate
        if (!empty($no_ic)) {
            $check_sql = "SELECT id_penyewa FROM penyewa WHERE no_ic = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $no_ic);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "No Kad Pengenalan sudah didaftarkan!";
                $_SESSION['error'] = $error;
                header("Location: penyewa.php");
                exit();
            }
        }
    }

    // PROSES TAMBAH
    if (empty($id_penyewa)) {
        // TAMBAH - hash password
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
        $sql = "INSERT INTO penyewa (nama, no_ic, no_telefon, email, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $nama, $no_ic, $no_telefon, $email, $hashed_password);
    } else {
        // EDIT - semak jika password diisi
        if (!empty($password)) {
            // Jika password diisi, hash dan update
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE penyewa SET nama=?, no_ic=?, no_telefon=?, email=?, password=? WHERE id_penyewa=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssi", $nama, $no_ic, $no_telefon, $email, $hashed_password, $id_penyewa);
        } else {
            // Jika password kosong, jangan update password
            $sql = "UPDATE penyewa SET nama=?, no_ic=?, no_telefon=?, email=? WHERE id_penyewa=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $no_ic, $no_telefon, $email, $id_penyewa);
        }
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Data berjaya disimpan!";
    } else {
        $_SESSION['error'] = "Ralat: " . mysqli_error($conn);
    }
    header("Location: penyewa.php");
    exit();
}

// ---------- PADAM ----------
if (isset($_GET['padam'])) {
    $id_penyewa = $_GET['padam'];
    
    // Check if tenant has active rental
    $check_sql = "SELECT id_sewa FROM sewa WHERE id_penyewa = ? AND tarikh_keluar IS NULL";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $id_penyewa);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $_SESSION['error'] = "Penyewa ini masih mempunyai sewaan aktif. Tidak boleh dipadam!";
        header("Location: penyewa.php");
        exit();
    }
    
    $sql = "DELETE FROM penyewa WHERE id_penyewa=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = "Penyewa berjaya dipadam!";
    header("Location: penyewa.php");
    exit();
}

// ---------- SENARAI ----------
$result = mysqli_query($conn, "SELECT * FROM penyewa ORDER BY id_penyewa DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengurusan Penyewa</title>
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
        
        .badge-status.hash {
            background: #6c757d;
            color: white;
        }
        
        .badge-status.tiada {
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
                <i class="fas fa-users me-2"></i>
                Pengurusan Penyewa
            </h4>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalPenyewa" onclick="tambahPenyewa()">
                <i class="fas fa-plus"></i> Tambah Penyewa
            </button>
        </div>

        <!-- Mesej Error/Success -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
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
                    <a href="#" style="color: #1a1a2e; cursor: default;">penyewa</a>
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
                            <th>Nama</th>
                            <th>No KP</th>
                            <th>No Telefon</th>
                            <th>Email</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['no_ic'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['no_telefon'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn-edit-action"
                                            onclick='editPenyewa(<?= json_encode($row) ?>)'
                                            data-bs-toggle="modal" data-bs-target="#modalPenyewa">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <a href="penyewa.php?padam=<?= $row['id_penyewa'] ?>"
                                           class="btn-padam-action"
                                           onclick="return confirm('Anda pasti mahu padam penyewa ini?')">
                                            <i class="fas fa-times me-1"></i> Padam
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h5>Tiada Penyewa Berdaftar</h5>
                                        <p>Klik butang "Tambah Penyewa" untuk mendaftar penyewa baru.</p>
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
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Penyewa -->
    <div class="modal fade" id="modalPenyewa" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-user-plus me-2"></i> Tambah Penyewa
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_penyewa" id="id_penyewa">
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user me-1" style="color: #e4b700;"></i> 
                                Nama Penuh <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama" id="nama" placeholder="Masukkan nama penuh" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-id-card me-1" style="color: #e4b700;"></i> 
                                No Kad Pengenalan
                            </label>
                            <input type="text" class="form-control" name="no_ic" id="no_ic" placeholder="Contoh: 010101-01-0101">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-phone me-1" style="color: #e4b700;"></i> 
                                No Telefon
                            </label>
                            <input type="text" class="form-control" name="no_telefon" id="no_telefon" placeholder="Contoh: 012-3456789">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-envelope me-1" style="color: #e4b700;"></i> 
                                Email <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Masukkan email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock me-1" style="color: #e4b700;"></i> 
                                Kata Laluan
                            </label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Kosongkan jika tidak mahu tukar (untuk edit)">
                            <div class="form-text">Minimum 6 aksara. Akan di-hash secara automatik.</div>
                        </div>
                        
                        <div class="mb-3" id="confirm_password_div">
                            <label class="form-label">
                                <i class="fas fa-check-circle me-1" style="color: #e4b700;"></i> 
                                Sahkan Kata Laluan
                            </label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Taip semula kata laluan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-batal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Batal
                        </button>
                        <button type="submit" name="simpan" class="btn-simpan">
                            <i class="fas fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function tambahPenyewa() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i> Tambah Penyewa';
            document.getElementById('id_penyewa').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('no_ic').value = '';
            document.getElementById('no_telefon').value = '';
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Masukkan kata laluan';
            document.getElementById('password').required = true;
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm_password').required = true;
            document.getElementById('confirm_password_div').style.display = 'block';
        }

        function editPenyewa(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i> Edit Penyewa';
            document.getElementById('id_penyewa').value = data.id_penyewa;
            document.getElementById('nama').value = data.nama;
            document.getElementById('no_ic').value = data.no_ic || '';
            document.getElementById('no_telefon').value = data.no_telefon || '';
            document.getElementById('email').value = data.email;
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Kosongkan jika tidak mahu tukar';
            document.getElementById('password').required = false;
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm_password').required = false;
            document.getElementById('confirm_password_div').style.display = 'none';
        }
    </script>
</body>
</html>
