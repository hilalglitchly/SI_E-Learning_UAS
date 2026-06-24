<?php
session_start();
require_once 'includes/koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Rate Limiting Check
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }
    
    // Reset limit after 15 minutes
    if (time() - $_SESSION['last_login_attempt'] > 900) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $error = 'Terlalu banyak percobaan login gagal. Silakan coba lagi setelah 15 menit.';
    } else {
        // 2. CSRF Check
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            $error = 'Token keamanan tidak valid atau sesi telah kadaluarsa. Silakan refresh halaman.';
        } else {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            if (empty($username) || empty($password)) {
                $error = 'Username dan Password wajib diisi!';
    } else {
        // Cek kredensial di database
        $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();

        // Validasi password menggunakan password_verify()
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate Session ID for Session Fixation protection
            session_regenerate_id(true);
            
            // Reset login attempts on success
            $_SESSION['login_attempts'] = 0;

            // Set session variables
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Cek hak akses Pimpinan
            if ($user['role'] == "Pimpinan") {
                $_SESSION['level'] = "pimpinan";
                header("Location: dashboard.php");
                exit();
            }

            // Redirect ke halaman dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_login_attempt'] = time();
            $error = 'Username atau Password salah!';
        }
    }
        } // Close else from CSRF Check
    } // Close else from Rate Limiting
} // Close if POST
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Learning</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
                Login ke<br><span style="color: var(--accent);">E-Learning</span><br>Programming
            </h1>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'register_success'): ?>
                <div class="neo-error" style="background-color: var(--success, #16A34A); color: #FFF; border-color: var(--border);">
                    <i class='bx bx-check-circle' style="margin-right: 0.5rem;"></i>Registrasi berhasil! Silakan login.
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="neo-error">
                    <i class='bx bx-error-circle' style="margin-right: 0.5rem;"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="neo-form-group" style="margin-bottom: 1.5rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.8rem; font-weight: 800; color: var(--foreground);">Username</label>
                    
                    <div style="display: flex; align-items: stretch; border: 3px solid var(--border); background-color: var(--card); box-shadow: 4px 4px 0px var(--shadow-color);">
                        <div style="padding: 1rem; border-right: 3px solid var(--border); background-color: var(--secondary); display: flex; align-items: center; justify-content: center;">
                            <i class='bx bx-user' style="font-size: 1.5rem; color: var(--secondary-foreground);"></i>
                        </div>
                        <input type="text" name="username" class="neo-input" style="border: none; outline: none; width: 100%; padding: 1rem; font-size: 1.1rem; background: transparent; color: var(--card-foreground); font-family: inherit; font-weight: 600;" placeholder="Contoh: mhilal99" required>
                    </div>
                </div>

                <div class="neo-form-group" style="margin-bottom: 2.5rem;">
                    <label class="neo-label" style="display: block; margin-bottom: 0.8rem; font-weight: 800; color: var(--foreground);">Password</label>
                    
                    <div style="display: flex; align-items: stretch; border: 3px solid var(--border); background-color: var(--card); box-shadow: 4px 4px 0px var(--shadow-color);">
                        <div style="padding: 1rem; border-right: 3px solid var(--border); background-color: var(--primary); display: flex; align-items: center; justify-content: center;">
                            <i class='bx bx-key' style="font-size: 1.5rem; color: var(--primary-foreground);"></i>
                        </div>
                        <input type="password" name="password" class="neo-input" style="border: none; outline: none; width: 100%; padding: 1rem; font-size: 1.1rem; background: transparent; color: var(--card-foreground); font-family: inherit; font-weight: 600;" placeholder="Masukkan password Anda" required>
                    </div>
                </div>

                <button type="submit" name="login" class="neo-btn" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: 900; background-color: var(--accent); color: var(--accent-foreground); border: 3px solid var(--border); box-shadow: 5px 5px 0px var(--shadow-color); cursor: pointer; text-transform: uppercase; transition: transform 0.1s, box-shadow 0.1s;">
                    MASUK
                </button>
            </form>
            
            <div style="margin-top: 2rem; text-align: center; font-weight: 600;">
                Belum punya akun? <br><br> 
                <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 800; border-bottom: 2px solid var(--accent); transition: background-color 0.2s, color 0.2s;">Daftar di sini</a>
            </div>
            
        </div>
    </div>
<?php include 'includes/cursor.php'; ?>
</body>
</html>
