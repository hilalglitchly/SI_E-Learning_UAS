<?php
require_once 'includes/koneksi.php';

echo "Memulai migrasi password...\n";

try {
    $stmt = $pdo->query("SELECT id_user, username, password FROM tb_user");
    $users = $stmt->fetchAll();

    $migrated_count = 0;

    foreach ($users as $user) {
        // Bcrypt hashes are 60 characters long. 
        $info = password_get_info($user['password']);
        
        if ($info['algoName'] === 'unknown' && strlen($user['password']) < 60) {
            $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE tb_user SET password = :password WHERE id_user = :id_user");
            $update_stmt->execute([
                'password' => $hashed,
                'id_user' => $user['id_user']
            ]);
            echo "User " . $user['username'] . " password migrated.\n";
            $migrated_count++;
        }
    }

    echo "Selesai. $migrated_count password berhasil di-hash.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
