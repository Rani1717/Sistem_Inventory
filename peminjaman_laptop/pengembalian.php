<?php
include 'koneksi.php';
session_start();

if (isset($_POST['pengembalian'])) {
    $id = $_POST['id'];
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
    
    $target_dir = "uploads/";
    $bukti_pengembalian = $target_dir . basename($_FILES["bukti_pengembalian"]["name"]);
    move_uploaded_file($_FILES["bukti_pengembalian"]["tmp_name"], $bukti_pengembalian);

    $query = "UPDATE peminjaman SET tanggal_pengembalian='$tanggal_pengembalian', bukti_pengembalian='$bukti_pengembalian' WHERE id='$id'";
    $conn->query($query);
    echo "<script>alert('Data Pengembalian Berhasil Disimpan');window.location='index.php';</script>";
}
?>
