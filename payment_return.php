<?php
include("db.php");
include_once("db_toyyipay.php");

// ============================================
// PAYMENT RETURN - TOYYIBPAY
// ============================================

// ToyyibPay biasanya hantar status_id dan billcode melalui query string.
// Sesetengah kaedah pembayaran menghantar data melalui POST, jadi sokong kedua-duanya.
$return_data = array_merge($_GET, $_POST);

if (isset($return_data['id_bayaran']) && isset($return_data['status_id'])) {
    $id_bayaran = (int) $return_data['id_bayaran'];
    $status_id = (string) $return_data['status_id'];
    // 1 = success, 2 = pending, 3 = failed
    
    // Log untuk debugging
    error_log("Payment Return - ID Bayaran: $id_bayaran, Status ID: $status_id");
    
    if ($status_id == 1) {
        // Payment success
        $status = 'Lunas';
        $tarikh_bayar = date('Y-m-d H:i:s');
        
        $sql = "UPDATE bayaran SET status = ?, tarikh_bayar = ? WHERE id_bayaran = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $tarikh_bayar, $id_bayaran);
        
        if (mysqli_stmt_execute($stmt)) {
            $payment_sql = "SELECT id_sewa, bulan, tahun FROM bayaran WHERE id_bayaran = ?";
            $payment_stmt = mysqli_prepare($conn, $payment_sql);
            mysqli_stmt_bind_param($payment_stmt, "i", $id_bayaran);
            mysqli_stmt_execute($payment_stmt);
            $payment_row = mysqli_fetch_assoc(mysqli_stmt_get_result($payment_stmt));
            if ($payment_row && $payment_row['bulan'] && $payment_row['tahun']) {
                syncKewanganBayaran($conn, (int)$payment_row['id_sewa'], $payment_row['bulan'], (int)$payment_row['tahun']);
            }
            $_SESSION['payment_message'] = "Bayaran berjaya! Terima kasih.";
            $_SESSION['payment_type'] = "success";
        } else {
            $_SESSION['payment_message'] = "Ralat: Gagal update status bayaran.";
            $_SESSION['payment_type'] = "danger";
        }
        
    } else if ($status_id == 2) {
        $_SESSION['payment_message'] = "Bayaran dalam proses. Sila semak semula nanti.";
        $_SESSION['payment_type'] = "warning";
    } else {
        $_SESSION['payment_message'] = "Bayaran gagal. Sila cuba lagi.";
        $_SESSION['payment_type'] = "danger";
    }
    
    // Selepas pulang daripada ToyyibPay, tunjukkan sejarah/status bayaran.
    header("Location: bayaran.php?payment=1");
    exit();
    
} else {
    // No parameter - redirect ke dashboard
    header("Location: bayaran.php");
    exit();
}
?>
