<?php
include 'koneksi.php';
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <title>IT PELINDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <style>
     body {
    background: linear-gradient(135deg, #1e3c72, #2a5298, #4a90e2);
    font-family: 'Arial', sans-serif;
    color: #fff;
    line-height: 1.6;
    overflow-x: hidden;
    background-size: 400% 400%;
    animation: gradientAnimation 10s ease infinite;
}

@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

h1 {
    font-weight: bold;
    color: #ffffff;
    text-align: center;
    text-shadow: 2px 2px 8px rgba(99, 4, 4, 0.3);
}

h2 {
    font-weight: bold;
    color:rgb(255, 255, 255);
    text-align: center;
    text-shadow: 2px 2px 8px rgb(0, 0, 0);
}

.card-custom {
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
    border: none;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.9);
    transition: transform 0.4s, box-shadow 0.4s;
    overflow: hidden;
}

.card-custom:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.card-custom h2 {
    color: #ffffff;
    font-size: 1.8rem;
}

.card-header-custom {
    background: linear-gradient(135deg, #4a90e2, #357ab8);
    color: white;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    padding: 15px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    box-shadow: inset 0 -3px 5px rgba(0, 0, 0, 0.2);
}

.card-custom .form-control {
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 12px;
    background: #f9f9f9;
    transition: all 0.3s;
}

.card-custom .form-control:focus {
    box-shadow: 0 0 8px rgba(74, 144, 226, 0.5);
    border-color: #4a90e2;
    background: #fff;
}

.card-custom .btn-primary,
.card-custom .btn-success {
    display: inline-block;
    border-radius: 50px;
    transition: all 0.3s;
    padding: 10px 25px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    margin: 0 auto;
}

.card-custom .btn-primary {
    background: linear-gradient(135deg, #4a90e2, #357ab8);
    border: none;
    color: #fff;
}

.card-custom .btn-primary:hover {
    background: linear-gradient(135deg, #357ab8, #4a90e2);
}

.card-custom .btn-success {
    background: linear-gradient(135deg, #28a745, #218838);
    border: none;
    color: #fff;
}

.card-custom .btn-success:hover {
    background: linear-gradient(135deg, #218838, #28a745);
}

.card-footer {
    background: #f0f2f5;
    border-top: 1px solid #eaeaea;
    padding: 10px 15px;
    text-align: right;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
}

.text-center {
    text-align: center;
    margin-bottom: 30px;
    color: #fff;
    text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.3);
}

.logo {
            display: block;
            margin: 0 auto 30px;
            width: 150px;
        }

.text-center {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}


    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <img src="assets/images/logo.png" alt="Logo" class="logo">
        <h1>TEKNOLOGI INFORMASI PELINDO MULTI TERMINAL</h1>
        <!-- Card Peminjaman -->
        <div class="col-md-6">
    <div class="card card-custom mb-4">
        <div class="card-header-custom">
            <h2>Peminjaman IT</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="save_image.php" onsubmit="return validateForm()">
                <div class="mb-3">
                    <label>Nama Barang</label>
                    <input type="text" class="form-control" name="nama_barang" required>
                </div>
                <div class="mb-3">
                    <label>Merk Barang</label>
                    <input type="text" class="form-control" name="merk_barang" required>
                </div>
                <div class="mb-3">
                    <label>Nama Peminjam</label>
                    <input type="text" class="form-control" name="nama_peminjam" required>
                </div>
                <div class="mb-3">
                    <label>Tanggal Peminjaman</label>
                    <input type="date" class="form-control" name="tanggal_peminjaman" required>
                </div>
                <div class="mb-3">
                    <label>Bukti Peminjaman (Foto Memegang Barang)</label><br>
                    <video id="video" autoplay class="w-100 mb-2"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <input type="hidden" name="bukti_peminjaman" id="bukti_peminjaman" required>
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-success" onclick="takePhoto()">Ambil Foto</button>
                    <button type="submit" class="btn btn-primary" name="peminjaman">Simpan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>


        <!-- Card Pengembalian -->
        <div class="col-md-6">
    <div class="card card-custom mb-4">
        <div class="card-header-custom">
            <h2>Pengembalian IT</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="return_item.php" onsubmit="return validateReturnForm()">
                <div class="mb-3">
                    <label>Pilih Barang Yang Dikembalikan</label>
                    <select class="form-control" name="id_peminjaman" required>
                        <option value="">Pilih Barang</option>
                        <?php
                        $query = "SELECT * FROM peminjaman WHERE tanggal_pengembalian IS NULL";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='".$row['id']."'>".$row['nama_barang']." - ".$row['nama_peminjam']."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Tanggal Pengembalian</label>
                    <input type="date" class="form-control" name="tanggal_pengembalian" required>
                </div>
                <div class="mb-3">
                    <label>Bukti Pengembalian (Foto Memegang Barang)</label><br>
                    <video id="videoKembali" autoplay class="w-100 mb-2"></video>
                    <canvas id="canvasKembali" style="display: none;"></canvas>
                    <input type="hidden" name="bukti_pengembalian" id="bukti_pengembalian" required>
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-success" onclick="takePhotoKembali()">Ambil Foto</button>
                    <button type="submit" class="btn btn-primary" name="pengembalian">Simpan Pengembalian</button>
                </div>
            </form>
        </div>
    </div>
</div>
    </div>
</div>
<a href="logout.php" class="btn btn-danger mb-3" style="display: block; width: fit-content; margin: 0 auto;">Logout</a>

<script>
   const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const bukti_peminjaman = document.getElementById('bukti_peminjaman');

navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
        video.play();
    })
    .catch(err => {
        console.error("Error: ", err);
    });

    function takePhoto() {
    canvas.style.display = 'block';
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/png');
    bukti_peminjaman.value = imageData;
    }
const videoKembali = document.getElementById('videoKembali');
const canvasKembali = document.getElementById('canvasKembali');
const bukti_pengembalian = document.getElementById('bukti_pengembalian');

navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        videoKembali.srcObject = stream;
        videoKembali.play();
    })
    .catch(err => {
        console.error("Error: ", err);
    });

    function takePhotoKembali() {
    canvasKembali.style.display = 'block';  // Tampilkan canvasKembali
    canvasKembali.width = videoKembali.videoWidth;
    canvasKembali.height = videoKembali.videoHeight;
    const context = canvasKembali.getContext('2d');
    context.drawImage(videoKembali, 0, 0, canvasKembali.width, canvasKembali.height);
    const imageData = canvasKembali.toDataURL('image/png');
    bukti_pengembalian.value = imageData;
}


 // Validasi Form Sebelum Submit
 function validateForm() {
        const buktiPeminjaman = document.getElementById('bukti_peminjaman').value;
        
        if (buktiPeminjaman === "") {
            alert("Silakan ambil foto bukti peminjaman terlebih dahulu!");
            return false;
        }
        
        return true;
    }

    function validateReturnForm() {
        const buktiPengembalian = document.getElementById('bukti_pengembalian').value;
        
        if (buktiPengembalian === "") {
            alert("Silakan ambil foto bukti pengembalian terlebih dahulu!");
            return false;
        }
        
        return true;
    }

</script>
<script>
    $(document).ready(function() {
        <?php 
        if (isset($_SESSION['notif']) && $_SESSION['notif'] == "success") { 
            echo "toastr.success('Data Berhasil Disimpan!', 'Berhasil');";
            unset($_SESSION['notif']);
        } elseif (isset($_SESSION['notif']) && $_SESSION['notif'] == "error") {
            echo "toastr.error('Data Gagal Disimpan!', 'Gagal');";
            unset($_SESSION['notif']);
        }
        ?>
    });
</script>

</body>
</html>
