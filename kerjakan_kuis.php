<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session: Hanya Murid
if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'murid') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id_kuis'])) {
    die("ID Kuis tidak ditemukan.");
}

$id_kuis = $_GET['id_kuis'];
$id_user = $_SESSION['id_user'];

// Ambil NIM murid
$stmtNim = $pdo->prepare("SELECT nim FROM tb_murid WHERE id_user = :id_user");
$stmtNim->execute(['id_user' => $id_user]);
$mhs = $stmtNim->fetch();
if (!$mhs) {
    die("Data murid tidak ditemukan.");
}
$nim = $mhs['nim'];

// Ambil info kuis
$stmtKuis = $pdo->prepare("SELECT * FROM tb_kuis WHERE id_kuis = :id_kuis");
$stmtKuis->execute(['id_kuis' => $id_kuis]);
$kuisInfo = $stmtKuis->fetch();

if (!$kuisInfo) {
    die("Kuis tidak ditemukan.");
}
$id_kelas = $kuisInfo['id_kelas'];

// Cek apakah sudah pernah mengerjakan
$stmtCek = $pdo->prepare("SELECT * FROM tb_nilai_kuis WHERE id_kuis = ? AND nim = ?");
$stmtCek->execute([$id_kuis, $nim]);
if ($stmtCek->rowCount() > 0) {
    header("Location: kelas_detail.php?id=" . $id_kelas);
    exit();
}

// Ambil soal
$stmtSoal = $pdo->prepare("SELECT * FROM tb_soal_kuis WHERE id_kuis = ? ORDER BY id_soal ASC");
$stmtSoal->execute([$id_kuis]);
$soalList = $stmtSoal->fetchAll();

// Handle Submit Jawaban
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_kuis') {
    $total_soal = count($soalList);
    $benar = 0;

    if ($total_soal > 0) {
        foreach ($soalList as $soal) {
            $id_soal = $soal['id_soal'];
            // Cek jawaban yang dikirim user
            if (isset($_POST['soal_' . $id_soal])) {
                $jawaban_user = $_POST['soal_' . $id_soal];
                if ($jawaban_user === $soal['jawaban_benar']) {
                    $benar++;
                }
            }
        }
        $nilai_akhir = round(($benar / $total_soal) * 100);
    } else {
        $nilai_akhir = 100; // Jika tidak ada soal
    }

    // Simpan nilai
    $stmtSimpan = $pdo->prepare("INSERT INTO tb_nilai_kuis (id_kuis, nim, nilai) VALUES (?, ?, ?)");
    $stmtSimpan->execute([$id_kuis, $nim, $nilai_akhir]);

    header("Location: kelas_detail.php?id=" . $id_kelas . "&msg=kuis_selesai");
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kerjakan Kuis - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .soal-box {
            background-color: var(--primary-foreground);
            border: 3px solid var(--border);
            box-shadow: 4px 4px 0px var(--border);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .opsi-label {
            display: block;
            padding: 1rem;
            border: 2px solid var(--border);
            margin-bottom: 0.5rem;
            background-color: var(--background);
            font-weight: bold;
            cursor: pointer;
            transition: all 0.1s;
        }
        .opsi-label:hover {
            background-color: var(--muted);
            transform: translate(-2px, -2px);
            box-shadow: 2px 2px 0px var(--border);
        }
        .opsi-input {
            margin-right: 10px;
            transform: scale(1.2);
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="neo-layout-container">
    <div style="margin-bottom: 2rem; margin-top: 1rem; padding-left: 2rem;">
        <a href="kelas_detail.php?id=<?= $id_kelas ?>" class="brutal-hover neo-box" style="background-color: var(--accent); padding: 0.5rem 1rem; text-decoration: none; color: var(--primary-foreground); font-weight: 800; font-size: 0.9rem; display: inline-block;" onclick="return confirm('Yakin ingin kembali? Kuis belum disubmit.')">← KEMBALI KE KELAS</a>
    </div>

    <div class="neo-content-inner" style="max-width: 900px; margin: 0 auto; padding-bottom: 4rem;">
        
        <div class="brutal-card" style="background-color: var(--primary); color: var(--primary-foreground); margin-bottom: 2rem;">
            <h2 style="font-size: 2rem; font-weight: 900; text-transform: uppercase;"><?= htmlspecialchars($kuisInfo['judul_kuis']) ?></h2>
            <p style="font-weight: bold; margin-top: 0.5rem;">Durasi Maksimal: <?= htmlspecialchars($kuisInfo['durasi_menit']) ?> Menit | Total Soal: <?= count($soalList) ?></p>
        </div>

        <?php if (count($soalList) > 0): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="submit_kuis">
                
                <?php foreach ($soalList as $idx => $soal): ?>
                    <div class="soal-box">
                        <div style="font-weight: 900; font-size: 1.2rem; margin-bottom: 1.5rem; line-height: 1.5;">
                            <?= ($idx + 1) ?>. <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                        </div>
                        
                        <label class="opsi-label">
                            <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="A" class="opsi-input" required>
                            A. <?= htmlspecialchars($soal['opsi_a']) ?>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="B" class="opsi-input" required>
                            B. <?= htmlspecialchars($soal['opsi_b']) ?>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="C" class="opsi-input" required>
                            C. <?= htmlspecialchars($soal['opsi_c']) ?>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="soal_<?= $soal['id_soal'] ?>" value="D" class="opsi-input" required>
                            D. <?= htmlspecialchars($soal['opsi_d']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <div class="brutal-card" style="text-align: center; background-color: var(--card);">
                    <p style="font-weight: bold; margin-bottom: 1rem;">Pastikan semua soal sudah dijawab sebelum mengirimkan.</p>
                    <button type="submit" class="btn-accent" style="width: 100%; padding: 1.2rem; font-size: 1.4rem;" onclick="return confirm('Kumpulkan kuis sekarang?')">SUBMIT JAWABAN</button>
                </div>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; border: 3px dashed var(--border); font-weight: bold; color: var(--muted-foreground);">
                Pengajar belum memasukkan soal ke dalam kuis ini.
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
