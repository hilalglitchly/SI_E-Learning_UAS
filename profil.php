<?php
session_start();
require_once 'includes/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

$nama_lengkap = '';
$nomor_induk = '';
$email = '';

try {
    if (strtolower($role) === 'murid') {
        $stmt = $pdo->prepare("SELECT * FROM tb_murid WHERE id_user = :id_user");
        $stmt->execute(['id_user' => $id_user]);
        $m = $stmt->fetch();
        if ($m) {
            $nama_lengkap = $m['nama_murid'];
            $nomor_induk = $m['nim'];
            $email = $m['email'];
        }
    } elseif ($role === 'Pengajar') {
        $stmt = $pdo->prepare("SELECT * FROM tb_pengajar WHERE id_user = :id_user");
        $stmt->execute(['id_user' => $id_user]);
        $p = $stmt->fetch();
        if ($p) {
            $nama_lengkap = $p['nama_pengajar'];
            $nomor_induk = $p['nidn'];
            $email = $p['email'];
        }
    } else {
        // Admin
        $nama_lengkap = 'System Administrator';
        $nomor_induk = 'ADMIN-SI';
        $email = 'admin@elearning.ac.id';
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan pada query: " . $e->getMessage());
}

// Handle Edit Password
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $pesan_error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            $pesan_error = 'Semua kolom password wajib diisi!';
        } elseif ($password_baru !== $konfirmasi_password) {
            $pesan_error = 'Password baru dan konfirmasi password tidak cocok!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM tb_user WHERE id_user = :id_user");
                $stmt->execute(['id_user' => $id_user]);
                $user = $stmt->fetch();

                if ($user && password_verify($password_lama, $user['password'])) {
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                    $stmt_update = $pdo->prepare("UPDATE tb_user SET password = :password WHERE id_user = :id_user");
                    $stmt_update->execute(['password' => $hashed_password, 'id_user' => $id_user]);
                    $pesan_sukses = 'Password berhasil diperbarui!';
                } else {
                    $pesan_error = 'Password lama yang Anda masukkan salah!';
                }
            } catch (PDOException $e) {
                $pesan_error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
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
    <title>Profil Pengguna - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">

    <main class="neo-layout-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: calc(100vh - 4rem);">
        
        <div class="neo-container neo-box" style="background-color: var(--primary-foreground); width: 100%; max-width: 600px; padding: 3rem; border: 3px solid var(--border); box-shadow: 8px 8px 0px var(--border); position: relative;">
            
            <!-- Brutalism Avatar / Circle Badge -->
            <div style="width: 90px; height: 90px; border: 3px solid var(--border); background-color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 2rem auto; box-shadow: 4px 4px 0px var(--border); border-radius: 50%;">
                👤
            </div>

            <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase; text-align: center; margin-bottom: 2rem; letter-spacing: -1px; border-bottom: 3px solid var(--border); padding-bottom: 1rem;">
                Profil Pengguna
            </h2>

            <?php if ($pesan_sukses): ?>
                <div class="neo-alert-success brutal-hover" style="background-color: #a8e6cf; color: black; padding: 1rem; font-weight: bold; border: 3px solid #000; box-shadow: 5px 5px 0px #000; margin-bottom: 2rem;">
                    <?= htmlspecialchars($pesan_sukses) ?>
                </div>
            <?php endif; ?>

            <?php if ($pesan_error): ?>
                <div class="neo-error brutal-hover" style="background-color: var(--destructive, #EF4444); color: white; padding: 1rem; font-weight: bold; border: 3px solid #000; box-shadow: 5px 5px 0px #000; margin-bottom: 2rem;">
                    <?= htmlspecialchars($pesan_error) ?>
                </div>
            <?php endif; ?>

            <!-- Identity Grid -->
            <div style="display: flex; flex-direction: column; gap: 1.2rem; margin-bottom: 2.5rem;">
                
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed var(--border); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--foreground);">Nama Lengkap</span>
                    <span style="font-weight: 700; text-align: right; text-transform: uppercase; color: var(--foreground);"><?= htmlspecialchars($nama_lengkap) ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed var(--border); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--foreground);"><?= strtolower($role) === 'murid' ? 'NIM' : ($role === 'Pengajar' ? 'NIDN' : 'ID') ?></span>
                    <span style="font-weight: 700; font-family: monospace; font-size: 1.1rem; color: var(--foreground);"><?= htmlspecialchars($nomor_induk) ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed var(--border); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--foreground);">Alamat Email</span>
                    <span style="font-weight: 700; color: var(--foreground);"><?= htmlspecialchars($email) ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed var(--border); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--foreground);">Hak Akses</span>
                    <span class="neo-role-badge" style="background-color: var(--primary); color: var(--primary-foreground); border: 2px solid var(--border); padding: 0.15rem 0.5rem; font-size: 0.8rem; box-shadow: 2px 2px 0px var(--border); margin: 0;"><?= htmlspecialchars($role) ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed var(--border); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--foreground);">Username</span>
                    <span style="font-weight: 700; color: var(--foreground);"><?= htmlspecialchars($username) ?></span>
                </div>

            </div>

            <!-- Form Edit Password -->
            <button id="btn-show-password" onclick="document.getElementById('form-password').style.display = 'block'; this.style.display = 'none';" class="neo-box neo-btn" style="background-color: var(--primary); color: var(--primary-foreground); border: 3px solid var(--border); box-shadow: 4px 4px 0px var(--border); font-weight: 800; text-transform: uppercase; width: 100%; font-size: 1.1rem; padding: 1rem; cursor: pointer; display: <?= ($pesan_error || $pesan_sukses) ? 'none' : 'block' ?>;">
                🔒 Edit Password
            </button>

            <form id="form-password" method="POST" style="display: <?= ($pesan_error || $pesan_sukses) ? 'block' : 'none' ?>; border-top: 3px dashed var(--border); padding-top: 1.5rem; margin-top: 1.5rem;">
                <h3 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin-bottom: 1rem; color: var(--foreground);">Ubah Password</h3>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="update_password">

                <div class="neo-form-group" style="margin-bottom: 1rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.5rem; font-weight: 800; color: var(--foreground);">Password Lama</label>
                    <input type="password" name="password_lama" class="neo-input neo-box" style="width: 100%; padding: 0.8rem; border: 3px solid var(--border); background-color: var(--background); color: var(--foreground);" required>
                </div>
                
                <div class="neo-form-group" style="margin-bottom: 1rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.5rem; font-weight: 800; color: var(--foreground);">Password Baru</label>
                    <input type="password" name="password_baru" class="neo-input neo-box" style="width: 100%; padding: 0.8rem; border: 3px solid var(--border); background-color: var(--background); color: var(--foreground);" required>
                </div>

                <div class="neo-form-group" style="margin-bottom: 1.5rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.5rem; font-weight: 800; color: var(--foreground);">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" class="neo-input neo-box" style="width: 100%; padding: 0.8rem; border: 3px solid var(--border); background-color: var(--background); color: var(--foreground);" required>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="neo-btn" style="flex: 1; padding: 0.8rem; font-size: 1rem; font-weight: 900; background-color: var(--accent); color: var(--accent-foreground); border: 3px solid var(--border); box-shadow: 3px 3px 0px var(--border); cursor: pointer; text-transform: uppercase;">Simpan</button>
                    <button type="button" onclick="document.getElementById('form-password').style.display = 'none'; document.getElementById('btn-show-password').style.display = 'block';" class="neo-btn" style="padding: 0.8rem; font-size: 1rem; font-weight: 900; background-color: var(--muted); color: var(--foreground); border: 3px solid var(--border); box-shadow: 3px 3px 0px var(--border); cursor: pointer; text-transform: uppercase;">Batal</button>
                </div>
            </form>

        </div>

    </main>
</div>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
