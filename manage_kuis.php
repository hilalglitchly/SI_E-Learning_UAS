<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session
if (!isset($_SESSION['id_user']) || (strtolower($_SESSION['role']) !== 'admin' && $_SESSION['role'] !== 'Pengajar')) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'];

if (!isset($_GET['id_kuis'])) {
    die("ID Kuis tidak ditemukan.");
}

$id_kuis = $_GET['id_kuis'];

// Ambil info kuis
$stmtKuis = $pdo->prepare("SELECT k.*, c.nama_kelas FROM tb_kuis k JOIN tb_kelas c ON k.id_kelas = c.id_kelas WHERE k.id_kuis = :id_kuis");
$stmtKuis->execute(['id_kuis' => $id_kuis]);
$kuisInfo = $stmtKuis->fetch();

if (!$kuisInfo) {
    die("Kuis tidak ditemukan.");
}

$id_kelas = $kuisInfo['id_kelas'];
$pesan = '';

// Handle tambah soal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah_soal') {
    $pertanyaan = $_POST['pertanyaan'];
    $opsi_a = $_POST['opsi_a'];
    $opsi_b = $_POST['opsi_b'];
    $opsi_c = $_POST['opsi_c'];
    $opsi_d = $_POST['opsi_d'];
    $jawaban_benar = $_POST['jawaban_benar'];

    $stmtSoal = $pdo->prepare("INSERT INTO tb_soal_kuis (id_kuis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmtSoal->execute([$id_kuis, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban_benar])) {
        $pesan = "Soal berhasil ditambahkan!";
    } else {
        $pesan = "Gagal menambahkan soal.";
    }
}

// Handle hapus soal
if (isset($_GET['delete_soal'])) {
    $id_hapus = $_GET['delete_soal'];
    $stmtDel = $pdo->prepare("DELETE FROM tb_soal_kuis WHERE id_soal = ? AND id_kuis = ?");
    if ($stmtDel->execute([$id_hapus, $id_kuis])) {
        header("Location: manage_kuis.php?id_kuis=" . $id_kuis . "&msg=deleted");
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $pesan = "Soal berhasil dihapus!";
}

// Ambil daftar soal
$stmtListSoal = $pdo->prepare("SELECT * FROM tb_soal_kuis WHERE id_kuis = ? ORDER BY id_soal ASC");
$stmtListSoal->execute([$id_kuis]);
$soalList = $stmtListSoal->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kuis - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .soal-card {
            background-color: var(--primary-foreground);
            border: 3px solid var(--border);
            box-shadow: 4px 4px 0px var(--border);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .opsi-list {
            margin-top: 1rem;
            list-style-type: none;
            padding-left: 0;
        }
        .opsi-list li {
            padding: 0.5rem;
            border: 2px solid var(--border);
            margin-bottom: 0.5rem;
            background-color: var(--background);
            font-weight: 500;
        }
        .opsi-benar {
            background-color: #4CAF50 !important;
            color: #fff;
            border-color: #000 !important;
            font-weight: 900 !important;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="neo-layout-container">
    <div style="margin-bottom: 2rem; margin-top: 1rem; padding-left: 2rem;">
        <a href="kelas_detail.php?id=<?= $id_kelas ?>" class="brutal-hover neo-box" style="background-color: var(--accent); padding: 0.5rem 1rem; text-decoration: none; color: var(--primary-foreground); font-weight: 800; font-size: 0.9rem; display: inline-block;">← KEMBALI KE KELAS</a>
    </div>

    <div class="neo-content-inner" style="max-width: 900px; margin: 0 auto; padding-bottom: 4rem;">
        
        <div class="brutal-card" style="background-color: var(--primary); color: var(--primary-foreground); margin-bottom: 2rem;">
            <h2 style="font-size: 2rem; font-weight: 900; text-transform: uppercase;"><?= htmlspecialchars($kuisInfo['judul_kuis']) ?></h2>
            <p style="font-weight: bold; margin-top: 0.5rem;">Kelas: <?= htmlspecialchars($kuisInfo['nama_kelas']) ?> | Durasi: <?= htmlspecialchars($kuisInfo['durasi_menit']) ?> Menit</p>
        </div>

        <?php if ($pesan): ?>
            <div class="neo-alert-success brutal-hover" style="background-color: #a8e6cf; color: black; padding: 1rem; font-weight: bold; border: 3px solid #000; box-shadow: 5px 5px 0px #000; margin-bottom: 2rem;">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Soal -->
        <div class="brutal-card" style="margin-bottom: 3rem;">
            <h3 style="font-size: 1.4rem; text-transform: uppercase; font-weight: 800; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;">Tambah Soal Pilihan Ganda</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="tambah_soal">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Pertanyaan</label>
                    <textarea name="pertanyaan" rows="3" required class="neo-input neo-box" style="width: 100%;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Opsi A</label>
                        <input type="text" name="opsi_a" required class="neo-input neo-box" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Opsi B</label>
                        <input type="text" name="opsi_b" required class="neo-input neo-box" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Opsi C</label>
                        <input type="text" name="opsi_c" required class="neo-input neo-box" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Opsi D</label>
                        <input type="text" name="opsi_d" required class="neo-input neo-box" style="width: 100%;">
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Jawaban Benar</label>
                    <select name="jawaban_benar" required class="neo-input neo-box" style="width: 100%; font-weight: bold;">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>

                <button type="submit" class="btn-accent" style="width: 100%; padding: 1rem; font-size: 1.2rem;">Simpan Soal</button>
            </form>
        </div>

        <h3 style="font-size: 1.6rem; text-transform: uppercase; font-weight: 800; margin-bottom: 1.5rem;">Daftar Soal (<?= count($soalList) ?>)</h3>
        
        <?php if (count($soalList) > 0): ?>
            <?php foreach ($soalList as $idx => $soal): ?>
                <div class="soal-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="font-weight: 900; font-size: 1.1rem; flex: 1;">
                            <?= ($idx + 1) ?>. <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                        </div>
                        <a href="manage_kuis.php?id_kuis=<?= $id_kuis ?>&delete_soal=<?= $soal['id_soal'] ?>" onclick="return confirm('Yakin ingin menghapus soal ini?')" class="btn-small" style="background-color: #ff4d4d; color: white;">Hapus</a>
                    </div>
                    <ul class="opsi-list">
                        <li class="<?= $soal['jawaban_benar'] === 'A' ? 'opsi-benar' : '' ?>">A. <?= htmlspecialchars($soal['opsi_a']) ?></li>
                        <li class="<?= $soal['jawaban_benar'] === 'B' ? 'opsi-benar' : '' ?>">B. <?= htmlspecialchars($soal['opsi_b']) ?></li>
                        <li class="<?= $soal['jawaban_benar'] === 'C' ? 'opsi-benar' : '' ?>">C. <?= htmlspecialchars($soal['opsi_c']) ?></li>
                        <li class="<?= $soal['jawaban_benar'] === 'D' ? 'opsi-benar' : '' ?>">D. <?= htmlspecialchars($soal['opsi_d']) ?></li>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; border: 3px dashed var(--border); font-weight: bold; color: var(--muted-foreground);">
                Belum ada soal untuk kuis ini.
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
