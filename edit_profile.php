<?php
include("db.php");

// Semak sama ada user dah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";
$upload_error = "";

// ============================================
// AMBIL MAKLUMAT USER
// ============================================
if ($role == 'admin') {
    // Admin - ambil dari session
    $nama = $_SESSION['nama'] ?? 'Admin';
    $email = $_SESSION['email'] ?? 'admin@umahkakjum.com';
    $no_telefon = $_SESSION['no_telefon'] ?? '';
    $profile_picture = $_SESSION['profile_picture'] ?? null;
    $role_display = 'Tuan Rumah / Admin';
    $username = $_SESSION['username'] ?? 'admin';
} else {
    // Penyewa - ambil dari database
    $sql = "SELECT * FROM penyewa WHERE id_penyewa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penyewa = mysqli_fetch_assoc($result);
    
    if ($penyewa) {
        $nama = $penyewa['nama'];
        $email = $penyewa['email'];
        $no_telefon = $penyewa['no_telefon'] ?? '';
        $no_ic = $penyewa['no_ic'] ?? '';
        $profile_picture = $penyewa['profile_picture'] ?? null;
        $role_display = 'Penyewa';
    } else {
        header("Location: logout.php");
        exit();
    }
}

// ============================================
// FUNGSI UPLOAD GAMBAR
// ============================================
function uploadProfilePicture($file, $user_id, $role) {
    $target_dir = "uploads/profile/";
    
    // Create directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "Saiz gambar melebihi limit server.",
            UPLOAD_ERR_FORM_SIZE => "Saiz gambar melebihi limit form.",
            UPLOAD_ERR_PARTIAL => "Gambar hanya sebahagian sahaja diupload.",
            UPLOAD_ERR_NO_FILE => "Tiada gambar dipilih.",
            UPLOAD_ERR_NO_TMP_DIR => "Folder temporary tiada.",
            UPLOAD_ERR_CANT_WRITE => "Gagal menulis gambar ke disk.",
            UPLOAD_ERR_EXTENSION => "Upload gambar dihentikan oleh extension PHP.",
        ];
        return ['error' => $upload_errors[$file['error']] ?? 'Ralat upload tidak diketahui.'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Saiz gambar melebihi 5MB. Sila gunakan gambar yang lebih kecil.'];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['error' => 'Format gambar tidak dibenarkan. Sila gunakan JPG, PNG, GIF atau WEBP.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $role . '_' . $user_id . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => $filename];
    } else {
        return ['error' => 'Gagal menyimpan gambar. Sila cuba lagi.'];
    }
}

// ============================================
// FUNGSI DELETE GAMBAR LAMA
// ============================================
function deleteOldProfilePicture($filename) {
    if (!empty($filename)) {
        $filepath = "uploads/profile/" . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
            return true;
        }
    }
    return false;
}

