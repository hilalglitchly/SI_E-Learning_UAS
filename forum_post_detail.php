<?php
session_start();
require_once 'includes/koneksi.php';

$is_logged_in = isset($_SESSION['id_user']);
$id_user = $_SESSION['id_user'] ?? null;
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: forum.php");
    exit();
}

$id_post = intval($_GET['id']);

// Update views
$pdo->prepare("UPDATE tb_forum_post SET views = views + 1 WHERE id_post = ?")->execute([$id_post]);

// Get Post Detail
$stmtPost = $pdo->prepare("
    SELECT p.*, u.username, u.role as user_role,
           COALESCE((SELECT SUM(CASE WHEN v.tipe_vote = 'up' THEN 1 WHEN v.tipe_vote = 'down' THEN -1 ELSE 0 END) FROM tb_forum_vote v WHERE v.id_post = p.id_post), 0) as vote_count
    FROM tb_forum_post p
    JOIN tb_user u ON p.id_user = u.id_user
    WHERE p.id_post = ?
");
$stmtPost->execute([$id_post]);
$post = $stmtPost->fetch();

if (!$post) {
    die("Postingan tidak ditemukan.");
}

// Get Answers
$stmtAnswers = $pdo->prepare("
    SELECT j.*, u.username, u.role as user_role,
           COALESCE((SELECT SUM(CASE WHEN v.tipe_vote = 'up' THEN 1 WHEN v.tipe_vote = 'down' THEN -1 ELSE 0 END) FROM tb_forum_vote v WHERE v.id_jawaban = j.id_jawaban), 0) as vote_count
    FROM tb_forum_jawaban j
    JOIN tb_user u ON j.id_user = u.id_user
    WHERE j.id_post = ?
    ORDER BY j.is_solusi DESC, vote_count DESC, j.tgl_jawaban ASC
");
$stmtAnswers->execute([$id_post]);
$answers = $stmtAnswers->fetchAll();

// Handle New Answer Submission
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_jawaban'])) {
    if (!$is_logged_in) {
        $msg = "Anda harus login untuk membalas.";
        $msg_type = "error";
    } elseif (!verify_csrf_token($_POST['csrf_token'])) {
        $msg = "Token keamanan tidak valid.";
        $msg_type = "error";
    } else {
        $konten = trim($_POST['konten']);
        // Sanitasi konten menggunakan fungsi dari koneksi.php
        $konten = sanitize_rich_text($konten);
        
        if (empty(strip_tags($konten))) {
            $msg = "Komentar tidak boleh kosong.";
            $msg_type = "warning";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO tb_forum_jawaban (id_post, id_user, konten) VALUES (?, ?, ?)");
            if ($stmtInsert->execute([$id_post, $id_user, $konten])) {
                // Beri notifikasi ke pembuat post (jika bukan diri sendiri)
                if ($post['id_user'] != $id_user) {
                    $pesan_notif = "$username menjawab postingan Anda: \"" . htmlspecialchars(substr($post['judul'], 0, 30)) . "...\"";
                    $link_notif = "forum_post_detail.php?id=$id_post";
                    
                    $stmtNotif = $pdo->prepare("INSERT INTO tb_notifikasi (id_user, judul, pesan, link) VALUES (?, 'Jawaban Baru', ?, ?)");
                    $stmtNotif->execute([$post['id_user'], $pesan_notif, $link_notif]);
                }
                
                header("Location: forum_post_detail.php?id=$id_post#answers");
                exit();
            } else {
                $msg = "Gagal memposting jawaban.";
                $msg_type = "error";
            }
        }
    }
}

// Handle Mark as Solved (Hanya pembuat post atau Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_solved'])) {
    if ($is_logged_in && ($id_user == $post['id_user'] || strtolower($role) === 'admin')) {
        $id_jawaban_solved = intval($_POST['id_jawaban']);
        
        $pdo->beginTransaction();
        try {
            // Reset semua jawaban di post ini jadi bukan solusi
            $pdo->prepare("UPDATE tb_forum_jawaban SET is_solusi = 0 WHERE id_post = ?")->execute([$id_post]);
            // Set jawaban terpilih jadi solusi
            $pdo->prepare("UPDATE tb_forum_jawaban SET is_solusi = 1 WHERE id_jawaban = ?")->execute([$id_jawaban_solved]);
            // Ubah status post jadi solved
            $pdo->prepare("UPDATE tb_forum_post SET status = 'solved' WHERE id_post = ?")->execute([$id_post]);
            
            $pdo->commit();
            header("Location: forum_post_detail.php?id=$id_post");
            exit();
        } catch(PDOException $e) {
            $pdo->rollBack();
            $msg = "Gagal menandai solusi: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

// Get Tags
$stmtTags = $pdo->prepare("SELECT t.* FROM tb_forum_post_tag pt JOIN tb_forum_tag t ON pt.id_tag = t.id_tag WHERE pt.id_post = ?");
$stmtTags->execute([$id_post]);
$tags = $stmtTags->fetchAll();

// Get User Vote for Post
$userVotePost = null;
if ($is_logged_in) {
    $stmtUserVote = $pdo->prepare("SELECT tipe_vote FROM tb_forum_vote WHERE id_user = ? AND id_post = ?");
    $stmtUserVote->execute([$id_user, $id_post]);
    $uv = $stmtUserVote->fetch();
    if ($uv) $userVotePost = $uv['tipe_vote'];
}

// Check Bookmark
$isBookmarked = false;
if ($is_logged_in) {
    $stmtCek = $pdo->prepare("SELECT 1 FROM tb_forum_simpan WHERE id_user = ? AND id_post = ?");
    $stmtCek->execute([$id_user, $id_post]);
    $isBookmarked = $stmtCek->fetch() ? true : false;
}

// Get User Votes for Answers
$userVoteAnswers = [];
if ($is_logged_in && !empty($answers)) {
    $ansIds = array_column($answers, 'id_jawaban');
    $placeholders = str_repeat('?,', count($ansIds) - 1) . '?';
    $stmtUVAns = $pdo->prepare("SELECT id_jawaban, tipe_vote FROM tb_forum_vote WHERE id_user = ? AND id_jawaban IN ($placeholders)");
    $params = array_merge([$id_user], $ansIds);
    $stmtUVAns->execute($params);
    while ($row = $stmtUVAns->fetch()) {
        $userVoteAnswers[$row['id_jawaban']] = $row['tipe_vote'];
    }
}

// Helper Functions
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'baru saja';
}
function getInitial($username) {
    return strtoupper(substr($username, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['judul']) ?> - E-FORVM</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/forum.css?v=<?= filemtime('assets/css/forum.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Highlight.js for code blocks -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <!-- Quill.js CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .forum-detail-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
            min-height: calc(100vh - 120px);
        }
        
        .forum-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.5rem;
            color: var(--foreground);
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .forum-back-btn:hover {
            color: var(--accent);
        }
        
        .post-detail-card {
            background-color: var(--card);
            border: 3px solid var(--border);
            box-shadow: 8px 8px 0px var(--shadow-color);
            margin-bottom: 2rem;
            display: flex;
        }
        
        .post-detail-body {
            flex: 1;
            padding: 20px 24px;
            min-width: 0;
        }
        
        .post-detail-title {
            font-size: 1.8rem;
            font-weight: 900;
            margin: 15px 0;
            line-height: 1.2;
            color: var(--foreground);
        }
        
        .post-detail-content {
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--foreground);
            margin-bottom: 20px;
        }
        
        /* Quill Content Styling overrides */
        .post-detail-content p { margin-bottom: 15px; }
        .post-detail-content pre { 
            background: #282c34; 
            color: #abb2bf;
            padding: 15px; 
            border: 3px solid var(--border); 
            border-radius: 0;
            overflow-x: auto;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .post-detail-content blockquote {
            border-left: 5px solid var(--secondary);
            padding-left: 15px;
            margin-left: 0;
            color: var(--muted-foreground);
            font-style: italic;
        }
        .post-detail-content ul, .post-detail-content ol {
            margin-bottom: 15px;
            padding-left: 20px;
        }
        
        .answers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid var(--border);
            padding-bottom: 10px;
        }
        
        .answers-header h3 {
            font-size: 1.4rem;
            font-weight: 900;
            text-transform: uppercase;
        }
        
        .answer-card {
            background-color: var(--card);
            border: 2px solid var(--border);
            margin-bottom: 1.5rem;
            display: flex;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        
        .answer-card:hover {
            box-shadow: 4px 4px 0px var(--shadow-color);
            transform: translate(-2px, -2px);
        }
        
        .answer-card.is-solution {
            border: 3px solid #22C55E;
            box-shadow: 6px 6px 0px #22C55E;
        }
        
        .solution-badge {
            background-color: #22C55E;
            color: white;
            font-weight: 900;
            font-size: 0.8rem;
            padding: 4px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-bottom: 2px solid var(--border);
            width: 100%;
        }
        
        .answer-body {
            flex: 1;
            padding: 16px 20px;
            min-width: 0;
        }
        
        .answer-content {
            margin: 15px 0;
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--foreground);
        }
        
        /* Quill formatting for answer */
        .answer-content pre {
            background: #282c34;
            padding: 12px;
            border: 2px solid var(--border);
            overflow-x: auto;
        }
        
        .mark-solved-btn {
            background-color: transparent;
            border: 2px solid #22C55E;
            color: #22C55E;
            font-weight: 800;
            padding: 6px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.1s;
        }
        
        .mark-solved-btn:hover {
            background-color: #22C55E;
            color: white;
            transform: translate(-2px, -2px);
            box-shadow: 3px 3px 0px var(--shadow-color);
        }
        
        .reply-box {
            background-color: var(--card);
            border: 3px solid var(--border);
            box-shadow: 6px 6px 0px var(--shadow-color);
            padding: 20px;
            margin-top: 3rem;
        }
        
        .reply-box h3 {
            font-size: 1.2rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        #editor-container {
            height: 200px;
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
        
        .submit-reply-btn {
            margin-top: 15px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
            background-color: var(--primary);
            color: white;
            border: 3px solid var(--border);
            box-shadow: 4px 4px 0px var(--shadow-color);
            cursor: pointer;
            transition: all 0.1s;
        }
        
        .submit-reply-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--shadow-color);
        }
        
        @media (max-width: 768px) {
            .post-detail-card, .answer-card {
                flex-direction: column;
            }
            .forum-post-votes {
                flex-direction: row;
                border-right: none;
                border-bottom: 3px solid var(--border);
                justify-content: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="forum-detail-container">
    <a href="forum.php" class="forum-back-btn"><i class='bx bx-arrow-back'></i> Kembali ke Forum</a>
    
    <?php if ($msg): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                NeoToast('<?= addslashes($msg) ?>', '<?= $msg_type ?>');
            });
        </script>
    <?php endif; ?>

    <!-- MAIN POST -->
    <article class="post-detail-card">
        <!-- Vote Column -->
        <div class="forum-post-votes">
            <button class="forum-vote-btn <?= $userVotePost === 'up' ? 'voted-up' : '' ?>" 
                    onclick="vote(<?= $post['id_post'] ?>, 'post', 'up')" title="Upvote" <?= !$is_logged_in ? 'disabled' : '' ?>>
                <i class='bx bxs-up-arrow'></i>
            </button>
            <span class="forum-vote-count" id="vote-count-post-<?= $post['id_post'] ?>"><?= $post['vote_count'] ?></span>
            <button class="forum-vote-btn <?= $userVotePost === 'down' ? 'voted-down' : '' ?>" 
                    onclick="vote(<?= $post['id_post'] ?>, 'post', 'down')" title="Downvote" <?= !$is_logged_in ? 'disabled' : '' ?>>
                <i class='bx bxs-down-arrow'></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="post-detail-body">
            <div class="forum-post-meta" style="font-size: 0.9rem;">
                <span class="forum-post-avatar"><?= getInitial($post['username']) ?></span>
                <span class="forum-post-username"><?= htmlspecialchars($post['username']) ?></span>
                <span class="forum-post-role <?= $post['user_role'] === 'Pengajar' ? 'role-pengajar' : ($post['user_role'] === 'Admin' ? 'role-admin' : '') ?>"><?= htmlspecialchars($post['user_role']) ?></span>
                <span class="forum-post-time"><?= timeAgo($post['tgl_post']) ?></span>
            </div>
            
            <h1 class="post-detail-title"><?= htmlspecialchars($post['judul']) ?></h1>
            
            <?php if (!empty($tags)): ?>
            <div class="forum-post-tags" style="margin-bottom: 20px;">
                <span class="forum-tipe-badge tipe-<?= $post['tipe'] ?>" style="margin-right: 10px; padding: 4px 10px; font-size: 0.8rem;"><?= $post['tipe'] === 'pertanyaan' ? '❓ Pertanyaan' : ($post['tipe'] === 'diskusi' ? '💬 Diskusi' : '📤 Berbagi') ?></span>
                <?php foreach ($tags as $t): ?>
                <a href="forum.php?tag=<?= urlencode($t['nama_tag']) ?>" class="forum-tag" style="border-left: 4px solid <?= htmlspecialchars($t['warna']) ?>; padding: 4px 10px; font-size: 0.8rem;">
                    #<?= htmlspecialchars($t['nama_tag']) ?>
                </a>
                <?php endforeach; ?>
                
                <?php if ($post['tipe'] === 'pertanyaan'): ?>
                <span class="forum-status status-<?= $post['status'] ?>" style="margin-left: auto; padding: 4px 10px; font-size: 0.8rem;">
                    <?= $post['status'] === 'solved' ? '✓ SOLVED' : '○ OPEN' ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="post-detail-content">
                <?= $post['konten'] ?> <!-- Not htmlspecialchars because it's rich text, already sanitized on input -->
            </div>
            
            <div class="forum-post-actions" style="margin-top: 20px; padding-top: 15px;">
                <button class="forum-action-btn" onclick="sharePost(<?= $post['id_post'] ?>, '<?= htmlspecialchars(addslashes($post['judul'])) ?>')">
                    <i class='bx bx-share-alt'></i> Bagikan
                </button>
                <?php if ($is_logged_in): ?>
                <button class="forum-action-btn" id="bookmark-btn-<?= $post['id_post'] ?>" onclick="toggleBookmark(<?= $post['id_post'] ?>)">
                    <i class='bx <?= $isBookmarked ? 'bxs-bookmark' : 'bx-bookmark' ?>'></i> 
                    <span><?= $isBookmarked ? 'Tersimpan' : 'Simpan' ?></span>
                </button>
                <?php endif; ?>
                <span class="forum-action-btn" style="cursor: default;"><i class='bx bx-show'></i> <?= $post['views'] ?> Dilihat</span>
            </div>
        </div>
    </article>

    <!-- ANSWERS SECTION -->
    <div id="answers">
        <div class="answers-header">
            <h3><?= count($answers) ?> Jawaban</h3>
        </div>
        
        <?php if (empty($answers)): ?>
            <div class="forum-empty-state" style="padding: 2rem; margin-bottom: 2rem; border-width: 2px; box-shadow: 4px 4px 0px var(--shadow-color);">
                <i class='bx bx-comment-detail' style="font-size: 2.5rem;"></i>
                <p>Belum ada jawaban. Jadilah yang pertama memberikan solusi!</p>
            </div>
        <?php else: ?>
            <?php foreach ($answers as $ans): 
                $ansVote = $userVoteAnswers[$ans['id_jawaban']] ?? null;
            ?>
            <div class="answer-card <?= $ans['is_solusi'] ? 'is-solution' : '' ?>" id="jawaban-<?= $ans['id_jawaban'] ?>">
                <?php if ($ans['is_solusi']): ?>
                <div class="solution-badge" style="position: absolute; top: 0; left: 0; right: 0; z-index: 1;">
                    <i class='bx bx-check-circle'></i> JAWABAN TERBAIK
                </div>
                <?php endif; ?>
                
                <!-- Vote Column -->
                <div class="forum-post-votes" style="<?= $ans['is_solusi'] ? 'padding-top: 40px;' : '' ?>">
                    <button class="forum-vote-btn <?= $ansVote === 'up' ? 'voted-up' : '' ?>" 
                            onclick="vote(<?= $ans['id_jawaban'] ?>, 'jawaban', 'up')" title="Upvote" <?= !$is_logged_in ? 'disabled' : '' ?>>
                        <i class='bx bxs-up-arrow'></i>
                    </button>
                    <span class="forum-vote-count" id="vote-count-jawaban-<?= $ans['id_jawaban'] ?>"><?= $ans['vote_count'] ?></span>
                    <button class="forum-vote-btn <?= $ansVote === 'down' ? 'voted-down' : '' ?>" 
                            onclick="vote(<?= $ans['id_jawaban'] ?>, 'jawaban', 'down')" title="Downvote" <?= !$is_logged_in ? 'disabled' : '' ?>>
                        <i class='bx bxs-down-arrow'></i>
                    </button>
                </div>
                
                <!-- Answer Body -->
                <div class="answer-body" style="<?= $ans['is_solusi'] ? 'padding-top: 40px;' : '' ?>">
                    <div class="forum-post-meta">
                        <span class="forum-post-avatar" style="width: 28px; height: 28px; font-size: 0.7rem;"><?= getInitial($ans['username']) ?></span>
                        <span class="forum-post-username"><?= htmlspecialchars($ans['username']) ?></span>
                        <span class="forum-post-role <?= $ans['user_role'] === 'Pengajar' ? 'role-pengajar' : ($ans['user_role'] === 'Admin' ? 'role-admin' : '') ?>"><?= htmlspecialchars($ans['user_role']) ?></span>
                        <span class="forum-post-time"><?= timeAgo($ans['tgl_jawaban']) ?></span>
                    </div>
                    
                    <div class="answer-content">
                        <?= $ans['konten'] ?>
                    </div>
                    
                    <!-- Post owner can mark as solved -->
                    <?php if ($is_logged_in && ($id_user == $post['id_user'] || strtolower($role) === 'admin') && $post['tipe'] === 'pertanyaan' && !$ans['is_solusi']): ?>
                    <div style="margin-top: 15px; text-align: right;">
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="id_jawaban" value="<?= $ans['id_jawaban'] ?>">
                            <button type="submit" name="mark_solved" class="mark-solved-btn">
                                <i class='bx bx-check'></i> Tandai sebagai Solusi
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- REPLY FORM -->
    <div class="reply-box">
        <h3>Berikan Jawaban Anda</h3>
        <?php if ($is_logged_in): ?>
            <form method="POST" action="" id="replyForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="konten" id="hidden_konten">
                
                <div id="editor-container"></div>
                
                <button type="submit" name="submit_jawaban" class="submit-reply-btn" onclick="return submitReply()">
                    <i class='bx bx-send'></i> Kirim Jawaban
                </button>
            </form>
        <?php else: ?>
            <div style="padding: 20px; background-color: var(--muted); border: 2px solid var(--border); text-align: center;">
                <p style="margin-bottom: 10px; font-weight: 700;">Anda harus masuk untuk ikut berdiskusi.</p>
                <a href="login.php" class="forum-fab" style="font-size: 0.8rem; padding: 8px 16px;"><i class='bx bx-log-in'></i> Masuk Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/cursor.php'; ?>
<script src="assets/js/neo-alert.js"></script>

<!-- Highlight.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
// Initialize Code Highlighting
document.addEventListener('DOMContentLoaded', (event) => {
    document.querySelectorAll('pre code').forEach((el) => {
        hljs.highlightElement(el);
    });
});

// Initialize Quill Editor if logged in
<?php if ($is_logged_in): ?>
var quill = new Quill('#editor-container', {
    theme: 'snow',
    placeholder: 'Tulis jawaban Anda di sini (Gunakan ikon < > untuk memasukkan kode)...',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
});

function submitReply() {
    var konten = quill.root.innerHTML;
    // Cek jika kosong
    if (quill.getText().trim().length === 0 && !konten.includes('<img')) {
        NeoToast('Jawaban tidak boleh kosong!', 'warning');
        return false;
    }
    document.getElementById('hidden_konten').value = konten;
    return true;
}
<?php endif; ?>

// ========== VOTING SYSTEM ==========
function vote(id, type, voteType) {
    <?php if (!$is_logged_in): ?>
    NeoToast('Silakan login terlebih dahulu untuk voting.', 'warning');
    return;
    <?php endif; ?>

    fetch('api/forum_vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id: id, 
            type: type, 
            vote: voteType,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const countEl = document.getElementById('vote-count-' + type + '-' + id);
            if (countEl) countEl.textContent = data.new_count;

            const container = type === 'post' ? document.querySelector('.post-detail-card') : document.getElementById('jawaban-' + id);
            if (container) {
                const upBtn = container.querySelector('.forum-vote-btn:first-child');
                const downBtn = container.querySelector('.forum-vote-btn:last-of-type');
                upBtn.classList.remove('voted-up');
                downBtn.classList.remove('voted-down');
                if (data.action === 'up') upBtn.classList.add('voted-up');
                if (data.action === 'down') downBtn.classList.add('voted-down');
            }
        } else {
            NeoToast(data.message || 'Gagal memproses vote.', 'error');
        }
    })
    .catch(() => NeoToast('Terjadi kesalahan jaringan.', 'error'));
}

// ========== BOOKMARK SYSTEM ==========
function toggleBookmark(postId) {
    fetch('api/forum_simpan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id_post: postId,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const btn = document.getElementById('bookmark-btn-' + postId);
            if (btn) {
                const icon = btn.querySelector('i');
                const text = btn.querySelector('span');
                if (data.action === 'saved') {
                    icon.className = 'bx bxs-bookmark';
                    text.textContent = 'Tersimpan';
                    NeoToast('Postingan disimpan!', 'success');
                } else {
                    icon.className = 'bx bx-bookmark';
                    text.textContent = 'Simpan';
                    NeoToast('Bookmark dihapus.', 'success');
                }
            }
        } else {
            NeoToast(data.message || 'Gagal menyimpan.', 'error');
        }
    })
    .catch(() => NeoToast('Terjadi kesalahan jaringan.', 'error'));
}

function sharePost(postId, title) {
    const url = window.location.href.split('#')[0]; // Remove hash if any
    if (navigator.share) {
        navigator.share({ title: title, url: url });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            NeoToast('Link berhasil disalin ke clipboard!', 'success');
        });
    }
}
</script>

</body>
</html>
