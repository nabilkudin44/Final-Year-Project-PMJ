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
$success = $_SESSION['finance_success'] ?? "";
unset($_SESSION['finance_success']);

// Senarai bulan
$bulan_list = [
    'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun',
    'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'
];
$bulan_semasa = $bulan_list[(int) date('n') - 1];
$bulan_selected = isset($_GET['bulan']) && in_array($_GET['bulan'], $bulan_list, true)
    ? $_GET['bulan'] : $bulan_semasa;
$tahun_selected = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int) date('Y');

// ============================================
// PROSES GENERATE KEWANGAN BULANAN
// ============================================
if (isset($_POST['generate_kewangan'])) {
    $bulan = $_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    
    // Semak jika data sudah wujud
    $check_sql = "SELECT id_kewangan FROM kewangan_bulanan WHERE bulan = ? AND tahun = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "si", $bulan, $tahun);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error = "Data kewangan untuk bulan $bulan $tahun sudah wujud!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // Dapatkan semua sewaan aktif
            $sewa_sql = "SELECT 
                            s.id_sewa,
                            s.id_penyewa,
                            s.id_rumah,
                            p.nama as nama_penyewa,
                            r.no_rumah,
                            r.harga_sewa
                        FROM sewa s
                        JOIN penyewa p ON s.id_penyewa = p.id_penyewa
                        JOIN rumah r ON s.id_rumah = r.id_rumah
                        WHERE r.status = 'Disewa'";
            $sewa_result = mysqli_query($conn, $sewa_sql);
            
            $total_sewa_dijangka = 0;
            $total_kutipan = 0;
            $total_tunggakan = 0;
            $bilangan_penyewa = mysqli_num_rows($sewa_result);
            $bilangan_rumah_disewa = $bilangan_penyewa;
            
            // Masukkan ke kewangan_bulanan
            $insert_sql = "INSERT INTO kewangan_bulanan 
                          (bulan, tahun, total_sewa_dijangka, total_kutipan, total_tunggakan, 
                           bilangan_penyewa, bilangan_rumah_disewa)
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "sidddii", 
                $bulan, $tahun, $total_sewa_dijangka, $total_kutipan, $total_tunggakan,
                $bilangan_penyewa, $bilangan_rumah_disewa
            );
            mysqli_stmt_execute($insert_stmt);
            $id_kewangan = mysqli_insert_id($conn);
            
            // Proses setiap sewaan
            while ($row = mysqli_fetch_assoc($sewa_result)) {
                $id_sewa = $row['id_sewa'];
                $id_penyewa = $row['id_penyewa'];
                $nama_penyewa = $row['nama_penyewa'];
                $no_rumah = $row['no_rumah'];
                $sewa_bulanan = $row['harga_sewa'];
                
                // Semak bayaran untuk bulan ini
                $bayaran_sql = "SELECT id_bayaran, status, tarikh_bayar, jumlah 
                               FROM bayaran 
                               WHERE id_sewa = ? 
                               AND bulan = ? 
                               AND tahun = ?
                               ORDER BY id_bayaran DESC
                               LIMIT 1";
                $bayaran_stmt = mysqli_prepare($conn, $bayaran_sql);
                mysqli_stmt_bind_param($bayaran_stmt, "isi", $id_sewa, $bulan, $tahun);
                mysqli_stmt_execute($bayaran_stmt);
                $bayaran_result = mysqli_stmt_get_result($bayaran_stmt);
                $bayaran = mysqli_fetch_assoc($bayaran_result);
                
                $total_sewa_dijangka += $sewa_bulanan;
                
                $status_bayaran = 'Belum Lunas';
                $tarikh_bayar = null;
                
                if ($bayaran) {
                    if ($bayaran['status'] == 'Lunas') {
                        $status_bayaran = 'Lunas';
                        $total_kutipan += $sewa_bulanan;
                        $tarikh_bayar = $bayaran['tarikh_bayar'];
                    } elseif ($bayaran['status'] == 'Pending') {
                        $status_bayaran = 'Pending';
                        $total_tunggakan += $sewa_bulanan;
                    } else {
                        $total_tunggakan += $sewa_bulanan;
                    }
                } else {
                    // Tiada bayaran langsung
                    $total_tunggakan += $sewa_bulanan;
                }
                
                // Masukkan ke kewangan_penyewa
                $detail_sql = "INSERT INTO kewangan_penyewa 
                              (id_kewangan, id_sewa, id_penyewa, nama_penyewa, no_rumah, 
                               sewa_bulanan, status_bayaran, tarikh_bayar)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $detail_stmt = mysqli_prepare($conn, $detail_sql);
                mysqli_stmt_bind_param($detail_stmt, "iiissdss", 
                    $id_kewangan, $id_sewa, $id_penyewa, $nama_penyewa, $no_rumah,
                    $sewa_bulanan, $status_bayaran, $tarikh_bayar
                );
                mysqli_stmt_execute($detail_stmt);
            }
            
            // Update total dalam kewangan_bulanan
            $update_sql = "UPDATE kewangan_bulanan 
                          SET total_sewa_dijangka = ?, total_kutipan = ?, total_tunggakan = ?
                          WHERE id_kewangan = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "dddi", 
                $total_sewa_dijangka, $total_kutipan, $total_tunggakan, $id_kewangan
            );
            mysqli_stmt_execute($update_stmt);
            
            mysqli_commit($conn);
            $_SESSION['finance_success'] = "Data kewangan untuk bulan $bulan $tahun berjaya dijana!";
            header("Location: kewangan.php?bulan=" . urlencode($bulan) . "&tahun=" . $tahun);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Ralat: " . $e->getMessage();
        }
    }
}

