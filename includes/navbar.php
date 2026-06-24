<?php
// Tentukan menu aktif berdasarkan nama file saat ini
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';
?>
<script>
    // Mencegah FOUC (Flicker) saat load awal mode gelap
    if (localStorage.getItem("theme") === "dark") {
        document.documentElement.classList.add("dark-mode");
    }
</script>
<!-- TOP NAVBAR (Floating Style) -->
<nav class="neo-navbar" style="width: 96%; max-width: 1400px; margin: 20px auto; position: sticky; top: 20px; z-index: 1000; border: 3px solid #000; box-shadow: 8px 8px 0px #000; border-radius: 0; background-color: #FFD700; color: #000;">
    <a href="<?= isset($_SESSION['id_user']) ? 'dashboard.php' : 'index.php' ?>" class="neo-navbar-brand" style="font-weight: 900; color: #000; display: flex; align-items: center; gap: 8px; text-decoration: none; line-height: 1.1;">
        <span style="font-size: 1.8rem; font-family: monospace; font-weight: 900;">&lt;/&gt;</span>
        <div style="display: flex; flex-direction: column;">
            <span>E-LEARNING</span>
            <span>PROGRAMMING</span>
        </div>
    </a>
    
    <ul class="neo-navbar-menu">
        <li><a id="nav-beranda" href="<?= isset($_SESSION['id_user']) ? 'dashboard.php' : ($current_page === 'index.php' ? '#' : 'index.php') ?>" style="color: #000;" class="<?= ($current_page === 'dashboard.php') ? 'active' : '' ?>"><i class='bx bxs-home'></i> BERANDA</a></li>
        
        <?php if (strtolower($role) === 'murid'): ?>
            <li><a href="tugas_global.php" style="color: #000;" class="<?= $current_page === 'tugas_global.php' ? 'active' : '' ?>"><i class='bx bx-task'></i> TUGAS</a></li>
            <li><a href="enroll.php" style="color: #000;" class="<?= $current_page === 'enroll.php' ? 'active' : '' ?>"><i class='bx bx-search'></i> KATALOG KELAS</a></li>
        <?php elseif ($role === 'Pengajar'): ?>
            <li><a href="tugas_global.php" style="color: #000;" class="<?= $current_page === 'tugas_global.php' ? 'active' : '' ?>"><i class='bx bx-task'></i> TUGAS</a></li>
        <?php elseif (strtolower($role) === 'admin'): ?>
            <li><a href="tugas_global.php" style="color: #000;" class="<?= $current_page === 'tugas_global.php' ? 'active' : '' ?>"><i class='bx bx-task'></i> KELOLA TUGAS</a></li>
            <li><a href="tambah_user.php" style="color: #000;" class="<?= $current_page === 'tambah_user.php' ? 'active' : '' ?>"><i class='bx bxs-user-plus'></i> TAMBAH PENGGUNA</a></li>
            <li><a href="laporan.php" style="color: #000;" class="<?= $current_page === 'laporan.php' ? 'active' : '' ?>"><i class='bx bxs-report'></i> LAPORAN</a></li>
        <?php elseif (strtolower($role) === 'pimpinan'): ?>
            <li><a href="laporan.php" style="color: #000;" class="<?= $current_page === 'laporan.php' ? 'active' : '' ?>"><i class='bx bxs-report'></i> LAPORAN</a></li>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['id_user'])): ?>
            <li><a href="profil.php" style="color: #000;" class="<?= $current_page === 'profil.php' ? 'active' : '' ?>"><i class='bx bxs-user-circle'></i> AKUN</a></li>
        <?php else: ?>
            <li><a id="nav-katalog" href="<?= $current_page === 'index.php' ? '#katalog' : 'index.php#katalog' ?>" style="color: #000;"><i class='bx bx-search'></i> KATALOG</a></li>
            <li><a id="nav-faq" href="<?= $current_page === 'index.php' ? '#faq' : 'index.php#faq' ?>" style="color: #000;"><i class='bx bx-question-mark'></i> FAQ</a></li>
            <li><a id="nav-kontak" href="<?= $current_page === 'index.php' ? '#kontak' : 'index.php#kontak' ?>" style="color: #000;"><i class='bx bxs-contact'></i> KONTAK</a></li>
        <?php endif; ?>
    </ul>

    <div class="neo-navbar-user">
        <button title="Ganti Tema" id="theme-toggle" onclick="toggleDarkMode()" class="neo-navbar-btn brutal-hover" style="padding: 8px; font-size: 1.2rem; line-height: 1; background-color: #FFF; border: 3px solid #000; box-shadow: 3px 3px 0px #000; color: #000; margin-right: 10px;"><i class='bx bxs-moon' id='theme-icon'></i></button>
        
        <?php if (isset($_SESSION['id_user'])): ?>
            <div class="neo-notif-dropdown" style="position: relative; display: inline-block;">
                <button title="Notifikasi" id="notif-btn" class="neo-navbar-btn" style="padding: 8px; font-size: 1.2rem; line-height: 1; background-color: #FFF; border: 3px solid #000; box-shadow: 3px 3px 0px #000; color: #000; cursor: pointer; position: relative;">
                    <i class='bx bxs-bell'></i>
                    <span id="notif-badge" style="display: none; position: absolute; top: -8px; right: -8px; background-color: var(--primary); color: #FFF; font-size: 0.7rem; font-weight: 900; padding: 2px 6px; border: 2px solid #000; border-radius: 50%;">0</span>
                </button>
                <div id="notif-menu" class="neo-dropdown-menu" style="position: absolute; top: 100%; right: 0; z-index: 1001; min-width: 320px; padding-top: 10px; display: none;">
                    <div style="background-color: var(--card); border: 3px solid #000; box-shadow: 6px 6px 0px #000; display: flex; flex-direction: column;">
                        <div style="padding: 10px; border-bottom: 3px solid #000; font-weight: 900; text-transform: uppercase; display:flex; justify-content: space-between; align-items: center;">
                            Notifikasi
                            <button onclick="closeNotif()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; font-weight: 900; line-height: 1;">&times;</button>
                        </div>
                        <div id="notif-list" style="max-height: 350px; overflow-y: auto; padding: 0;">
                            <div style="padding: 15px; text-align: center; font-weight: bold; color: var(--muted-foreground);">Tidak ada notifikasi</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="neo-user-dropdown" style="position: relative; display: inline-block;">
                <div class="neo-user-badge" style="cursor: pointer; display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2; background-color: #FFF; padding: 8px 14px; border: 3px solid #000; box-shadow: 3px 3px 0px #000; transition: transform 0.1s, box-shadow 0.1s, background-color 0.2s;">
                    <strong style="font-size: 1em; color: #000;"><?= htmlspecialchars(strtoupper($username)) ?></strong>
                    <span style="font-size: 0.85em; color: #000; font-weight: 500; display: flex; align-items: center; gap: 4px;"><?= htmlspecialchars($role) ?> <i class='bx bxs-chevron-down' style="font-size: 1.1em;"></i></span>
                </div>
                
                <div class="neo-dropdown-menu" style="position: absolute; top: 100%; right: 0; z-index: 1001; min-width: 100%; padding-top: 5px;">
                    <script src="assets/js/neo-alert.js"></script>
                    <a href="dashboard.php?action=logout" onclick="event.preventDefault(); NeoConfirm('Konfirmasi Log Out', 'Anda yakin ingin keluar dari sistem?', this.href);" class="neo-logout-btn" style="background-color: var(--primary); color: #FFF; border: 3px solid #000; box-shadow: 4px 4px 0px #000; padding: 8px 16px; font-weight: bold; text-decoration: none; text-transform: uppercase; font-family: inherit; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 6px; transition: transform 0.1s, box-shadow 0.1s; width: 100%; box-sizing: border-box;"><i class='bx bx-log-out'></i> LOG OUT</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="brutal-hover" style="padding: 8px 16px; font-weight: 900; background-color: #FFF; color: #000; border: 3px solid #000; box-shadow: 3px 3px 0px #000; text-decoration: none; text-transform: uppercase; margin-right: 10px; font-size: 0.9rem;">Masuk</a>
            <a href="register.php" class="brutal-hover" style="padding: 8px 16px; font-weight: 900; background-color: var(--primary); color: #FFF; border: 3px solid #000; box-shadow: 3px 3px 0px #000; text-decoration: none; text-transform: uppercase; font-size: 0.9rem;">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($current_page === 'index.php'): ?>
