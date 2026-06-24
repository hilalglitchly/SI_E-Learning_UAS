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

// Ambil ID Diskusi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Diskusi tidak ditemukan.");
}
$id_diskusi = $_GET['id'];

// Ambil detail diskusi
$stmtDiskusi = $pdo->prepare("
    SELECT d.*, u.username, u.role, k.nama_kelas, k.id_kelas 
    FROM tb_diskusi d 
    JOIN tb_user u ON d.id_user = u.id_user 
    JOIN tb_kelas k ON d.id_kelas = k.id_kelas 
    WHERE d.id_diskusi = :id_diskusi
");
$stmtDiskusi->execute(['id_diskusi' => $id_diskusi]);
$diskusiInfo = $stmtDiskusi->fetch();

if (!$diskusiInfo) {
    die("Diskusi tidak ditemukan.");
}

$id_kelas = $diskusiInfo['id_kelas'];

// Validasi akses kelas (Mirip seperti di kelas_detail.php)
if (strtolower($role) === 'murid') {
    $stmtNim = $pdo->prepare("SELECT nim FROM tb_murid WHERE id_user = :id_user");
    $stmtNim->execute(['id_user' => $id_user]);
    $mhs = $stmtNim->fetch();
    
    if ($mhs) {
        $nim = $mhs['nim'];
        $stmtCek = $pdo->prepare("SELECT * FROM tb_peserta_kelas WHERE id_kelas = :id_kelas AND nim = :nim");
        $stmtCek->execute(['id_kelas' => $id_kelas, 'nim' => $nim]);
        
        if ($stmtCek->rowCount() == 0) {
            die("Akses Ditolak. Anda tidak terdaftar di kelas ini.");
        }
    } else {
        die("Data murid tidak ditemukan di database.");
    }
}

// Handle Form Submit: Balas Diskusi
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'post_balasan') {
    // Validasi CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $pesan = "Token keamanan tidak valid. Silakan muat ulang halaman.";
    } else {
        // Sesuai dengan instruksi: "pengajar dan murid saja"
        if (strtolower($role) === 'murid' || $role === 'Pengajar') {
            $isi_balasan = trim($_POST['isi_balasan']);
            if (!empty($isi_balasan)) {
                $stmtReply = $pdo->prepare("INSERT INTO tb_balasan_diskusi (id_diskusi, id_user, isi_balasan) VALUES (?, ?, ?)");
                $stmtReply->execute([$id_diskusi, $id_user, $isi_balasan]);
                
                // --- TRIGGER NOTIFIKASI ---
                // Jika yang membalas bukan pembuat diskusi, kirim notifikasi
                if ($diskusiInfo['id_user'] != $id_user) {
                    $judul_notif = "Balasan Baru di Forum";
                    // Potong judul jika terlalu panjang
                    $judul_singkat = mb_strimwidth($diskusiInfo['judul_diskusi'], 0, 30, "...");
                    $pesan_notif = htmlspecialchars($username) . " membalas diskusi Anda: '" . htmlspecialchars($judul_singkat) . "'";
                    $link_notif = "forum_detail.php?id=" . $id_diskusi;
                    
                    $stmtNotif = $pdo->prepare("INSERT INTO tb_notifikasi (id_user, judul, pesan, link) VALUES (?, ?, ?, ?)");
                    $stmtNotif->execute([$diskusiInfo['id_user'], $judul_notif, $pesan_notif, $link_notif]);
                }
                
                header("Location: forum_detail.php?id=" . $id_diskusi . "&msg=success");
                exit();
            }
        } else {
            $pesan = "Hanya Pengajar dan Murid yang dapat membalas diskusi.";
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $pesan = "Balasan berhasil ditambahkan!";
}

// Fetch semua balasan
$stmtBalasan = $pdo->prepare("
    SELECT b.*, u.username, u.role 
    FROM tb_balasan_diskusi b 
    JOIN tb_user u ON b.id_user = u.id_user 
    WHERE b.id_diskusi = :id_diskusi 
    ORDER BY b.tgl_balasan ASC
");
$stmtBalasan->execute(['id_diskusi' => $id_diskusi]);
$balasanList = $stmtBalasan->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Diskusi - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Quill Snow Theme -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        .forum-card {
            background-color: var(--card);
            border: 3px solid var(--border);
            box-shadow: 5px 5px 0px var(--shadow-color);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.15s;
        }
        .forum-card-reply {
            background-color: var(--background);
            border: 3px solid var(--border);
            box-shadow: 4px 4px 0px var(--shadow-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            margin-left: 2rem;
            position: relative;
        }
        .forum-card-reply::before {
            content: '↳';
            position: absolute;
            left: -1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--muted-foreground);
        }
        .badge-role {
            font-size: 0.8rem;
            background-color: var(--primary);
            color: var(--primary-foreground);
            padding: 0.2rem 0.6rem;
            border: 2px solid var(--border);
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-pengajar {
            background-color: #FFD700;
            color: #000;
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

        <?php if ($pesan): ?>
            <div class="neo-alert-success brutal-hover" style="background-color: #a8e6cf; color: black; padding: 1rem; font-weight: bold; border: 3px solid #000; box-shadow: 5px 5px 0px #000; margin-bottom: 2rem;">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <!-- TOPIK UTAMA -->
        <div class="forum-card">
            <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1.5rem; border-bottom: 3px solid var(--border); padding-bottom: 1rem;">
                <div style="width: 50px; height: 50px; background-color: var(--foreground); color: var(--background); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 900; flex-shrink: 0;">
                    <?= strtoupper(substr($diskusiInfo['username'], 0, 1)) ?>
                </div>
                <div style="flex-grow: 1;">
                    <h1 style="font-size: 2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 0.5rem; line-height: 1.1;"><?= htmlspecialchars($diskusiInfo['judul_diskusi']) ?></h1>
                    <div style="font-size: 0.95rem; font-weight: 700; color: var(--muted-foreground);">
                        Diposting oleh <strong style="color: var(--foreground);"><?= htmlspecialchars($diskusiInfo['username']) ?></strong> 
                        <span class="badge-role <?= $diskusiInfo['role'] === 'Pengajar' ? 'badge-pengajar' : '' ?>"><?= htmlspecialchars($diskusiInfo['role']) ?></span>
                        di kelas <?= htmlspecialchars($diskusiInfo['nama_kelas']) ?> pada <?= date('d M Y, H:i', strtotime($diskusiInfo['tgl_post'])) ?>
                    </div>
                </div>
            </div>
            
            <div style="font-size: 1.1rem; line-height: 1.6; font-weight: 500;">
                <div class="ql-editor-content"><?= sanitize_rich_text($diskusiInfo['isi_diskusi']) ?></div>
            </div>
        </div>

        <h3 style="font-size: 1.5rem; text-transform: uppercase; font-weight: 900; margin-bottom: 1.5rem; margin-top: 3rem; border-bottom: 4px solid var(--foreground); padding-bottom: 0.5rem; display: inline-block;">Balasan (<?= count($balasanList) ?>)</h3>

        <!-- DAFTAR BALASAN -->
        <div style="margin-bottom: 3rem;">
            <?php if (count($balasanList) > 0): ?>
                <?php foreach ($balasanList as $balasan): ?>
                    <div class="forum-card-reply">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                            <div style="font-weight: 800; font-size: 1rem;">
                                <?= htmlspecialchars($balasan['username']) ?> 
                                <span class="badge-role <?= $balasan['role'] === 'Pengajar' ? 'badge-pengajar' : '' ?>" style="font-size: 0.7rem;"><?= htmlspecialchars($balasan['role']) ?></span>
                            </div>
                            <div style="font-size: 0.85rem; font-weight: bold; color: var(--muted-foreground);">
                                <?= date('d M Y, H:i', strtotime($balasan['tgl_balasan'])) ?>
                            </div>
                        </div>
                        <div class="ql-editor-content" style="line-height: 1.5; font-weight: 500;">
                            <?= sanitize_rich_text($balasan['isi_balasan']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; border: 3px dashed var(--border); font-weight: bold; color: var(--muted-foreground);">
                    Belum ada balasan. Jadilah yang pertama memberikan tanggapan!
                </div>
            <?php endif; ?>
        </div>

        <!-- FORM BALASAN -->
        <?php if (strtolower($role) === 'murid' || $role === 'Pengajar'): ?>
            <div class="brutal-card" style="background-color: var(--primary-foreground); border: 3px solid var(--border); box-shadow: 6px 6px 0px var(--border); padding: 1.5rem;">
                <h4 style="font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 1rem;">Tulis Balasan</h4>
                <form method="POST" action="" id="reply-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="action" value="post_balasan">
                    
                    <!-- Editor Quill -->
                    <div id="editor-container" style="background-color: var(--background); border: 3px solid var(--border); font-size: 1rem; margin-bottom: 1rem; min-height: 150px;"></div>
                    
                    <!-- Hidden textarea for form submission -->
                    <textarea name="isi_balasan" id="isi_balasan_hidden" style="display: none;"></textarea>
                    
                    <button type="submit" class="neo-box" style="background-color: var(--primary); color: var(--primary-foreground); font-weight: 900; font-size: 1rem; padding: 0.8rem 1.5rem; text-transform: uppercase; cursor: pointer; border: 3px solid var(--border); box-shadow: 3px 3px 0px var(--border); transition: all 0.1s;">Kirim Balasan &rarr;</button>
                </form>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 1.5rem; background-color: var(--muted); border: 3px solid var(--border); font-weight: bold; text-transform: uppercase;">
                Hanya Pengajar dan Murid yang dapat memberikan balasan.
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('editor-container')) {
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Ketik balasan Anda di sini...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ 'header': 1 }, { 'header': 2 }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'script': 'sub'}, { 'script': 'super' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['clean']
                    ]
                }
            });

            var form = document.getElementById('reply-form');
            var hiddenTextarea = document.getElementById('isi_balasan_hidden');
            
            form.addEventListener('submit', function(e) {
                var html = quill.root.innerHTML;
                
                if (html === '<p><br></p>') {
                    html = '';
                }
                
                hiddenTextarea.value = html;
                
                if(hiddenTextarea.value.trim() === '') {
                    e.preventDefault();
                    NeoToast('Balasan tidak boleh kosong!', 'error');
                }
            });
        }
    });
</script>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
