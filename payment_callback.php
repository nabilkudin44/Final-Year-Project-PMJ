<?php
include("db.php");
include("db_toyyipay.php");

// ============================================
// PAYMENT CALLBACK - TOYYIBPAY
// ============================================

// ToyyibPay akan POST data ke sini
$input = file_get_contents('php://input');
parse_str($input, $data);

// Log untuk debugging
error_log("Callback received: " . print_r($data, true));

// Atau melalui GET (bergantung pada konfigurasi)
if (isset($_GET['billcode']) && isset($_GET['status_id'])) {
    $bill_code = $_GET['billcode'];
    $status_id = $_GET['status_id'];
    
    // Verify bill dengan API ToyyibPay
    $api_url = (TOYYIBPAY_SANDBOX ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/') . 
               'index.php/api/getBillTransactions';
    
    $post_data = ['billCode' => $bill_code];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_URL, $api_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    
    $result = curl_exec($curl);
    curl_close($curl);
    
    $response = json_decode($result);
    
    // Log response
    error_log("API Response: " . print_r($response, true));
    
    if ($response && isset($response[0])) {
        $transaction = $response[0];
        $status = ($transaction->billpaymentStatus == '1') ? 'Lunas' : 'Belum Lunas';
        $tarikh_bayar = date('Y-m-d H:i:s', strtotime($transaction->billpaymentPaidDate));
        
        // Update status bayaran dalam database
        $sql = "UPDATE bayaran SET status = ?, tarikh_bayar = ? WHERE bill_code = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $status, $tarikh_bayar, $bill_code);
        mysqli_stmt_execute($stmt);
        
        error_log("Bayaran updated: $bill_code -> $status");
    }
}

// Return response untuk ToyyibPay
echo "OK";
?>