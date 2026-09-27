<?php
session_start();
require_once 'includes/koneksi.php';

// Wajib login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? '';

// Ambil list semua tags untuk dipilih
$stmtTags = $pdo->query("SELECT * FROM tb_forum_tag ORDER BY nama_tag ASC");
$allTags = $stmtTags->fetchAll();

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $msg = "Token keamanan tidak valid.";
        $msg_type = "error";
    } else {
        $judul = trim($_POST['judul']);
        $tipe = $_POST['tipe'];
        $konten = trim($_POST['konten']);
        $tags_selected = isset($_POST['tags']) ? (array)$_POST['tags'] : []; // Array of tag IDs
        
        // Validasi
        if (empty($judul) || empty(strip_tags($konten))) {
            $msg = "Judul dan konten tidak boleh kosong.";
            $msg_type = "warning";
        } elseif (strlen($judul) > 250) {
            $msg = "Judul terlalu panjang (maksimal 250 karakter).";
            $msg_type = "warning";
        } elseif (count($tags_selected) > 5) {
            $msg = "Maksimal memilih 5 tag.";
            $msg_type = "warning";
        } else {
            // Sanitasi
            $konten = sanitize_rich_text($konten);
            
            $pdo->beginTransaction();
            try {
                // 1. Insert Post
                $is_announcement = (strtolower($role) === 'admin' || strtolower($role) === 'pengajar') && isset($_POST['is_announcement']) ? 1 : 0;
                
                $stmtInsert = $pdo->prepare("INSERT INTO tb_forum_post (id_user, judul, konten, tipe, is_announcement) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$id_user, $judul, $konten, $tipe, $is_announcement]);
                
                $new_post_id = $pdo->lastInsertId();
                
                // 2. Insert Tags
                if (!empty($tags_selected)) {
                    $insertTagQuery = "INSERT INTO tb_forum_post_tag (id_post, id_tag) VALUES ";
                    $tagValues = [];
                    $tagParams = [];
                    foreach ($tags_selected as $tag_id) {
                        $tagValues[] = "(?, ?)";
                        $tagParams[] = $new_post_id;
                        $tagParams[] = intval($tag_id);
                    }
                    $insertTagQuery .= implode(', ', $tagValues);
                    $stmtInsertTag = $pdo->prepare($insertTagQuery);
                    $stmtInsertTag->execute($tagParams);
                }
                
                $pdo->commit();
                
                // Redirect ke halaman detail
                header("Location: forum_post_detail.php?id=$new_post_id&new=1");
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = "Gagal membuat postingan: " . $e->getMessage();
                $msg_type = "error";
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
    <title>Buat Postingan Baru - E-FORVM</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Quill.js CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .create-post-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
            min-height: calc(100vh - 120px);
        }
        
        .create-post-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 4px solid var(--border);
        }
        
        .create-post-header h1 {
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        
        .create-post-header i {
            font-size: 2.5rem;
            color: var(--secondary);
        }
        
        .post-form-box {
            background-color: var(--card);
            border: 3px solid var(--border);
            box-shadow: 8px 8px 0px var(--shadow-color);
            padding: 2rem;
        }
        
        /* Custom Checkbox for Tags */
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .tag-checkbox-wrapper {
            position: relative;
        }
        
        .tag-checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
        }
        
        .tag-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 2px solid var(--border);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.1s;
            user-select: none;
        }
        
        .tag-checkbox-wrapper input[type="checkbox"]:checked + .tag-checkbox-label {
            background-color: var(--secondary);
            color: #000;
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0px var(--shadow-color);
        }
        
        .tag-checkbox-label i {
            font-size: 1.2em;
        }
        
        /* Tipe Radio Buttons */
        .tipe-options {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }
        
        .tipe-radio-wrapper {
            position: relative;
            flex: 1;
        }
        
        .tipe-radio-wrapper input[type="radio"] {
            opacity: 0;
            position: absolute;
        }
        
        .tipe-radio-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 15px;
            border: 3px solid var(--border);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.1s;
            text-align: center;
            background-color: var(--background);
        }
        
        .tipe-radio-label i {
            font-size: 2rem;
        }
        
        .tipe-radio-wrapper input[type="radio"]:checked + .tipe-radio-label {
            transform: translate(-4px, -4px);
            box-shadow: 6px 6px 0px var(--shadow-color);
        }
        
        #tipe-pertanyaan:checked + .tipe-radio-label { background-color: #3B82F6; color: #FFF; border-color: #000; }
        #tipe-diskusi:checked + .tipe-radio-label { background-color: #8B5CF6; color: #FFF; border-color: #000; }
        #tipe-berbagi:checked + .tipe-radio-label { background-color: #10B981; color: #FFF; border-color: #000; }
        
        /* Editor */
        #editor-container {
            height: 350px;
            background-color: var(--background);
            font-family: inherit;
            font-size: 1rem;
        }
        
        .ql-toolbar {
            background-color: var(--muted);
            border-color: var(--border) !important;
            border-width: 3px 3px 0 3px !important;
        }
        
        .ql-container {
            border-color: var(--border) !important;
            border-width: 0 3px 3px 3px !important;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 2rem;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--foreground);
            background-color: var(--background);
            border: 3px solid var(--border);
            transition: transform 0.1s, box-shadow 0.1s;
        }
        .btn-cancel:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0px var(--shadow-color);
            background-color: var(--muted);
        }
        
        .form-hint {
            font-size: 0.8rem;
            color: var(--muted-foreground);
            margin-top: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="create-post-container">
    
    <?php if ($msg): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                NeoToast('<?= addslashes($msg) ?>', '<?= $msg_type ?>');
            });
        </script>
    <?php endif; ?>

    <div class="create-post-header">
        <i class='bx bx-edit-alt'></i>
        <h1>Buat Postingan Baru</h1>
    </div>
    
    <div class="post-form-box">
        <form method="POST" action="" id="postForm" onsubmit="return validateForm()">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <!-- 1. Tipe Postingan -->
            <div class="neo-form-group">
                <label class="neo-label">Tipe Postingan</label>
                <div class="tipe-options">
                    <div class="tipe-radio-wrapper">
                        <input type="radio" name="tipe" id="tipe-pertanyaan" value="pertanyaan" checked>
                        <label for="tipe-pertanyaan" class="tipe-radio-label">
                            <i class='bx bx-help-circle'></i>
                            Pertanyaan
                        </label>
                    </div>
                    <div class="tipe-radio-wrapper">
                        <input type="radio" name="tipe" id="tipe-diskusi" value="diskusi">
                        <label for="tipe-diskusi" class="tipe-radio-label">
                            <i class='bx bx-chat'></i>
                            Diskusi
                        </label>
                    </div>
                    <div class="tipe-radio-wrapper">
                        <input type="radio" name="tipe" id="tipe-berbagi" value="berbagi">
                        <label for="tipe-berbagi" class="tipe-radio-label">
                            <i class='bx bx-share-alt'></i>
                            Berbagi / Tutorial
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- 2. Judul -->
            <div class="neo-form-group">
                <label class="neo-label" for="judul">Judul Topik <span style="color:var(--primary)">*</span></label>
                <input type="text" id="judul" name="judul" class="neo-input" placeholder="Misal: Cara mengatasi error 'Cannot read properties of undefined' di React?" required maxlength="250">
                <div class="form-hint">Buat judul yang spesifik agar mudah dipahami (Maks. 250 karakter)</div>
            </div>
            
            <!-- 3. Editor Konten -->
            <div class="neo-form-group">
                <label class="neo-label">Isi Detail Topik <span style="color:var(--primary)">*</span></label>
                <input type="hidden" name="konten" id="hidden_konten">
                <div id="editor-container"></div>
                <div class="form-hint">Jelaskan secara rinci. Gunakan ikon &lt; &gt; untuk memasukkan cuplikan kode.</div>
            </div>
            
            <!-- 4. Tags -->
            <div class="neo-form-group">
                <label class="neo-label">Tags (Pilih 1 - 5 tag)</label>
                <div class="tags-grid">
                    <?php foreach($allTags as $tag): ?>
                    <div class="tag-checkbox-wrapper">
                        <input type="checkbox" name="tags[]" id="tag-<?= $tag['id_tag'] ?>" value="<?= $tag['id_tag'] ?>" class="tag-checkbox">
                        <label for="tag-<?= $tag['id_tag'] ?>" class="tag-checkbox-label">
                            <i class='bx <?= htmlspecialchars($tag['icon']) ?>' style="color: <?= htmlspecialchars($tag['warna']) ?>;"></i>
                            <?= htmlspecialchars($tag['nama_tag']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint" id="tag-counter">Terpilih: 0/5</div>
            </div>
            
            <!-- Khusus Admin/Pengajar -->
            <?php if (strtolower($role) === 'admin' || strtolower($role) === 'pengajar'): ?>
            <div class="neo-form-group" style="padding: 15px; background-color: var(--muted); border: 2px solid var(--border); display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_announcement" id="is_announcement" value="1" style="width: 20px; height: 20px; cursor: pointer;">
                <label for="is_announcement" style="font-weight: 800; cursor: pointer;">Jadikan sebagai Pengumuman (Pinned)</label>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <a href="forum.php" class="btn-cancel">Batal</a>
                <button type="submit" name="submit_post" class="neo-btn" style="width: auto; padding: 12px 30px;">
                    <i class='bx bx-send'></i> POSTING SEKARANG
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/cursor.php'; ?>
<script src="assets/js/neo-alert.js"></script>
<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    // Inisialisasi Quill
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Tulis isi postingan di sini...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    // Tag Limiter
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    const tagCounter = document.getElementById('tag-counter');
    
    checkboxes.forEach(box => {
        box.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.tag-checkbox:checked').length;
            tagCounter.textContent = `Terpilih: ${checkedCount}/5`;
            
            if (checkedCount >= 5) {
                checkboxes.forEach(b => {
                    if (!b.checked) b.disabled = true;
                });
                tagCounter.style.color = 'var(--primary)';
            } else {
                checkboxes.forEach(b => {
                    b.disabled = false;
                });
                tagCounter.style.color = 'var(--muted-foreground)';
            }
        });
    });

    // Form Validation
    function validateForm() {
        const judul = document.getElementById('judul').value.trim();
        const kontenText = quill.getText().trim();
        const kontenHtml = quill.root.innerHTML;
        const checkedTags = document.querySelectorAll('.tag-checkbox:checked').length;
        
        if (judul === '') {
            NeoToast('Judul tidak boleh kosong!', 'warning');
            document.getElementById('judul').focus();
            return false;
        }
        
        if (kontenText.length === 0 && !kontenHtml.includes('<img')) {
            NeoToast('Konten tidak boleh kosong!', 'warning');
            quill.focus();
            return false;
        }
        
        if (checkedTags === 0) {
            NeoToast('Pilih setidaknya 1 tag.', 'warning');
            return false;
        }
        
        // Populate hidden input
        document.getElementById('hidden_konten').value = kontenHtml;
        return true;
    }
</script>

</body>
</html>
