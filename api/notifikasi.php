<?php
session_start();
require_once '../includes/koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id_user = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ambil notifikasi terbaru yang belum dibaca
    try {
        $stmt = $pdo->prepare("SELECT * FROM tb_notifikasi WHERE id_user = :id_user ORDER BY tgl_dibuat DESC LIMIT 10");
        $stmt->execute(['id_user' => $id_user]);
        $notifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hitung unread
        $stmtUnread = $pdo->prepare("SELECT COUNT(*) as unread_count FROM tb_notifikasi WHERE id_user = :id_user AND is_read = 0");
        $stmtUnread->execute(['id_user' => $id_user]);
        $unread = $stmtUnread->fetch(PDO::FETCH_ASSOC)['unread_count'];

        echo json_encode([
            'status' => 'success',
            'unread_count' => $unread,
            'data' => $notifikasi
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tandai notifikasi dibaca
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'mark_read' && isset($input['id_notifikasi'])) {
        try {
            $stmt = $pdo->prepare("UPDATE tb_notifikasi SET is_read = 1 WHERE id_notifikasi = :id_notifikasi AND id_user = :id_user");
            $stmt->execute([
                'id_notifikasi' => $input['id_notifikasi'],
                'id_user' => $id_user
            ]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif (isset($input['action']) && $input['action'] === 'delete' && isset($input['id_notifikasi'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_notifikasi WHERE id_notifikasi = :id_notifikasi AND id_user = :id_user");
            $stmt->execute([
                'id_notifikasi' => $input['id_notifikasi'],
                'id_user' => $id_user
            ]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}
?>
