-- ============================================
-- MIGRASI DATABASE: FORUM DISKUSI E-LEARNING
-- Jalankan file ini di database db_elearning
-- ============================================

USE db_elearning;

-- ============================================
-- TABEL NOTIFIKASI (belum ada di database.sql)
-- ============================================
CREATE TABLE IF NOT EXISTS tb_notifikasi (
    id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    judul VARCHAR(150),
    pesan TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    tgl_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- ============================================
-- 1. TABEL TAG FORUM
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_tag (
    id_tag INT AUTO_INCREMENT PRIMARY KEY,
    nama_tag VARCHAR(50) NOT NULL UNIQUE,
    warna VARCHAR(7) DEFAULT '#FFD700',
    icon VARCHAR(50) DEFAULT 'bx-code-alt'
);

-- ============================================
-- 2. TABEL POSTINGAN FORUM
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_post (
    id_post INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    tipe ENUM('pertanyaan','diskusi','berbagi') DEFAULT 'pertanyaan',
    status ENUM('open','solved') DEFAULT 'open',
    views INT DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    is_announcement TINYINT(1) DEFAULT 0,
    tgl_post DATETIME DEFAULT CURRENT_TIMESTAMP,
    tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- ============================================
-- 3. TABEL JAWABAN FORUM
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_jawaban (
    id_jawaban INT AUTO_INCREMENT PRIMARY KEY,
    id_post INT NOT NULL,
    id_user INT NOT NULL,
    konten TEXT NOT NULL,
    is_solusi TINYINT(1) DEFAULT 0,
    tgl_jawaban DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_post) REFERENCES tb_forum_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- ============================================
-- 4. TABEL VOTE (Upvote/Downvote)
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_vote (
    id_vote INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_post INT DEFAULT NULL,
    id_jawaban INT DEFAULT NULL,
    tipe_vote ENUM('up','down') NOT NULL,
    tgl_vote DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_post) REFERENCES tb_forum_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_jawaban) REFERENCES tb_forum_jawaban(id_jawaban) ON DELETE CASCADE,
    UNIQUE KEY unique_vote_post (id_user, id_post),
    UNIQUE KEY unique_vote_jawaban (id_user, id_jawaban)
);

-- ============================================
-- 5. TABEL RELASI POST <-> TAG (Many-to-Many)
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_post_tag (
    id_post INT NOT NULL,
    id_tag INT NOT NULL,
    PRIMARY KEY (id_post, id_tag),
    FOREIGN KEY (id_post) REFERENCES tb_forum_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_tag) REFERENCES tb_forum_tag(id_tag) ON DELETE CASCADE
);

-- ============================================
-- 6. TABEL SIMPAN/BOOKMARK
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_simpan (
    id_user INT NOT NULL,
    id_post INT NOT NULL,
    tgl_simpan DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, id_post),
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_post) REFERENCES tb_forum_post(id_post) ON DELETE CASCADE
);

-- ============================================
-- 7. TABEL MEDIA LAMPIRAN
-- ============================================
CREATE TABLE IF NOT EXISTS tb_forum_media (
    id_media INT AUTO_INCREMENT PRIMARY KEY,
    id_post INT DEFAULT NULL,
    id_jawaban INT DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    tipe_file VARCHAR(50) DEFAULT 'image',
    tgl_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_post) REFERENCES tb_forum_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_jawaban) REFERENCES tb_forum_jawaban(id_jawaban) ON DELETE CASCADE
);


-- ============================================
-- DUMMY DATA
-- ============================================

