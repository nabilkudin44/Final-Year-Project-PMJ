<?php
include("db.php");

// ---------- TAMBAH / EDIT ----------
if (isset($_POST['simpan'])) {
    $id_rumah   = $_POST['id_rumah'];
    $no_rumah   = mysqli_real_escape_string($conn, $_POST['no_rumah']);
    $alamat     = mysqli_real_escape_string($conn, $_POST['alamat']);
    $harga_sewa = $_POST['harga_sewa'];
    $status     = $_POST['status'];

    if (empty($id_rumah)) {
        // Tambah rumah baru
        $sql = "INSERT INTO rumah (no_rumah, alamat, harga_sewa, status) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssds", $no_rumah, $alamat, $harga_sewa, $status);
    } else {
        // Kemaskini rumah sedia ada
        $sql = "UPDATE rumah SET no_rumah=?, alamat=?, harga_sewa=?, status=? WHERE id_rumah=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdsi", $no_rumah, $alamat, $harga_sewa, $status, $id_rumah);
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
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pengurusan Rumah</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRumah" onclick="tambahRumah()">
            + Tambah Rumah
        </button>
    </div>

    <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr>
                <th>No Rumah</th>
                <th>Alamat</th>
                <th>Harga Sewa</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td>RM <?= number_format($row['harga_sewa'], 2) ?></td>
                <td>
                    <?php if ($row['status'] == 'Disewa'): ?>
                        <span class="badge bg-danger">Disewa</span>
                    <?php else: ?>
                        <span class="badge bg-success">Kosong</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm"
                        onclick='editRumah(<?= json_encode($row) ?>)'
                        data-bs-toggle="modal" data-bs-target="#modalRumah">
                        Edit
                    </button>
                    <a href="rumah.php?padam=<?= $row['id_rumah'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Padam rumah ini?')">
                       Padam
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah/Edit Rumah -->
<div class="modal fade" id="modalRumah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Tambah Rumah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_rumah" id="id_rumah">
          <div class="mb-3">
            <label class="form-label">No Rumah</label>
            <input type="text" class="form-control" name="no_rumah" id="no_rumah" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <input type="text" class="form-control" name="alamat" id="alamat">
          </div>
          <div class="mb-3">
            <label class="form-label">Harga Sewa (RM)</label>
            <input type="number" step="0.01" class="form-control" name="harga_sewa" id="harga_sewa" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="status">
              <option value="Kosong">Kosong</option>
              <option value="Disewa">Disewa</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function tambahRumah() {
    document.getElementById('modalTitle').innerText = 'Tambah Rumah';
    document.getElementById('id_rumah').value = '';
    document.getElementById('no_rumah').value = '';
    document.getElementById('alamat').value = '';
    document.getElementById('harga_sewa').value = '';
    document.getElementById('status').value = 'Kosong';
}

function editRumah(data) {
    document.getElementById('modalTitle').innerText = 'Edit Rumah';
    document.getElementById('id_rumah').value = data.id_rumah;
    document.getElementById('no_rumah').value = data.no_rumah;
    document.getElementById('alamat').value = data.alamat;
    document.getElementById('harga_sewa').value = data.harga_sewa;
    document.getElementById('status').value = data.status;
}
</script>
</body>
</html>