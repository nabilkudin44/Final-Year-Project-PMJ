<?php
include("db.php");
include ("header.php");

//Jumlah Rumah
$rumah = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah"));

//Rumah Disewa
$disewa = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah WHERE status='Disewa'"));

//Rumah Kosong
$kosong = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM rumah WHERE status='Kosong'"));

//Jumlah Penyewa
$penyewa = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM penyewa"));

//Jumlah Kutipan
$kutipan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Lunas'"));

//Jumlah Tunggakan
$tunggakan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(jumlah) AS total FROM bayaran WHERE status='Belum Lunas'"));

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Tuan Rumah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4">Dashboard Tuan Rumah</h2>
        
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No Rumah</th>
                    <th>Penyewa</th>
                    <th>Tarikh Masuk</th>
                    <th>Harga Sewa</th>
                    <th>Status Bayaran</th>
                    <th>Tindakan</th>
                    <th>Biodata Penyewa</th> <!-- Tambah kolum baru -->
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT
                    rumah.id_rumah,
                    rumah.no_rumah,
                    rumah.harga_sewa,
                    rumah.status,
                    penyewa.nama,
                    penyewa.id_penyewa,
                    sewa.id_sewa,
                    sewa.tarikh_masuk,
                    bayaran.status AS status_bayaran
                    FROM rumah
                    LEFT JOIN sewa ON rumah.id_rumah = sewa.id_rumah
                    LEFT JOIN penyewa ON penyewa.id_penyewa = sewa.id_penyewa
                    LEFT JOIN bayaran ON bayaran.id_sewa = sewa.id_sewa";
                
                $result = mysqli_query($conn, $sql);
                
                while($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                    <td><?= $row['no_rumah']; ?></td>
                    <td>
                        <?= empty($row['nama']) ? "Tiada Penyewa" : $row['nama']; ?>
                    </td>
                    <td>
                        <?= empty($row['tarikh_masuk']) ? "-" : $row['tarikh_masuk']; ?>
                    </td>
                    <td>
                        RM <?= number_format($row['harga_sewa'],2); ?>
                    </td>
                    <td>
                        <?php
                        if($row['status_bayaran'] == "Lunas") {
                            echo "<span class='badge bg-success'>Lunas</span>";
                        } else if($row['status_bayaran'] == "Belum Lunas") {
                            echo "<span class='badge bg-danger'>Belum Lunas</span>";
                        } else {
                            echo "<span class='badge bg-secondary'>Tiada Data</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="detailRumah.php?id=<?= $row['id_rumah']; ?>" 
                           class="btn btn-primary btn-sm">
                           Lihat
                        </a>
                    </td>
                    <td>
                        <?php if(!empty($row['id_penyewa'])): ?>
                            <a href="detail_penyewa.php?id=<?= $row['id_penyewa']; ?>"
                               class="btn btn-info btn-sm">
                               Lihat Biodata
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Tiada Penyewa</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>