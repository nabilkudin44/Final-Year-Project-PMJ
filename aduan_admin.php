<?php
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $id_aduan = (int)($_POST['id_aduan'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['Baru', 'Dalam Proses', 'Selesai'];
    if ($id_aduan > 0 && in_array($status, $allowed, true)) {
        $stmt = mysqli_prepare($conn, "UPDATE aduan SET status = ? WHERE id_aduan = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $id_aduan);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($conn,
    "SELECT a.*, p.nama, p.no_ic, p.no_telefon, p.email
     FROM aduan a
     INNER JOIN penyewa p ON a.id_penyewa = p.id_penyewa
     ORDER BY a.tarikh DESC"
);

include("header.php");
?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-comments me-2"></i>Aduan Penyewa</h2>
        <p class="text-muted">Lihat dan urus aduan yang dihantar oleh penyewa.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Penyewa</th>
                            <th>Maklumat</th>
                            <th>Tajuk</th>
                            <th>Aduan</th>
                            <th>Tarikh</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['nama']) ?></strong><br>
                                    <small class="text-muted">ID: <?= $row['id_penyewa'] ?></small>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-id-card me-1"></i><?= htmlspecialchars($row['no_ic']) ?><br>
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($row['no_telefon'] ?? '-') ?><br>
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($row['email']) ?>
                                    </small>
                                </td>
                                <td><strong><?= htmlspecialchars($row['tajuk']) ?></strong></td>
                                <td style="min-width:250px"><?= nl2br(htmlspecialchars($row['aduan'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['tarikh'])) ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="id_aduan" value="<?= $row['id_aduan'] ?>">
                                        <select name="status" class="form-select form-select-sm"
                                                onchange="this.form.submit()">
                                            <option value="Baru" <?= $row['status']==='Baru'?'selected':'' ?>>Baru</option>
                                            <option value="Dalam Proses" <?= $row['status']==='Dalam Proses'?'selected':'' ?>>Dalam Proses</option>
                                            <option value="Selesai" <?= $row['status']==='Selesai'?'selected':'' ?>>Selesai</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                Tiada aduan daripada penyewa.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