// ============================================
// PROSES UPDATE PROFILE
// ============================================
if (isset($_POST['update'])) {
    if ($role == 'admin') {
        $nama_baru = mysqli_real_escape_string($conn, $_POST['nama']);
        $email_baru = mysqli_real_escape_string($conn, $_POST['email']);
        $no_telefon_baru = mysqli_real_escape_string($conn, $_POST['no_telefon']);
        $password_baru = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($nama_baru) || empty($email_baru)) {
            $error = "Nama dan email wajib diisi!";
        } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
            $error = "Kata laluan tidak sepadan!";
        } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
            $error = "Kata laluan mesti sekurang-kurangnya 6 aksara!";
        } else {
            // Handle profile picture upload
            $profile_pic_upload = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = uploadProfilePicture($_FILES['profile_picture'], $user_id, 'admin');
                if (isset($upload_result['error'])) {
                    $upload_error = $upload_result['error'];
                } else {
                    $profile_pic_upload = $upload_result['success'];
                    // Delete old picture if exists
                    if (!empty($profile_picture)) {
                        deleteOldProfilePicture($profile_picture);
                    }
                }
            }

            // Update admin dalam database
            if (empty($password_baru) && empty($profile_pic_upload)) {
                $sql = "UPDATE admin SET nama=?, email=?, no_telefon=? WHERE id_admin=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $nama_baru, $email_baru, $no_telefon_baru, $user_id);
            } elseif (empty($password_baru) && !empty($profile_pic_upload)) {
                $sql = "UPDATE admin SET nama=?, email=?, no_telefon=?, profile_picture=? WHERE id_admin=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nama_baru, $email_baru, $no_telefon_baru, $profile_pic_upload, $user_id);
            } elseif (!empty($password_baru) && empty($profile_pic_upload)) {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $sql = "UPDATE admin SET nama=?, email=?, no_telefon=?, password=? WHERE id_admin=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nama_baru, $email_baru, $no_telefon_baru, $hashed_password, $user_id);
            } else {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $sql = "UPDATE admin SET nama=?, email=?, no_telefon=?, password=?, profile_picture=? WHERE id_admin=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $nama_baru, $email_baru, $no_telefon_baru, $hashed_password, $profile_pic_upload, $user_id);
            }

            if (mysqli_stmt_execute($stmt)) {
                // Update session
                $_SESSION['nama'] = $nama_baru;
                $_SESSION['email'] = $email_baru;
                $_SESSION['no_telefon'] = $no_telefon_baru;
                if ($profile_pic_upload) {
                    $_SESSION['profile_picture'] = $profile_pic_upload;
                }
                
                $success = "Profile berjaya dikemaskini!";
                
                // Refresh data
                $nama = $nama_baru;
                $email = $email_baru;
                $no_telefon = $no_telefon_baru;
                if ($profile_pic_upload) {
                    $profile_picture = $profile_pic_upload;
                }
            } else {
                $error = "Ralat: " . mysqli_error($conn);
            }
        }
    } else {
        // ============================================
        // PROSES UPDATE PENYEWA
        // ============================================
        $nama_baru = mysqli_real_escape_string($conn, $_POST['nama']);
        $email_baru = mysqli_real_escape_string($conn, $_POST['email']);
        $no_telefon_baru = mysqli_real_escape_string($conn, $_POST['no_telefon']);
        $no_ic_baru = mysqli_real_escape_string($conn, $_POST['no_ic']);
        $password_baru = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($nama_baru) || empty($email_baru)) {
            $error = "Nama dan email wajib diisi!";
        } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
            $error = "Kata laluan tidak sepadan!";
        } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
            $error = "Kata laluan mesti sekurang-kurangnya 6 aksara!";
        } else {
            // Handle profile picture upload
            $profile_pic_upload = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = uploadProfilePicture($_FILES['profile_picture'], $user_id, 'penyewa');
                if (isset($upload_result['error'])) {
                    $upload_error = $upload_result['error'];
                } else {
                    $profile_pic_upload = $upload_result['success'];
                    // Delete old picture if exists
                    if (!empty($profile_picture)) {
                        deleteOldProfilePicture($profile_picture);
                    }
                }
            }

            // Update penyewa
            if (empty($password_baru) && empty($profile_pic_upload)) {
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $user_id);
            } elseif (empty($password_baru) && !empty($profile_pic_upload)) {
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=?, profile_picture=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $profile_pic_upload, $user_id);
            } elseif (!empty($password_baru) && empty($profile_pic_upload)) {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=?, password=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $hashed_password, $user_id);
            } else {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $sql = "UPDATE penyewa SET nama=?, email=?, no_telefon=?, no_ic=?, password=?, profile_picture=? WHERE id_penyewa=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssssi", $nama_baru, $email_baru, $no_telefon_baru, $no_ic_baru, $hashed_password, $profile_pic_upload, $user_id);
            }

            if (mysqli_stmt_execute($stmt)) {
                // Update session
                $_SESSION['username'] = $nama_baru;
                $_SESSION['nama'] = $nama_baru;
                $_SESSION['email'] = $email_baru;
                $_SESSION['no_telefon'] = $no_telefon_baru;
                $_SESSION['no_ic'] = $no_ic_baru;
                if ($profile_pic_upload) {
                    $_SESSION['profile_picture'] = $profile_pic_upload;
                }
                
                $success = "Profile berjaya dikemaskini!";
                
                // Refresh data
                $nama = $nama_baru;
                $email = $email_baru;
                $no_telefon = $no_telefon_baru;
                $no_ic = $no_ic_baru;
                if ($profile_pic_upload) {
                    $profile_picture = $profile_pic_upload;
                }
            } else {
                $error = "Ralat: " . mysqli_error($conn);
            }
        }
    }
}

// ============================================
// PROSES PADAM GAMBAR
// ============================================
if (isset($_POST['remove_picture'])) {
    if (!empty($profile_picture)) {
        if (deleteOldProfilePicture($profile_picture)) {
            // Update database
            if ($role == 'admin') {
                $sql = "UPDATE admin SET profile_picture = NULL WHERE id_admin = ?";
            } else {
                $sql = "UPDATE penyewa SET profile_picture = NULL WHERE id_penyewa = ?";
            }
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['profile_picture'] = null;
                $profile_picture = null;
                $success = "Gambar profil berjaya dipadam!";
            } else {
                $error = "Gagal memadam gambar profil.";
            }
        } else {
            $error = "Gambar profil tidak dijumpai.";
        }
    } else {
        $error = "Tiada gambar profil untuk dipadam.";
    }
}
?>

<?php
if ($role == 'admin') {
    include("header.php");
} else {
    include("header_penyewa.php");
}
?>