// ============================================
// PROSES UPDATE STATUS BAYARAN
// ============================================
if (isset($_POST['update_status_bayaran'])) {
    $id_kewangan_penyewa = (int)$_POST['id_kewangan_penyewa'];
    $status_baru = $_POST['status_baru'];
    $id_sewa = (int)$_POST['id_sewa'];
    $bulan = $_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    
    $allowed_status = ['Lunas', 'Belum Lunas', 'Pending'];
    if (!in_array($status_baru, $allowed_status)) {
        $error = "Status tidak sah!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            $tarikh_bayar_baru = $status_baru === 'Lunas' ? date('Y-m-d H:i:s') : null;

            // Update kewangan_penyewa
            $sql = "UPDATE kewangan_penyewa
                    SET status_bayaran = ?, tarikh_bayar = ?
                    WHERE id_kewangan_penyewa = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $status_baru, $tarikh_bayar_baru, $id_kewangan_penyewa);
            mysqli_stmt_execute($stmt);
            
            // Update atau insert bayaran
            $check_sql = "SELECT id_bayaran FROM bayaran WHERE id_sewa = ? AND bulan = ? AND tahun = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "isi", $id_sewa, $bulan, $tahun);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                // Update existing
                $sql = "UPDATE bayaran
                        SET status = ?, tarikh_bayar = ?
                        WHERE id_sewa = ? AND bulan = ? AND tahun = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssisi", $status_baru, $tarikh_bayar_baru, $id_sewa, $bulan, $tahun);
            } else {
                // Insert new
                $sql = "INSERT INTO bayaran (id_sewa, bulan, tahun, jumlah, status, tarikh_bayar) 
                        SELECT ?, ?, ?, harga_sewa, ?, ?
                        FROM sewa s
                        JOIN rumah r ON s.id_rumah = r.id_rumah
                        WHERE s.id_sewa = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isissi", $id_sewa, $bulan, $tahun, $status_baru, $tarikh_bayar_baru, $id_sewa);
            }
            mysqli_stmt_execute($stmt);
            
            // Kira semula total kewangan_bulanan
            $total_sql = "SELECT 
                            COALESCE(SUM(CASE WHEN status_bayaran = 'Lunas' THEN sewa_bulanan ELSE 0 END), 0) as total_kutipan,
                            COALESCE(SUM(CASE WHEN status_bayaran != 'Lunas' THEN sewa_bulanan ELSE 0 END), 0) as total_tunggakan
                         FROM kewangan_penyewa 
                         WHERE id_kewangan = (SELECT id_kewangan FROM kewangan_penyewa WHERE id_kewangan_penyewa = ?)";
            $total_stmt = mysqli_prepare($conn, $total_sql);
            mysqli_stmt_bind_param($total_stmt, "i", $id_kewangan_penyewa);
            mysqli_stmt_execute($total_stmt);
            $total_result = mysqli_stmt_get_result($total_stmt);
            $total_row = mysqli_fetch_assoc($total_result);
            
            $update_sql = "UPDATE kewangan_bulanan 
                          SET total_kutipan = ?, total_tunggakan = ? 
                          WHERE id_kewangan = (SELECT id_kewangan FROM kewangan_penyewa WHERE id_kewangan_penyewa = ?)";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ddi", 
                $total_row['total_kutipan'], $total_row['total_tunggakan'], $id_kewangan_penyewa
            );
            mysqli_stmt_execute($update_stmt);
            
            mysqli_commit($conn);
            $_SESSION['finance_success'] = "Status bayaran berjaya dikemaskini!";
            header("Location: kewangan.php?bulan=" . urlencode($bulan) . "&tahun=" . $tahun);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Ralat: " . $e->getMessage();
        }
    }
}

