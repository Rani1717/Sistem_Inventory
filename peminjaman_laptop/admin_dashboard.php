<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <title>IT PELINDO</title>
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

        table {
            background: #fff;
            color: #333;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        table thead {
            background: #4a90e2;
            color: #fff;
        }

        table tbody tr:hover {
            background: #f2f2f2;
            cursor: pointer;
        }

        table img {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Dashboard Admin</h2>
        <div class="card card-custom mb-4">
            <div class="card-body">
                <a href="logout.php" class="btn btn-danger mb-3">Logout</a>
                <a href="export_excel.php" class="btn btn-success mb-3">Export Data ke Excel</a>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Merk Barang</th>
                            <th>Nama Peminjam</th>
                            <th>Tanggal Peminjaman</th>
                            <th>Bukti Peminjaman</th>
                            <th>Tanggal Pengembalian</th>
                            <th>Bukti Pengembalian</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM peminjaman";
                        $result = $conn->query($query);
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['tanggal_pengembalian'] ? "Sudah Dikembalikan" : "Belum Dikembalikan";

                            echo "<tr>
                                    <td>".$no++."</td>
                                    <td>".$row['nama_barang']."</td>
                                    <td>".$row['merk_barang']."</td>
                                    <td>".$row['nama_peminjam']."</td>
                                    <td>".$row['tanggal_peminjaman']."</td>
                                    <td><img src='uploads/".$row['bukti_peminjaman']."' width='100'></td>
                                    <td>".$row['tanggal_pengembalian']."</td>
                                    <td>";
                                        if ($row['bukti_pengembalian']) {
                                            echo "<img src='uploads/".$row['bukti_pengembalian']."' width='100'>";
                                        } else {
                                            echo "Belum Ada";
                                        }
                            echo "</td>
                                    <td>".$status."</td>
                                    <td>
                                        <div class='btn-group' role='group'>
                                            <a href='edit.php?id=".$row['id']."' class='btn btn-warning btn-sm'>Edit</a>
                                            <a href='delete.php?id=".$row['id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus data ini?\")'>Delete</a>
                                        </div>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
