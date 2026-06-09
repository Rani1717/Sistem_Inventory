<?php
include 'koneksi.php';
session_start();

if (isset($_POST['peminjaman'])) {
    $nama_barang = $_POST['nama_barang'];
    $merk_barang = $_POST['merk_barang'];
    $nama_peminjam = $_POST['nama_peminjam'];
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'];
    
    $target_dir = "uploads/";
    $bukti_peminjaman = $target_dir . basename($_FILES["bukti_peminjaman"]["name"]);
    move_uploaded_file($_FILES["bukti_peminjaman"]["tmp_name"], $bukti_peminjaman);

    $query = "INSERT INTO peminjaman (nama_barang, merk_barang, nama_peminjam, tanggal_peminjaman, bukti_peminjaman) 
              VALUES ('$nama_barang', '$merk_barang', '$nama_peminjam', '$tanggal_peminjaman', '$bukti_peminjaman')";
    $conn->query($query);
    echo "<script>alert('Data Peminjaman Berhasil Disimpan');window.location='index.php';</script>";
}
?>