// ============================================
// PROSES PADAM DATA KEWANGAN
// ============================================
if (isset($_POST['delete_kewangan'])) {
    $id_kewangan = (int)($_POST['id_kewangan'] ?? 0);
    
    $sql = "DELETE FROM kewangan_bulanan WHERE id_kewangan = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_kewangan);
    
    if ($id_kewangan > 0 && mysqli_stmt_execute($stmt)) {
        $_SESSION['finance_success'] = "Data kewangan berjaya dipadam!";
        header("Location: kewangan.php?bulan=" . urlencode($bulan_selected) . "&tahun=" . $tahun_selected);
        exit();
    } else {
        $error = "Gagal memadam data.";
    }
}

// ============================================
// AMBIL DATA KEWANGAN
// ============================================
$month_num = array_search($bulan_selected, $bulan_list) + 1;
$month_num = str_pad($month_num, 2, '0', STR_PAD_LEFT);

// Dapatkan data kewangan bulanan
$kewangan_sql = "SELECT * FROM kewangan_bulanan WHERE bulan = ? AND tahun = ?";
$kewangan_stmt = mysqli_prepare($conn, $kewangan_sql);
mysqli_stmt_bind_param($kewangan_stmt, "si", $bulan_selected, $tahun_selected);
mysqli_stmt_execute($kewangan_stmt);
$kewangan_result = mysqli_stmt_get_result($kewangan_stmt);
$kewangan_data = mysqli_fetch_assoc($kewangan_result);

// Dapatkan butiran penyewa untuk bulan ini
$detail_sql = "SELECT * FROM kewangan_penyewa 
               WHERE id_kewangan = ? 
               ORDER BY no_rumah ASC";
$detail_stmt = null;
$detail_result = null;

if ($kewangan_data) {
    $detail_stmt = mysqli_prepare($conn, $detail_sql);
    mysqli_stmt_bind_param($detail_stmt, "i", $kewangan_data['id_kewangan']);
    mysqli_stmt_execute($detail_stmt);
    $detail_result = mysqli_stmt_get_result($detail_stmt);
}

// Dapatkan senarai bulan yang ada data
$history_sql = "SELECT *
                FROM kewangan_bulanan 
                ORDER BY tahun DESC, FIELD(bulan, 
                    'Januari','Februari','Mac','April','Mei','Jun',
                    'Julai','Ogos','September','Oktober','November','Disember') DESC";
$history_result = mysqli_query($conn, $history_sql);

// Data graf tahunan: sentiasa bina 12 bulan supaya bulan tanpa rekod tetap kelihatan.
$year_chart_sql = "SELECT bulan,
                          SUM(total_sewa_dijangka) AS total_sewa_dijangka,
                          SUM(total_kutipan) AS total_kutipan,
                          SUM(total_tunggakan) AS total_tunggakan
                   FROM kewangan_bulanan
                   WHERE tahun = ?
                   GROUP BY bulan";
$year_chart_stmt = mysqli_prepare($conn, $year_chart_sql);
mysqli_stmt_bind_param($year_chart_stmt, "i", $tahun_selected);
mysqli_stmt_execute($year_chart_stmt);
$year_chart_result = mysqli_stmt_get_result($year_chart_stmt);
$year_chart_lookup = [];
while ($chart_row = mysqli_fetch_assoc($year_chart_result)) {
    $year_chart_lookup[$chart_row['bulan']] = $chart_row;
}
$year_chart = [];
$chart_has_data = false;
$annual_expected = 0.0;
$annual_collected = 0.0;
$annual_arrears = 0.0;
foreach ($bulan_list as $chart_month) {
    $chart_row = $year_chart_lookup[$chart_month] ?? null;
    if ($chart_row) {
        $chart_has_data = true;
    }
    $year_chart[] = [
        'bulan' => $chart_month,
        'sewa' => (float) ($chart_row['total_sewa_dijangka'] ?? 0),
        'kutipan' => (float) ($chart_row['total_kutipan'] ?? 0),
        'tunggakan' => (float) ($chart_row['total_tunggakan'] ?? 0),
    ];
    $annual_expected += (float) ($chart_row['total_sewa_dijangka'] ?? 0);
    $annual_collected += (float) ($chart_row['total_kutipan'] ?? 0);
    $annual_arrears += (float) ($chart_row['total_tunggakan'] ?? 0);
}
$annual_month_count = count($year_chart_lookup);
$annual_collection_rate = $annual_expected > 0 ? ($annual_collected / $annual_expected) * 100 : 0;

