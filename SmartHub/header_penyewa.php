<?php  // <-- ADD THIS AT THE VERY FIRST LINE, NO SPACES BEFORE IT
$__current = basename($_SERVER['PHP_SELF']);
function navActiveT($files) {
    global $__current;
    $files = is_array($files) ? $files : [$files];
    return in_array($__current, $files) ? 'active' : '';
}
// ... rest of your code

// Function untuk dapatkan inisial
function getInitialsT($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2) ?: 'U';
}

// Check if profile picture exists
$profile_pic = $_SESSION['profile_picture'] ?? null;
$has_profile_pic = !empty($profile_pic) && file_exists('uploads/profile/' . $profile_pic);
$avatar_text = getInitialsT($_SESSION['nama'] ?? $_SESSION['username'] ?? 'P');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Rent Hub — Penyewa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        /* Style untuk profile chip klik */
        .profile-chip-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px 5px 5px;
            border-radius: 24px;
            background: #f2f4f9;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }
        .profile-chip-link:hover {
            background: #e8ecf5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            color: var(--ink);
        }
        .profile-chip-link .avatar-sm {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .profile-chip-link .avatar-sm img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-chip-link i {
            font-size: 12px;
            color: var(--muted-soft);
            margin-left: 2px;
        }
        .profile-chip-link:hover i {
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="app-shell">

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fas fa-home"></i></div>
            <div>
                <div class="brand-text">Smart<span>Rent</span> Hub</div>
                <span class="brand-sub">Tenant Portal</span>
            </div>
        </div>

        <div class="sidebar-section-label">Menu Utama</div>
        <nav class="sidebar-nav">
            <a href="dashboard_tenant.php" class="nav-item <?= navActiveT('dashboard_tenant.php') ?>">
                <i class="fas fa-chart-pie"></i><span>Dashboard</span>
            </a>
            <a href="bayaran.php" class="nav-item <?= navActiveT('bayaran.php') ?>">
                <i class="fas fa-credit-card"></i><span>Sewaan &amp; Bayaran</span>
            </a>
        </nav>

        <div class="sidebar-section-label">Akaun</div>
        <nav class="sidebar-nav" style="flex: 0;">
            <a href="profile.php" class="nav-item <?= navActiveT(['profile.php','edit_profile.php']) ?>">
                <i class="fas fa-user-circle"></i><span>Profile Saya</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i><span>Log Keluar</span>
            </a>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">
                <span class="crumb-muted">Smart Rent Hub</span> / <?= ucfirst(str_replace(['_tenant.php','.php'], ['','' ], $__current)) ?>
            </div>
            <div class="topbar-spacer"></div>
            <div class="topbar-profile">
                <span class="badge-role tenant"><i class="fas fa-user"></i> Penyewa</span>
                <!-- Profile Chip dengan link ke profile.php -->
                <a href="profile.php" class="profile-chip-link" title="Klik untuk ke Profile">
                    <div class="avatar-sm">
                        <?php if ($has_profile_pic): ?>
                            <img src="uploads/profile/<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
                        <?php else: ?>
                            <?= $avatar_text ?>
                        <?php endif; ?>
                    </div>
                    <span><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Penyewa') ?></span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </header>

        <main class="content-wrapper">