<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$query = "DELETE FROM peminjaman WHERE id='$id'";

if ($conn->query($query) === TRUE) {
    echo "<script>alert('Data berhasil dihapus!'); window.location.href='admin_dashboard.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus data!'); window.location.href='admin_dashboard.php';</script>";
}
?>