-- Tag Forum (Topik Populer)
INSERT IGNORE INTO tb_forum_tag (id_tag, nama_tag, warna, icon) VALUES
(1, 'JavaScript', '#F7DF1E', 'bxl-javascript'),
(2, 'Python', '#3776AB', 'bxl-python'),
(3, 'PHP', '#777BB4', 'bxl-php'),
(4, 'React', '#61DAFB', 'bxl-react'),
(5, 'Node.js', '#339933', 'bxl-nodejs'),
(6, 'Database', '#4479A1', 'bx-data'),
(7, 'SQL', '#CC2927', 'bx-table'),
(8, 'Java', '#ED8B00', 'bxl-java'),
(9, 'C++', '#00599C', 'bx-code-curly'),
(10, 'HTML & CSS', '#E34F26', 'bxl-html5'),
(11, 'Git', '#F05032', 'bxl-git'),
(12, 'Android', '#3DDC84', 'bxl-android'),
(13, 'Kotlin', '#7F52FF', 'bx-code-block'),
(14, 'Frontend', '#FF6B6B', 'bx-layout'),
(15, 'Backend', '#4ECDC4', 'bx-server'),
(16, 'DevOps', '#FF9F43', 'bx-cloud'),
(17, 'Algoritma', '#A855F7', 'bx-brain'),
(18, 'Tips & Trik', '#10B981', 'bx-bulb'),
(19, 'Karir', '#EC4899', 'bx-briefcase'),
(20, 'Portofolio', '#F59E0B', 'bx-folder-open');

