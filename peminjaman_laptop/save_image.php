<?php
include 'koneksi.php';
session_start();

if (isset($_POST['peminjaman'])) {
    $nama_barang = $_POST['nama_barang'];
    $merk_barang = $_POST['merk_barang'];
    $nama_peminjam = $_POST['nama_peminjam'];
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'];
    $bukti_peminjaman = $_POST['bukti_peminjaman'];

    // Ekstrak data gambar dari base64
    $folderPath = "uploads/";
    $image_parts = explode(";base64,", $bukti_peminjaman);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    $file_name = uniqid() . '.png';
    $file = $folderPath . $file_name;
    file_put_contents($file, $image_base64);

    // Simpan data ke database
    $query = "INSERT INTO peminjaman (nama_barang, merk_barang, nama_peminjam, tanggal_peminjaman, bukti_peminjaman) 
              VALUES ('$nama_barang', '$merk_barang', '$nama_peminjam', '$tanggal_peminjaman', '$file_name')";
    $result = $conn->query($query);

    if ($result) {
        $_SESSION['notif'] = "success";
    } else {
        $_SESSION['notif'] = "error";
    }

    // Redirect ke index.php setelah submit
    header("Location: index.php");
    exit();
}
?>