<script>
// Scroll Spy untuk Navbar Landing Page
document.addEventListener('DOMContentLoaded', function() {
    const sections = [
        { id: 'kontak', navId: 'nav-kontak' },
        { id: 'faq', navId: 'nav-faq' },
        { id: 'katalog', navId: 'nav-katalog' }
    ];
    
    const navBeranda = document.getElementById('nav-beranda');

    function updateActiveNav() {
        const scrollPosition = window.scrollY;
        let activeSectionId = null;

        for (const sec of sections) {
            const el = document.getElementById(sec.id);
            if (el && scrollPosition >= el.offsetTop - 150) {
                activeSectionId = sec.id;
                break; // Because array is ordered from bottom to top
            }
        }

        // Reset all active classes
        if (navBeranda) navBeranda.classList.remove('active');
        sections.forEach(sec => {
            const navEl = document.getElementById(sec.navId);
            if (navEl) navEl.classList.remove('active');
        });

        // Set active class
        if (activeSectionId) {
            const secInfo = sections.find(s => s.id === activeSectionId);
            if (secInfo) {
                const activeNavEl = document.getElementById(secInfo.navId);
                if (activeNavEl) activeNavEl.classList.add('active');
            }
        } else {
            if (navBeranda) navBeranda.classList.add('active');
        }
    }

    // Jalankan saat scroll dan load
    window.addEventListener('scroll', updateActiveNav);
    updateActiveNav();
    
    // Smooth scroll JS fallback for all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                window.scrollTo({ top: targetElement.offsetTop - 80, behavior: 'smooth' });
            }
        });
    });
});
</script>
<?php endif; ?>

