<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli("127.0.0.1", "root", "", "rumah_sewa");

if ($conn->connect_error) {
    http_response_code(500);
    die("Sambungan database gagal: " . htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");

/**
 * Selaraskan snapshot kewangan bulanan selepas status bayaran berubah.
 * Pembayaran Lunas diberi keutamaan jika penyewa mempunyai beberapa cubaan bil.
 */
function syncKewanganBayaran(mysqli $conn, int $id_sewa, string $bulan, int $tahun): bool
{
    if ($id_sewa <= 0 || $bulan === '' || $tahun <= 0) {
        return false;
    }

    $payment_sql = "SELECT status, tarikh_bayar
                    FROM bayaran
                    WHERE id_sewa = ? AND bulan = ? AND tahun = ?
                    ORDER BY FIELD(status, 'Lunas', 'Pending', 'Belum Lunas'), id_bayaran DESC
                    LIMIT 1";
    $payment_stmt = mysqli_prepare($conn, $payment_sql);
    mysqli_stmt_bind_param($payment_stmt, "isi", $id_sewa, $bulan, $tahun);
    mysqli_stmt_execute($payment_stmt);
    $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($payment_stmt));
    mysqli_stmt_close($payment_stmt);

    if (!$payment) {
        return false;
    }

    $detail_sql = "UPDATE kewangan_penyewa kp
                   JOIN kewangan_bulanan kb ON kb.id_kewangan = kp.id_kewangan
                   SET kp.status_bayaran = ?, kp.tarikh_bayar = ?
                   WHERE kp.id_sewa = ? AND kb.bulan = ? AND kb.tahun = ?";
    $detail_stmt = mysqli_prepare($conn, $detail_sql);
    mysqli_stmt_bind_param(
        $detail_stmt,
        "ssisi",
        $payment['status'],
        $payment['tarikh_bayar'],
        $id_sewa,
        $bulan,
        $tahun
    );
    mysqli_stmt_execute($detail_stmt);
    $changed = mysqli_stmt_affected_rows($detail_stmt) >= 0;
    mysqli_stmt_close($detail_stmt);

    $total_sql = "UPDATE kewangan_bulanan kb
                  SET kb.total_kutipan = (
                          SELECT COALESCE(SUM(CASE WHEN kp.status_bayaran = 'Lunas' THEN kp.sewa_bulanan ELSE 0 END), 0)
                          FROM kewangan_penyewa kp WHERE kp.id_kewangan = kb.id_kewangan
                      ),
                      kb.total_tunggakan = (
                          SELECT COALESCE(SUM(CASE WHEN kp.status_bayaran <> 'Lunas' THEN kp.sewa_bulanan ELSE 0 END), 0)
                          FROM kewangan_penyewa kp WHERE kp.id_kewangan = kb.id_kewangan
                      )
                  WHERE kb.bulan = ? AND kb.tahun = ?";
    $total_stmt = mysqli_prepare($conn, $total_sql);
    mysqli_stmt_bind_param($total_stmt, "si", $bulan, $tahun);
    mysqli_stmt_execute($total_stmt);
    mysqli_stmt_close($total_stmt);

    return $changed;
}


?>
