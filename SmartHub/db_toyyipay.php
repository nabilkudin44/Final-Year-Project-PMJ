<?php
// db_toyyipay.php — ToyyibPay Configuration
// ============================================
// GANTIKAN nilai di bawah dengan Secret Key & Category Code
// SEBENAR anda dari dashboard ToyyibPay (Settings > User Secret Key).
// JANGAN share fail ini atau paste nilainya di mana-mana (chat, GitHub
// public repo, forum, dll). Kalau key pernah terdedah, regenerate
// segera di dashboard ToyyibPay.
// ============================================

// Guard supaya define() tak berulang walaupun fail ini include() lebih
// sekali dalam permintaan yang sama (elak "Warning: Constant already defined").
if (!defined('TOYYIBPAY_SECRET_KEY')) {

    define('TOYYIBPAY_SECRET_KEY', 'z4we25dg-686z-7k17-03vd-z00r19yb9wxm');
    define('TOYYIBPAY_CATEGORY_CODE', 'xazz2grf');
    define('TOYYIBPAY_SANDBOX', true); // true = testing (dev.toyyibpay.com), false = production (toyyibpay.com)

    // ============================================
    // RETURN URL & CALLBACK URL
    // ============================================
    // billReturnUrl  -> redirect BROWSER pengguna lepas bayar. Boleh guna
    //                   localhost / domain .test Laragon sebab ini cuma
    //                   redirect di browser pengguna sendiri.
    // billCallbackUrl -> webhook yang dipanggil oleh SERVER ToyyibPay untuk
    //                    confirm status bayaran secara automatik. Ini WAJIB
    //                    boleh diakses dari internet awam — "localhost" atau
    //                    "127.0.0.1" TIDAK akan berfungsi untuk ini.
    //                    Untuk testing tempatan, guna ngrok (https://ngrok.com)
    //                    untuk dapatkan URL awam sementara, contoh:
    //                    https://abcd1234.ngrok-free.app/payment_callback.php
    // ============================================

    // Tukar 'smartrent.test' ikut domain Laragon / localhost path projek anda.
    define('TOYYIBPAY_RETURN_URL', 'http://smartrent.test/payment_return.php');
    define('TOYYIBPAY_CALLBACK_URL', 'http://smartrent.test/payment_callback.php');

}
