<?php
session_start();
require_once 'includes/koneksi.php';

// Proteksi Session: Hanya Pengajar yang boleh mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pengajar') {
    header("Location: login.php");
    exit();
}

// Pastikan ada parameter id_tugas
if (!isset($_GET['id_tugas'])) {
    die("ID Tugas tidak ditemukan.");
}

$id_tugas = $_GET['id_tugas'];
$id_user = $_SESSION['id_user'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Validasi apakah id_tugas ini berada di dalam kelas yang diajar oleh pengajar ini
$sql_check = "
    SELECT t.id_tugas, k.nama_kelas, t.judul_tugas 
    FROM tb_tugas t
    JOIN tb_kelas k ON t.id_kelas = k.id_kelas
    JOIN tb_pengajar p ON k.nidn = p.nidn
    WHERE t.id_tugas = :id_tugas AND p.id_user = :id_user
";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute(['id_tugas' => $id_tugas, 'id_user' => $id_user]);
$tugas_info = $stmt_check->fetch();

if (!$tugas_info) {
    die("Akses ditolak atau tugas tidak ditemukan.");
}

$pesan_sukses = '';

// Logika POST jika tombol Simpan ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_submission'])) {
    $id_submission = $_POST['id_submission'];
    $nilai = $_POST['nilai'];
    $catatan = $_POST['catatan'];

    $sql_update = "UPDATE tb_submission SET nilai = :nilai, catatan = :catatan WHERE id_submission = :id_submission";
    $stmt_update = $pdo->prepare($sql_update);
    if ($stmt_update->execute(['nilai' => $nilai, 'catatan' => $catatan, 'id_submission' => $id_submission])) {
        $pesan_sukses = "Nilai berhasil disimpan!";
    }
}

// Ambil daftar submission untuk tugas ini
$sql_subs = "
    SELECT s.id_submission, s.file_jawaban, s.tgl_kumpul, s.nilai, s.catatan, m.nama_murid 
    FROM tb_submission s
    JOIN tb_murid m ON s.nim = m.nim
    WHERE s.id_tugas = :id_tugas
    ORDER BY s.tgl_kumpul ASC
";
$stmt_subs = $pdo->prepare($sql_subs);
$stmt_subs->execute(['id_tugas' => $id_tugas]);
$submissions = $stmt_subs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Tugas - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .neo-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px solid var(--border-color);
            background-color: var(--primary-foreground);
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--shadow-color);
        }
        .neo-table th, .neo-table td {
            padding: 1rem;
            text-align: left;
        }
        .neo-table th {
            background-color: var(--primary);
            border-bottom: 3px solid var(--border-color);
            text-transform: uppercase;
            font-weight: 800;
        }
        .neo-table td {
            border-bottom: 2px solid var(--border-color);
            vertical-align: middle;
        }
        .neo-table tr:last-child td {
            border-bottom: none;
        }
        .neo-table input[type="number"], .neo-table input[type="text"] {
            padding: 0.5rem;
            border: 2px solid var(--border-color);
            font-family: inherit;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            background-color: #4CAF50;
            color: var(--primary-foreground);
            border: 2px solid var(--border-color);
            box-shadow: 2px 2px 0px var(--shadow-color);
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            font-family: inherit;
        }
        .btn-small:hover {
            transform: translate(2px, 2px);
            box-shadow: 0px 0px 0px var(--shadow-color);
        }
        .btn-download {
            background-color: var(--primary-foreground);
            color: var(--foreground);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            box-shadow: 2px 2px 0px var(--shadow-color);
            display: inline-block;
            font-weight: bold;
        }
        .btn-download:hover {
            transform: translate(2px, 2px);
            box-shadow: 0px 0px 0px var(--shadow-color);
        }
    </style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="neo-main-content">
<main class="neo-layout-container">
    <div style="margin-bottom: 2rem;">
        <a href="dashboard.php" class="neo-box" style="background-color: var(--primary); padding: 0.5rem 1rem; text-decoration: none; color: var(--foreground); font-weight: 800; font-size: 0.9rem; display: inline-block;">← KEMBALI</a>
    </div>

    <div class="neo-content-inner">
        
        <div class="neo-box" style="background-color: var(--primary); padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 800; text-transform: uppercase;"><?= htmlspecialchars($tugas_info['judul_tugas']) ?></h2>
            <p style="font-size: 1rem; margin-top: 0.3rem;">Kelas: <strong><?= htmlspecialchars($tugas_info['nama_kelas']) ?></strong></p>
        </div>

        <?php if ($pesan_sukses): ?>
            <div class="neo-alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
        <?php endif; ?>

        <?php if (count($submissions) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Nama Murid</th>
                            <th style="width: 15%;">Waktu Kumpul</th>
                            <th style="width: 15%;">File Jawaban</th>
                            <th style="width: 50%;">Form Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sub['nama_murid']) ?></strong></td>
                                <td><?= htmlspecialchars($sub['tgl_kumpul']) ?></td>
                                <td>
                                    <?php if ($sub['file_jawaban']): ?>
                                        <a href="uploads/tugas/<?= htmlspecialchars($sub['file_jawaban']) ?>" target="_blank" class="btn-download">Unduh File</a>
                                    <?php else: ?>
                                        <span>Tidak ada file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="" style="display: flex; gap: 1rem; align-items: flex-start;">
                                        <input type="hidden" name="id_submission" value="<?= htmlspecialchars($sub['id_submission']) ?>">
                                        
                                        <div style="flex: 1;">
                                            <label style="font-size: 0.8rem; font-weight: bold;">Nilai:</label>
                                            <input type="number" name="nilai" value="<?= htmlspecialchars($sub['nilai']) ?>" min="0" max="100" placeholder="0-100">
                                        </div>
                                        
                                        <div style="flex: 3;">
                                            <label style="font-size: 0.8rem; font-weight: bold;">Catatan:</label>
                                            <input type="text" name="catatan" value="<?= htmlspecialchars($sub['catatan']) ?>" placeholder="Tambahkan catatan (opsional)">
                                        </div>
                                        
                                        <div style="padding-top: 1.2rem;">
                                            <button type="submit" class="btn-small">Simpan</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="neo-box" style="background-color: var(--primary-foreground); padding: 2rem;">
                <p style="font-weight: bold; font-size: 1.2rem;">Belum ada murid yang mengumpulkan tugas ini.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
</div>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
