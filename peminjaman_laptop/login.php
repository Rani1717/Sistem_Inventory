<?php
include 'koneksi.php';
session_start();

// Cek apakah sudah login
if (isset($_SESSION['username']) && $_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} elseif (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Proses Login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Menggunakan prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "<script>alert('Password salah!');</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <title>Login - IT PELINDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .card-custom {
            background: #2b3e50;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 20px;
            color: #fff;
            width: 100%;
            max-width: 400px;
        }
        .card-header {
            background: #4a90e2;
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            text-align: center;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
        }
        .form-control {
            background: #3a4b5c;
            border: none;
            color: #fff;
        }
        .form-control::placeholder {
            color: #ccc;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #4a90e2;
            background: #2b3e50;
            color: #fff;
        }
        .btn-primary {
            background-color: #4a90e2;
            border: none;
            width: 100%;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: #357ab8;
        }
    </style>
</head>
<body>
    <div class="card card-custom">
        <div class="card-header">Login</div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" name="login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
