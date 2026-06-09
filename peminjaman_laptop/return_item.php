<?php
include 'koneksi.php';
session_start();

if (isset($_POST['pengembalian'])) {
    $id_peminjaman = $_POST['id_peminjaman'];
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'];
    $bukti_pengembalian = $_POST['bukti_pengembalian'];

    // Ekstrak data gambar dari base64
    $folderPath = "uploads/";
    $image_parts = explode(";base64,", $bukti_pengembalian);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    $file_name = uniqid() . '.png';
    $file = $folderPath . $file_name;
    file_put_contents($file, $image_base64);

    // Update data di database
    $query = "UPDATE peminjaman SET tanggal_pengembalian='$tanggal_pengembalian', bukti_pengembalian='$file_name' 
              WHERE id='$id_peminjaman'";
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
