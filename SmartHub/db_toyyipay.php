<?php
// db_toyyipay.php — ToyyibPay Configuration (LIVE / PRODUCTION)
// ============================================
// ⚠️ PERINGATAN PENTING UNTUK LIVE:
// 1. Secret Key dan Category Code MESTI dari portal LIVE (toyyibpay.com)
// 2. URL Callback MESTI guna domain sebenar (boleh diakses dari internet)
// 3. JANGAN gunakan localhost / .test untuk callback
// ============================================

if (!defined('TOYYIBPAY_SECRET_KEY')) {

    // ============================================
    // 🔑 GANTIKAN DENGAN KEY DARI PORTAL LIVE
    // ============================================
    // Log masuk ke https://toyyibpay.com
    // Settings > User Secret Key -> salin key baru
    define('TOYYIBPAY_SECRET_KEY', 'z4we25dg-686z-7k17-03vd-z00r19yb9wxm');

    // Category > pilih/buat kategori -> salin Category Code
    define('TOYYIBPAY_CATEGORY_CODE', 'xazz2grf');

    // ⚠️ TUKAR ke false untuk PRODUCTION / LIVE
    define('TOYYIBPAY_SANDBOX', false);

    // ============================================
    // 🌐 URL UNTUK LIVE - GUNA DOMAIN SEBENAR
    // ============================================
    // Gantikan 'domain-anda.com' dengan domain sebenar anda
    // Contoh: https://smartrent.com.my/payment_return.php
    // ============================================

    define('TOYYIBPAY_RETURN_URL', 'https://domain-anda.com/payment_return.php');
    define('TOYYIBPAY_CALLBACK_URL', 'https://domain-anda.com/payment_callback.php');

}
?>