-- Postingan Forum (Dummy)
INSERT IGNORE INTO tb_forum_post (id_post, id_user, judul, konten, tipe, status, views, is_pinned, is_announcement, tgl_post) VALUES
(1, 15, 'Tips & Solusi Praktis: Mengoptimalkan Query PostgreSQL yang Lambat pada Relasi 10+ Juta Data',
'<p>Banyak mahasiswa kelas backend yang mengeluhkan waktu eksekusi <strong>Seq Scan</strong> lambat saat joins multi-tabel. Gunakan komposit index B-Tree dan perluas buffer cache dengan query di bawah ini:</p>
<pre><code class="language-sql">EXPLAIN (ANALYZE, BUFFERS)
SELECT u.id, u.username, COUNT(o.id) AS total_order
FROM users u
JOIN orders o ON u.user_id = o.user_id
WHERE o.created_at >= ''2024-01-01''
GROUP BY u.id, u.username;</code></pre>
<p>Pastikan kolom <code>created_at</code> dan <code>user_id</code> sudah terindeks. Untuk tabel 10M+ row, pertimbangkan partitioning by range.</p>',
'berbagi', 'solved', 428, 1, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

(2, 16, 'Kenapa `useEffect` di React 18 trigger fetch 2 kali saat dev mode? Solusi clean-up AbortController yang tepat?',
'<p>Halo lagi-lagi, pas migrasi ke Vite + React 18 StrictMode, API call di <code>useEffect([])</code> selalu dipanggil ganda. Apakah best practice nya membatalkan request via controller?</p>
<pre><code class="language-javascript">// Masalah: Dua network request terkirim sekaligus di console
useEffect(() => {
  fetchData();
  return () => abort();
}, []);</code></pre>
<p>Sudah coba pakai AbortController tapi masih ada race condition di response. Ada yang punya solusi clean?</p>',
'pertanyaan', 'solved', 152, 0, 0, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),

(3, 20, 'Review Portofolio Web Developer 2026: Apakah HRD Masih Melirik Project Clone E-Commerce & To-Do App?',
'<p>Setelah wawancara dengan beberapa tech lead startup di Jakarta, hampir 80% reviewer sudah jenuh dengan proyek tutorial YouTube. Apa ide proyek unik yang membuktikan pemahaman state, caching, dan websocket di skala real-world?</p>
<p>Menurut saya, portofolio yang bagus harus menunjukkan:</p>
<ul>
<li>Kemampuan handling real-time data</li>
<li>Authentication & authorization yang proper</li>
<li>Database design yang scalable</li>
<li>CI/CD pipeline (minimal GitHub Actions)</li>
</ul>
<p>Ada saran project yang worth it untuk dimasukkan portofolio?</p>',
'diskusi', 'open', 310, 0, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),

(4, 21, 'Cara implementasi Dark Mode yang benar di Vanilla CSS tanpa FOUC (Flash of Unstyled Content)?',
'<p>Saya sedang buat website portfolio dan ingin menambahkan dark mode. Masalahnya, setiap kali refresh halaman, ada <strong>flash putih</strong> sebelum dark mode diterapkan.</p>
<pre><code class="language-javascript">// Saya taruh di head
if (localStorage.getItem("theme") === "dark") {
    document.documentElement.classList.add("dark-mode");
}</code></pre>
<p>Apakah ini sudah benar? Atau ada cara yang lebih baik? Terima kasih!</p>',
'pertanyaan', 'open', 89, 0, 0, DATE_SUB(NOW(), INTERVAL 5 HOUR)),

(5, 22, 'Belajar Git untuk Pemula: Perintah yang WAJIB Dikuasai Sebelum Kerja Tim',
'<p>Sebagai mahasiswa semester 3, saya baru mulai belajar Git dan merasa overwhelmed. Berikut rangkuman perintah Git yang menurut saya paling penting:</p>
<pre><code class="language-bash">git init
git clone [url]
git add .
git commit -m "pesan"
git push origin main
git pull origin main
git branch [nama-branch]
git checkout [nama-branch]
git merge [nama-branch]
git log --oneline</code></pre>
<p>Ada yang mau menambahkan perintah penting lainnya?</p>',
'berbagi', 'open', 245, 0, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),

(6, 14, '[PENGUMUMAN] Hackathon Mingguan #12 Telah Dibuka: Bikin Sorting Visualizer JS & Canvas!',
'<p>Tantangan terbuka untuk seluruh tingkatan skill! Tanpa library tambahan. Buat kontrol slider kecepatan animasi dan tombol pause.</p>
<p><strong>Deadline: 3 hari</strong></p>
<p>Source code terbaik akan direview live di YouTube DevForum.</p>
<p>Ketentuan:</p>
<ul>
<li>Gunakan Vanilla JS + Canvas API</li>
<li>Minimal 3 algoritma sorting (Bubble, Merge, Quick)</li>
<li>Ada kontrol speed dan pause/resume</li>
<li>Responsive design</li>
</ul>',
'berbagi', 'open', 89, 1, 1, DATE_SUB(NOW(), INTERVAL 6 HOUR)),

(7, 23, 'Kenapa PHP masih relevan di 2026? Ini alasan kenapa saya tetap belajar PHP',
'<p>Banyak yang bilang PHP sudah mati, tapi faktanya:</p>
<ul>
<li>WordPress menguasai 40%+ web di dunia</li>
<li>Laravel terus berkembang dan sangat produktif</li>
<li>PHP 8.3 sudah sangat modern (fibers, enums, named arguments)</li>
<li>Job market masih besar, terutama di Indonesia</li>
</ul>
<p>PHP bukan bahasa yang paling sexy, tapi paling pragmatis untuk web development. Change my mind! 🔥</p>',
'diskusi', 'open', 178, 0, 0, DATE_SUB(NOW(), INTERVAL 8 HOUR)),

(8, 24, 'Error: "Cannot read properties of undefined" di JavaScript - Bagaimana cara debug yang efektif?',
'<p>Saya selalu dapat error ini dan bingung cara debugnya:</p>
<pre><code class="language-javascript">TypeError: Cannot read properties of undefined (reading ''map'')
    at UserList (UserList.jsx:15)
    at renderWithHooks (react-dom.development.js:14985)</code></pre>
<p>Data dari API kadang kosong dan langsung crash. Bagaimana best practice handle null/undefined di JavaScript modern?</p>',
'pertanyaan', 'open', 134, 0, 0, DATE_SUB(NOW(), INTERVAL 12 HOUR));

-- Relasi Post-Tag
INSERT IGNORE INTO tb_forum_post_tag (id_post, id_tag) VALUES
(1, 6), (1, 7), (1, 15),
(2, 4), (2, 1), (2, 14),
(3, 19), (3, 20), (3, 18),
(4, 10), (4, 1), (4, 14),
(5, 11), (5, 18),
(6, 1), (6, 17),
(7, 3), (7, 15), (7, 18),
(8, 1), (8, 4), (8, 14);

-- Jawaban Forum (Dummy)
INSERT IGNORE INTO tb_forum_jawaban (id_jawaban, id_post, id_user, konten, is_solusi, tgl_jawaban) VALUES
(1, 1, 16, '<p>Mantap penjelasannya! Tambahan: gunakan <code>pg_stat_statements</code> untuk monitoring query yang paling sering dipanggil. Ini sangat membantu untuk identifikasi bottleneck.</p>', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 1, 20, '<p>Terima kasih banyak! Setelah menambahkan composite index, query saya turun dari 12 detik ke 0.3 detik. Luar biasa!</p>', 0, DATE_SUB(NOW(), INTERVAL 45 MINUTE)),

(3, 2, 15, '<p>Di React 18 StrictMode memang sengaja double-invoke useEffect di development. Solusi terbaik:</p>
<pre><code class="language-javascript">useEffect(() => {
  const controller = new AbortController();
  
  fetch("/api/data", { signal: controller.signal })
    .then(res => res.json())
    .then(setData)
    .catch(err => {
      if (err.name !== "AbortError") console.error(err);
    });
    
  return () => controller.abort();
}, []);</code></pre>
<p>Ini adalah pattern resmi yang direkomendasikan oleh tim React.</p>', 1, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),

(4, 4, 25, '<p>Cara kamu sudah benar! Script di &lt;head&gt; sebelum body render adalah solusi paling efektif. Tambahkan juga ini untuk mencegah transition saat load awal:</p>
<pre><code class="language-css">.no-transition * {
  transition: none !important;
}</code></pre>', 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

(5, 5, 26, '<p>Jangan lupa <code>git stash</code> untuk menyimpan perubahan sementara, dan <code>git rebase</code> untuk merapikan history commit. Sangat berguna saat kerja tim!</p>', 0, DATE_SUB(NOW(), INTERVAL 20 HOUR)),

(6, 8, 14, '<p>Gunakan optional chaining dan nullish coalescing:</p>
<pre><code class="language-javascript">const items = data?.results ?? [];
return items.map(item => &lt;div&gt;{item.name}&lt;/div&gt;);</code></pre>
<p>Ini cara paling clean di JavaScript modern (ES2020+).</p>', 0, DATE_SUB(NOW(), INTERVAL 10 HOUR));

-- Vote Dummy
INSERT IGNORE INTO tb_forum_vote (id_user, id_post, id_jawaban, tipe_vote) VALUES
(20, 1, NULL, 'up'), (21, 1, NULL, 'up'), (22, 1, NULL, 'up'), (23, 1, NULL, 'up'), (24, 1, NULL, 'up'),
(13, 2, NULL, 'up'), (20, 2, NULL, 'up'), (25, 2, NULL, 'up'),
(14, 3, NULL, 'up'), (15, 3, NULL, 'up'), (16, 3, NULL, 'up'), (20, 3, NULL, 'up'),
(20, 5, NULL, 'up'), (21, 5, NULL, 'up'), (22, 5, NULL, 'up'),
(20, 6, NULL, 'up'), (21, 6, NULL, 'up'),
(20, 7, NULL, 'up'), (21, 7, NULL, 'up'), (15, 7, NULL, 'up'),
(20, 8, NULL, 'up'), (21, 8, NULL, 'up');

-- Bookmark Dummy
INSERT IGNORE INTO tb_forum_simpan (id_user, id_post) VALUES
(20, 1), (21, 1), (22, 2), (23, 5), (20, 6);