<div class="page-wrapper">
    <div class="edit-card">
        <!-- Header -->
        <div class="edit-header">
            <h5><i class="fas fa-edit"></i> Kemaskini Profile</h5>
        </div>

        <!-- Body -->
        <div class="edit-body">
            <!-- Mesej -->
            <?php if ($error): ?>
                <div class="alert-custom danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($upload_error): ?>
                <div class="alert-custom danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $upload_error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-custom success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <div class="info-badge">
                <i class="fas fa-info-circle"></i>
                Kemaskini maklumat profile anda di bawah. <strong>Semua perubahan akan disimpan.</strong>
            </div>

            <div class="role-badge-display">
                <i class="fas fa-<?= $role == 'admin' ? 'user-shield' : 'user' ?>"></i>
                Anda log masuk sebagai: <strong><?= $role_display ?></strong>
                <?php if ($role == 'admin'): ?>
                    (Username: <?= htmlspecialchars($username) ?>)
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <!-- ============================================ -->
                <!-- GAMBAR PROFIL -->
                <!-- ============================================ -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-image me-1" style="color: var(--primary);"></i> 
                        Gambar Profil
                    </label>
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="profile-avatar-preview" style="
                                width: 100px; 
                                height: 100px; 
                                border-radius: 50%; 
                                background: linear-gradient(135deg, var(--primary), var(--secondary));
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 38px;
                                color: #fff;
                                font-weight: 700;
                                overflow: hidden;
                                border: 3px solid var(--border);
                            ">
                                <?php 
                                $has_pic = !empty($profile_picture) && file_exists('uploads/profile/' . $profile_picture);
                                if ($has_pic): 
                                ?>
                                    <img src="uploads/profile/<?= htmlspecialchars($profile_picture) ?>" 
                                         alt="Profile Picture" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: 
                                    $initials = '';
                                    $words = explode(' ', $nama);
                                    foreach ($words as $word) {
                                        if (!empty($word)) {
                                            $initials .= strtoupper($word[0]);
                                        }
                                    }
                                    $initials = substr($initials, 0, 2) ?: 'U';
                                ?>
                                    <?= $initials ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-2">
                                <input type="file" class="form-control" name="profile_picture" 
                                       accept="image/*" id="profile_picture_input">
                                <small class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Format: JPG, PNG, GIF, WEBP. Maksimum 5MB.
                                </small>
                            </div>
                            <?php if ($has_pic): ?>
                                <button type="submit" name="remove_picture" class="btn-padam-action" 
                                        onclick="return confirm('Anda pasti mahu padam gambar profil ini?')">
                                    <i class="fas fa-trash me-1"></i> Padam Gambar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <?php if ($role == 'admin'): ?>
                    <!-- ============================================ -->
                    <!-- ADMIN FORM - BOLEH EDIT SEMUA -->
                    <!-- ============================================ -->
                    <div class="mb-3">
                        <label class="form-label">Nama Penuh <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No Telefon</label>
                        <input type="text" class="form-control" name="no_telefon" value="<?= htmlspecialchars($no_telefon) ?>" placeholder="Contoh: 012-3456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                        <small class="form-text">Username tidak boleh diubah untuk keselamatan.</small>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="fw-bold"><i class="fas fa-lock me-2" style="color: var(--primary);"></i> Tukar Kata Laluan</h6>
                    <div class="mb-3">
                        <label class="form-label">Kata Laluan Baru</label>
                        <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak mahu tukar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Taip semula kata laluan">
                        <small class="form-text">Minimum 6 aksara</small>
                    </div>
                    
                <?php else: ?>
                    <!-- ============================================ -->
                    <!-- PENYEWA FORM -->
                    <!-- ============================================ -->
                    <div class="mb-3">
                        <label class="form-label">Nama Penuh <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No Telefon</label>
                            <input type="text" class="form-control" name="no_telefon" value="<?= htmlspecialchars($no_telefon ?? '') ?>" placeholder="Contoh: 012-3456789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No Kad Pengenalan</label>
                            <input type="text" class="form-control" name="no_ic" value="<?= htmlspecialchars($no_ic ?? '') ?>" placeholder="Contoh: 010101-01-0101">
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="fw-bold"><i class="fas fa-lock me-2" style="color: var(--primary);"></i> Tukar Kata Laluan</h6>
                    <div class="mb-3">
                        <label class="form-label">Kata Laluan Baru</label>
                        <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak mahu tukar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Taip semula kata laluan">
                        <small class="form-text">Minimum 6 aksara</small>
                    </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <button type="submit" name="update" class="btn-simpan">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                    <a href="profile.php" class="btn-batal">
                        <i class="fas fa-arrow-left"></i> Kembali ke Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.profile-avatar-preview {
    transition: all 0.3s ease;
}
.profile-avatar-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);
}
</style>

<?php
if ($role == 'admin') {
    include("footer.php");
} else {
    include("footer_penyewa.php");
}
?>