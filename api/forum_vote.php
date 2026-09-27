<?php
session_start();
require_once '../includes/koneksi.php';

header('Content-Type: application/json');

// Cek autentikasi
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Cek method & parse input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id_user = $_SESSION['id_user'];
$id = $input['id'] ?? null;
$type = $input['type'] ?? 'post'; // 'post' atau 'jawaban'
$vote = $input['vote'] ?? null; // 'up' atau 'down'
$csrf_token = $input['csrf_token'] ?? '';

// Validasi input
if (!$id || !in_array($type, ['post', 'jawaban']) || !in_array($vote, ['up', 'down'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit();
}

// Validasi CSRF
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit();
}

$col_id = $type === 'post' ? 'id_post' : 'id_jawaban';

try {
    $pdo->beginTransaction();

    // 1. Cek apakah user sudah vote sebelumnya
    $stmtCek = $pdo->prepare("SELECT tipe_vote FROM tb_forum_vote WHERE id_user = ? AND $col_id = ?");
    $stmtCek->execute([$id_user, $id]);
    $existing = $stmtCek->fetch();

    $action = '';
    
    if ($existing) {
        if ($existing['tipe_vote'] === $vote) {
            // User menekan tombol yang sama -> hapus vote (toggle)
            $stmtDel = $pdo->prepare("DELETE FROM tb_forum_vote WHERE id_user = ? AND $col_id = ?");
            $stmtDel->execute([$id_user, $id]);
            $action = 'removed';
        } else {
            // User menekan tombol beda -> update vote
            $stmtUpdate = $pdo->prepare("UPDATE tb_forum_vote SET tipe_vote = ? WHERE id_user = ? AND $col_id = ?");
            $stmtUpdate->execute([$vote, $id_user, $id]);
            $action = $vote;
        }
    } else {
        // Vote baru
        $stmtInsert = $pdo->prepare("INSERT INTO tb_forum_vote (id_user, $col_id, tipe_vote) VALUES (?, ?, ?)");
        $stmtInsert->execute([$id_user, $id, $vote]);
        $action = $vote;
    }

    // 2. Hitung jumlah vote terbaru
    $stmtCount = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipe_vote = 'up' THEN 1 WHEN tipe_vote = 'down' THEN -1 ELSE 0 END), 0) as total
        FROM tb_forum_vote WHERE $col_id = ?
    ");
    $stmtCount->execute([$id]);
    $new_count = $stmtCount->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'action' => $action,
        'new_count' => $new_count
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
