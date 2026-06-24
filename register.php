<?php
session_start();
require_once 'includes/koneksi.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = "Token keamanan tidak valid atau sesi telah kadaluarsa. Silakan refresh halaman.";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Validasi input kosong
        if (empty($username) || empty($password)) {
            $error = "Username dan Password tidak boleh kosong!";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Cek Ketersediaan Username
            $stmt_cek = $pdo->prepare("SELECT id_user FROM tb_user WHERE username = :username");
            $stmt_cek->execute(['username' => $username]);
            
            if ($stmt_cek->rowCount() > 0) {
                // Username sudah ada
                $error = "Username sudah digunakan, silakan pilih yang lain.";
                $pdo->rollBack();
            } else {
                // 2. Insert ke tb_user dengan role hardcoded 'Murid'
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("INSERT INTO tb_user (username, password, role) VALUES (:username, :password, 'Murid')");
                $stmt_user->execute([
                    'username' => $username,
                    'password' => $hashed_password
                ]);
                
                $new_id_user = $pdo->lastInsertId();
                
                // 3. Insert ke tb_murid agar user bisa menggunakan fitur enroll dsb.
                $nim_dummy = 'NIM' . date('ymd') . rand(100, 999);
                $email_dummy = strtolower($username) . rand(100,999) . '@student.local';
                
                $stmt_murid = $pdo->prepare("INSERT INTO tb_murid (nim, nama_murid, email, id_user) VALUES (:nim, :nama_murid, :email, :id_user)");
                $stmt_murid->execute([
                    'nim' => $nim_dummy,
                    'nama_murid' => $username,
                    'email' => $email_dummy,
                    'id_user' => $new_id_user
                ]);

                $pdo->commit();
                
                // Redirect ke login.php dengan pesan sukses
                header("Location: login.php?msg=register_success");
                exit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
        } // Close else from CSRF Check
} // Close if POST
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Murid Baru - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Menambahkan pattern stripe pada body register agar identik dengan login.php yang mungkin di set di tempat lain, 
           atau jika login.php memiliki stripe dari base css, ia akan terwarisi otomatis */
        body {
            background-color: var(--background);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Tombol Kembali -->
    <a href="index.php" class="brutal-hover" style="position: absolute; top: 20px; left: 20px; text-decoration: none; font-weight: 800; font-size: 1.1rem; color: var(--foreground); display: flex; align-items: center; gap: 0.5rem; border: 3px solid var(--border); padding: 6px 12px; background: var(--card); z-index: 1000; box-shadow: 3px 3px 0px var(--shadow-color);">
        <i class='bx bx-arrow-back'></i> Kembali
    </a>
    <!-- Dark Mode Toggle -->
    <button id="theme-toggle" onclick="toggleDarkMode()" style="position: absolute; top: 20px; right: 20px; padding: 8px; font-size: 1.2rem; line-height: 1; background-color: var(--card); border: 3px solid var(--border); box-shadow: 3px 3px 0px var(--shadow-color); color: var(--foreground); z-index: 1000; cursor: pointer;">
        <i class='bx bxs-moon' id='theme-icon'></i>
    </button>
    <script>
        // Mencegah FOUC
        if (localStorage.getItem("theme") === "dark") {
            document.documentElement.classList.add("dark-mode");
        }

        function toggleDarkMode() {
            document.documentElement.classList.add("theme-transition");
            document.documentElement.classList.toggle("dark-mode");
            
            const themeIcon = document.getElementById("theme-icon");
            if (document.documentElement.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
                if (themeIcon) themeIcon.classList.replace('bxs-moon', 'bxs-sun');
            } else {
                localStorage.setItem("theme", "light");
                if (themeIcon) themeIcon.classList.replace('bxs-sun', 'bxs-moon');
            }
            
            setTimeout(() => {
                document.documentElement.classList.remove("theme-transition");
            }, 400);
        }
        
        // Sesuaikan icon saat halaman dimuat
        window.addEventListener('DOMContentLoaded', () => {
            if (document.documentElement.classList.contains("dark-mode")) {
                const themeIcon = document.getElementById("theme-icon");
                if (themeIcon) themeIcon.classList.replace('bxs-moon', 'bxs-sun');
            }
        });
    </script>

    <div class="auth-wrapper" style="background-color: var(--background);">
        
        <div class="neo-container <?php echo !empty($error) ? 'shake' : ''; ?>" style="max-width: 450px; width: 100%; padding: 3rem 2.5rem; border: 3px solid var(--border); box-shadow: 8px 8px 0px var(--shadow-color); background-color: var(--card);">
            
            <h1 class="neo-title" style="font-size: 2.2rem; margin-bottom: 2.5rem; line-height: 1.2; text-transform: uppercase; font-weight: 900; color: var(--foreground);">
                DAFTAR KE<br><span style="color: var(--accent);">E-Learning</span><br>Programming
            </h1>

            <?php if (!empty($error)): ?>
                <div class="neo-error">
                    <i class='bx bx-error-circle' style="margin-right: 0.5rem;"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="neo-form-group" style="margin-bottom: 1.5rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.8rem; font-weight: 800; color: var(--foreground);">Username</label>
                    
                    <div style="display: flex; align-items: stretch; border: 3px solid var(--border); background-color: var(--card); box-shadow: 4px 4px 0px var(--shadow-color);">
                        <div style="padding: 1rem; border-right: 3px solid var(--border); background-color: var(--secondary); display: flex; align-items: center; justify-content: center;">
                            <i class='bx bx-user' style="font-size: 1.5rem; color: var(--secondary-foreground);"></i>
                        </div>
                        <input type="text" name="username" class="neo-input" style="border: none; outline: none; width: 100%; padding: 1rem; font-size: 1.1rem; background: transparent; color: var(--card-foreground); font-family: inherit; font-weight: 600;" placeholder="Buat username unik" required autocomplete="off">
                    </div>
                </div>

                <div class="neo-form-group" style="margin-bottom: 2.5rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.8rem; font-weight: 800; color: var(--foreground);">Password</label>
                    
                    <div style="display: flex; align-items: stretch; border: 3px solid var(--border); background-color: var(--card); box-shadow: 4px 4px 0px var(--shadow-color);">
                        <div style="padding: 1rem; border-right: 3px solid var(--border); background-color: var(--primary); display: flex; align-items: center; justify-content: center;">
                            <i class='bx bx-key' style="font-size: 1.5rem; color: var(--primary-foreground);"></i>
                        </div>
                        <input type="password" name="password" class="neo-input" style="border: none; outline: none; width: 100%; padding: 1rem; font-size: 1.1rem; background: transparent; color: var(--card-foreground); font-family: inherit; font-weight: 600;" placeholder="Masukkan password kuat" required>
                    </div>
                </div>

                <button type="submit" class="neo-btn" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: 900; background-color: var(--accent); color: var(--accent-foreground); border: 3px solid var(--border); box-shadow: 5px 5px 0px var(--shadow-color); cursor: pointer; text-transform: uppercase; transition: transform 0.1s, box-shadow 0.1s;">
                    DAFTAR SEKARANG
                </button>
            </form>
            
            <div style="margin-top: 2rem; text-align: center; font-weight: 600;">
                Sudah punya akun? <br><br> 
                <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 800; border-bottom: 2px solid var(--accent); transition: background-color 0.2s, color 0.2s;">Kembali ke Login</a>
            </div>
            
        </div>
    </div>
<?php include 'includes/cursor.php'; ?>
</body>
</html>
