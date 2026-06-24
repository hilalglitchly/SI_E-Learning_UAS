<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session: Hanya Murid yang boleh mengakses
if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'murid') {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Ambil NIM murid
$stmtNim = $pdo->prepare("SELECT nim FROM tb_murid WHERE id_user = :id_user");
$stmtNim->execute(['id_user' => $id_user]);
$mhs = $stmtNim->fetch();

if (!$mhs) {
    die("Data murid tidak ditemukan.");
}

$nim = $mhs['nim'];

// Proses Pendaftaran Kelas (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_kelas'])) {
    $id_kelas = $_POST['id_kelas'];

    try {
        $stmtInsert = $pdo->prepare("INSERT INTO tb_peserta_kelas (id_kelas, nim) VALUES (:id_kelas, :nim)");
        $stmtInsert->execute(['id_kelas' => $id_kelas, 'nim' => $nim]);
        header("Location: dashboard.php?msg=success");
        exit();
    } catch (PDOException $e) {
        // Jika sudah terdaftar (duplicate key), abaikan
        header("Location: enroll.php");
        exit();
    }
}

// Ambil kelas yang BELUM diikuti oleh murid
$sql = "
    SELECT k.id_kelas, k.nama_kelas, k.deskripsi, p.nama_pengajar
    FROM tb_kelas k
    LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
    WHERE k.id_kelas NOT IN (
        SELECT id_kelas FROM tb_peserta_kelas WHERE nim = :nim
    )
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['nim' => $nim]);
$available_classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Kelas - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">
<main class="neo-layout-container">


    <div class="neo-content-inner">
        <div class="neo-box" style="background-color: #FFD700; color: #000; padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.6rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Temukan Kelas Baru</h2>
            <p style="font-size: 1rem;">Pilih kelas yang ingin Anda ikuti dari daftar di bawah ini.</p>
        </div>

        <?php if (count($available_classes) > 0): ?>
            <!-- Search Filter Bar -->
            <div style="margin-bottom: 2rem; position: relative;">
                <i class='bx bx-search' style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); font-size: 1.6rem; color: #000; font-weight: bold; z-index: 10;"></i>
                <input type="text" id="searchKelas" placeholder="Cari nama kelas atau pengajar secara instan..." style="width: 100%; padding: 1rem 1rem 1rem 3.8rem; font-size: 1.1rem; border: 3px solid var(--border); background-color: var(--card); color: var(--foreground); outline: none; font-family: inherit; box-shadow: 4px 4px 0px var(--border); font-weight: 600;">
            </div>
            
            <div class="neo-grid">
                <?php foreach ($available_classes as $kelas): ?>
                    <div class="neo-box neo-card kelas-item">
                        <div>
                            <h3 class="neo-card-title"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                            <div class="neo-card-subtitle">Pengajar: <?= htmlspecialchars($kelas['nama_pengajar'] ?? 'Belum Ditentukan') ?></div>
                            <p class="neo-card-desc"><?= htmlspecialchars($kelas['deskripsi'] ?? 'Tidak ada deskripsi tersedia untuk kelas ini.') ?></p>
                        </div>
                        <form method="POST" action="" style="margin-top: auto;" onsubmit="event.preventDefault(); NeoConfirm('Konfirmasi', 'Yakin ingin mengikuti kelas ini?', () => this.submit());">
                            <input type="hidden" name="id_kelas" value="<?= htmlspecialchars($kelas['id_kelas']) ?>">
                            <button type="submit" class="neo-btn neo-box" style="font-size: 1rem; padding: 0.8rem;">Ikuti Kelas</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="neo-box" style="background-color: var(--primary-foreground); padding: 2rem; text-align: center;">
                <h3 class="neo-card-title">Semua Kelas Sudah Diikuti!</h3>
                <p style="margin-top: 0.5rem;">Anda sudah terdaftar di semua kelas yang tersedia. Kembali ke <a href="dashboard.php" style="color: var(--foreground); font-weight: bold;">Dashboard</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
</div>

<script>
    // Real-time Search Filter
    document.getElementById('searchKelas')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.kelas-item');
        
        cards.forEach(card => {
            const title = card.querySelector('.neo-card-title').textContent.toLowerCase();
            const subtitle = card.querySelector('.neo-card-subtitle').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || subtitle.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
