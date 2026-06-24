<?php
session_start();
require_once 'includes/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Hanya Pimpinan dan Admin yang boleh akses
$role = $_SESSION['role'];
$username = $_SESSION['username'];

if (strtolower($role) !== 'pimpinan' && strtolower($role) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// ========== QUERY DATA LAPORAN ==========

// Total Kelas Aktif
$stmt_kelas = $pdo->query("SELECT COUNT(*) as total FROM tb_kelas");
$total_kelas = $stmt_kelas->fetch()['total'];

// Total Materi
$stmt_materi = $pdo->query("SELECT COUNT(*) as total FROM tb_materi");
$total_materi = $stmt_materi->fetch()['total'];

// Total Murid (Murid)
$stmt_murid = $pdo->query("SELECT COUNT(*) as total FROM tb_murid");
$total_murid = $stmt_murid->fetch()['total'];

// Total Pengajar
$stmt_pengajar = $pdo->query("SELECT COUNT(*) as total FROM tb_pengajar");
$total_pengajar = $stmt_pengajar->fetch()['total'];

// Total Tugas
$stmt_tugas = $pdo->query("SELECT COUNT(*) as total FROM tb_tugas");
$total_tugas = $stmt_tugas->fetch()['total'];

// Aktivitas Kelas Terbaru (data real)
$stmt_aktivitas = $pdo->query("
    SELECT 
        k.id_kelas, 
        k.nama_kelas, 
        COALESCE(p.nama_pengajar, 'Belum Ditentukan') as nama_pengajar,
        (SELECT COUNT(*) FROM tb_peserta_kelas pk WHERE pk.id_kelas = k.id_kelas) as jumlah_siswa,
        (SELECT COUNT(*) FROM tb_materi m WHERE m.id_kelas = k.id_kelas) as jumlah_materi,
        (SELECT COUNT(*) FROM tb_tugas t WHERE t.id_kelas = k.id_kelas) as jumlah_tugas
    FROM tb_kelas k
    LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
    ORDER BY k.id_kelas DESC
");
$aktivitas_kelas = $stmt_aktivitas->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* ===================================================
           LAPORAN PAGE — NEO BRUTALISM STYLE
           Semua class diawali .lap- agar tidak bentrok
           =================================================== */

        .lap-page {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
        }

        /* ---------- HEADER / BANNER ---------- */
        .lap-header {
            background-color: #6C3CE1;
            color: #FFF;
            padding: 2.2rem 2.5rem;
            border: 3px solid #000;
            box-shadow: 8px 8px 0px #000;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .lap-header::after {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            background-color: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .lap-header h1 {
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            margin-bottom: 0.4rem;
        }
        .lap-header p {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.9;
            margin: 0;
        }
        .lap-header .lap-meta {
            margin-top: 1rem;
            font-size: 0.85rem;
            opacity: 0.75;
            font-weight: 500;
        }

        /* ---------- ACTION BAR (Tombol Cetak) ---------- */
        .lap-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .lap-action-bar .lap-subtitle {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--foreground);
        }

        .lap-btn-cetak {
            background-color: #FF4C4C;
            color: #FFF;
            border: 3px solid #000;
            box-shadow: 6px 6px 0px #000;
            padding: 1rem 2.5rem;
            font-size: 1.15rem;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            transition: transform 0.12s, box-shadow 0.12s;
            letter-spacing: 0.5px;
        }
        .lap-btn-cetak:hover {
            transform: translate(-3px, -3px);
            box-shadow: 9px 9px 0px #000;
        }
        .lap-btn-cetak:active {
            transform: translate(4px, 4px);
            box-shadow: 2px 2px 0px #000;
        }

        /* ---------- STATISTIK CARDS ---------- */
        .lap-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .lap-stat-card {
            border: 3px solid #000;
            box-shadow: 6px 6px 0px #000;
            padding: 1.8rem 1.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
            overflow: hidden;
        }
        .lap-stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background-color: #000;
        }

        /* Warna berbeda per card */
        .lap-stat-card--kuning   { background-color: #FFE66D; }
        .lap-stat-card--cyan     { background-color: #7DFFC5; }
        .lap-stat-card--pink     { background-color: #FF9DE2; }
        .lap-stat-card--biru     { background-color: #A8D8FF; }
        .lap-stat-card--oranye   { background-color: #FFB86C; }

        .lap-stat-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 9px 9px 0px #000;
        }

        .lap-stat-icon {
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        .lap-stat-value {
            font-size: 3.2rem;
            font-weight: 900;
            color: #000;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .lap-stat-label {
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #333;
            letter-spacing: 0.8px;
        }

        /* ---------- SECTION TITLE ---------- */
        .lap-section-title {
            font-size: 1.6rem;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--foreground);
            margin-bottom: 1.2rem;
            padding-bottom: 0.5rem;
            border-bottom: 4px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* ---------- TABEL ---------- */
        .lap-table-wrapper {
            border: 3px solid #000;
            box-shadow: 6px 6px 0px #000;
            overflow-x: auto;
            margin-bottom: 2.5rem;
            background-color: #FFF;
        }
        .lap-table {
            width: 100%;
            border-collapse: collapse;
        }
        .lap-table thead th {
            padding: 1rem 1.2rem;
            background-color: #6C3CE1;
            color: #FFF;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            text-align: left;
            border-bottom: 3px solid #000;
            white-space: nowrap;
        }
        .lap-table tbody td {
            padding: 0.85rem 1.2rem;
            border-bottom: 2px solid #E8E8E8;
            font-weight: 600;
            font-size: 0.95rem;
            color: #222;
        }
        .lap-table tbody tr:nth-child(even) {
            background-color: #FAFAFA;
        }
        .lap-table tbody tr:hover {
            background-color: #F0EAFF;
        }
        .lap-table tbody tr:last-child td {
            border-bottom: none;
        }

        .lap-badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border: 2px solid #000;
            font-weight: 800;
            font-size: 0.78rem;
            text-transform: uppercase;
        }
        .lap-badge--aktif {
            background-color: #7DFFC5;
            color: #000;
        }
        .lap-badge--kosong {
            background-color: #FFE66D;
            color: #000;
        }

        /* ---------- FOOTER CETAK (tersembunyi di layar) ---------- */
        .lap-print-footer {
            display: none;
        }

        /* ---------- UTILITY CLASS PENGAMAN ---------- */
        .no-print {
            /* Class ini bisa ditambahkan ke sidebar/navbar di halaman lain */
        }

        /* ===========================================================
           @MEDIA PRINT — Optimasi Cetak ke Kertas
           =========================================================== */
        @media print {

            /* Sembunyikan elemen navigasi & tombol cetak */
            .no-print,
            .neo-navbar,
            .lap-btn-cetak,
            .lap-action-bar,
            nav,
            .neo-logout-btn,
            .neo-navbar-user {
                display: none !important;
            }

            /* Reset background & warna ke hitam putih */
            * {
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                background: #FFF !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .neo-main-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .neo-layout-container {
                padding: 0 !important;
                max-width: 100% !important;
            }

            /* Header tetap berwarna untuk identitas */
            .lap-header {
                background-color: #6C3CE1 !important;
                color: #FFF !important;
                box-shadow: none !important;
                border: 2px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin-bottom: 1.5rem !important;
            }
            .lap-header h1,
            .lap-header p,
            .lap-header .lap-meta {
                color: #FFF !important;
            }

            /* Cards: hilangkan shadow, buat simpel */
            .lap-stat-card {
                box-shadow: none !important;
                border: 2px solid #000 !important;
                break-inside: avoid;
            }
            .lap-stat-card--kuning   { background-color: #FFF9D6 !important; }
            .lap-stat-card--cyan     { background-color: #E0FFF0 !important; }
            .lap-stat-card--pink     { background-color: #FFE8F7 !important; }
            .lap-stat-card--biru     { background-color: #E8F4FF !important; }
            .lap-stat-card--oranye   { background-color: #FFF3E0 !important; }

            .lap-stat-card:hover {
                transform: none !important;
            }

            /* Tabel: hilangkan shadow */
            .lap-table-wrapper {
                box-shadow: none !important;
                border: 2px solid #000 !important;
            }
            .lap-table thead th {
                background-color: #333 !important;
                color: #FFF !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                border-bottom: 2px solid #000 !important;
            }
            .lap-table tbody td {
                color: #000 !important;
                border-bottom: 1px solid #CCC !important;
            }
            .lap-table tbody tr:hover {
                background-color: transparent !important;
            }
            .lap-table tbody tr:nth-child(even) {
                background-color: #F5F5F5 !important;
            }

            /* Badge: hilangkan warna cerah */
            .lap-badge {
                border: 1px solid #000 !important;
                background-color: #EEE !important;
            }

            /* Section title */
            .lap-section-title {
                border-bottom: 2px solid #000 !important;
                page-break-after: avoid;
            }

            /* Tampilkan footer cetak */
            .lap-print-footer {
                display: block !important;
                margin-top: 2.5rem;
                padding-top: 1.5rem;
                border-top: 2px solid #000;
                text-align: center;
                font-size: 0.85rem;
                color: #555 !important;
            }

            /* Atur margin kertas */
            @page {
                margin: 1.5cm;
                size: A4 portrait;
            }
        }

        /* ---------- DARK MODE ---------- */
        .dark-mode .lap-table-wrapper {
            background-color: #2A2A2A;
            border-color: #FFF;
            box-shadow: 6px 6px 0px #FFF;
        }
        .dark-mode .lap-table thead th {
            border-bottom-color: #FFF;
        }
        .dark-mode .lap-table tbody td {
            color: #EEE;
            border-bottom-color: #444;
        }
        .dark-mode .lap-table tbody tr:nth-child(even) {
            background-color: #333;
        }
        .dark-mode .lap-table tbody tr:hover {
            background-color: #3A2E5A;
        }
        .dark-mode .lap-stat-card {
            border-color: #FFF;
            box-shadow: 6px 6px 0px #FFF;
        }
        .dark-mode .lap-header {
            border-color: #FFF;
            box-shadow: 8px 8px 0px #FFF;
        }
        .dark-mode .lap-btn-cetak {
            border-color: #FFF;
            box-shadow: 6px 6px 0px #FFF;
        }
        .dark-mode .lap-btn-cetak:hover {
            box-shadow: 9px 9px 0px #FFF;
        }
        .dark-mode .lap-section-title {
            border-bottom-color: #FFF;
        }

        /* Animasi masuk card */
        @keyframes lapCardPop {
            0% { transform: scale(0.92) translateY(15px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .lap-stat-card {
            animation: lapCardPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .lap-stat-card:nth-child(1) { animation-delay: 0.05s; }
        .lap-stat-card:nth-child(2) { animation-delay: 0.12s; }
        .lap-stat-card:nth-child(3) { animation-delay: 0.19s; }
        .lap-stat-card:nth-child(4) { animation-delay: 0.26s; }
        .lap-stat-card:nth-child(5) { animation-delay: 0.33s; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="neo-main-content">
    <main class="neo-layout-container">

        <div class="lap-page">

            <!-- ============ HEADER ============ -->
            <div class="lap-header">
                <h1>📊 Laporan Rekapitulasi E-Learning</h1>
                <p>Ringkasan data keseluruhan Sistem Informasi E-Learning Programming</p>
                <div class="lap-meta">
                    Dicetak oleh: <strong><?= htmlspecialchars(strtoupper($username)) ?></strong> (<?= htmlspecialchars($role) ?>)
                    &nbsp;•&nbsp; Tanggal: <strong><?= date('d F Y, H:i') ?> WIB</strong>
                </div>
            </div>

            <!-- ============ TOMBOL CETAK ============ -->
            <div class="lap-action-bar no-print">
                <span class="lap-subtitle">Dashboard Rekapitulasi Data</span>
                <button class="lap-btn-cetak" onclick="window.print()">
                    🖨️ CETAK LAPORAN
                </button>
            </div>

            <!-- ============ STATISTIK CARDS ============ -->
            <div class="lap-stats-grid">
                <div class="lap-stat-card lap-stat-card--kuning">
                    <div class="lap-stat-icon">📚</div>
                    <div class="lap-stat-value"><?= $total_kelas ?></div>
                    <div class="lap-stat-label">Total Kelas Aktif</div>
                </div>
                <div class="lap-stat-card lap-stat-card--cyan">
                    <div class="lap-stat-icon">📖</div>
                    <div class="lap-stat-value"><?= $total_materi ?></div>
                    <div class="lap-stat-label">Total Materi</div>
                </div>
                <div class="lap-stat-card lap-stat-card--pink">
                    <div class="lap-stat-icon">🎓</div>
                    <div class="lap-stat-value"><?= $total_murid ?></div>
                    <div class="lap-stat-label">Total Murid</div>
                </div>
                <div class="lap-stat-card lap-stat-card--biru">
                    <div class="lap-stat-icon">👨‍🏫</div>
                    <div class="lap-stat-value"><?= $total_pengajar ?></div>
                    <div class="lap-stat-label">Total Pengajar</div>
                </div>
                <div class="lap-stat-card lap-stat-card--oranye">
                    <div class="lap-stat-icon">📝</div>
                    <div class="lap-stat-value"><?= $total_tugas ?></div>
                    <div class="lap-stat-label">Total Tugas</div>
                </div>
            </div>

            <!-- ============ TABEL AKTIVITAS KELAS ============ -->
            <h2 class="lap-section-title">
                <i class='bx bx-table'></i> Aktivitas Kelas Terbaru
            </h2>

            <div class="lap-table-wrapper">
                <table class="lap-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kelas</th>
                            <th>Pengajar</th>
                            <th>Jumlah Siswa</th>
                            <th>Jumlah Materi</th>
                            <th>Jumlah Tugas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($aktivitas_kelas) > 0): ?>
                            <?php $no = 1; foreach ($aktivitas_kelas as $ak): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($ak['nama_kelas']) ?></strong></td>
                                    <td><?= htmlspecialchars($ak['nama_pengajar']) ?></td>
                                    <td style="text-align: center;"><?= $ak['jumlah_siswa'] ?></td>
                                    <td style="text-align: center;"><?= $ak['jumlah_materi'] ?></td>
                                    <td style="text-align: center;"><?= $ak['jumlah_tugas'] ?></td>
                                    <td>
                                        <?php if ($ak['jumlah_siswa'] > 0): ?>
                                            <span class="lap-badge lap-badge--aktif">Aktif</span>
                                        <?php else: ?>
                                            <span class="lap-badge lap-badge--kosong">Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; font-weight: 700;">
                                    Belum ada data kelas tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (count($aktivitas_kelas) > 0): ?>
                    <tfoot>
                        <tr style="background-color: #F0F0F0; border-top: 3px solid #000;">
                            <td colspan="3" style="padding: 0.85rem 1.2rem; font-weight: 900; text-transform: uppercase;">
                                Total
                            </td>
                            <td style="text-align: center; font-weight: 900; padding: 0.85rem;">
                                <?= array_sum(array_column($aktivitas_kelas, 'jumlah_siswa')) ?>
                            </td>
                            <td style="text-align: center; font-weight: 900; padding: 0.85rem;">
                                <?= array_sum(array_column($aktivitas_kelas, 'jumlah_materi')) ?>
                            </td>
                            <td style="text-align: center; font-weight: 900; padding: 0.85rem;">
                                <?= array_sum(array_column($aktivitas_kelas, 'jumlah_tugas')) ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- ============ FOOTER CETAK (hanya muncul saat print) ============ -->
            <div class="lap-print-footer">
                <p><strong>Laporan Rekapitulasi E-Learning</strong></p>
                <p>Dicetak pada: <?= date('d F Y, H:i') ?> WIB — oleh <?= htmlspecialchars(strtoupper($username)) ?> (<?= htmlspecialchars($role) ?>)</p>
                <p style="margin-top: 0.5rem;">Sistem Informasi E-Learning Programming &copy; <?= date('Y') ?></p>
            </div>

        </div>

    </main>
</div>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
