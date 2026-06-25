<?php
session_start();
require_once 'includes/koneksi.php';

$is_logged_in = isset($_SESSION['id_user']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? ''; // needed for navbar.php

// Stats Database Query
$countMurid = $pdo->query("SELECT COUNT(*) FROM tb_murid")->fetchColumn();
$countKelas = $pdo->query("SELECT COUNT(*) FROM tb_kelas")->fetchColumn();
$countPengajar = $pdo->query("SELECT COUNT(*) FROM tb_user WHERE role = 'Pengajar'")->fetchColumn();

// Katalog Kelas (Limit 12 for scrolling)
$stmtKelas = $pdo->query("
    SELECT k.id_kelas, k.nama_kelas, k.deskripsi, u.username as pengajar 
    FROM tb_kelas k 
    LEFT JOIN tb_pengajar p ON k.nidn = p.nidn
    LEFT JOIN tb_user u ON p.id_user = u.id_user
    ORDER BY k.id_kelas DESC
    LIMIT 12
");
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

// If join fails, fallback
if (empty($kelasList) && $countKelas > 0) {
    $stmtKelasFallback = $pdo->query("SELECT id_kelas, nama_kelas, deskripsi, 'Instruktur' as pengajar FROM tb_kelas LIMIT 12");
    $kelasList = $stmtKelasFallback->fetchAll(PDO::FETCH_ASSOC);
}

// Mock data to ensure we have exactly 12 cards for the scroll effect
$mockClasses = [
    ['id_kelas' => 1, 'nama_kelas' => 'Pengenalan Python', 'deskripsi' => 'Belajar dasar Python untuk pemula.', 'pengajar' => 'dosen_hendra'],
    ['id_kelas' => 2, 'nama_kelas' => 'JavaScript Lanjut', 'deskripsi' => 'Kuasai DOM, Async, dan Await.', 'pengajar' => 'dosen_eko'],
    ['id_kelas' => 3, 'nama_kelas' => 'PHP Modern', 'deskripsi' => 'Belajar fitur terbaru di PHP 8+.', 'pengajar' => 'dosen_budi'],
    ['id_kelas' => 4, 'nama_kelas' => 'C++ Dasar', 'deskripsi' => 'Pemrograman C++ dari nol.', 'pengajar' => 'dosen_hendra'],
    ['id_kelas' => 5, 'nama_kelas' => 'React JS', 'deskripsi' => 'Membangun UI interaktif dengan React.', 'pengajar' => 'dosen_siti'],
    ['id_kelas' => 6, 'nama_kelas' => 'Java Enterprise', 'deskripsi' => 'Pemrograman backend skala besar.', 'pengajar' => 'dosen_dewi'],
    ['id_kelas' => 7, 'nama_kelas' => 'UI/UX Design', 'deskripsi' => 'Mendesain tampilan antarmuka modern.', 'pengajar' => 'dosen_rina'],
    ['id_kelas' => 8, 'nama_kelas' => 'Golang Backend', 'deskripsi' => 'Membangun microservices cepat.', 'pengajar' => 'dosen_tono'],
    ['id_kelas' => 9, 'nama_kelas' => 'Rust for Beginners', 'deskripsi' => 'Bahasa yang aman dan super cepat.', 'pengajar' => 'dosen_agus'],
    ['id_kelas' => 10, 'nama_kelas' => 'DevOps Dasar', 'deskripsi' => 'Belajar Docker & CI/CD pipeline.', 'pengajar' => 'dosen_rudi'],
    ['id_kelas' => 11, 'nama_kelas' => 'Machine Learning', 'deskripsi' => 'Kecerdasan buatan dengan Python.', 'pengajar' => 'dosen_vina'],
    ['id_kelas' => 12, 'nama_kelas' => 'Data Science', 'deskripsi' => 'Menganalisis data tingkat lanjut.', 'pengajar' => 'dosen_nina'],
];

while (count($kelasList) < 12) {
    $kelasList[] = $mockClasses[count($kelasList) % 12];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Programming - Belajar Tanpa Batas</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Global Smooth Scroll */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        /* Specific Landing Page Styles */
        body {
            background-color: var(--background);
        }
        .landing-hero {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 6rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            min-height: 80vh;
            overflow: hidden;
        }
        .hero-text {
            z-index: 10;
            position: relative;
        }
        .hero-text h1 {
            font-size: 5rem;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--foreground);
            text-shadow: 4px 4px 0px var(--shadow-color);
        }
        .hero-text p {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--muted-foreground);
            max-width: 700px;
            line-height: 1.5;
            margin-left: auto;
            margin-right: auto;
        }
        .hero-box {
            position: absolute;
            background-color: var(--card);
            border: 4px solid var(--border);
            box-shadow: 6px 6px 0px var(--border);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(3deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        
        /* Left Side Logos */
        .box-js    { top: 8%; left: 14%; background-color: #F7DF1E; animation-delay: 0s; transform: rotate(-5deg);}
        .box-cplus { top: 30%; left: 4%;  background-color: #00599C; animation-delay: 2s; transform: rotate(4deg);}
        .box-react { top: 53%; left: 15%; background-color: #222; animation-delay: 1.5s; transform: rotate(10deg);}
        .box-mysql { top: 77%; left: 7%;  background-color: #FFF; animation-delay: 0.5s; transform: rotate(-8deg);}
        
        /* Right Side Logos */
        .box-python  { top: 8%; right: 6%; background-color: #3776AB; animation-delay: 1s; transform: rotate(8deg);}
        .box-php     { top: 30%; right: 16%; background-color: #777BB4; animation-delay: 0.2s; transform: rotate(-6deg);}
        .box-laravel { top: 53%; right: 4%;  background-color: #FF2D20; animation-delay: 1.2s; transform: rotate(-10deg);}
        .box-java    { top: 77%; right: 17%; background-color: #FFF; animation-delay: 2.5s; transform: rotate(12deg);}
        
        .stat-bar {
            background-color: var(--foreground);
            color: var(--background);
            padding: 2rem;
            display: flex;
            justify-content: space-around;
            border-top: 4px solid var(--border);
            border-bottom: 4px solid var(--border);
            flex-wrap: wrap;
            gap: 2rem;
        }
        .stat-item {
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: 3rem;
            color: var(--accent);
            text-shadow: 3px 3px 0px #000;
        }
        .section-title {
            font-size: 3rem;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 3rem;
            border-bottom: 4px solid var(--border);
            padding-bottom: 1rem;
            display: inline-block;
        }
        .popular-courses, .features {
            padding: 4rem 2rem;
            text-align: center;
        }
        
        /* HORIZONTAL SCROLL FOR CATALOG */
        .course-scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 2rem;
            padding: 1rem 1rem 2rem 1rem;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .course-scroll-container::-webkit-scrollbar {
            height: 12px;
        }
        .course-scroll-container::-webkit-scrollbar-track {
            background: var(--background);
            border: 3px solid var(--border);
        }
        .course-scroll-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border: 3px solid var(--border);
        }
        .course-card {
            min-width: 320px;
            max-width: 320px;
            flex: 0 0 auto;
            scroll-snap-align: start;
            display: flex;
            flex-direction: column;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            text-align: left;
            max-width: 1400px;
            margin: 0 auto;
        }
        .footer {
            background-color: var(--card);
            border-top: 4px solid var(--border);
            padding: 3rem 2rem;
            text-align: center;
            font-weight: 800;
            margin-top: 4rem;
        }
        
        @media (max-width: 992px) {
            .hero-box { display: none; } /* Hide floating boxes on small screens */
            .hero-text h1 { font-size: 3.5rem; }
            .landing-hero { padding: 4rem 1rem; min-height: 60vh; }
        }



        /* 3. Glitch / Jitter CSS */
        .glitch-hover:hover {
            animation: jitter 0.2s infinite;
        }
        @keyframes jitter {
            0% { transform: translate(0, 0) rotate(-3deg); }
            20% { transform: translate(-3px, 3px) rotate(-5deg); }
            40% { transform: translate(-3px, -3px) rotate(-1deg); }
            60% { transform: translate(3px, 3px) rotate(-4deg); }
            80% { transform: translate(3px, -3px) rotate(-6deg); }
            100% { transform: translate(0, 0) rotate(-3deg); }
        }

        /* 4. Reveal CSS */
        .reveal-text {
            opacity: 0;
            transform: translateY(50px) rotate(-10deg);
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .reveal-text.is-revealed {
            opacity: 1;
            transform: translateY(0) rotate(0);
        }
    </style>
</head>
<body>
    <!-- Reveal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Auto Scroll for Course Container
            const scrollContainer = document.querySelector('.course-scroll-container');
            if(scrollContainer) {
                scrollContainer.style.scrollBehavior = 'auto';
                scrollContainer.style.scrollSnapType = 'none';
                scrollContainer.innerHTML += scrollContainer.innerHTML; // Duplicate for infinite effect
                
                let isHovering = false;
                scrollContainer.addEventListener('mouseenter', () => isHovering = true);
                scrollContainer.addEventListener('mouseleave', () => isHovering = false);
                scrollContainer.addEventListener('touchstart', () => isHovering = true);
                scrollContainer.addEventListener('touchend', () => isHovering = false);
                
                function autoScroll() {
                    if (!isHovering) {
                        scrollContainer.scrollLeft += 1;
                        if (scrollContainer.scrollLeft >= scrollContainer.scrollWidth / 2) {
                            scrollContainer.scrollLeft = 0;
                        }
                    }
                    requestAnimationFrame(autoScroll);
                }
                autoScroll();
            }

            // Intersection Observer for Reveal
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                    }
                });
            }, { threshold: 0.1 });
            
            // Add reveal class to section titles
            document.querySelectorAll('.section-title').forEach(title => {
                title.classList.add('reveal-text');
                observer.observe(title);
            });
        });
    </script>
    <!-- NAVBAR INCLUDED -->
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION -->
    <section class="landing-hero" style="background-image: radial-gradient(var(--border) 2px, transparent 2px); background-size: 30px 30px; border-bottom: 4px solid var(--border);">
        
        <!-- Left Side Logos -->
        <div class="hero-box box-js">
            <i class='bx bxl-javascript' style="font-size: 4rem; color: #000;"></i>
        </div>
        <div class="hero-box box-react">
            <i class='bx bxl-react' style="font-size: 4rem; color: #61DAFB;"></i>
        </div>
        <div class="hero-box box-mysql">
            <i class='bx bxs-data' style="font-size: 4rem; color: #4479A1;"></i>
        </div>
        <div class="hero-box box-cplus">
            <i class='bx bxl-c-plus-plus' style="font-size: 4rem; color: #FFF;"></i>
        </div>

        <!-- Center Content -->
        <div class="hero-text">
            <h1>Belajar<br><span class="glitch-hover" style="color: #000; background-color: #06B6D4; padding: 0 15px; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); display: inline-block; transform: rotate(-3deg); cursor: pointer;">Programming</span><br>Tanpa Batas.</h1>
            <p style="background-color: var(--card); padding: 1rem; border: 3px solid var(--border); box-shadow: 4px 4px 0px var(--border);">Solusi platform pembelajaran jarak jauh dengan video materi, tugas, dan forum interaktif. Tingkatkan keahlian <i>coding</i> Anda di mana saja dan kapan saja.</p>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; justify-content: center;">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="brutal-hover" style="display: inline-flex; align-items: center; justify-content: center; width: max-content; font-size: 1.2rem; background-color: #FF4C4C; color: #FFF; padding: 1rem 2rem; text-decoration: none; font-weight: 900; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); text-transform: uppercase;">Ke Dashboard &rarr;</a>
                <?php else: ?>
                    <a href="register.php" class="brutal-hover" style="display: inline-flex; align-items: center; justify-content: center; width: max-content; font-size: 1.2rem; background-color: #FF4C4C; color: #FFF; padding: 1rem 2rem; text-decoration: none; font-weight: 900; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); text-transform: uppercase;">Mulai Sekarang &rarr;</a>
                <?php endif; ?>
                <a href="#katalog" class="brutal-hover" style="display: inline-flex; align-items: center; justify-content: center; width: max-content; font-size: 1.2rem; background-color: #FFD700; color: #000; padding: 1rem 2rem; text-decoration: none; font-weight: 900; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); text-transform: uppercase;">Lihat Katalog</a>
            </div>
        </div>
        
        <!-- Right Side Logos -->
        <div class="hero-box box-python">
            <i class='bx bxl-python' style="font-size: 4rem; color: #FFF;"></i>
        </div>
        <div class="hero-box box-php">
            <i class='bx bxl-php' style="font-size: 4rem; color: #FFF;"></i>
        </div>
        <div class="hero-box box-java">
            <i class='bx bxl-java' style="font-size: 4rem; color: #f89820;"></i>
        </div>
        <div class="hero-box box-laravel">
            <!-- Boxicons doesn't have laravel, use a custom SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" style="fill: #FFF;"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
        </div>
    </section>

    <!-- STAT BAR -->
    <div class="stat-bar" style="background-color: #A855F7; border-bottom: 4px solid #000;">
        <div class="stat-item" style="background: #fff; color: #000; padding: 1rem; border: 4px solid #000; box-shadow: 4px 4px 0px #000; transform: rotate(-2deg);">
            <div class="stat-number" style="text-shadow: none; color: #000;"><?= number_format($countMurid) ?>+</div>
            <div>Murid Aktif</div>
        </div>
        <div class="stat-item" style="background: #FFD700; color: #000; padding: 1rem; border: 4px solid #000; box-shadow: 4px 4px 0px #000; transform: rotate(1deg);">
            <div class="stat-number" style="text-shadow: none; color: #000;"><?= number_format($countKelas) ?>+</div>
            <div>Kelas Tersedia</div>
        </div>
        <div class="stat-item" style="background: #10B981; color: #000; padding: 1rem; border: 4px solid #000; box-shadow: 4px 4px 0px #000; transform: rotate(-1deg);">
            <div class="stat-number" style="text-shadow: none; color: #000;"><?= number_format($countPengajar) ?>+</div>
            <div>Instruktur Ahli</div>
        </div>
        <div class="stat-item" style="background: #FF4C4C; color: #FFF; padding: 1rem; border: 4px solid #000; box-shadow: 4px 4px 0px #000; transform: rotate(2deg);">
            <div class="stat-number" style="text-shadow: none; color: #FFF;">4.9/5</div>
            <div>Rating Rata-rata</div>
        </div>
    </div>

    <!-- POPULAR COURSES -->
    <section class="popular-courses" id="katalog" style="padding-top: 8rem; padding-bottom: 8rem; background-color: #FCD34D; background-image: radial-gradient(#000 3px, transparent 3px); background-size: 40px 40px; border-top: 4px solid #000; border-bottom: 4px solid #000;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <h2 class="section-title" style="border: none; margin-bottom: 5rem; display: block; width: 100%; text-align: center;">🔥 <span style="background-color: #FFF; color: #000; padding: 0 15px; display: inline-block; transform: rotate(2deg); border: 4px solid #000; box-shadow: 6px 6px 0px #000;">KELAS POPULER</span></h2>
        <div class="course-scroll-container">
            <?php foreach($kelasList as $index => $kelas): ?>
                <?php 
                    $colors = ['#3776AB', '#F7DF1E', '#777BB4', '#EF4444', '#10B981', '#3B82F6'];
                    $icons = ['bxl-python', 'bxl-javascript', 'bxl-php', 'bx-code-alt', 'bxl-react', 'bxl-java'];
                    $color = $colors[$index % count($colors)];
                    $icon = $icons[$index % count($icons)];
                    $textColor = ($color === '#F7DF1E') ? '#000' : '#FFF';
                ?>
                <div class="neo-card brutal-hover course-card">
                    <div style="background-color: <?= $color ?>; height: 150px; border-bottom: 3px solid var(--border); display: flex; align-items: center; justify-content: center; color: <?= $textColor ?>;">
                        <i class='bx <?= $icon ?>' style="font-size: 6rem;"></i>
                    </div>
                    <div class="neo-card-body" style="flex-grow: 1; text-align: left; display: flex; flex-direction: column;">
                        <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 0.5rem; text-transform: uppercase;"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                        <div style="color: var(--muted-foreground); font-weight: bold; margin-bottom: 1rem;">Oleh: <?= htmlspecialchars($kelas['pengajar'] ?? 'Instruktur') ?></div>
                        <p style="font-size: 0.9rem; margin-bottom: 1.5rem; flex-grow: 1;"><?= htmlspecialchars(mb_strimwidth($kelas['deskripsi'] ?? 'Pelajari lebih lanjut tentang kelas ini.', 0, 80, '...')) ?></p>
                        
                        <?php if ($is_logged_in): ?>
                            <a href="kelas_detail.php?id=<?= $kelas['id_kelas'] ?>" class="neo-btn neo-box" style="display: block; text-align: center; text-decoration: none;">Masuk Kelas</a>
                        <?php else: ?>
                            <a href="register.php" class="neo-btn neo-box" style="display: block; text-align: center; text-decoration: none;">Daftar Sekarang</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 2rem; display: flex; justify-content: center;">
            <?php if ($is_logged_in): ?>
                <a href="enroll.php" style="background-color: var(--card); color: var(--foreground); padding: 0.8rem 1.5rem; font-weight: 900; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); text-transform: uppercase; text-decoration: none; display: inline-block; transition: transform 0.1s, box-shadow 0.1s;" class="brutal-hover">Lihat Selengkapnya &rarr;</a>
            <?php else: ?>
                <a href="register.php" style="background-color: var(--card); color: var(--foreground); padding: 0.8rem 1.5rem; font-weight: 900; border: 4px solid var(--border); box-shadow: 6px 6px 0px var(--border); text-transform: uppercase; text-decoration: none; display: inline-block; transition: transform 0.1s, box-shadow 0.1s;" class="brutal-hover">Lihat Selengkapnya &rarr;</a>
            <?php endif; ?>
        </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features" style="background-color: #EC4899; background-image: radial-gradient(#000 3px, transparent 3px); background-size: 40px 40px; border-top: 4px solid #000; border-bottom: 4px solid #000; padding-top: 8rem; padding-bottom: 8rem;">
        <h2 class="section-title" style="border: none; margin-bottom: 5rem; display: block; width: 100%; text-align: center;">💡 <span style="background-color: #FFF; color: #000; padding: 0 15px; display: inline-block; transform: rotate(-2deg); border: 4px solid #000; box-shadow: 6px 6px 0px #000;">MENGAPA MEMILIH KAMI?</span></h2>
        <div class="feature-grid">
            <div class="neo-card brutal-hover" style="background-color: var(--card);">
                <div style="width: 80px; height: 80px; background-color: var(--primary); border: 4px solid var(--border); box-shadow: 4px 4px 0px var(--border); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--primary-foreground); margin-bottom: 1.5rem; border-radius: 50%;">
                    <i class='bx bx-book-open'></i>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 1rem; text-transform: uppercase;">Materi Terstruktur</h3>
                <p style="font-weight: 500; color: var(--muted-foreground); line-height: 1.5;">Kurikulum disusun oleh para praktisi industri. Belajar dari dasar hingga tingkat lanjut dengan contoh proyek dunia nyata.</p>
            </div>
            
            <div class="neo-card brutal-hover" style="background-color: var(--card);">
                <div style="width: 80px; height: 80px; background-color: #FFD43B; border: 4px solid var(--border); box-shadow: 4px 4px 0px var(--border); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #000; margin-bottom: 1.5rem; border-radius: 50%;">
                    <i class='bx bx-message-square-dots'></i>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 1rem; text-transform: uppercase;">Diskusi Interaktif</h3>
                <p style="font-weight: 500; color: var(--muted-foreground); line-height: 1.5;">Bertanya langsung kepada pengajar melalui forum kelas interaktif dengan notifikasi real-time dan editor teks canggih.</p>
            </div>

            <div class="neo-card brutal-hover" style="background-color: var(--card);">
                <div style="width: 80px; height: 80px; background-color: #EF4444; border: 4px solid var(--border); box-shadow: 4px 4px 0px var(--border); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #FFF; margin-bottom: 1.5rem; border-radius: 50%;">
                    <i class='bx bx-timer'></i>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 1rem; text-transform: uppercase;">Ujian Real-Time</h3>
                <p style="font-weight: 500; color: var(--muted-foreground); line-height: 1.5;">Uji pemahaman Anda dengan kuis pilihan ganda dan tugas harian yang langsung dinilai oleh sistem secara instan.</p>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="faq" id="faq" style="padding: 8rem 2rem; border-bottom: 4px solid #000; background-color: #10B981; background-image: radial-gradient(#000 3px, transparent 3px); background-size: 40px 40px;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <h2 class="section-title" style="text-align: center; margin-bottom: 4rem; border: none; width: 100%;">❓ <span style="background-color: #FFD700; color: #000; padding: 0 15px; display: inline-block; transform: rotate(1deg); border: 4px solid #000; box-shadow: 6px 6px 0px #000;">FAQ & Cara Kerja</span></h2>
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="neo-card brutal-hover" style="background-color: #000; border: 4px solid #000; box-shadow: 12px 12px 0px #000; padding: 0; overflow: hidden; transform: rotate(-0.5deg);">
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" src="https://www.youtube.com/embed/0EdYaYIUOvA" title="Demo E-Learning Programming" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <p style="font-size: 1.2rem; font-weight: 800; background-color: #FFF; padding: 1rem 2rem; border: 3px solid #000; display: inline-block; box-shadow: 4px 4px 0px #000;">
                        Tonton video panduan di atas untuk memahami seluruh fitur dan kemudahan belajar di platform kami! 🚀
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="contact" id="kontak" style="background-color: #06B6D4; background-image: radial-gradient(#000 3px, transparent 3px); background-size: 30px 30px; padding: 10rem 2rem 8rem 2rem; position: relative; overflow: hidden; border-top: 4px solid #000; border-bottom: 4px solid #000;">
        
        <!-- MARQUEE BACKGROUND -->
        <div style="position: absolute; top: 7%; left: -10%; width: 120%; transform: rotate(-3deg); background: #FFD700; color: #000; font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 900; white-space: nowrap; overflow: hidden; padding: 15px 0; border-top: 4px solid #000; border-bottom: 4px solid #000; box-shadow: 0 8px 0px rgba(0,0,0,0.2); z-index: 1;">
            <div style="display: inline-block; animation: marquee 20s linear infinite;">
                HUBUNGI KAMI • FAST RESPONSE • 24/7 SUPPORT • KAMI SIAP MEMBANTU • HUBUNGI KAMI • FAST RESPONSE • 24/7 SUPPORT • KAMI SIAP MEMBANTU • HUBUNGI KAMI • FAST RESPONSE • 24/7 SUPPORT • KAMI SIAP MEMBANTU •
            </div>
        </div>

        <style>
            @keyframes marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            .icon-bounce { transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
            .brutal-hover:hover .icon-bounce { transform: scale(1.4) rotate(15deg); }
        </style>

        <div style="max-width: 1200px; margin: 0 auto; text-align: center; position: relative; z-index: 2; margin-top: 3rem;">
            
            <!-- Title -->
            <h2 class="section-title" style="font-size: 3.8rem; font-weight: 900; margin-bottom: 1rem; color: #000; line-height: 1.1; font-family: 'Space Grotesk', sans-serif; border: none;">
                Butuh <span style="background-color: #FFD700; color: #000; padding: 0 15px; display: inline-block; transform: rotate(-3deg); border: 4px solid #000; box-shadow: 6px 6px 0px #000;">Bantuan?</span>
            </h2>
            <p style="font-size: 1.2rem; font-weight: 800; color: #000; margin-bottom: 4rem; max-width: 700px; margin-left: auto; margin-right: auto; background-color: #fff; padding: 1rem; border: 3px solid #000; box-shadow: 4px 4px 0px #000;">
                Tim dukungan kami siap membantu Anda kapan saja. Pilih metode komunikasi yang paling nyaman untuk menyelesaikan kendala Anda!
            </p>
            
            <!-- Cards Grid -->
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 3rem; text-align: left;">
                
                <!-- Card 1 -->
                <div class="brutal-hover" style="border: 4px solid #000; box-shadow: 10px 10px 0px #000; display: flex; flex-direction: column; background: #fff; border-radius: 0; overflow: hidden; width: 100%; max-width: 350px;">
                    <div style="background-color: #FF4C4C; height: 160px; display: flex; align-items: center; justify-content: center; border-bottom: 4px solid #000;">
                        <i class='bx bx-envelope icon-bounce' style="font-size: 5rem; color: #fff;"></i>
                    </div>
                    <div style="padding: 2rem; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 1.6rem; font-weight: 900; margin-bottom: 0.8rem; color: #000; text-transform: uppercase;">Tanya Instruktur</h3>
                        <p style="font-size: 1.05rem; font-weight: 600; color: #000; margin-bottom: 2rem; flex-grow: 1; line-height: 1.4;">Punya pertanyaan seputar materi pelajaran? Tim mentor kami siap membalas pesan email Anda.</p>
                        <a href="mailto:support@elearning.com" style="display: inline-flex; align-items: center; justify-content: center; width: 100%; font-size: 1.1rem; background-color: #FFD700; color: #000; padding: 1rem; text-decoration: none; font-weight: 900; border: 4px solid #000; box-shadow: 4px 4px 0px #000; text-transform: uppercase; gap: 8px; transition: transform 0.1s, box-shadow 0.1s;" class="brutal-hover">
                            <i class='bx bx-send'></i> Email Kami
                        </a>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="brutal-hover" style="border: 4px solid #000; box-shadow: 10px 10px 0px #000; display: flex; flex-direction: column; background: #fff; border-radius: 0; overflow: hidden; width: 100%; max-width: 350px;">
                    <div style="background-color: #25D366; height: 160px; display: flex; align-items: center; justify-content: center; border-bottom: 4px solid #000;">
                        <i class='bx bxl-whatsapp icon-bounce' style="font-size: 5rem; color: #fff;"></i>
                    </div>
                    <div style="padding: 2rem; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 1.6rem; font-weight: 900; margin-bottom: 0.8rem; color: #000; text-transform: uppercase;">Bantuan Teknis</h3>
                        <p style="font-size: 1.05rem; font-weight: 600; color: #000; margin-bottom: 2rem; flex-grow: 1; line-height: 1.4;">Terkendala saat mendaftar atau mengakses sistem? Chat admin kami untuk bantuan instan.</p>
                        <a href="https://wa.me/628123456789" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; width: 100%; font-size: 1.1rem; background-color: #A855F7; color: #FFF; padding: 1rem; text-decoration: none; font-weight: 900; border: 4px solid #000; box-shadow: 4px 4px 0px #000; text-transform: uppercase; gap: 8px; transition: transform 0.1s, box-shadow 0.1s;" class="brutal-hover">
                            <i class='bx bxl-whatsapp'></i> Chat WhatsApp
                        </a>
                    </div>
                </div>

            </div>
        </div>
        
    </section>

    <!-- FOOTER -->
    <footer class="footer" style="background-color: #212126; color: #fff; text-align: left; padding: 4rem 2rem; border-top: none; font-family: monospace, sans-serif;">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 2rem;" class="footer-grid">
            
            <!-- Column 1: Brand & Contact -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="font-size: 2rem; font-weight: 900; text-transform: uppercase; color: var(--secondary); display: flex; align-items: center; gap: 10px; font-family: 'Space Grotesk', sans-serif;">
                    <i class='bx bx-code-alt' style="color: #10B981;"></i> E-LEARNING
                </div>
                
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 1rem;">
                    <div style="width: 40px; height: 40px; background-color: #3b3b42; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                        <i class='bx bx-envelope' style="font-size: 1.5rem; color: #ccc;"></i>
                    </div>
                    <div style="font-weight: 600; font-size: 0.95rem; font-family: 'Space Grotesk', sans-serif;">support@elearning.com</div>
                </div>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 40px; height: 40px; background-color: #3b3b42; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                        <i class='bx bxl-whatsapp' style="font-size: 1.5rem; color: #ccc;"></i>
                    </div>
                    <div style="display: flex; flex-direction: column; font-family: 'Space Grotesk', sans-serif;">
                        <span style="color: #F59E0B; font-weight: 800; font-size: 1.1rem;">+62 812-3456-7890</span>
                        <span style="font-size: 0.85rem; color: #9ca3af;">Jam operasional 08.30 - 16.30</span>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; font-size: 0.85rem; color: #6b7280; font-family: 'Space Grotesk', sans-serif;">
                    &copy; <?= date('Y') ?> E-LEARNING PROGRAMMING. All Right Reserved
                </div>
            </div>

            <!-- Column 2: Katalog -->
            <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff; font-family: 'Space Grotesk', sans-serif;">Katalog Kelas</h4>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Web Development</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Data Science</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Mobile App</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">UI/UX Design</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Game Dev</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Cyber Security</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Machine Learning</a>
            </div>

            <!-- Column 3: Perusahaan -->
            <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff; font-family: 'Space Grotesk', sans-serif;">Perusahaan</h4>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Tentang Kami</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Karir</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Berita & Blog</a>
                
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-top: 0.5rem; margin-bottom: 0.5rem; color: #fff; font-family: 'Space Grotesk', sans-serif;">Bantuan</h4>
                <a href="#faq" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">FAQ / Cara Kerja</a>
                <a href="#kontak" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Pusat Bantuan</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">Privacy & Policy</a>
            </div>

            <!-- Column 4: Ikuti Kami -->
            <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff; font-family: 'Space Grotesk', sans-serif;">Ikuti Kami</h4>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 24px; height: 24px; background-color: #3b5998; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class='bx bxl-facebook' style="color: #fff; font-size: 0.9rem;"></i>
                    </div>
                    <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">E-Learning Official</a>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 24px; height: 24px; background-color: #e1306c; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class='bx bxl-instagram' style="color: #fff; font-size: 0.9rem;"></i>
                    </div>
                    <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">@elearning_id</a>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 24px; height: 24px; background-color: #1da1f2; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class='bx bxl-twitter' style="color: #fff; font-size: 0.9rem;"></i>
                    </div>
                    <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">@elearning_dev</a>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 24px; height: 24px; background-color: #ff0000; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class='bx bxl-youtube' style="color: #fff; font-size: 0.9rem;"></i>
                    </div>
                    <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 0.95rem;">E-Learning Tech</a>
                </div>
            </div>
        </div>
        
        <style>
            @media (max-width: 992px) {
                .footer-grid {
                    grid-template-columns: 1fr 1fr !important;
                }
            }
            @media (max-width: 576px) {
                .footer-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            .footer a:hover {
                color: var(--primary) !important;
            }
        </style>
    </footer>

<?php include 'includes/cursor.php'; ?>
</body>
</html>
