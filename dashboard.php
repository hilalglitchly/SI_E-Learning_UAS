<?php
session_start();
require_once 'includes/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Fitur Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fitur Delete User untuk Admin
if (isset($_GET['action']) && $_GET['action'] == 'delete_user') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin' && isset($_GET['id'])) {
        $del_id = $_GET['id'];
        $stmt_del = $pdo->prepare("DELETE FROM tb_user WHERE id_user = :id");
        $stmt_del->execute(['id' => $del_id]);
        header("Location: dashboard.php?msg=success_delete");
        exit();
    }
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil daftar kelas berdasarkan role
$classes = [];
$total_kelas = 0;
$total_tugas = 0;
$status_user = '';

if (strtolower($role) === 'murid') {
    $sql = "
        SELECT k.id_kelas, k.nama_kelas, k.deskripsi, p.nama_pengajar, m.nim,
            (SELECT COUNT(*) FROM tb_tugas WHERE id_kelas = k.id_kelas) as total_tugas_kelas,
            (SELECT COUNT(*) FROM tb_kuis WHERE id_kelas = k.id_kelas) as total_kuis_kelas,
            (SELECT COUNT(DISTINCT s.id_tugas) FROM tb_submission s JOIN tb_tugas t ON s.id_tugas = t.id_tugas WHERE t.id_kelas = k.id_kelas AND s.nim = m.nim) as tugas_selesai,
            (SELECT COUNT(DISTINCT nk.id_kuis) FROM tb_nilai_kuis nk JOIN tb_kuis ku ON nk.id_kuis = ku.id_kuis WHERE ku.id_kelas = k.id_kelas AND nk.nim = m.nim) as kuis_selesai
        FROM tb_murid m
        JOIN tb_peserta_kelas pk ON m.nim = pk.nim
        JOIN tb_kelas k ON pk.id_kelas = k.id_kelas
        LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
        WHERE m.id_user = :id_user
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->execute();
    $classes = $stmt->fetchAll();
    $total_kelas = count($classes);

    // Hitung total tugas dari semua kelas yang diikuti
    $sql_tugas = "
        SELECT COUNT(*) as total FROM tb_tugas t
        JOIN tb_kelas k ON t.id_kelas = k.id_kelas
        JOIN tb_peserta_kelas pk ON k.id_kelas = pk.id_kelas
        JOIN tb_murid m ON pk.nim = m.nim
        WHERE m.id_user = :id_user
    ";
    $stmt_tugas = $pdo->prepare($sql_tugas);
    $stmt_tugas->execute(['id_user' => $id_user]);
    $total_tugas = $stmt_tugas->fetch()['total'];
    $status_user = 'Murid Aktif';

} elseif ($role === 'Pengajar') {
    $sql = "
        SELECT k.id_kelas, k.nama_kelas, k.deskripsi 
        FROM tb_kelas k
        JOIN tb_pengajar p ON k.nidn = p.nidn
        WHERE p.id_user = :id_user
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->execute();
    $classes = $stmt->fetchAll();
    $total_kelas = count($classes);

    // Hitung total tugas dari semua kelas yang diampu
    $sql_tugas = "
        SELECT COUNT(*) as total FROM tb_tugas t
        JOIN tb_kelas k ON t.id_kelas = k.id_kelas
        JOIN tb_pengajar p ON k.nidn = p.nidn
        WHERE p.id_user = :id_user
    ";
    $stmt_tugas = $pdo->prepare($sql_tugas);
    $stmt_tugas->execute(['id_user' => $id_user]);
    $total_tugas = $stmt_tugas->fetch()['total'];
    $status_user = 'Pengajar';

} elseif (strtolower($role) === 'admin') {
    $stmt_p = $pdo->query("
        SELECT p.nidn, p.nama_pengajar, p.email, p.id_user 
        FROM tb_pengajar p
    ");
    $pengajar_list = $stmt_p->fetchAll();

    $stmt_m = $pdo->query("
        SELECT m.nim, m.nama_murid, m.email, m.id_user 
        FROM tb_murid m
    ");
    $murid_list = $stmt_m->fetchAll();

    $total_kelas = count($pengajar_list) + count($murid_list);
    $status_user = 'Administrator';
} elseif (strtolower($role) === 'pimpinan') {
    // Hitung total kelas
    $stmt_total_kelas = $pdo->query("SELECT COUNT(*) as total FROM tb_kelas");
    $total_kelas_pimpinan = $stmt_total_kelas->fetch()['total'];

    // Hitung total materi
    $stmt_total_materi = $pdo->query("SELECT COUNT(*) as total FROM tb_materi");
    $total_materi_pimpinan = $stmt_total_materi->fetch()['total'];

    // Hitung total pengajar
    $stmt_total_pengajar = $pdo->query("SELECT COUNT(*) as total FROM tb_pengajar");
    $total_pengajar_pimpinan = $stmt_total_pengajar->fetch()['total'];

    // Hitung total murid
    $stmt_total_murid = $pdo->query("SELECT COUNT(*) as total FROM tb_murid");
    $total_murid_pimpinan = $stmt_total_murid->fetch()['total'];

    // Ambil data kelas lengkap untuk laporan
    $stmt_kelas_list = $pdo->query("
        SELECT k.id_kelas, k.nama_kelas, k.deskripsi, p.nama_pengajar,
               (SELECT COUNT(*) FROM tb_peserta_kelas pk WHERE pk.id_kelas = k.id_kelas) as jumlah_peserta,
               (SELECT COUNT(*) FROM tb_materi m WHERE m.id_kelas = k.id_kelas) as jumlah_materi
        FROM tb_kelas k
        LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
        ORDER BY k.id_kelas ASC
    ");
    $kelas_laporan = $stmt_kelas_list->fetchAll();

    $status_user = 'Pimpinan';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<script src="assets/js/neo-alert.js?v=<?= time() ?>"></script>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">

    <main class="neo-layout-container">

    <!-- Flash Messages via SweetAlert2 -->
    <?php if (isset($_GET['msg'])): ?>
        <?php 
            $msg_text = "";
            if ($_GET['msg'] == 'success') $msg_text = "Pendaftaran Kelas Berhasil!";
            elseif ($_GET['msg'] == 'success_add_user') $msg_text = "Pengguna baru berhasil ditambahkan!";
            elseif ($_GET['msg'] == 'success_delete') $msg_text = "Pengguna berhasil dihapus.";
        ?>
        <?php if ($msg_text): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                NeoToast('<?= $msg_text ?>', 'success');
            });
        </script>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (strtolower($role) === 'admin'): ?>
        <!-- ==================== ADMIN VIEW ==================== -->
        
        <!-- Greeting Banner -->
        <div class="neo-box" style="background-color: #FFD700; color: #000; padding: 2rem; margin-bottom: 2rem;">
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; font-weight: 800; text-transform: uppercase; letter-spacing: -1px;">Selamat Datang Kembali, <?= htmlspecialchars($username) ?>!</h1>
            <p style="font-size: 1.1rem; margin-bottom: 0; font-weight: 600;">Panel Administrasi E-Learning — Kelola seluruh pengguna dari sini.</p>
        </div>

        <!-- Summary Cards -->
        <div class="neo-summary-row">
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">👨‍🏫</div>
                <div class="neo-summary-value"><?= count($pengajar_list) ?></div>
                <div class="neo-summary-label">Total Pengajar</div>
            </div>
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">🎓</div>
                <div class="neo-summary-value"><?= count($murid_list) ?></div>
                <div class="neo-summary-label">Total Murid</div>
            </div>
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">🛡️</div>
                <div class="neo-summary-value"><?= htmlspecialchars($status_user) ?></div>
                <div class="neo-summary-label">Status Pengguna</div>
            </div>
        </div>

        <!-- Data Pengajar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.8rem; text-transform: uppercase; font-weight: 800; color: var(--foreground);">Data Pengajar</h3>
        </div>
        
        <div class="neo-table-wrapper">
            <table class="neo-table">
                <thead>
                    <tr>
                        <th>NIDN</th>
                        <th>Nama Pengajar</th>
                        <th>Email</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengajar_list as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nidn']) ?></td>
                            <td><strong><?= htmlspecialchars($p['nama_pengajar']) ?></strong></td>
                            <td><?= htmlspecialchars($p['email'] ?? 'Tidak ada email') ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="update_user.php?id=<?= htmlspecialchars($p['id_user']) ?>" class="neo-btn" style="background-color: var(--secondary); color: #000; padding: 0.4rem 0.8rem; text-decoration: none;">UPDATE</a>
                                    <a href="#" onclick="confirmDelete(event, '?action=delete_user&id=<?= htmlspecialchars($p['id_user']) ?>')" class="neo-btn" style="background-color: var(--primary); color: #fff; padding: 0.4rem 0.8rem; text-decoration: none;">HAPUS</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($pengajar_list) == 0): ?>
                        <tr><td colspan="4" style="text-align: center; font-weight: bold;">Belum ada data pengajar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel Murid -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.8rem; text-transform: uppercase; font-weight: 800; color: var(--foreground);">Data Murid</h3>
        </div>
        
        <div class="neo-table-wrapper">
            <table class="neo-table">
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Murid</th>
                        <th>Email</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($murid_list as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['nim']) ?></td>
                            <td><strong><?= htmlspecialchars($m['nama_murid']) ?></strong></td>
                            <td><?= htmlspecialchars($m['email'] ?? 'Tidak ada email') ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="update_user.php?id=<?= htmlspecialchars($m['id_user']) ?>" class="neo-btn" style="background-color: var(--secondary); color: #000; padding: 0.4rem 0.8rem; text-decoration: none;">UPDATE</a>
                                    <a href="#" onclick="confirmDelete(event, '?action=delete_user&id=<?= htmlspecialchars($m['id_user']) ?>')" class="neo-btn" style="background-color: var(--primary); color: #fff; padding: 0.4rem 0.8rem; text-decoration: none;">HAPUS</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($murid_list) == 0): ?>
                        <tr><td colspan="4" style="text-align: center; font-weight: bold;">Belum ada data murid.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif (strtolower($role) === 'pimpinan'): ?>
        <!-- ==================== PIMPINAN VIEW ==================== -->


        <div id="pimpinan-laporan">
            
            <!-- Banner Pimpinan -->
            <div class="neo-box" style="background-color: #FFD700; color: #000; padding: 2rem; margin-bottom: 2rem;">
                <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; font-weight: 800; text-transform: uppercase; letter-spacing: -1px;">📊 Dashboard Pimpinan</h1>
                <p style="font-size: 1.1rem; margin-bottom: 0; font-weight: 600;">Selamat datang, <strong><?= htmlspecialchars($username) ?></strong> — Berikut ringkasan data E-Learning terkini.</p>
            </div>

            <!-- Tombol Cetak -->
            <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
                <button class="neo-btn" style="background-color: var(--primary); color: #fff; padding: 1rem 2rem; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;" onclick="window.print()">
                    <i class='bx bx-printer'></i> CETAK LAPORAN
                </button>
            </div>

            <!-- Statistik Cards -->
            <div class="neo-summary-row">
                <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                    <div class="neo-summary-icon">📚</div>
                    <div class="neo-summary-value count-up" data-target="<?= $total_kelas_pimpinan ?>">0</div>
                    <div class="neo-summary-label">Total Kelas</div>
                </div>
                <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                    <div class="neo-summary-icon">📖</div>
                    <div class="neo-summary-value count-up" data-target="<?= $total_materi_pimpinan ?>">0</div>
                    <div class="neo-summary-label">Total Materi</div>
                </div>
                <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                    <div class="neo-summary-icon">👨‍🏫</div>
                    <div class="neo-summary-value count-up" data-target="<?= $total_pengajar_pimpinan ?>">0</div>
                    <div class="neo-summary-label">Total Pengajar</div>
                </div>
                <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                    <div class="neo-summary-icon">🎓</div>
                    <div class="neo-summary-value count-up" data-target="<?= $total_murid_pimpinan ?>">0</div>
                    <div class="neo-summary-label">Total Murid</div>
                </div>
            </div>

            <!-- Tabel Rekap Data Kelas -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1.8rem; text-transform: uppercase; font-weight: 800; color: var(--foreground);">📋 Rekap Data Kelas</h3>
            </div>

            <div class="neo-table-wrapper">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kelas</th>
                            <th>Pengajar</th>
                            <th>Jumlah Peserta</th>
                            <th>Jumlah Materi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kelas_laporan) > 0): ?>
                            <?php $no = 1; foreach ($kelas_laporan as $kl): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($kl['nama_kelas']) ?></strong></td>
                                    <td><?= htmlspecialchars($kl['nama_pengajar'] ?? 'Belum ditentukan') ?></td>
                                    <td><span style="background-color: var(--secondary); border: 2px solid var(--border); padding: 0.2rem 0.5rem; font-weight: bold; font-size: 0.85rem; box-shadow: 2px 2px 0px var(--border);"><?= $kl['jumlah_peserta'] ?> Peserta</span></td>
                                    <td><span style="background-color: var(--accent); color: #fff; border: 2px solid var(--border); padding: 0.2rem 0.5rem; font-weight: bold; font-size: 0.85rem; box-shadow: 2px 2px 0px var(--border);"><?= $kl['jumlah_materi'] ?> Materi</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; font-weight: 700;">Belum ada data kelas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Cetak (hanya muncul di print) -->
            <div class="pimpinan-print-footer" style="display: none;">
                <p><strong>Laporan E-Learning</strong> — Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
                <p>Sistem Informasi E-Learning Programming</p>
            </div>

        </div>

    <?php else: ?>
        <!-- ==================== MURID / PENGAJAR VIEW ==================== -->

        <!-- Greeting Banner -->
        <div class="neo-box" style="background-color: #FFD700; color: #000; padding: 2rem; margin-bottom: 2rem;">
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; font-weight: 800; text-transform: uppercase; letter-spacing: -1px;">Selamat Datang Kembali, <?= htmlspecialchars($username) ?>!</h1>
            <p style="font-size: 1.1rem; margin-bottom: 0; font-weight: 600;">Berikut ringkasan aktivitas akademik Anda hari ini.</p>
        </div>

        <!-- Summary Cards -->
        <div class="neo-summary-row">
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">📚</div>
                <div class="neo-summary-value count-up" data-target="<?= $total_kelas ?>">0</div>
                <div class="neo-summary-label">Total Kelas</div>
            </div>
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">📝</div>
                <div class="neo-summary-value count-up" data-target="<?= $total_tugas ?>">0</div>
                <div class="neo-summary-label">Tugas Tersedia</div>
            </div>
            <div class="neo-summary-card brutal-hover" style="background-color: var(--card);">
                <div class="neo-summary-icon">🎓</div>
                <div class="neo-summary-value"><?= htmlspecialchars($status_user) ?></div>
                <div class="neo-summary-label">Status Pengguna</div>
            </div>
        </div>

        <!-- Kelas Section Title -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.8rem; font-weight: 800; text-transform: uppercase; color: var(--foreground);">Kelas <?= strtolower($role) === 'murid' ? 'Akademik' : 'yang Diampu' ?></h2>
            <?php if (strtolower($role) === 'murid'): ?>
                <a href="enroll.php" class="neo-btn neo-box" style="width: auto; background-color: var(--accent); padding: 0.6rem 1.2rem; text-decoration: none; color: var(--accent-foreground); font-weight: 900; text-transform: uppercase;">+ Cari Kelas Baru</a>
            <?php endif; ?>
        </div>
        
        <div style="margin-bottom: 2rem; font-weight: 700; font-size: 1.1rem; color: var(--foreground);">
            Anda memiliki <?= count($classes) ?> kelas aktif pada periode ini.
        </div>

        <div class="neo-grid">
            <?php if (count($classes) > 0): ?>
                <?php foreach ($classes as $kelas): ?>
                    <div class="neo-card brutal-hover">
                        <div class="neo-card-header">
                            <div class="neo-card-title"><?= htmlspecialchars($kelas['nama_kelas']) ?></div>
                            <div class="neo-card-subtitle">Sistem Informasi</div>
                        </div>
                        <div class="neo-card-body">
                            <div class="neo-card-info">
                                <span>👤</span> <?= htmlspecialchars($kelas['nama_pengajar'] ?? 'Belum Ditentukan') ?>
                            </div>
                            <div class="neo-card-info">
                                <span>🗓️</span> Jadwal belum diatur
                            </div>
                        </div>
                        <?php if (strtolower($role) === 'murid'): ?>
                            <?php 
                            $real_progress = 0;
                            if (isset($kelas['total_tugas_kelas'])) {
                                $total_items = $kelas['total_tugas_kelas'] + $kelas['total_kuis_kelas'];
                                $total_done = $kelas['tugas_selesai'] + $kelas['kuis_selesai'];
                                if ($total_items > 0) {
                                    $real_progress = round(($total_done / $total_items) * 100);
                                } else {
                                    $real_progress = 100;
                                }
                            }
                            ?>
                            <div class="neo-card-progress" style="margin-top: 1.5rem; display: block; background: transparent; padding: 0; border: none;">
                                <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 0.85rem; margin-bottom: 0.5rem; text-transform: uppercase;">
                                    <span>Progress Belajar</span>
                                    <span><?= $real_progress ?>%</span>
                                </div>
                                <div style="width: 100%; height: 14px; background-color: var(--muted); border: 2px solid var(--border); position: relative; overflow: hidden; box-shadow: inset 2px 2px 0px rgba(0,0,0,0.1);">
                                    <div style="width: 0%; height: 100%; background-color: var(--primary); border-right: 2px solid var(--border); transition: width 1.2s cubic-bezier(0.22, 1, 0.36, 1);" class="progress-fill" data-width="<?= $real_progress ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="kelas_detail.php?id=<?= htmlspecialchars($kelas['id_kelas']) ?>" class="neo-btn neo-box" style="margin-top: 1rem; text-decoration: none; box-sizing: border-box;">Masuk Kelas</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="neo-box" style="grid-column: 1 / -1; text-align: center; padding: 5rem 2rem; background: repeating-linear-gradient(45deg, var(--muted), var(--muted) 10px, transparent 10px, transparent 20px); border: 4px dashed var(--border);">
                    <div style="display: inline-block; background-color: #FFF; border: 4px solid #000; padding: 2rem; box-shadow: 8px 8px 0px #000; transform: rotate(-3deg); margin-bottom: 2.5rem; transition: transform 0.3s;" onmouseover="this.style.transform='rotate(3deg) scale(1.1)'" onmouseout="this.style.transform='rotate(-3deg) scale(1)'">
                        <i class='bx bx-ghost' style="font-size: 6rem; color: var(--primary);"></i>
                    </div>
                    <div style="background-color: var(--card); border: 3px solid var(--border); box-shadow: 6px 6px 0px var(--border); padding: 2rem; display: inline-block; transform: rotate(1deg); max-width: 500px;">
                        <h3 class="neo-card-title" style="color: var(--foreground); font-size: 1.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Belum Ada Kelas Aktif</h3>
                        <p style="font-weight: 600; color: var(--muted-foreground); font-size: 1.1rem; line-height: 1.5;">Sepertinya Anda belum memiliki kelas di periode ini. Yuk, jelajahi katalog untuk menemukan kelas baru!</p>
                        <a href="enroll.php" class="neo-btn neo-box" style="margin-top: 1.5rem; text-decoration: none; display: inline-block;">🔍 Cari Kelas Sekarang</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
    </main>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Efek Count-Up untuk Statistik
        const counters = document.querySelectorAll('.count-up');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const duration = 1500; // waktu animasi dalam ms
            let current = 0;
            
            if (target > 0) {
                const stepTime = Math.max(Math.floor(duration / target), 10);
                const increment = Math.ceil(target / (duration / stepTime));
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.innerText = target;
                        clearInterval(timer);
                    } else {
                        counter.innerText = current;
                    }
                }, stepTime);
            } else {
                counter.innerText = target;
            }
        });

        // Animasi Visual Progress Bar
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(fill => {
                fill.style.width = fill.getAttribute('data-width');
            });
        }, 150);
    });

    function confirmDelete(event, url) {
        event.preventDefault();
        NeoConfirm(
            'Apakah Anda Yakin?', 
            'Data yang dihapus tidak dapat dikembalikan!', 
            url
        );
    }
</script>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
