<?php
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = mysqli_query($conn,
    "SELECT
        p.id_penyewa,
        p.nama,
        p.no_ic,
        p.no_telefon,
        p.email,
        r.no_rumah,
        d.ic_file,
        d.akuan_janji_file,
        d.tarikh_upload
     FROM penyewa p
     LEFT JOIN dokumen_penyewa d ON p.id_penyewa = d.id_penyewa
     LEFT JOIN sewa s ON p.id_penyewa = s.id_penyewa
     LEFT JOIN rumah r ON s.id_rumah = r.id_rumah
     ORDER BY p.nama ASC"
);

include("header.php");
?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-folder-open me-2"></i>Dokumen Penyewa</h2>
        <p class="text-muted">Lihat maklumat penyewa dan dokumen yang telah dihantar.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-users me-2"></i>Senarai Penyewa</h5>
                    <small class="text-muted">Maklumat akaun dan dokumen penyewa</small>
                </div>
                <span class="badge bg-primary"><?= mysqli_num_rows($result) ?> Penyewa</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Penyewa</th>
                            <th>No. IC</th>
                            <th>No. Telefon</th>
                            <th>Email</th>
                            <th>Rumah</th>
                            <th>IC</th>
                            <th>Akuan Janji</th>
                            <th>Tarikh Upload</th>
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
                                <td><?= htmlspecialchars($row['no_ic']) ?></td>
                                <td><?= htmlspecialchars($row['no_telefon'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <?php if (!empty($row['no_rumah'])): ?>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($row['no_rumah']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Tiada rumah</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['ic_file'])): ?>
                                        <a href="uploads/documents/<?= rawurlencode($row['ic_file']) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Lihat
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum dihantar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['akuan_janji_file'])): ?>
                                        <a href="uploads/documents/<?= rawurlencode($row['akuan_janji_file']) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-file-alt me-1"></i>Lihat
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum dihantar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['tarikh_upload'])): ?>
                                        <?= date('d/m/Y H:i', strtotime($row['tarikh_upload'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-users-slash fa-2x mb-2"></i><br>
                                Tiada penyewa ditemui.
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
