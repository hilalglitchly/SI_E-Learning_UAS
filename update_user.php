<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session: Hanya Admin yang boleh mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$id_user_update = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_user_update) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Ambil data user
$stmt_user = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = :id");
$stmt_user->execute(['id' => $id_user_update]);
$user_data = $stmt_user->fetch();

if (!$user_data) {
    header("Location: dashboard.php");
    exit();
}

$role_pilihan = $user_data['role'];
$username_existing = $user_data['username'];
$nomor_induk = '';
$nama_lengkap = '';
$email = '';

// Ambil data detail berdasarkan role
if ($role_pilihan === 'Pengajar') {
    $stmt_detail = $pdo->prepare("SELECT * FROM tb_pengajar WHERE id_user = :id");
    $stmt_detail->execute(['id' => $id_user_update]);
    $detail = $stmt_detail->fetch();
    if ($detail) {
        $nomor_induk = $detail['nidn'];
        $nama_lengkap = $detail['nama_pengajar'];
        $email = $detail['email'];
    }
} elseif ($role_pilihan === 'Murid') {
    $stmt_detail = $pdo->prepare("SELECT * FROM tb_murid WHERE id_user = :id");
    $stmt_detail->execute(['id' => $id_user_update]);
    $detail = $stmt_detail->fetch();
    if ($detail) {
        $nomor_induk = $detail['nim'];
        $nama_lengkap = $detail['nama_murid'];
        $email = $detail['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_username = trim($_POST['username']);
    $password = trim($_POST['password']); // Opsional
    $form_nama_lengkap = trim($_POST['nama_lengkap']);
    $form_email = trim($_POST['email']);

    if (empty($form_username) || empty($form_nama_lengkap) || empty($form_email)) {
        $error = "Semua field kecuali password wajib diisi!";
    } else {
        try {
            $pdo->beginTransaction();

            // Update tabel tb_user
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_user = "UPDATE tb_user SET username = :username, password = :password WHERE id_user = :id_user";
                $stmt_user_update = $pdo->prepare($sql_user);
                $stmt_user_update->execute([
                    'username' => $form_username,
                    'password' => $hashed_password,
                    'id_user' => $id_user_update
                ]);
            } else {
                $sql_user = "UPDATE tb_user SET username = :username WHERE id_user = :id_user";
                $stmt_user_update = $pdo->prepare($sql_user);
                $stmt_user_update->execute([
                    'username' => $form_username,
                    'id_user' => $id_user_update
                ]);
            }

            // Update tabel detail (Pengajar atau Murid)
            if ($role_pilihan === 'Pengajar') {
                $sql_pengajar = "UPDATE tb_pengajar SET nama_pengajar = :nama_pengajar, email = :email WHERE id_user = :id_user";
                $stmt_pengajar = $pdo->prepare($sql_pengajar);
                $stmt_pengajar->execute([
                    'nama_pengajar' => $form_nama_lengkap,
                    'email' => $form_email,
                    'id_user' => $id_user_update
                ]);
            } elseif ($role_pilihan === 'Murid') {
                $sql_mhs = "UPDATE tb_murid SET nama_murid = :nama_murid, email = :email WHERE id_user = :id_user";
                $stmt_mhs = $pdo->prepare($sql_mhs);
                $stmt_mhs->execute([
                    'nama_murid' => $form_nama_lengkap,
                    'email' => $form_email,
                    'id_user' => $id_user_update
                ]);
            }

            $pdo->commit();
            $success = "Data pengguna berhasil diperbarui!";
            
            // Perbarui variabel untuk tampilan
            $username_existing = $form_username;
            $nama_lengkap = $form_nama_lengkap;
            $email = $form_email;

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Gagal: Username atau Email sudah digunakan oleh pengguna lain!";
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
    <title>Update Pengguna - E-Learning</title>
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
        }
        .neo-select[disabled] {
            background-color: var(--muted);
            cursor: not-allowed;
            color: var(--muted-foreground);
        }
        .readonly-input {
            background-color: var(--muted) !important;
            cursor: not-allowed;
            color: var(--muted-foreground);
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

    <div class="neo-content-inner">
        <div class="neo-box neo-container" style="max-width: 600px; padding: 2rem; background-color: var(--card);">
            <h2 class="neo-title" style="text-align: left; font-size: 2rem; margin-bottom: 1.5rem;">Update Pengguna</h2>

            <?php if ($error): ?>
                <div class="neo-alert-error neo-box" style="background-color: #ff6b6b; color: white; padding: 1rem; font-weight: bold; margin-bottom: 1.5rem; border: 3px solid #000;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="neo-alert-success neo-box" style="background-color: #a8e6cf; color: black; padding: 1rem; font-weight: bold; margin-bottom: 1.5rem; border: 3px solid #000;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label class="neo-label" for="role_pilihan">Role Pengguna (Tidak dapat diubah)</label>
                <select id="role_pilihan" name="role_pilihan" class="neo-select" disabled>
                    <option value="Pengajar" <?= $role_pilihan === 'Pengajar' ? 'selected' : '' ?>>Pengajar</option>
                    <option value="Murid" <?= $role_pilihan === 'Murid' ? 'selected' : '' ?>>Murid</option>
                </select>

                <div class="neo-form-group">
                    <label class="neo-label" for="username">Username Login</label>
                    <input type="text" id="username" name="username" class="neo-input neo-box" value="<?= htmlspecialchars($username_existing) ?>" required autocomplete="off">
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="password">Password Login (Kosongkan jika tidak ingin diubah)</label>
                    <input type="password" id="password" name="password" class="neo-input neo-box" autocomplete="new-password">
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" id="label_induk" for="nomor_induk"><?= $role_pilihan === 'Pengajar' ? 'Nomor Induk (NIDN)' : 'Nomor Induk (NIM)' ?> (Tidak dapat diubah)</label>
                    <input type="text" id="nomor_induk" name="nomor_induk" class="neo-input neo-box readonly-input" value="<?= htmlspecialchars($nomor_induk) ?>" readonly>
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="neo-input neo-box" value="<?= htmlspecialchars($nama_lengkap) ?>" required>
                </div>

                <div class="neo-form-group">
                    <label class="neo-label" for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" class="neo-input neo-box" value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <button type="submit" class="neo-btn neo-box" style="margin-top: 2rem; background-color: #ffd900; color: #000; font-weight: 900; text-transform: uppercase;">Update Pengguna</button>
            </form>
        </div>
    </div>
</main>
</div>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
