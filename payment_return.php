<?php
include("db.php");
include("db_toyyipay.php");

// ============================================
// PAYMENT RETURN - TOYYIBPAY
// ============================================

if (isset($_GET['id_bayaran']) && isset($_GET['status_id'])) {
    $id_bayaran = $_GET['id_bayaran'];
    $status_id = $_GET['status_id']; 
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
    
    header("Location: dashboard_tenant.php");
    exit();
    
} else {
    // No parameter - redirect ke dashboard
    header("Location: dashboard_tenant.php");
    exit();
}
?>