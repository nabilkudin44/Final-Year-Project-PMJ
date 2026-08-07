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
// ---------- PADAM ----------
if (isset($_GET['padam'])) {
    $id_penyewa = $_GET['padam'];
    
    // Check if tenant has any rental record (active or not)
    // Kita semak jika ada rekod sewaan sahaja, tanpa tarikh_keluar
    $check_sql = "SELECT id_sewa FROM sewa WHERE id_penyewa = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $id_penyewa);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $_SESSION['error'] = "Penyewa ini masih mempunyai rekod sewaan. Tidak boleh dipadam! Sila tamatkan sewaan dahulu.";
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
                    <a href="#" style="color: var(--ink); cursor: default;">penyewa</a>
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
                                <i class="fas fa-user me-1" style="color: var(--primary);"></i> 
                                Nama Penuh <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama" id="nama" placeholder="Masukkan nama penuh" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-id-card me-1" style="color: var(--primary);"></i> 
                                No Kad Pengenalan
                            </label>
                            <input type="text" class="form-control" name="no_ic" id="no_ic" placeholder="Contoh: 010101-01-0101">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-phone me-1" style="color: var(--primary);"></i> 
                                No Telefon
                            </label>
                            <input type="text" class="form-control" name="no_telefon" id="no_telefon" placeholder="Contoh: 012-3456789">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-envelope me-1" style="color: var(--primary);"></i> 
                                Email <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Masukkan email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock me-1" style="color: var(--primary);"></i> 
                                Kata Laluan
                            </label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Kosongkan jika tidak mahu tukar (untuk edit)">
                            <div class="form-text">Minimum 6 aksara. Akan di-hash secara automatik.</div>
                        </div>
                        
                        <div class="mb-3" id="confirm_password_div">
                            <label class="form-label">
                                <i class="fas fa-check-circle me-1" style="color: var(--primary);"></i> 
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
<?php include("footer.php"); ?>
