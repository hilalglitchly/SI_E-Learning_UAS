<?php
session_start();
require_once 'includes/koneksi.php';

$is_logged_in = isset($_SESSION['id_user']);
$id_user = $_SESSION['id_user'] ?? null;
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

// ========== FILTER & SORTING PARAMETERS ==========
$sort = $_GET['sort'] ?? 'hot';
$tag_filter = $_GET['tag'] ?? '';
$search = $_GET['q'] ?? '';
$tipe_filter = $_GET['tipe'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ========== BUILD QUERY ==========
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(p.judul LIKE :search OR p.konten LIKE :search2)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

if (!empty($tag_filter)) {
    $where_clauses[] = "EXISTS (SELECT 1 FROM tb_forum_post_tag pt JOIN tb_forum_tag t ON pt.id_tag = t.id_tag WHERE pt.id_post = p.id_post AND t.nama_tag = :tag_filter)";
    $params['tag_filter'] = $tag_filter;
}

if (!empty($tipe_filter) && in_array($tipe_filter, ['pertanyaan', 'diskusi', 'berbagi'])) {
    $where_clauses[] = "p.tipe = :tipe_filter";
    $params['tipe_filter'] = $tipe_filter;
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
switch ($sort) {
    case 'baru':
        $order_sql = "ORDER BY p.is_pinned DESC, p.tgl_post DESC";
        break;
    case 'top':
        $order_sql = "ORDER BY p.is_pinned DESC, vote_count DESC, p.views DESC";
        break;
    case 'unsolved':
        $where_sql = ($where_sql ? $where_sql . ' AND ' : 'WHERE ') . "p.status = 'open' AND p.tipe = 'pertanyaan'";
        $order_sql = "ORDER BY p.tgl_post DESC";
        break;
    default: // hot
        $order_sql = "ORDER BY p.is_pinned DESC, (vote_count * 2 + p.views + jawaban_count * 3) DESC, p.tgl_post DESC";
        break;
}

// Main Query
$sql = "
    SELECT p.*, 
           u.username, u.role as user_role,
           COALESCE((SELECT SUM(CASE WHEN v.tipe_vote = 'up' THEN 1 WHEN v.tipe_vote = 'down' THEN -1 ELSE 0 END) FROM tb_forum_vote v WHERE v.id_post = p.id_post), 0) as vote_count,
           (SELECT COUNT(*) FROM tb_forum_jawaban j WHERE j.id_post = p.id_post) as jawaban_count
    FROM tb_forum_post p
    JOIN tb_user u ON p.id_user = u.id_user
    $where_sql
    $order_sql
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM tb_forum_post p $where_sql";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue(":$key", $val);
}
$countStmt->execute();
$total_posts = $countStmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Get tags for each post
function getPostTags($pdo, $id_post) {
    $stmt = $pdo->prepare("SELECT t.nama_tag, t.warna FROM tb_forum_post_tag pt JOIN tb_forum_tag t ON pt.id_tag = t.id_tag WHERE pt.id_post = ?");
    $stmt->execute([$id_post]);
    return $stmt->fetchAll();
}

// Check if user voted on a post
function getUserVote($pdo, $id_user, $id_post) {
    if (!$id_user) return null;
    $stmt = $pdo->prepare("SELECT tipe_vote FROM tb_forum_vote WHERE id_user = ? AND id_post = ?");
    $stmt->execute([$id_user, $id_post]);
    $row = $stmt->fetch();
    return $row ? $row['tipe_vote'] : null;
}

// Check if user bookmarked a post
function isBookmarked($pdo, $id_user, $id_post) {
    if (!$id_user) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM tb_forum_simpan WHERE id_user = ? AND id_post = ?");
    $stmt->execute([$id_user, $id_post]);
    return $stmt->fetch() ? true : false;
}

// Get all tags for sidebar
$allTags = $pdo->query("
    SELECT t.*, COUNT(pt.id_post) as post_count 
    FROM tb_forum_tag t 
    LEFT JOIN tb_forum_post_tag pt ON t.id_tag = pt.id_tag 
    GROUP BY t.id_tag 
    ORDER BY post_count DESC 
    LIMIT 10
")->fetchAll();

// Get announcements
$announcements = $pdo->query("SELECT p.*, u.username FROM tb_forum_post p JOIN tb_user u ON p.id_user = u.id_user WHERE p.is_announcement = 1 ORDER BY p.tgl_post DESC LIMIT 3")->fetchAll();

// Forum stats
$totalMembers = $pdo->query("SELECT COUNT(*) FROM tb_user")->fetchColumn();
$totalSolved = $pdo->query("SELECT COUNT(*) FROM tb_forum_post WHERE status = 'solved'")->fetchColumn();
$totalForumPosts = $pdo->query("SELECT COUNT(*) FROM tb_forum_post")->fetchColumn();

// Top contributors (by vote count)
$topContributors = $pdo->query("
    SELECT u.username, u.role,
        (SELECT COALESCE(SUM(CASE WHEN v.tipe_vote = 'up' THEN 1 ELSE 0 END), 0)
         FROM tb_forum_vote v 
         JOIN tb_forum_post p2 ON v.id_post = p2.id_post 
         WHERE p2.id_user = u.id_user) +
        (SELECT COALESCE(SUM(CASE WHEN v2.tipe_vote = 'up' THEN 1 ELSE 0 END), 0)
         FROM tb_forum_vote v2 
         JOIN tb_forum_jawaban j2 ON v2.id_jawaban = j2.id_jawaban 
         WHERE j2.id_user = u.id_user) as total_score
    FROM tb_user u
    HAVING total_score > 0
    ORDER BY total_score DESC
    LIMIT 5
")->fetchAll();

// Helper: format relative time
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

// Helper: get initial from username
function getInitial($username) {
    return strtoupper(substr($username, 0, 2));
}

// Helper: strip HTML for preview
function getTextPreview($html, $maxLen = 200) {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return mb_strlen($text) > $maxLen ? mb_substr($text, 0, $maxLen) . '...' : $text;
}

// Helper: extract first code block for preview
function extractCodeBlock($html) {
    if (preg_match('/<pre><code(?:\s+class="language-(\w+)")?>(.*?)<\/code><\/pre>/s', $html, $matches)) {
        return [
            'lang' => $matches[1] ?? 'code',
            'code' => html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8')
        ];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-FORVM — Forum Diskusi Programming</title>
    <meta name="description" content="Forum diskusi programming terbuka. Tanya jawab, berbagi kode, dan diskusi seputar pemrograman bersama komunitas E-Learning Programming.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/forum.css?v=<?= filemtime('assets/css/forum.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- ============================================
     TICKER MARQUEE
     ============================================ -->
<div class="forum-ticker">
    <div class="forum-ticker-inner">
        <span><i class='bx bxs-hot'></i> FORUM PROGRAMMER TERBUKA INDONESIA</span>
        <span><i class='bx bx-star'></i> TANYA KODE & REVIEW ARSITEKTUR</span>
        <span><i class='bx bx-group'></i> KOMUNITAS WEB DEVELOPER BELAJAR BERSAMA</span>
        <span><i class='bx bx-code-alt'></i> KOLABORASI OPEN-SOURCE</span>
        <span><i class='bx bx-trophy'></i> CHALLENGE MINGGUAN AKTIF</span>
        <!-- Duplicate for seamless loop -->
        <span><i class='bx bxs-hot'></i> FORUM PROGRAMMER TERBUKA INDONESIA</span>
        <span><i class='bx bx-star'></i> TANYA KODE & REVIEW ARSITEKTUR</span>
        <span><i class='bx bx-group'></i> KOMUNITAS WEB DEVELOPER BELAJAR BERSAMA</span>
        <span><i class='bx bx-code-alt'></i> KOLABORASI OPEN-SOURCE</span>
        <span><i class='bx bx-trophy'></i> CHALLENGE MINGGUAN AKTIF</span>
    </div>
</div>

<!-- ============================================
     MAIN 3-COLUMN LAYOUT
     ============================================ -->
<div class="forum-layout">

    <!-- ========== SIDEBAR LEFT ========== -->
    <aside class="forum-sidebar-left">
        <!-- Explore -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-compass'></i> Eksplorasi</div>
            <ul class="forum-explore-list">
                <li class="forum-explore-item">
                    <a href="forum.php?sort=hot" class="<?= $sort === 'hot' ? 'active' : '' ?>">
                        <i class='bx bxs-hot'></i> Populer & Hangat
                        <span class="forum-explore-badge">HOT</span>
                    </a>
                </li>
                <li class="forum-explore-item">
                    <a href="forum.php?sort=baru" class="<?= $sort === 'baru' ? 'active' : '' ?>">
                        <i class='bx bx-time-five'></i> Terbaru Masuk
                    </a>
                </li>
                <li class="forum-explore-item">
                    <a href="forum.php?sort=top" class="<?= $sort === 'top' ? 'active' : '' ?>">
                        <i class='bx bx-trophy'></i> Teratas Pekan Ini
                        <span class="forum-explore-badge" style="background-color: var(--secondary); color: #000;">TOP</span>
                    </a>
                </li>
                <li class="forum-explore-item">
                    <a href="forum.php?sort=unsolved" class="<?= $sort === 'unsolved' ? 'active' : '' ?>">
                        <i class='bx bx-help-circle'></i> Belum Terjawab
                    </a>
                </li>
            </ul>
        </div>

        <!-- Topik -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-hash'></i> Topik</div>
            <ul class="forum-topic-list">
                <?php foreach ($allTags as $tag): ?>
                <li class="forum-topic-item">
                    <a href="forum.php?tag=<?= urlencode($tag['nama_tag']) ?>">
                        <span class="forum-topic-icon" style="background-color: <?= htmlspecialchars($tag['warna']) ?>; color: #000;">
                            <i class='bx <?= htmlspecialchars($tag['icon']) ?>'></i>
                        </span>
                        <?= htmlspecialchars($tag['nama_tag']) ?>
                        <span class="forum-topic-count"><?= number_format($tag['post_count']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="forum.php" class="forum-view-all-btn">
                <i class='bx bx-plus'></i> Lihat Semua Topik
            </a>
        </div>

        <!-- Aturan -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-shield-quarter'></i> Aturan</div>
            <ul class="forum-rules-list">
                <li><i class='bx bx-check-circle'></i> <span><strong>Snippet Kode:</strong> Jangan langsung kasih kode, tapi jelaskan logic-nya juga.</span></li>
                <li><i class='bx bx-check-circle'></i> <span><strong>Hormati Pemula:</strong> Kritik substansi, bukan orangnya.</span></li>
                <li><i class='bx bx-check-circle'></i> <span><strong>No Spam/Promo:</strong> Jualan bootcamp tanpa izin akan langsung di-ban.</span></li>
            </ul>
        </div>
    </aside>

    <!-- ========== MAIN FEED ========== -->
    <main class="forum-main-feed">

        <!-- Mobile Topic Buttons -->
        <div class="forum-mobile-header">
            <a href="forum.php?sort=hot" class="forum-mobile-topic-btn <?= $sort === 'hot' ? 'active' : '' ?>" style="<?= $sort === 'hot' ? 'background-color:var(--secondary);color:#000;' : '' ?>"><i class='bx bxs-hot'></i> Hot</a>
            <a href="forum.php?sort=baru" class="forum-mobile-topic-btn <?= $sort === 'baru' ? 'active' : '' ?>" style="<?= $sort === 'baru' ? 'background-color:var(--secondary);color:#000;' : '' ?>"><i class='bx bx-time-five'></i> Baru</a>
            <a href="forum.php?sort=top" class="forum-mobile-topic-btn <?= $sort === 'top' ? 'active' : '' ?>" style="<?= $sort === 'top' ? 'background-color:var(--secondary);color:#000;' : '' ?>"><i class='bx bx-trophy'></i> Top</a>
            <?php if ($is_logged_in): ?>
            <a href="forum_buat_post.php" class="forum-mobile-topic-btn" style="background-color:var(--primary);color:#FFF;"><i class='bx bx-plus'></i> Post</a>
            <?php endif; ?>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="forum.php" class="forum-search-bar">
            <input type="text" name="q" class="forum-search-input" placeholder="Ada bug apa hari ini? Tulis pertanyaan atau bagikan kode..." value="<?= htmlspecialchars($search) ?>">
            <?php if (!empty($tag_filter)): ?>
                <input type="hidden" name="tag" value="<?= htmlspecialchars($tag_filter) ?>">
            <?php endif; ?>
            <?php if (!empty($sort) && $sort !== 'hot'): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <?php endif; ?>
            <button type="submit" class="forum-search-btn"><i class='bx bx-search'></i> Kirim</button>
        </form>

        <!-- Filter Chips -->
        <div class="forum-filter-chips">
            <a href="forum.php?tipe=pertanyaan<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?>" class="forum-chip <?= $tipe_filter === 'pertanyaan' ? 'active' : '' ?>"><i class='bx bx-help-circle'></i> Pertanyaan</a>
            <a href="forum.php?tipe=diskusi<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?>" class="forum-chip <?= $tipe_filter === 'diskusi' ? 'active' : '' ?>"><i class='bx bx-chat'></i> Diskusi</a>
            <a href="forum.php?tipe=berbagi<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?>" class="forum-chip <?= $tipe_filter === 'berbagi' ? 'active' : '' ?>"><i class='bx bx-share-alt'></i> Berbagi</a>
            <?php if (!empty($tipe_filter) || !empty($tag_filter) || !empty($search)): ?>
                <a href="forum.php" class="forum-chip" style="background-color: var(--primary); color: #FFF;"><i class='bx bx-x'></i> Reset Filter</a>
            <?php endif; ?>
        </div>

        <!-- Active Tag Filter Notice -->
        <?php if (!empty($tag_filter)): ?>
            <div class="forum-announcement" style="background-color: var(--accent); border-color: var(--border);">
                <span class="forum-announcement-badge" style="background-color: #FFF; color: #000;">FILTER</span>
                <span class="forum-announcement-text" style="color: #FFF;">Menampilkan postingan dengan tag: <strong>#<?= htmlspecialchars($tag_filter) ?></strong></span>
                <a href="forum.php" class="forum-announcement-dismiss" style="color: #FFF;">&times;</a>
            </div>
        <?php endif; ?>

        <!-- Sort Tabs -->
        <div class="forum-sort-tabs">
            <a href="forum.php?sort=hot<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?><?= !empty($tipe_filter) ? '&tipe='.urlencode($tipe_filter) : '' ?>" class="forum-sort-tab <?= $sort === 'hot' ? 'active' : '' ?>"><i class='bx bxs-hot'></i> Hot</a>
            <a href="forum.php?sort=baru<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?><?= !empty($tipe_filter) ? '&tipe='.urlencode($tipe_filter) : '' ?>" class="forum-sort-tab <?= $sort === 'baru' ? 'active' : '' ?>"><i class='bx bx-time-five'></i> Baru</a>
            <a href="forum.php?sort=top<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?><?= !empty($tipe_filter) ? '&tipe='.urlencode($tipe_filter) : '' ?>" class="forum-sort-tab <?= $sort === 'top' ? 'active' : '' ?>"><i class='bx bx-up-arrow-alt'></i> Top</a>
            <a href="forum.php?sort=unsolved<?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?>" class="forum-sort-tab <?= $sort === 'unsolved' ? 'active' : '' ?>"><i class='bx bx-help-circle'></i> Belum Solved</a>
        </div>

        <!-- Announcements -->
        <?php foreach ($announcements as $ann): ?>
        <div class="forum-announcement">
            <span class="forum-announcement-badge">PINNED</span>
            <span class="forum-announcement-text">
                <a href="forum_post_detail.php?id=<?= $ann['id_post'] ?>"><?= htmlspecialchars($ann['judul']) ?></a>
            </span>
        </div>
        <?php endforeach; ?>

        <!-- Post List -->
        <?php if (empty($posts)): ?>
            <div class="forum-empty-state">
                <i class='bx bx-message-square-detail'></i>
                <h3>Belum Ada Postingan</h3>
                <p>Jadilah yang pertama memulai diskusi!</p>
                <?php if ($is_logged_in): ?>
                    <a href="forum_buat_post.php" class="forum-fab" style="margin-top: 1rem; display: inline-flex;"><i class='bx bx-plus'></i> Buat Postingan</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): 
                $tags = getPostTags($pdo, $post['id_post']);
                $userVote = getUserVote($pdo, $id_user, $post['id_post']);
                $bookmarked = isBookmarked($pdo, $id_user, $post['id_post']);
                $codeBlock = extractCodeBlock($post['konten']);
                $textPreview = getTextPreview($post['konten']);
            ?>
            <div class="forum-post-card" id="post-<?= $post['id_post'] ?>">
                <!-- Vote Column -->
                <div class="forum-post-votes">
                    <button class="forum-vote-btn <?= $userVote === 'up' ? 'voted-up' : '' ?>" 
                            onclick="vote(<?= $post['id_post'] ?>, 'post', 'up')" 
                            title="Upvote"
                            <?= !$is_logged_in ? 'disabled' : '' ?>>
                        <i class='bx bxs-up-arrow'></i>
                    </button>
                    <span class="forum-vote-count" id="vote-count-<?= $post['id_post'] ?>"><?= $post['vote_count'] ?></span>
                    <button class="forum-vote-btn <?= $userVote === 'down' ? 'voted-down' : '' ?>" 
                            onclick="vote(<?= $post['id_post'] ?>, 'post', 'down')"
                            title="Downvote"
                            <?= !$is_logged_in ? 'disabled' : '' ?>>
                        <i class='bx bxs-down-arrow'></i>
                    </button>
                </div>

                <!-- Post Body -->
                <div class="forum-post-body">
                    <!-- Meta -->
                    <div class="forum-post-meta">
                        <span class="forum-post-avatar"><?= getInitial($post['username']) ?></span>
                        <span class="forum-post-username"><?= htmlspecialchars($post['username']) ?></span>
                        <span class="forum-post-role <?= $post['user_role'] === 'Pengajar' ? 'role-pengajar' : ($post['user_role'] === 'Admin' ? 'role-admin' : '') ?>"><?= htmlspecialchars($post['user_role']) ?></span>
                        <?php if ($post['is_pinned']): ?>
                            <span class="forum-pinned-badge"><i class='bx bxs-pin'></i> Pinned</span>
                        <?php endif; ?>
                        <span class="forum-tipe-badge tipe-<?= $post['tipe'] ?>"><?= $post['tipe'] === 'pertanyaan' ? '❓ Pertanyaan' : ($post['tipe'] === 'diskusi' ? '💬 Diskusi' : '📤 Berbagi') ?></span>
                        <span class="forum-post-time"><?= timeAgo($post['tgl_post']) ?></span>
                    </div>

                    <!-- Title -->
                    <a href="forum_post_detail.php?id=<?= $post['id_post'] ?>" class="forum-post-title">
                        <?= htmlspecialchars($post['judul']) ?>
                    </a>

                    <!-- Preview -->
                    <div class="forum-post-preview">
                        <?= $textPreview ?>
                    </div>

                    <!-- Code Block Preview -->
                    <?php if ($codeBlock): ?>
                    <div class="forum-code-preview" data-lang="<?= htmlspecialchars($codeBlock['lang']) ?>">
<code><?= htmlspecialchars($codeBlock['code']) ?></code>
                    </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="forum-post-tags">
                        <?php foreach ($tags as $t): ?>
                        <a href="forum.php?tag=<?= urlencode($t['nama_tag']) ?>" class="forum-tag" style="border-left: 4px solid <?= htmlspecialchars($t['warna']) ?>;">
                            #<?= htmlspecialchars($t['nama_tag']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="forum-post-actions">
                        <a href="forum_post_detail.php?id=<?= $post['id_post'] ?>" class="forum-action-btn">
                            <i class='bx bx-message-rounded-dots'></i> <?= $post['jawaban_count'] ?> Jawaban
                        </a>
                        <button class="forum-action-btn" onclick="sharePost(<?= $post['id_post'] ?>, '<?= htmlspecialchars(addslashes($post['judul'])) ?>')">
                            <i class='bx bx-share-alt'></i> Bagikan
                        </button>
                        <?php if ($is_logged_in): ?>
                        <button class="forum-action-btn" id="bookmark-btn-<?= $post['id_post'] ?>" onclick="toggleBookmark(<?= $post['id_post'] ?>)">
                            <i class='bx <?= $bookmarked ? 'bxs-bookmark' : 'bx-bookmark' ?>'></i> 
                            <span><?= $bookmarked ? 'Tersimpan' : 'Simpan' ?></span>
                        </button>
                        <?php endif; ?>

                        <!-- Status Badge -->
                        <?php if ($post['tipe'] === 'pertanyaan'): ?>
                        <span class="forum-status status-<?= $post['status'] ?>">
                            <?= $post['status'] === 'solved' ? '✓ SOLVED' : '○ OPEN' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Load More / Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php if ($page < $total_pages): ?>
                    <a href="forum.php?page=<?= $page + 1 ?>&sort=<?= $sort ?><?= !empty($tag_filter) ? '&tag='.urlencode($tag_filter) : '' ?><?= !empty($tipe_filter) ? '&tipe='.urlencode($tipe_filter) : '' ?><?= !empty($search) ? '&q='.urlencode($search) : '' ?>" class="forum-load-more">
                        <i class='bx bx-chevron-down'></i> Muat <?= min($per_page, $total_posts - $page * $per_page) ?> Thread Lainnya
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- ========== SIDEBAR RIGHT ========== -->
    <aside class="forum-sidebar-right">
        <!-- Create Post CTA -->
        <?php if ($is_logged_in): ?>
        <a href="forum_buat_post.php" class="forum-fab" style="width: 100%; justify-content: center;">
            <i class='bx bx-edit'></i> Buat Postingan
        </a>
        <?php else: ?>
        <a href="login.php" class="forum-fab" style="width: 100%; justify-content: center; background-color: var(--accent);">
            <i class='bx bx-log-in'></i> Masuk untuk Posting
        </a>
        <?php endif; ?>

        <!-- About Forum -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-info-circle'></i> Tentang Forum</div>
            <div class="forum-about-text">
                Wadah tanya-jawab teknis, bedah arsitektur kode, dan mentoring langsung bersama pengajar dan software engineer se-Indonesia.
            </div>
            <div class="forum-stats-row">
                <div class="forum-stat-item">
                    <span class="forum-stat-number"><?= number_format($totalMembers) ?></span>
                    <span class="forum-stat-label">Anggota</span>
                </div>
                <div class="forum-stat-item">
                    <span class="forum-stat-number"><?= number_format($totalSolved) ?></span>
                    <span class="forum-stat-label">Solved</span>
                </div>
            </div>
        </div>

        <!-- Top Contributors -->
        <?php if (!empty($topContributors)): ?>
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-crown'></i> Top Kontributor</div>
            <ul class="forum-top-list">
                <?php foreach ($topContributors as $idx => $contrib): ?>
                <li class="forum-top-item">
                    <span class="forum-top-rank">#<?= $idx + 1 ?></span>
                    <span class="forum-top-avatar" style="<?= $idx === 0 ? 'background-color: #FFD700;' : ($idx === 1 ? 'background-color: #C0C0C0;' : ($idx === 2 ? 'background-color: #CD7F32;' : '')) ?>"><?= getInitial($contrib['username']) ?></span>
                    <span class="forum-top-name"><?= htmlspecialchars($contrib['username']) ?></span>
                    <span class="forum-top-score"><?= number_format($contrib['total_score']) ?> XP</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Bantuan Instruktur -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-support'></i> Butuh Bantuan?</div>
            <div class="forum-cta-box">
                <p>Stuck berjam-jam pada satu error? Ajukan pertanyaan atau hubungi instruktur.</p>
                <a href="forum_buat_post.php" class="forum-cta-btn" style="margin-bottom: 8px;">
                    <i class='bx bx-edit'></i> Ajukan Pertanyaan
                </a>
                <a href="index.php#kontak" class="forum-cta-btn" style="background-color: #25D366; color: #FFF;">
                    <i class='bx bxl-whatsapp'></i> Chat CS
                </a>
            </div>
        </div>

        <!-- Pelajari Format -->
        <div class="forum-sidebar-box">
            <div class="forum-sidebar-title"><i class='bx bx-book-open'></i> Tips Posting</div>
            <ul class="forum-rules-list">
                <li><i class='bx bx-code-alt'></i> <span>Gunakan blok kode untuk membagikan snippet</span></li>
                <li><i class='bx bx-hash'></i> <span>Tambahkan tag agar pertanyaan mudah ditemukan</span></li>
                <li><i class='bx bx-check-double'></i> <span>Tandai jawaban terbaik sebagai "Solusi"</span></li>
            </ul>
        </div>
    </aside>
</div>

<!-- ============================================
     FORUM FOOTER
     ============================================ -->
<footer class="forum-footer">
    <!-- Footer Marquee -->
    <div class="forum-footer-marquee">
        <div class="forum-footer-marquee-inner">
            <span>★ FORUM PROGRAMMER TERBUKA INDONESIA</span>
            <span>★ TANYA KODE & REVIEW ARSITEKTUR</span>
            <span>★ KOMUNITAS WEB DEVELOPER BELAJAR BERSAMA</span>
            <span>★ KOLABORASI OPEN-SOURCE</span>
            <span>★ FORUM PROGRAMMER TERBUKA INDONESIA</span>
            <span>★ TANYA KODE & REVIEW ARSITEKTUR</span>
            <span>★ KOMUNITAS WEB DEVELOPER BELAJAR BERSAMA</span>
            <span>★ KOLABORASI OPEN-SOURCE</span>
        </div>
    </div>
    
    <div class="forum-footer-content">
        <div class="forum-footer-brand">
            E-FORVM
            <small>Forum Diskusi Programming — E-Learning</small>
        </div>
        <div class="forum-footer-links">
            <a href="index.php">Beranda E-Learning</a>
            <a href="forum.php">Forum</a>
            <a href="index.php#faq">FAQ</a>
            <a href="index.php#kontak">Kontak</a>
        </div>
    </div>
    <div class="forum-footer-copy">
        &copy; <?= date('Y') ?> E-Learning Programming. Dibangun untuk Programmer Pemula.
    </div>
</footer>

<?php include 'includes/cursor.php'; ?>
<script src="assets/js/neo-alert.js"></script>

<script>
// ========== VOTING SYSTEM (AJAX) ==========
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
            // Update vote count
            const countEl = document.getElementById('vote-count-' + id);
            if (countEl) countEl.textContent = data.new_count;

            // Update button styles
            const card = document.getElementById('post-' + id);
            if (card) {
                const upBtn = card.querySelector('.forum-vote-btn:first-child');
                const downBtn = card.querySelector('.forum-vote-btn:last-of-type');
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

// ========== BOOKMARK SYSTEM (AJAX) ==========
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

// ========== SHARE ==========
function sharePost(postId, title) {
    const url = window.location.origin + '/SI_E-Learning_UAS/forum_post_detail.php?id=' + postId;
    
    if (navigator.share) {
        navigator.share({ title: title, url: url });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            NeoToast('Link berhasil disalin ke clipboard!', 'success');
        }).catch(() => {
            // Fallback
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            NeoToast('Link berhasil disalin!', 'success');
        });
    }
}
</script>

</body>
</html>
