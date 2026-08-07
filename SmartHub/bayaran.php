<?php
include("db.php");
include_once("db_toyyipay.php");
include("header_penyewa.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penyewa') {
    header("Location: login.php");
    exit();
}

// ============================================
// AMBIL MAKLUMAT SEWAAN PENYEWA
// ============================================
$id_penyewa = $_SESSION['user_id'];

$sql_sewa = "SELECT s.id_sewa, r.no_rumah, r.harga_sewa, r.id_rumah 
             FROM sewa s 
             JOIN rumah r ON s.id_rumah = r.id_rumah 
             WHERE s.id_penyewa = ? AND r.status = 'Disewa'";
             
$stmt = mysqli_prepare($conn, $sql_sewa);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);
$result_sewa = mysqli_stmt_get_result($stmt);
$sewa = mysqli_fetch_assoc($result_sewa);

// ============================================
// PROSES BAYARAN DENGAN TOYYIBPAY
// ============================================
if (isset($_POST['bayar'])) {
    $id_sewa = $_POST['id_sewa'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $jumlah = $_POST['jumlah'];
    $id_penyewa = $_SESSION['user_id'];
    
    // Ambil maklumat penyewa
    $sql = "SELECT nama, email, no_telefon FROM penyewa WHERE id_penyewa = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penyewa = mysqli_fetch_assoc($result);
    
    // Reference number unik
    $ref_no = 'SEWA-' . time() . '-' . rand(1000, 9999);
    
    // Simpan rekod bayaran sementara (status Pending dulu)
    $sql = "INSERT INTO bayaran (id_sewa, bulan, tahun, jumlah, status, tarikh_bayar, ref_no) 
            VALUES (?, ?, ?, ?, 'Pending', NULL, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isiss", $id_sewa, $bulan, $tahun, $jumlah, $ref_no);
    mysqli_stmt_execute($stmt);
    $id_bayaran = mysqli_insert_id($conn);
    
    // ============================================
    // HUBUNGI API TOYYIBPAY UNTUK BUAT BILL
    // ============================================
    
    // Pastikan URL betul
    $api_url = TOYYIBPAY_SANDBOX ? 
        'https://dev.toyyibpay.com/index.php/api/createBill' : 
        'https://toyyibpay.com/index.php/api/createBill';
    
    // Pastikan amount dalam sen (integer)
    $billAmount = $jumlah * 100;
    
    $data = [
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billName' => 'Sewaan Rumah - ' . $bulan . ' ' . $tahun,
        'billDescription' => 'Bayaran sewa untuk bulan ' . $bulan . ' ' . $tahun,
        'billPriceSetting' => 1,
        'billPayorInfo' => 1,
        'billAmount' => (string)$billAmount,
        'billReturnUrl' => TOYYIBPAY_RETURN_URL . '?id_bayaran=' . $id_bayaran,
        'billCallbackUrl' => TOYYIBPAY_CALLBACK_URL,
        'billExternalReferenceNo' => $ref_no,
        'billTo' => $penyewa['nama'],
        'billEmail' => $penyewa['email'],
        'billPhone' => $penyewa['no_telefon'],
        'billPaymentChannel' => '0',
        'billChargeToCustomer' => '2'
    ];
    
    // Debug - log data
    error_log("ToyyibPay Request: " . print_r($data, true));

    if (!function_exists('curl_init')) {
        $error = "Extension 'curl' tidak aktif dalam PHP. Buka php.ini, cari <code>;extension=curl</code>, buang tanda ';' di depan, restart Apache.";
    } else {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);

        $result_api = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        // Debug - log response
        error_log("ToyyibPay Response: " . $result_api);
        error_log("HTTP Code: " . $http_code);
        error_log("CURL Error: " . $curl_error);

        if ($curl_error) {
            $error = "CURL Error: " . $curl_error . " — semak sambungan internet server dan firewall.";
        } elseif (empty($result_api)) {
            $error = "Tiada respons dari ToyyibPay (HTTP $http_code). Cuba semak firewall/antivirus yang mungkin sekat sambungan keluar.";
        } else {
            $response = json_decode($result_api);

            // SEMAK RESPONSE DENGAN BETUL
            if ($response && is_array($response) && isset($response[0]->BillCode)) {
                $bill_code = $response[0]->BillCode;

                // Update record dengan bill code
                $sql = "UPDATE bayaran SET bill_code = ? WHERE id_bayaran = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $bill_code, $id_bayaran);
                mysqli_stmt_execute($stmt);

                // Redirect ke halaman pembayaran ToyyibPay
                $payment_url = (TOYYIBPAY_SANDBOX ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/') . $bill_code;
                header("Location: " . $payment_url);
                exit();
            } else {
                if (stripos($result_api, 'KEY-DID-NOT-EXIST') !== false) {
                    $error = "Secret Key / Category Code tidak sah atau akaun ToyyibPay tidak aktif. Sila sahkan: (1) key diambil dari dev.toyyibpay.com jika TOYYIBPAY_SANDBOX=true, (2) category code dicipta di bawah akaun yang sama, (3) akaun sudah disahkan/aktif.";
                } elseif (stripos($result_api, 'invalid') !== false) {
                    $error = "Data yang dihantar ke ToyyibPay tidak sah. Semak semula maklumat bil.";
                } else {
                    $error = "Gagal mencipta bil. Sila cuba lagi.";
                }
                $error .= "<br><small>Response: " . htmlspecialchars($result_api) . "</small>";
                if (isset($response->message)) {
                    $error .= "<br><small>Message: " . htmlspecialchars($response->message) . "</small>";
                }
            }
        }
    }
}

// ============================================
// AMBIL SEJARAH BAYARAN
// ============================================
$sql_histori = "SELECT 
                    b.id_bayaran,
                    b.tarikh_bayar,
                    b.jumlah,
                    b.status,
                    b.bulan,
                    b.tahun,
                    r.no_rumah
                FROM bayaran b
                INNER JOIN sewa s ON b.id_sewa = s.id_sewa
                INNER JOIN rumah r ON s.id_rumah = r.id_rumah
                WHERE s.id_penyewa = ?
                ORDER BY b.id_bayaran DESC";

$stmt_histori = mysqli_prepare($conn, $sql_histori);
mysqli_stmt_bind_param($stmt_histori, "i", $id_penyewa);
mysqli_stmt_execute($stmt_histori);
$result_histori = mysqli_stmt_get_result($stmt_histori);
?>

    <div class="page-wrapper">
        <!-- Header -->
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-credit-card"></i> Bayaran Sewa</h5>
            </div>
            <div class="card-body-custom">
                <?php if ($sewa): ?>
                    <div class="info-box">
                        <i class="fas fa-home"></i>
                        <strong>Rumah No: <?= htmlspecialchars($sewa['no_rumah']) ?></strong> 
                        | Harga Sewa: RM <?= number_format($sewa['harga_sewa'], 2) ?>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="id_sewa" value="<?= $sewa['id_sewa'] ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bulan</label>
                                <select class="form-select" name="bulan" required>
                                    <?php
                                    $bulan_list = ['Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
                                    $bulan_semasa = date('n') - 1;
                                    for ($i = 0; $i < 12; $i++) {
                                        $selected = ($i == $bulan_semasa) ? 'selected' : '';
                                        echo "<option value='{$bulan_list[$i]}' $selected>{$bulan_list[$i]}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tahun</label>
                                <select class="form-select" name="tahun" required>
                                    <?php
                                    $tahun_semasa = date('Y');
                                    for ($i = $tahun_semasa - 1; $i <= $tahun_semasa + 1; $i++) {
                                        $selected = ($i == $tahun_semasa) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Jumlah (RM)</label>
                                <input type="number" class="form-control" name="jumlah" 
                                       value="<?= $sewa['harga_sewa'] ?>" step="0.01" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="bayar" class="btn-bayar">
                                    <i class="fas fa-credit-card me-2"></i> Bayar
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-home"></i>
                        <h6>Tiada Sewaan Aktif</h6>
                        <p>Anda tidak mempunyai sewaan rumah yang aktif.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sejarah Bayaran -->
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-history"></i> Sejarah Bayaran</h5>
            </div>
            <div class="card-body-custom" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-history">
                        <thead>
                            <tr>
                                <th>No Rumah</th>
                                <th>Bulan</th>
                                <th>Tahun</th>
                                <th>Jumlah</th>
                                <th>Tarikh Bayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_histori) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result_histori)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['no_rumah']) ?></td>
                                    <td><?= $row['bulan'] ?? '-' ?></td>
                                    <td><?= $row['tahun'] ?? '-' ?></td>
                                    <td>RM <?= number_format($row['jumlah'] ?? 0, 2) ?></td>
                                    <td><?= $row['tarikh_bayar'] ? date('d/m/Y H:i', strtotime($row['tarikh_bayar'])) : '-' ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'Lunas'): ?>
                                            <span class="badge-status lunas"><i class="fas fa-check-circle me-1"></i> Lunas</span>
                                        <?php elseif ($row['status'] == 'Pending'): ?>
                                            <span class="badge-status pending"><i class="fas fa-clock me-1"></i> Pending</span>
                                        <?php elseif ($row['status'] == 'Belum Lunas'): ?>
                                            <span class="badge-status belum"><i class="fas fa-exclamation-circle me-1"></i> Belum Lunas</span>
                                        <?php else: ?>
                                            <span class="badge-status tiada">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-receipt"></i>
                                            <h6>Tiada Sejarah Bayaran</h6>
                                            <p>Anda belum membuat sebarang bayaran.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include("footer_penyewa.php"); ?>
