<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session: Hanya Admin yang boleh mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role_pilihan = $_POST['role_pilihan'];
    $form_username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nomor_induk = trim($_POST['nomor_induk']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);

    if (empty($role_pilihan) || empty($form_username) || empty($password) || empty($nomor_induk) || empty($nama_lengkap) || empty($email)) {
        $error = "Semua field wajib diisi!";
    } else {
        try {
            $pdo->beginTransaction();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_user = "INSERT INTO tb_user (username, password, role) VALUES (:username, :password, :role)";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([
                'username' => $form_username,
                'password' => $hashed_password,
                'role' => $role_pilihan
            ]);

            $new_id_user = $pdo->lastInsertId();

            if ($role_pilihan === 'Pengajar') {
                $sql_pengajar = "INSERT INTO tb_pengajar (nidn, nama_pengajar, email, id_user) VALUES (:nidn, :nama_pengajar, :email, :id_user)";
                $stmt_pengajar = $pdo->prepare($sql_pengajar);
                $stmt_pengajar->execute([
                    'nidn' => $nomor_induk,
                    'nama_pengajar' => $nama_lengkap,
                    'email' => $email,
                    'id_user' => $new_id_user
                ]);
            } elseif ($role_pilihan === 'Murid') {
                $sql_mhs = "INSERT INTO tb_murid (nim, nama_murid, email, id_user) VALUES (:nim, :nama_murid, :email, :id_user)";
                $stmt_mhs = $pdo->prepare($sql_mhs);
                $stmt_mhs->execute([
                    'nim' => $nomor_induk,
                    'nama_murid' => $nama_lengkap,
                    'email' => $email,
                    'id_user' => $new_id_user
                ]);
            }

            $pdo->commit();

            header("Location: dashboard.php?msg=success_add_user");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Gagal: Username, Email, atau Nomor Induk sudah digunakan!";
            } else {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .neo-select {
            width: 100%;
            padding: 1rem;
            font-family: inherit;
            font-size: 1.1rem;
            background-color: var(--card);
            color: var(--foreground);
            border: 3px solid var(--border);
            box-shadow: 5px 5px 0px var(--shadow-color);
            outline: none;
            margin-bottom: 1.5rem;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="black" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat;
            background-position-x: 98%;
            background-position-y: 50%;
        }
    </style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">
<main class="neo-layout-container">
    <div style="margin-bottom: 2rem;">
        <a href="dashboard.php" class="neo-box" style="background-color: var(--primary); padding: 0.5rem 1rem; text-decoration: none; color: var(--foreground); font-weight: 800; font-size: 0.9rem; display: inline-block;">← KEMBALI</a>
    </div>

    <div class="neo-content-inner" style="display: flex; justify-content: center;">
        <div class="neo-box neo-container" style="max-width: 600px; width: 100%; padding: 2rem; background-color: var(--card);">
            <h2 class="neo-title" style="text-align: left; font-size: 2rem; margin-bottom: 1.5rem;">Tambah Pengguna</h2>

            <?php if ($error): ?>
                <div class="neo-alert neo-box">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label class="neo-label" for="role_pilihan">Role Pengguna</label>
                <select id="role_pilihan" name="role_pilihan" class="neo-select" required onchange="updateLabel()">
                    <option value="Pengajar">Pengajar</option>
                    <option value="Murid">Murid</option>
                </select>

                <div class="neo-form-group">
                    <label class="neo-label" for="username">Username Login</label>
                    <input type="text" id="username" name="username" class="neo-input neo-box" required autocomplete="off">
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="password">Password Login</label>
                    <input type="password" id="password" name="password" class="neo-input neo-box" required>
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" id="label_induk" for="nomor_induk">Nomor Induk (NIDN)</label>
                    <input type="text" id="nomor_induk" name="nomor_induk" class="neo-input neo-box" required>
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="neo-input neo-box" required>
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" class="neo-input neo-box" required>
                </div>

                <button type="submit" class="neo-btn neo-box" style="margin-top: 2rem;">Simpan Pengguna</button>
            </form>
        </div>
    </div>
</main>

<script>
    function updateLabel() {
        var role = document.getElementById("role_pilihan").value;
        var labelInduk = document.getElementById("label_induk");
        if (role === 'Pengajar') {
            labelInduk.innerText = "Nomor Induk (NIDN)";
        } else {
            labelInduk.innerText = "Nomor Induk (NIM)";
        }
    }
    updateLabel();
</script>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