// Koordinat SVG dibina di server supaya graf tidak bergantung pada CDN/JavaScript luar.
$chart_max = 1.0;
foreach ($year_chart as $chart_item) {
    $chart_max = max($chart_max, $chart_item['sewa'], $chart_item['kutipan'], $chart_item['tunggakan']);
}
$chart_series = ['sewa' => [], 'kutipan' => [], 'tunggakan' => []];
foreach ($year_chart as $chart_index => $chart_item) {
    $x = 70 + ($chart_index * (900 / 11));
    foreach (array_keys($chart_series) as $series_key) {
        $y = 240 - (($chart_item[$series_key] / $chart_max) * 200);
        $chart_series[$series_key][] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'value' => $chart_item[$series_key],
            'bulan' => $chart_item['bulan'],
        ];
    }
}
?>

<style>
    .finance-page { --finance-blue: #1e40af; --finance-soft: #f8fafc; }
    .finance-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:20px; }
    .finance-heading h1 { font-size:24px; font-weight:800; letter-spacing:-.5px; margin:0 0 5px; }
    .finance-heading p { margin:0; color:var(--muted); font-size:14px; }
    .finance-filter { background:#fff; border:1px solid var(--border); border-radius:14px; padding:14px 16px; margin-bottom:18px; box-shadow:var(--shadow-sm); }
    .finance-filter form { display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
    .finance-filter .filter-field { min-width:180px; flex:1; max-width:260px; }
    .finance-filter .btn-view { min-height:43px; padding:9px 20px; border:0; border-radius:8px; background:var(--ink); color:#fff; font-size:14px; font-weight:700; }
    .finance-filter .filter-note { margin-left:auto; align-self:center; color:var(--muted); font-size:12.5px; }
    .finance-overview { background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:18px; }
    .overview-top { padding:20px 22px 4px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; }
    .section-kicker { color:var(--primary); font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; margin-bottom:5px; }
    .section-title { font-size:17px; font-weight:750; margin:0; color:var(--ink); }
    .section-copy { color:var(--muted); font-size:12.5px; margin:5px 0 0; }
    .year-pill { background:var(--primary-light); color:var(--primary-dark); border-radius:999px; padding:7px 12px; font-size:12px; font-weight:750; white-space:nowrap; }
    .annual-metrics { display:grid; grid-template-columns:repeat(4,1fr); margin:16px 22px 0; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    .annual-metric { padding:13px 15px; background:var(--finance-soft); border-right:1px solid var(--border); }
    .annual-metric:last-child { border-right:0; }
    .annual-metric span { display:block; color:var(--muted); font-size:11.5px; margin-bottom:3px; }
    .annual-metric strong { display:block; color:var(--ink); font-size:16px; font-weight:800; }
    .chart-area { padding:18px 20px 20px; min-height:320px; }
    .chart-svg-wrap { width:100%; overflow:hidden; }
    .finance-chart { display:block; width:100%; height:auto; min-height:260px; }
    .chart-grid { stroke:#e8edf5; stroke-width:1; }
    .chart-axis-label { fill:#7b8495; font-size:11px; font-family:Inter,sans-serif; }
    .chart-line { fill:none; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
    .chart-line.expected { stroke:#2563eb; }
    .chart-line.collected { stroke:#15803d; stroke-dasharray:8 5; }
    .chart-line.arrears { stroke:#c2410c; stroke-dasharray:3 5; }
    .chart-point { stroke:#fff; stroke-width:2; }
    .chart-point.expected { fill:#2563eb; }
    .chart-point.collected { fill:#15803d; }
    .chart-point.arrears { fill:#c2410c; }
    .chart-legend { display:flex; justify-content:center; gap:20px; flex-wrap:wrap; margin-top:4px; color:#475569; font-size:12px; }
    .chart-legend span { display:inline-flex; align-items:center; gap:7px; }
    .legend-line { width:20px; height:3px; border-radius:4px; background:#2563eb; }
    .legend-line.collected { background:repeating-linear-gradient(90deg,#15803d 0 7px,transparent 7px 11px); }
    .legend-line.arrears { background:repeating-linear-gradient(90deg,#c2410c 0 3px,transparent 3px 7px); }
    .chart-empty { min-height:260px; display:grid; place-items:center; text-align:center; color:var(--muted); }
    .chart-empty i { display:block; font-size:34px; color:#a8b6d4; margin-bottom:10px; }
    .chart-empty strong { display:block; color:var(--ink); margin-bottom:4px; }
    .sr-chart { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
    .month-panel { background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:18px; }
    .month-head { padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .month-actions { display:flex; align-items:center; gap:8px; }
    .btn-subtle { min-height:40px; border:1px solid var(--border); background:#fff; color:#475569; border-radius:8px; padding:8px 13px; font-size:12.5px; font-weight:650; }
    .btn-subtle:hover { background:#f8fafc; color:var(--primary-dark); }
    .btn-danger-quiet { color:#b42318; }
    .btn-danger-quiet:hover { color:#991b1b; background:#fff5f5; border-color:#fecaca; }
    .month-kpis { display:grid; grid-template-columns:repeat(4,1fr); padding:18px 22px; gap:12px; }
    .month-kpi { padding:14px 15px; border-radius:11px; background:var(--finance-soft); border:1px solid #edf0f5; }
    .month-kpi .label { color:var(--muted); font-size:11.5px; margin-bottom:5px; }
    .month-kpi .value { color:var(--ink); font-size:19px; font-weight:800; letter-spacing:-.3px; }
    .month-kpi.is-success .value { color:#0f7a3d; }
    .month-kpi.is-danger .value { color:#c1272d; }
    .finance-table-wrap { border-top:1px solid var(--border); }
    .finance-table-meta { padding:12px 22px; display:flex; justify-content:space-between; align-items:center; color:var(--muted); font-size:12.5px; background:#fbfcfe; }
    .finance-table-meta strong { color:var(--ink); }
    .month-empty { padding:48px 20px; text-align:center; }
    .month-empty i { color:#b7c2d7; font-size:36px; margin-bottom:12px; }
    .month-empty h3 { font-size:16px; font-weight:750; margin-bottom:5px; }
    .month-empty p { color:var(--muted); font-size:13px; margin-bottom:16px; }
    .history-disclosure { background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm); }
    .history-disclosure summary { list-style:none; cursor:pointer; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; font-size:14px; font-weight:700; }
    .history-disclosure summary::-webkit-details-marker { display:none; }
    .history-disclosure summary i { color:var(--primary); margin-right:8px; }
    .history-disclosure summary .chevron { color:var(--muted); transition:transform .2s ease; }
    .history-disclosure[open] summary .chevron { transform:rotate(180deg); }
    .history-content { border-top:1px solid var(--border); }
    .feedback-banner { display:flex; align-items:center; gap:10px; padding:12px 14px; margin-bottom:16px; }
    .finance-page + .modal:not(.show), .modal:not(.show) { display:none; }
    @media (max-width:900px) { .annual-metrics,.month-kpis { grid-template-columns:repeat(2,1fr); } .annual-metric:nth-child(2) { border-right:0; } .annual-metric:nth-child(-n+2) { border-bottom:1px solid var(--border); } }
    @media (max-width:600px) {
        .finance-heading,.month-head,.overview-top { flex-direction:column; align-items:stretch; }
        .finance-heading .btn-add { justify-content:center; }
        .finance-filter .filter-field { max-width:none; min-width:calc(50% - 6px); }
        .finance-filter .btn-view { flex:1 0 100%; }
        .finance-filter .filter-note { margin-left:0; }
        .annual-metrics,.month-kpis { grid-template-columns:1fr; }
        .annual-metric { border-right:0; border-bottom:1px solid var(--border); }
        .annual-metric:last-child { border-bottom:0; }
        .finance-chart { min-height:220px; }
        .month-actions { width:100%; }
        .month-actions .btn-subtle,.month-actions form { flex:1; }
        .month-actions form .btn-subtle { width:100%; }
    }
    @media (max-width:700px) {
        .finance-table-wrap .table-responsive { overflow:visible; }
        .finance-table-wrap .table thead { display:none; }
        .finance-table-wrap .table,.finance-table-wrap .table tbody { display:block; }
        .finance-table-wrap .table tbody tr { display:grid; grid-template-columns:1fr 1fr; gap:0; margin:12px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:#fff; }
        .finance-table-wrap .table tbody td { display:block; padding:10px 12px; border:0; min-width:0; }
        .finance-table-wrap .table tbody td::before { content:attr(data-label); display:block; color:var(--muted); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
        .finance-table-wrap .table tbody td:nth-last-child(1) { grid-column:1 / -1; text-align:left!important; border-top:1px solid var(--border); }
        .finance-table-wrap .table tbody td:nth-last-child(1) .btn-edit-action { width:100%; justify-content:center; min-height:40px; }
    }
    @media (prefers-reduced-motion:reduce) { .history-disclosure summary .chevron { transition:none; } }
    @media print { .finance-heading .btn-add,.finance-filter,.month-actions,.history-disclosure { display:none!important; } }
</style>

<div class="page-wrapper finance-page">
    <header class="finance-heading">
        <div>
            <h1>Kewangan</h1>
            <p>Pantau kutipan tahunan dan urus bayaran penyewa mengikut bulan.</p>
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalGenerate">
            <i class="fas fa-plus" aria-hidden="true"></i> Jana data bulanan
        </button>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-danger feedback-banner" role="alert"><i class="fas fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success feedback-banner" role="status"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success) ?></span></div>
    <?php endif; ?>

    <section class="finance-filter" aria-label="Tapis butiran kewangan">
        <form method="GET">
            <div class="filter-field">
                <label class="form-label" for="filterMonth">Bulan</label>
                <select id="filterMonth" name="bulan" class="form-select">
                    <?php foreach ($bulan_list as $bulan): ?>
                        <option value="<?= $bulan ?>" <?= $bulan === $bulan_selected ? 'selected' : '' ?>><?= $bulan ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label class="form-label" for="filterYear">Tahun</label>
                <select id="filterYear" name="tahun" class="form-select">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $tahun_selected ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-view"><i class="fas fa-arrow-right me-1"></i> Lihat bulan</button>
            <span class="filter-note"><i class="fas fa-circle-info me-1"></i> Graf mengikut tahun, butiran mengikut bulan.</span>
        </form>
    </section>

    <section class="finance-overview" aria-labelledby="annualTitle">
        <div class="overview-top">
            <div>
                <div class="section-kicker">Ringkasan tahunan</div>
                <h2 class="section-title" id="annualTitle">Aliran kewangan <?= $tahun_selected ?></h2>
                <p class="section-copy"><?= $annual_month_count ?> bulan mempunyai rekod.</p>
            </div>
            <span class="year-pill"><i class="fas fa-calendar-days me-1"></i><?= $tahun_selected ?></span>
        </div>
        <div class="annual-metrics">
            <div class="annual-metric"><span>Sewa dijangka</span><strong>RM <?= number_format($annual_expected, 2) ?></strong></div>
            <div class="annual-metric"><span>Kutipan diterima</span><strong>RM <?= number_format($annual_collected, 2) ?></strong></div>
            <div class="annual-metric"><span>Jumlah tunggakan</span><strong>RM <?= number_format($annual_arrears, 2) ?></strong></div>
            <div class="annual-metric"><span>Kadar kutipan</span><strong><?= number_format($annual_collection_rate, $annual_collection_rate > 0 && $annual_collection_rate < 1 ? 2 : 1) ?>%</strong></div>
        </div>
        <div class="chart-area">
            <?php if ($chart_has_data): ?>
                <div class="chart-svg-wrap">
                    <svg class="finance-chart" viewBox="0 0 1000 285" role="img" aria-labelledby="chartTitle chartDesc">
                        <title id="chartTitle">Graf aliran kewangan bulanan <?= $tahun_selected ?></title>
                        <desc id="chartDesc">Perbandingan sewa dijangka, kutipan lunas dan tunggakan untuk dua belas bulan.</desc>
                        <?php for ($grid = 0; $grid <= 4; $grid++): ?>
                            <?php $grid_y = 40 + ($grid * 50); $grid_value = $chart_max * ((4 - $grid) / 4); ?>
                            <line class="chart-grid" x1="70" y1="<?= $grid_y ?>" x2="970" y2="<?= $grid_y ?>" />
                            <text class="chart-axis-label" x="62" y="<?= $grid_y + 4 ?>" text-anchor="end"><?= $grid === 4 ? 'RM 0' : 'RM ' . number_format($grid_value, 0) ?></text>
                        <?php endfor; ?>
                        <?php foreach ($year_chart as $chart_index => $chart_item): ?>
                            <?php $label_x = 70 + ($chart_index * (900 / 11)); ?>
                            <text class="chart-axis-label" x="<?= round($label_x, 2) ?>" y="267" text-anchor="middle"><?= htmlspecialchars(substr($chart_item['bulan'], 0, 3)) ?></text>
                        <?php endforeach; ?>
                        <polyline class="chart-line expected" points="<?= implode(' ', array_map(fn($p) => $p['x'] . ',' . $p['y'], $chart_series['sewa'])) ?>" />
                        <polyline class="chart-line collected" points="<?= implode(' ', array_map(fn($p) => $p['x'] . ',' . $p['y'], $chart_series['kutipan'])) ?>" />
                        <polyline class="chart-line arrears" points="<?= implode(' ', array_map(fn($p) => $p['x'] . ',' . $p['y'], $chart_series['tunggakan'])) ?>" />
                        <?php foreach (['sewa' => 'expected', 'kutipan' => 'collected', 'tunggakan' => 'arrears'] as $series_key => $series_class): ?>
                            <?php foreach ($chart_series[$series_key] as $point): ?>
                                <circle class="chart-point <?= $series_class ?>" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="4">
                                    <title><?= htmlspecialchars($point['bulan']) ?>: RM <?= number_format($point['value'], 2) ?></title>
                                </circle>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </svg>
                    <div class="chart-legend" aria-hidden="true">
                        <span><i class="legend-line"></i>Sewa dijangka</span>
                        <span><i class="legend-line collected"></i>Kutipan lunas</span>
                        <span><i class="legend-line arrears"></i>Tunggakan</span>
                    </div>
                </div>
                <p class="sr-chart">Perbandingan sewa dijangka, kutipan dan tunggakan bagi Januari hingga Disember <?= $tahun_selected ?>.</p>
            <?php else: ?>
                <div class="chart-empty"><div><i class="fas fa-chart-line"></i><strong>Belum ada rekod untuk <?= $tahun_selected ?></strong><span>Jana data bulanan untuk mula melihat trend.</span></div></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="month-panel" aria-labelledby="monthTitle">
        <div class="month-head">
            <div>
                <div class="section-kicker">Butiran bulan</div>
                <h2 class="section-title" id="monthTitle"><?= htmlspecialchars($bulan_selected) ?> <?= $tahun_selected ?></h2>
            </div>
            <?php if ($kewangan_data): ?>
                <div class="month-actions">
                    <button type="button" class="btn-subtle" onclick="window.print()"><i class="fas fa-print me-1"></i> Cetak</button>
                    <form method="POST" onsubmit="return confirm('Padam data <?= htmlspecialchars($bulan_selected) ?> <?= $tahun_selected ?>? Tindakan ini tidak boleh dibatalkan.')">
                        <input type="hidden" name="id_kewangan" value="<?= (int)$kewangan_data['id_kewangan'] ?>">
                        <button type="submit" name="delete_kewangan" class="btn-subtle btn-danger-quiet"><i class="fas fa-trash me-1"></i> Padam</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($kewangan_data): ?>
            <div class="month-kpis">
                <div class="month-kpi"><div class="label">Sewa dijangka</div><div class="value">RM <?= number_format($kewangan_data['total_sewa_dijangka'], 2) ?></div></div>
                <div class="month-kpi is-success"><div class="label">Kutipan lunas</div><div class="value">RM <?= number_format($kewangan_data['total_kutipan'], 2) ?></div></div>
                <div class="month-kpi is-danger"><div class="label">Tunggakan</div><div class="value">RM <?= number_format($kewangan_data['total_tunggakan'], 2) ?></div></div>
                <div class="month-kpi"><div class="label">Penyewa aktif</div><div class="value"><?= (int)$kewangan_data['bilangan_penyewa'] ?></div></div>
            </div>
            <div class="finance-table-wrap">
                <div class="finance-table-meta"><strong>Bayaran penyewa</strong><span><?= mysqli_num_rows($detail_result) ?> rekod</span></div>
                <div class="table-responsive">
                    <table class="table" id="kewanganTable">
                        <thead><tr><th>Rumah</th><th>Penyewa</th><th>Sewa</th><th>Status</th><th>Tarikh bayar</th><th><span class="visually-hidden">Tindakan</span></th></tr></thead>
                        <tbody>
                        <?php if ($detail_result && mysqli_num_rows($detail_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($detail_result)): ?>
                                <?php
                                $badge_class = $row['status_bayaran'] === 'Lunas' ? 'lunas' : ($row['status_bayaran'] === 'Pending' ? 'pending' : 'belum');
                                $badge_icon = $row['status_bayaran'] === 'Lunas' ? 'fa-check-circle' : ($row['status_bayaran'] === 'Pending' ? 'fa-clock' : 'fa-exclamation-circle');
                                ?>
                                <tr>
                                    <td data-label="Rumah"><strong><?= htmlspecialchars($row['no_rumah']) ?></strong></td>
                                    <td data-label="Penyewa"><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                                    <td data-label="Sewa">RM <?= number_format($row['sewa_bulanan'], 2) ?></td>
                                    <td data-label="Status"><span class="badge-status <?= $badge_class ?>"><i class="fas <?= $badge_icon ?> me-1"></i><?= htmlspecialchars($row['status_bayaran']) ?></span></td>
                                    <td data-label="Tarikh bayar"><?= $row['tarikh_bayar'] ? date('d/m/Y H:i', strtotime($row['tarikh_bayar'])) : '<span class="text-muted">—</span>' ?></td>
                                    <td data-label="Tindakan" class="text-end">
                                        <button type="button" class="btn-edit-action js-edit-status"
                                                data-row='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'
                                                data-month="<?= htmlspecialchars($bulan_selected) ?>" data-year="<?= $tahun_selected ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditStatus" aria-label="Kemaskini status <?= htmlspecialchars($row['nama_penyewa']) ?>">
                                            <i class="fas fa-pen"></i> Ubah
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><h6>Tiada penyewa aktif</h6><p>Tiada rekod penyewa untuk bulan ini.</p></div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="month-empty">
                <i class="fas fa-calendar-xmark"></i>
                <h3>Belum ada data <?= htmlspecialchars($bulan_selected) ?> <?= $tahun_selected ?></h3>
                <p>Jana rekod bulan ini apabila anda sudah bersedia.</p>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalGenerate"><i class="fas fa-plus"></i> Jana bulan ini</button>
            </div>
        <?php endif; ?>
    </section>

    <details class="history-disclosure">
        <summary><span><i class="fas fa-clock-rotate-left"></i> Sejarah kewangan</span><i class="fas fa-chevron-down chevron"></i></summary>
        <div class="history-content table-responsive">
            <table class="table table-history">
                <thead><tr><th>Bulan</th><th>Sewa dijangka</th><th>Kutipan</th><th>Tunggakan</th><th>Penyewa</th><th></th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($history_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['bulan']) ?> <?= (int)$row['tahun'] ?></strong></td>
                            <td>RM <?= number_format($row['total_sewa_dijangka'], 2) ?></td>
                            <td>RM <?= number_format($row['total_kutipan'], 2) ?></td>
                            <td>RM <?= number_format($row['total_tunggakan'], 2) ?></td>
                            <td><?= (int)$row['bilangan_penyewa'] ?></td>
                            <td class="text-end"><a href="?bulan=<?= urlencode($row['bulan']) ?>&tahun=<?= (int)$row['tahun'] ?>" class="btn-edit-action"><i class="fas fa-arrow-right"></i> Buka</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada sejarah kewangan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>

<!-- ============================================ -->
<!-- MODAL GENERATE KEWANGAN -->
<!-- ============================================ -->
<div class="modal fade" id="modalGenerate" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-coins me-2"></i> Jana Data Kewangan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Data kewangan akan dijana berdasarkan penyewa yang aktif pada bulan tersebut.
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bulan</label>
                        <select name="bulan" class="form-select" required>
                            <?php foreach ($bulan_list as $bulan): ?>
                                <option value="<?= $bulan ?>" <?= $bulan == $bulan_selected ? 'selected' : '' ?>>
                                    <?= $bulan ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tahun</label>
                        <select name="tahun" class="form-select" required>
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $tahun_selected ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-batal" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Batal
                    </button>
                    <button type="submit" name="generate_kewangan" class="btn-simpan">
                        <i class="fas fa-save me-2"></i> Jana
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDIT STATUS BAYARAN -->
<!-- ============================================ -->
<div class="modal fade" id="modalEditStatus" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Kemaskini Status Bayaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_kewangan_penyewa" id="edit_id_kewangan_penyewa">
                    <input type="hidden" name="id_sewa" id="edit_id_sewa">
                    <input type="hidden" name="bulan" id="edit_bulan">
                    <input type="hidden" name="tahun" id="edit_tahun">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penyewa</label>
                        <p class="fw-bold" id="edit_nama_penyewa"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No Rumah</label>
                        <p class="fw-bold" id="edit_no_rumah"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Bayaran</label>
                        <select name="status_baru" class="form-select" required>
                            <option value="Lunas">Lunas</option>
                            <option value="Pending">Pending</option>
                            <option value="Belum Lunas">Belum Lunas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-batal" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Batal
                    </button>
                    <button type="submit" name="update_status_bayaran" class="btn-simpan">
                        <i class="fas fa-save me-2"></i> Kemaskini
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editStatus(data, bulan, tahun) {
        document.getElementById('edit_id_kewangan_penyewa').value = data.id_kewangan_penyewa;
        document.getElementById('edit_id_sewa').value = data.id_sewa;
        document.getElementById('edit_bulan').value = bulan;
        document.getElementById('edit_tahun').value = tahun;
        document.getElementById('edit_nama_penyewa').textContent = data.nama_penyewa;
        document.getElementById('edit_no_rumah').textContent = data.no_rumah;
        
        // Pilih status semasa
        var select = document.querySelector('select[name="status_baru"]');
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === data.status_bayaran) {
                select.selectedIndex = i;
                break;
            }
        }
    }

    document.querySelectorAll('.js-edit-status').forEach(function (button) {
        button.addEventListener('click', function () {
            editStatus(JSON.parse(button.dataset.row), button.dataset.month, button.dataset.year);
        });
    });
</script>

<?php include("footer.php"); ?>
