<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli ("localhost","root","","rumah_sewa");

if($conn->error){
    die("connection error").$conn->error;
}


?>