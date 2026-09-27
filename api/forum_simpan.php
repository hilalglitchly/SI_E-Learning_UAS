<?php
session_start();
require_once '../includes/koneksi.php';

header('Content-Type: application/json');

// Cek autentikasi
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id_user = $_SESSION['id_user'];
$id_post = $input['id_post'] ?? null;
$csrf_token = $input['csrf_token'] ?? '';

if (!$id_post) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid post ID']);
    exit();
}

if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit();
}

try {
    // Cek apakah sudah disave
    $stmtCek = $pdo->prepare("SELECT 1 FROM tb_forum_simpan WHERE id_user = ? AND id_post = ?");
    $stmtCek->execute([$id_user, $id_post]);
    $isSaved = $stmtCek->fetch();

    if ($isSaved) {
        // Hapus bookmark
        $stmtDel = $pdo->prepare("DELETE FROM tb_forum_simpan WHERE id_user = ? AND id_post = ?");
        $stmtDel->execute([$id_user, $id_post]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Tambah bookmark
        $stmtIns = $pdo->prepare("INSERT INTO tb_forum_simpan (id_user, id_post) VALUES (?, ?)");
        $stmtIns->execute([$id_user, $id_post]);
        echo json_encode(['status' => 'success', 'action' => 'saved']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