<style>
/* Override hover and active state styles inside the yellow navbar to remain static */
.neo-navbar .neo-navbar-menu a:hover,
.neo-navbar .neo-navbar-menu a.active {
    background-color: #FFF !important;
    color: #000 !important;
    border-color: #000 !important;
    box-shadow: 5px 5px 0px #000 !important;
}
/* Ensure the theme toggle and bell buttons hover state is also static */
.neo-navbar .neo-navbar-btn:hover {
    background-color: #eee !important;
    color: #000 !important;
    border-color: #000 !important;
}
/* Ensure the logout button hover state is also static border/shadow */
.neo-navbar .neo-logout-btn:hover {
    border-color: #000 !important;
    box-shadow: 2px 2px 0px #000 !important;
    transform: translate(2px, 2px);
    color: #FFF !important;
}
/* Dropdown styles */
.neo-dropdown-menu {
    display: none;
}
.neo-user-dropdown:hover .neo-dropdown-menu {
    display: block;
    animation: brutalPop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
.neo-user-dropdown:hover .neo-user-badge {
    background-color: #eee !important;
}
</style>

<script>
    function toggleDarkMode() {
        // Tambahkan class transisi sementara
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
        
        // Hapus class transisi setelah animasi selesai
        setTimeout(() => {
            document.documentElement.classList.remove("theme-transition");
        }, 400);
    }
    
    // Sesuaikan icon saat halaman dimuat
    if (document.documentElement.classList.contains("dark-mode")) {
        const themeIcon = document.getElementById("theme-icon");
        if (themeIcon) themeIcon.classList.replace('bxs-moon', 'bxs-sun');
    }
</script>

<script>
    // --- NOTIFIKASI INTERAKTIF ---
    const notifBtn = document.getElementById('notif-btn');
    const notifMenu = document.getElementById('notif-menu');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');

    if (notifBtn) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (notifMenu.style.display === 'none' || notifMenu.style.display === '') {
                notifMenu.style.display = 'block';
                notifMenu.style.animation = 'brutalPop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards';
            } else {
                notifMenu.style.display = 'none';
            }
        });
    }

    function closeNotif() {
        notifMenu.style.display = 'none';
    }

    // Tutup dropdown jika klik di luar
    document.addEventListener('click', function(event) {
        if (notifMenu && notifMenu.style.display === 'block' && !notifBtn.contains(event.target) && !notifMenu.contains(event.target)) {
            closeNotif();
        }
    });

    function markAsRead(id, link) {
        fetch('api/notifikasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id_notifikasi: id })
        }).then(() => {
            window.location.href = link;
        });
    }

    function deleteNotifikasi(id, event) {
        if(event) event.stopPropagation();
        fetch('api/notifikasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id_notifikasi: id })
        }).then(() => {
            fetchNotifikasi();
        });
    }

    function fetchNotifikasi() {
        fetch('api/notifikasi.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.unread_count > 0) {
                        notifBadge.style.display = 'block';
                        notifBadge.innerText = data.unread_count > 9 ? '9+' : data.unread_count;
                    } else {
                        notifBadge.style.display = 'none';
                    }

                    if (data.data.length > 0) {
                        notifList.innerHTML = '';
                        data.data.forEach(n => {
                            const bg = n.is_read == 1 ? 'var(--card)' : 'var(--background)';
                            const dot = n.is_read == 0 ? `<div style="width: 10px; height: 10px; background-color: var(--primary); border-radius: 50%; border: 2px solid #000; flex-shrink: 0;"></div>` : '';
                            notifList.innerHTML += `
                                <div onclick="markAsRead(${n.id_notifikasi}, '${n.link}')" style="padding: 12px; border-bottom: 2px solid var(--border); background-color: ${bg}; cursor: pointer; display: flex; gap: 10px; align-items: flex-start; transition: background-color 0.1s;" onmouseover="this.style.backgroundColor='var(--muted)'" onmouseout="this.style.backgroundColor='${bg}'">
                                    ${dot}
                                    <div style="flex-grow: 1;">
                                        <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 4px; color: var(--foreground);">${n.judul}</div>
                                        <div style="font-size: 0.85rem; line-height: 1.3; color: var(--foreground);">${n.pesan}</div>
                                        <div style="font-size: 0.75rem; margin-top: 6px; color: var(--muted-foreground); font-weight: bold;">${n.tgl_dibuat}</div>
                                    </div>
                                    <div onclick="deleteNotifikasi(${n.id_notifikasi}, event)" style="font-size: 1.2rem; font-weight: 900; color: var(--primary); padding: 0 5px; border: 2px solid transparent; border-radius: 4px;" onmouseover="this.style.borderColor='var(--border)'; this.style.backgroundColor='#fff'" onmouseout="this.style.borderColor='transparent'; this.style.backgroundColor='transparent'">&times;</div>
                                </div>
                            `;
                        });
                    } else {
                        notifList.innerHTML = '<div style="padding: 15px; text-align: center; font-weight: bold; color: var(--muted-foreground);">Tidak ada notifikasi</div>';
                    }
                }
            })
            .catch(err => console.error("Error fetching notif:", err));
    }

    // Mulai polling jika user sudah login (elemen badge ada)
    if (notifBadge) {
        setInterval(fetchNotifikasi, 10000); // 10 detik
        fetchNotifikasi(); // Fetch saat load
    }
</script>
