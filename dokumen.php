<?php
include("db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: login.php");
    exit();
}

$id_penyewa = (int)$_SESSION['user_id'];
$success = "";
$error = "";

$upload_folder = __DIR__ . "/uploads/documents/";
$upload_url = "uploads/documents/";

if (!is_dir($upload_folder)) {
    mkdir($upload_folder, 0755, true);
}

function upload_error_message($error_code) {
    return match ($error_code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Fail terlalu besar.",
        UPLOAD_ERR_PARTIAL => "Fail hanya dimuat naik sebahagian.",
        UPLOAD_ERR_NO_TMP_DIR => "Folder sementara PHP tidak tersedia.",
        UPLOAD_ERR_CANT_WRITE => "Fail tidak dapat disimpan.",
        UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh PHP.",
        default => "Terdapat masalah semasa upload."
    };
}

if (isset($_POST['upload_dokumen'])) {
    $ic_file = null;
    $akuan_file = null;

    // IC: image only
    if (isset($_FILES['ic_file']) && $_FILES['ic_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['ic_file']['error'] !== UPLOAD_ERR_OK) {
            $error = upload_error_message($_FILES['ic_file']['error']);
        } else {
            $ext = strtolower(pathinfo($_FILES['ic_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['ic_file']['tmp_name']);
            $allowed_mime = ['image/jpeg', 'image/png'];

            if (!in_array($ext, $allowed, true) || !in_array($mime, $allowed_mime, true)) {
                $error = "IC mestilah dalam format JPG, JPEG atau PNG.";
            } else {
                $ic_file = "ic_{$id_penyewa}_" . time() . "." . $ext;
                if (!move_uploaded_file($_FILES['ic_file']['tmp_name'], $upload_folder . $ic_file)) {
                    $error = "IC gagal disimpan.";
                    $ic_file = null;
                }
            }
        }
    }

    // Akuan Janji: PDF / Word
    if ($error === "" && isset($_FILES['akuan_janji_file']) &&
        $_FILES['akuan_janji_file']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['akuan_janji_file']['error'] !== UPLOAD_ERR_OK) {
            $error = upload_error_message($_FILES['akuan_janji_file']['error']);
        } else {
            $ext = strtolower(pathinfo($_FILES['akuan_janji_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx'];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['akuan_janji_file']['tmp_name']);
            $allowed_mime = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($ext, $allowed, true) || !in_array($mime, $allowed_mime, true)) {
                $error = "Akuan Janji mestilah dalam format PDF, DOC atau DOCX.";
            } else {
                $akuan_file = "akuan_{$id_penyewa}_" . time() . "." . $ext;
                if (!move_uploaded_file($_FILES['akuan_janji_file']['tmp_name'], $upload_folder . $akuan_file)) {
                    $error = "Akuan Janji gagal disimpan.";
                    $akuan_file = null;
                }
            }
        }
    }

    if ($error === "" && ($ic_file !== null || $akuan_file !== null)) {
        $check = mysqli_prepare($conn,
            "SELECT ic_file, akuan_janji_file FROM dokumen_penyewa WHERE id_penyewa = ?"
        );
        mysqli_stmt_bind_param($check, "i", $id_penyewa);
        mysqli_stmt_execute($check);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
        mysqli_stmt_close($check);

        if ($existing) {
            if ($ic_file === null) $ic_file = $existing['ic_file'];
            if ($akuan_file === null) $akuan_file = $existing['akuan_janji_file'];

            $stmt = mysqli_prepare($conn,
                "UPDATE dokumen_penyewa
                 SET ic_file = ?, akuan_janji_file = ?, tarikh_upload = NOW()
                 WHERE id_penyewa = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssi", $ic_file, $akuan_file, $id_penyewa);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO dokumen_penyewa (id_penyewa, ic_file, akuan_janji_file)
                 VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iss", $id_penyewa, $ic_file, $akuan_file);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success = "Dokumen berjaya dimuat naik.";
        } else {
            $error = "Dokumen gagal disimpan ke dalam database.";
        }
        mysqli_stmt_close($stmt);
    } elseif ($error === "") {
        $error = "Sila pilih sekurang-kurangnya satu dokumen.";
    }
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM dokumen_penyewa WHERE id_penyewa = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);
$dokumen = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

include("header_penyewa.php");
?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-folder-open me-2"></i>Dokumen Saya</h2>
        <p class="text-muted">Muat naik IC dan Akuan Janji untuk rekod penyewaan.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-upload me-2"></i>Upload Dokumen</h5>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-id-card me-2"></i>Salinan IC
                    </label>
                    <input type="file" name="ic_file" class="form-control" accept=".jpg,.jpeg,.png">
                    <small class="text-muted">Format: JPG, JPEG atau PNG</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-file-contract me-2"></i>Akuan Janji
                    </label>
                    <input type="file" name="akuan_janji_file" class="form-control" accept=".pdf,.doc,.docx">
                    <small class="text-muted">Format: PDF, DOC atau DOCX</small>
                </div>

                <button type="submit" name="upload_dokumen" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload Dokumen
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-file-alt me-2"></i>Dokumen Yang Telah Dihantar</h5>

            <?php if ($dokumen): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Status</th>
                                <th>Tarikh Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>IC</strong></td>
                                <td>
                                    <?php if (!empty($dokumen['ic_file'])): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Telah dihantar</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum dihantar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($dokumen['tarikh_upload'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Akuan Janji</strong></td>
                                <td>
                                    <?php if (!empty($dokumen['akuan_janji_file'])): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Telah dihantar</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum dihantar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($dokumen['tarikh_upload'])) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                    Anda belum menghantar sebarang dokumen.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("footer_penyewa.php"); ?>
