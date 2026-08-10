<?php
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: login.php");
    exit();
}

$id_penyewa = (int)$_SESSION['user_id'];
$success = "";
$error = "";

if (isset($_POST['hantar_aduan'])) {
    $tajuk = trim($_POST['tajuk'] ?? '');
    $aduan = trim($_POST['aduan'] ?? '');

    if ($tajuk === '' || $aduan === '') {
        $error = "Sila isi semua ruangan.";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO aduan (id_penyewa, tajuk, aduan) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iss", $id_penyewa, $tajuk, $aduan);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Aduan berjaya dihantar kepada pihak admin.";
        } else {
            $error = "Aduan gagal dihantar.";
        }
        mysqli_stmt_close($stmt);
    }
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM aduan WHERE id_penyewa = ? ORDER BY tarikh DESC"
);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);
$aduan_result = mysqli_stmt_get_result($stmt);

include("header_penyewa.php");
?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-comment-dots me-2"></i>Aduan</h2>
        <p class="text-muted">Hantar aduan atau masalah berkaitan rumah sewa kepada pihak admin.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-paper-plane me-2"></i>Hantar Aduan Baru</h5>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tajuk Aduan</label>
                    <input type="text" name="tajuk" class="form-control"
                           placeholder="Contoh: Paip Air Rosak" maxlength="150" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Aduan</label>
                    <textarea name="aduan" class="form-control" rows="6"
                              placeholder="Terangkan masalah atau aduan anda..." required></textarea>
                </div>

                <button type="submit" name="hantar_aduan" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Hantar Aduan
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-history me-2"></i>Aduan Saya</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tajuk</th>
                            <th>Aduan</th>
                            <th>Tarikh</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($aduan_result) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($aduan_result)): ?>
                            <?php
                            $badge = 'bg-secondary';
                            if ($row['status'] === 'Baru') $badge = 'bg-danger';
                            elseif ($row['status'] === 'Dalam Proses') $badge = 'bg-warning text-dark';
                            elseif ($row['status'] === 'Selesai') $badge = 'bg-success';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['tajuk']) ?></strong></td>
                                <td><?= nl2br(htmlspecialchars($row['aduan'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['tarikh'])) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>Tiada aduan dihantar.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
include("footer_penyewa.php");
?>
