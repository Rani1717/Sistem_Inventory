<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM peminjaman WHERE id='$id'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = $_POST['nama_barang'];
    $merk_barang = $_POST['merk_barang'];
    $nama_peminjam = $_POST['nama_peminjam'];
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'];
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'];

    $update = "UPDATE peminjaman SET 
                nama_barang='$nama_barang', 
                merk_barang='$merk_barang', 
                nama_peminjam='$nama_peminjam', 
                tanggal_peminjaman='$tanggal_peminjaman', 
                tanggal_pengembalian='$tanggal_pengembalian' 
                WHERE id='$id'";

    if ($conn->query($update) === TRUE) {
        echo "<script>alert('Data berhasil diperbarui!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        h2 {
            text-align: center;
            font-weight: bold;
            color: #fff;
            margin-bottom: 30px;
        }

        .card-custom {
            background: #2b3e50;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 20px;
            color: #fff;
        }

        .form-label {
            color: #ccc;
        }

        .btn-success {
            background-color: #4CAF50;
            border: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-success:hover {
            background-color: #45a049;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Data Peminjaman</h2>
        <div class="card card-custom">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="nama_barang" class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" value="<?= $row['nama_barang'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="merk_barang" class="form-label">Merk Barang</label>
                        <input type="text" class="form-control" id="merk_barang" name="merk_barang" value="<?= $row['merk_barang'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="nama_peminjam" class="form-label">Nama Peminjam</label>
                        <input type="text" class="form-control" id="nama_peminjam" name="nama_peminjam" value="<?= $row['nama_peminjam'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_peminjaman" class="form-label">Tanggal Peminjaman</label>
                        <input type="date" class="form-control" id="tanggal_peminjaman" name="tanggal_peminjaman" value="<?= $row['tanggal_peminjaman'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_pengembalian" class="form-label">Tanggal Pengembalian</label>
                        <input type="date" class="form-control" id="tanggal_pengembalian" name="tanggal_pengembalian" value="<?= $row['tanggal_pengembalian'] ?>">
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
