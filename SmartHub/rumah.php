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
                    <a href="#" style="color: var(--ink); cursor: default;">rumah</a>
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
                            <th>Harga Sewa</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
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
                                <i class="fas fa-tag me-1" style="color: var(--primary);"></i> 
                                No Rumah <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="no_rumah" id="no_rumah" placeholder="Contoh: 44A" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill me-1" style="color: var(--primary);"></i> 
                                Harga Sewa (RM) <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" class="form-control" name="harga_sewa" id="harga_sewa" placeholder="0.00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-info-circle me-1" style="color: var(--primary);"></i> 
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
<?php include("footer.php"); ?>
