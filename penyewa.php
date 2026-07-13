<?php
include("db.php");

$error = ""; // TAMBAH titik koma

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
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
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
            $error = "Email already registered!";
            $_SESSION['error'] = $error;
            header("Location: penyewa.php");
            exit();
        }
    }

    // PROSES TAMBAH
    if (empty($id_penyewa)) {
        // TAMBAH - hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
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
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: penyewa.php");
    exit();
}

// ---------- PADAM ----------
if (isset($_GET['padam'])) {
    $id_penyewa = $_GET['padam'];
    $sql = "DELETE FROM penyewa WHERE id_penyewa=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
    mysqli_stmt_execute($stmt);
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
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pengurusan Penyewa</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPenyewa" onclick="tambahPenyewa()">
            + Tambah Penyewa
        </button>
    </div>

    <!-- Mesej Error/Success -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr>
                <th>Nama</th>
                <th>No KP</th>
                <th>No Telefon</th>
                <th>Email</th>
                <th>Password</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['no_ic']) ?></td>
                <td><?= htmlspecialchars($row['no_telefon']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if (!empty($row['password'])): ?>
                        <span class="badge bg-secondary">🔒 Terhash</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Tiada</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm"
                        onclick='editPenyewa(<?= json_encode($row) ?>)'
                        data-bs-toggle="modal" data-bs-target="#modalPenyewa">
                        Edit
                    </button>
                    <a href="penyewa.php?padam=<?= $row['id_penyewa'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Padam penyewa ini?')">
                       Padam
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah/Edit Penyewa -->
<div class="modal fade" id="modalPenyewa" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Tambah Penyewa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_penyewa" id="id_penyewa">
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" name="nama" id="nama" required>
          </div>
          <div class="mb-3">
            <label class="form-label">No Kad Pengenalan</label>
            <input type="text" class="form-control" name="no_ic" id="no_ic">
          </div>
          <div class="mb-3">
            <label class="form-label">No Telefon</label>
            <input type="text" class="form-control" name="no_telefon" id="no_telefon">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="password" placeholder="Kosongkan jika tidak mahu tukar (untuk edit)">
            <small class="text-muted">Password akan di-hash secara automatik</small>
          </div>
          <div class="mb-3" id="confirm_password_div">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Sahkan password">
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
function tambahPenyewa() {
    document.getElementById('modalTitle').innerText = 'Tambah Penyewa';
    document.getElementById('id_penyewa').value = '';
    document.getElementById('nama').value = '';
    document.getElementById('no_ic').value = '';
    document.getElementById('no_telefon').value = '';
    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = 'Masukkan password';
    document.getElementById('password').required = true;
    document.getElementById('confirm_password').value = '';
    document.getElementById('confirm_password').required = true;
    document.getElementById('confirm_password_div').style.display = 'block';
}

function editPenyewa(data) {
    document.getElementById('modalTitle').innerText = 'Edit Penyewa';
    document.getElementById('id_penyewa').value = data.id_penyewa;
    document.getElementById('nama').value = data.nama;
    document.getElementById('no_ic').value = data.no_ic;
    document.getElementById('no_telefon').value = data.no_telefon;
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