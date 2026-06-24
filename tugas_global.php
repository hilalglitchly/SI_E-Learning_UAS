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

$tugas_list = [];

try {
    if (strtolower($role) === 'murid') {
        // Langkah 1: Ambil NIM
        $stmt_nim = $pdo->prepare("SELECT nim FROM tb_murid WHERE id_user = :id_user");
        $stmt_nim->execute(['id_user' => $id_user]);
        $mhs = $stmt_nim->fetch();
        
        if ($mhs) {
            $nim_yang_didapat = $mhs['nim'];
            
            // Langkah 2: Gunakan NIM untuk query JOIN
            $sql = "
                SELECT t.id_tugas, t.judul_tugas, t.deskripsi, t.deadline, k.id_kelas, k.nama_kelas, p.nama_pengajar
                FROM tb_tugas t
                JOIN tb_kelas k ON t.id_kelas = k.id_kelas
                JOIN tb_peserta_kelas pk ON k.id_kelas = pk.id_kelas
                LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
                WHERE pk.nim = :nim
                  AND t.id_tugas NOT IN (SELECT id_tugas FROM tb_submission WHERE nim = pk.nim)
                ORDER BY t.deadline ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['nim' => $nim_yang_didapat]);
            $tugas_list = $stmt->fetchAll();
        }
    } elseif ($role === 'Pengajar') {
        $sql = "
            SELECT t.id_tugas, t.judul_tugas, t.deskripsi, t.deadline, k.id_kelas, k.nama_kelas
            FROM tb_tugas t
            JOIN tb_kelas k ON t.id_kelas = k.id_kelas
            JOIN tb_pengajar p ON k.nidn = p.nidn
            WHERE p.id_user = :id_user
            ORDER BY t.deadline ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_user' => $id_user]);
        $tugas_list = $stmt->fetchAll();
    } elseif (strtolower($role) === 'admin') {
        $sql = "
            SELECT t.id_tugas, t.judul_tugas, t.deskripsi, t.deadline, k.id_kelas, k.nama_kelas
            FROM tb_tugas t
            JOIN tb_kelas k ON t.id_kelas = k.id_kelas
            ORDER BY t.deadline ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $tugas_list = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan pada query: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">

    <main class="neo-layout-container">

        <div class="neo-content-inner">
            
            <div class="neo-box" style="background-color: #FFD700; color: #000; padding: 2rem; margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Semua Tugas Aktif</h2>
                <p style="font-size: 1rem; font-weight: 500;">Berikut adalah daftar semua tugas dari seluruh kelas Anda yang diurutkan berdasarkan batas waktu terdekat.</p>
            </div>

            <?php if (count($tugas_list) > 0): ?>
                    <div class="neo-grid">
                        <?php foreach ($tugas_list as $t): ?>
                            <?php 
                            $deadline_date = !empty($t['deadline']) ? new DateTime($t['deadline']) : null;
                            $now = new DateTime();
                            $is_overdue = $deadline_date && $deadline_date < $now;
                            ?>
                            <div class="neo-card" style="border: 3px solid var(--border); display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <!-- Class Badge -->
                                    <span class="neo-role-badge" style="background-color: var(--primary); color: #FFF; border: 2px solid var(--border); font-size: 0.75rem; padding: 0.2rem 0.5rem; margin-bottom: 1rem; display: inline-block; box-shadow: 1.5px 1.5px 0px var(--border);"><?= htmlspecialchars($t['nama_kelas']) ?></span>
                                    
                                    <h3 class="neo-card-title" style="margin-top: 0.5rem; font-size: 1.4rem; font-weight: 800;"><?= htmlspecialchars($t['judul_tugas']) ?></h3>
                                    
                                    <?php if (strtolower($role) === 'murid'): ?>
                                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--muted-foreground); margin-bottom: 1rem;">
                                            Dosen: <?= htmlspecialchars($t['nama_pengajar'] ?? 'Belum Ditentukan') ?>
                                        </div>
                                    <?php endif; ?>

                                    <p style="font-size: 0.95rem; font-weight: 500; margin-bottom: 1.5rem; color: var(--card-foreground); line-height: 1.4;">
                                        <?= htmlspecialchars(mb_strimwidth($t['deskripsi'] ?? 'Tidak ada deskripsi.', 0, 120, "...")) ?>
                                    </p>
                                </div>

                                <div>
                                    <!-- Deadline Info -->
                                    <div class="neo-box" style="background-color: <?= $is_overdue ? 'var(--primary)' : 'var(--card)' ?>; color: <?= $is_overdue ? '#FFF' : 'var(--foreground)' ?>; padding: 0.8rem; border: 3px solid var(--border); box-shadow: var(--shadow-offset-x) var(--shadow-offset-y) var(--shadow-blur) var(--shadow-color); font-size: 0.85rem; font-weight: 800; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                        <span>⏰ BATAS WAKTU:</span>
                                        <span><?= $deadline_date ? $deadline_date->format('d M Y, H:i') : 'Tanpa Batas' ?></span>
                                    </div>

                                    <!-- Action Button -->
                                    <a href="kelas_detail.php?id=<?= htmlspecialchars($t['id_kelas']) ?>" class="neo-btn neo-box" style="text-decoration: none; display: block; box-sizing: border-box;">
                                        <?= strtolower($role) === 'murid' ? '🏁 Kerjakan Tugas' : '👁️ Kelola Tugas' ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="neo-box" style="background-color: var(--primary-foreground); padding: 3rem; text-align: center;">
                        <span style="font-size: 4rem;">🎉</span>
                        <h3 style="font-size: 1.6rem; text-transform: uppercase; margin-top: 1.5rem; font-weight: 800;">Semua Beres!</h3>
                        <p style="font-weight: 500; margin-top: 0.5rem;">Tidak ada rekap tugas akademik yang aktif saat ini.</p>
                    </div>
                <?php endif; ?>

        </div>
    </main>
</div>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
