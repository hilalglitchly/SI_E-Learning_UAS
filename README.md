# 🚀 E-Learning Programming (Neo-Brutalism Edition)

![Neo-Brutalism Theme](https://img.shields.io/badge/UI%2FUX-Neo--Brutalism-FFD700?style=for-the-badge&logo=css3&logoColor=black)
![PHP](https://img.shields.io/badge/Backend-PHP_8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

Proyek Sistem Informasi E-Learning ini dibangun sebagai representasi tugas akhir (**UAS**) multidisipliner yang mengintegrasikan tiga mata kuliah utama: **Analisis & Perancangan Sistem Informasi (APSI)**, **Multimedia**, dan **Interaksi Manusia Komputer (IMK)**.

Sistem ini menolak keras desain antarmuka _corporate_ (seperti Material Design) yang membosankan, dan dengan berani mengadopsi gaya **Neo-Brutalism** yang memberikan pengalaman pengguna (_User Experience_) yang sangat interaktif, agresif secara visual, namun tetap sangat fungsional.

---

## 1. Multimedia (Desain Visual & Estetika)

Fokus pada elemen multimedia meliputi pemilihan tema, hierarki tipografi, komposisi warna, hingga _micro-animations_.

### A. Tema Visual: Neo-Brutalism

Neo-Brutalism adalah gaya desain _web modern_ yang menggabungkan elemen retro web tahun 90-an dengan standar modern. Ditandai dengan warna-warna mencolok, garis batas (border) hitam yang sangat tebal, dan ketiadaan gradien atau bayangan buram (_blur_).

- **Background Grid**: Menggunakan pola kotak-kotak (_graph paper_) bergaya _blueprint_ teknik, merepresentasikan "ruang kerja" dan "pemrograman" yang terstruktur.
- **Solid Shadows**: Bayangan jatuh (`box-shadow`) tidak diburamkan, melainkan menggunakan warna blok solid `#000000` dengan _offset_ tajam (seperti 8px ke kanan dan bawah), memberikan ilusi 3D _pop-up card_ yang _cartoony_ namun tegas.

### B. Palet Warna (Color Theory)

Sistem ini menggunakan palet kontras super tinggi (_High-Contrast_) untuk menarik perhatian (_Attention Grabbing_):

- **Primary (Merah Terang - #FF4C4C)**: Digunakan untuk aksi destruktif atau tombol utama (Call-to-Action). Menciptakan urgensi dan semangat.
- **Secondary (Kuning E-Learning - #FFD700)**: Warna peringatan yang ramah dan energetik. Digunakan pada elemen sorotan, kursor, dan _header navbar_.
- **Background & Foreground**: Monokromatik ekstrem (Putih terang atau Hitam pekat) untuk memastikan _Readability_ (keterbacaan) teks tidak pernah terkompromi.

### C. Tipografi & Teks

- **Typeface**: Menggunakan _font_ Google **Space Grotesk** yang berkarakter _tech-savvy_, geometris, namun sangat mudah dibaca.
- **Hierarki Teks**: Elemen teks judul dibuat berukuran raksasa (`font-weight: 900`) dengan penulisan huruf kapital (_UPPERCASE_), sedangkan teks isi tetap menggunakan ukuran standar untuk keseimbangan (_balance_).

### D. Animasi Dinamis

Animasi dirancang tidak untuk memperlambat _user_, melainkan memberikan nyawa pada elemen antarmuka:

- **Glitch / Jitter Effect**: Efek getar visual bergaya _cyberpunk_ yang muncul ketika _mouse_ mendekati teks hero.
- **Brutal-Hover**: Kartu-kartu kelas atau tombol akan meloncat (`translate: -5px -5px`) dan bayangannya akan memanjang ketika disorot (_hover_), memberikan ilusi fisik bahwa elemen tersebut ditekan/terangkat.
- **Reveal Animation**: Elemen akan muncul perlahan dari bawah ke atas saat _user_ melakukan _scrolling_ (_Intersection Observer API_), menjaga mata _user_ tetap fokus pada konten yang baru masuk layar.

---

## 2. Interaksi Manusia & Komputer (Prinsip UI/UX)

Fokus pada bagaimana sistem dirancang untuk menjembatani kognisi manusia dengan mesin secara efektif (_Gulf of Execution_ & _Gulf of Evaluation_).

### A. Custom Global Cursor (Penanda Affordance)

Kursor standar OS diganti dengan elemen kursor kustom (titik kuning melayang) yang sangat reaktif.

- **UX Rationale**: Saat _mouse_ diarahkan ke elemen _clickable_ (Tautan, Tombol, Label, Area Form), kursor otomatis membesar ukurannya (`scale: 1.5`) dan berubah warna menjadi merah. Ini memecahkan masalah _Hick's Law_ dengan memberikan **Affordance** (petunjuk penggunaan) yang instan, sehingga otak _user_ tidak perlu menebak mana yang bisa diklik.

### B. Drag-and-Drop Area yang Pemaaf (Forgiving UI)

Alih-alih menggunakan tombol `<input type="file">` yang sempit dan usang, sistem menyediakan kanvas area lepas-tarik (_Drag-and-Drop_) raksasa berdesain '_Dashed Border_'.

- **UX Rationale (Feedback Loop)**: Menerapkan prinsip _Direct Manipulation_. Saat _user_ menarik _file_ masuk, layar berubah menjadi ungu (_Hover state_). Saat _file_ berhasil dijatuhkan, layar seketika berubah hijau terang (_Success state_) beserta kemunculan detail ukuran dan nama _file_. Ini memotong kebingungan (Gulf of Evaluation) agar _user_ langsung tahu tindakannya sukses.

### C. Immediate Feedback (Toast Notifications)

Setiap aksi asinkron di sistem (menambah balasan forum, gagal _login_, berhasil menyimpan profil) langsung dikomunikasikan via `NeoToast` (notifikasi mengambang di pojok layar).

- **UX Rationale**: Menerapkan heuristik Nielsen: _Visibility of System Status_. Manusia sangat bergantung pada timbal-balik cepat. _Toast_ yang muncul selama 3 detik lalu menghilang sendiri mencegah _user_ melakukan _double-submit_ karena panik.

### D. WYSIWYG Rich Text Editor (Forgiving Text Input)

Integrasi editor teks kaya (QuillJS) pada pembuatan materi dan balasan forum diskusi.

- **UX Rationale**: Menghilangkan hambatan teknis (_technical barrier_). Pengguna (khususnya Pengajar non-IT) tidak perlu menghafal tag HTML atau _markdown_ untuk menebalkan teks atau membuat _list_. Apa yang mereka lihat di layar editor adalah apa yang akan mereka dapatkan (_What You See Is What You Get_).

### E. Native Dark Mode (Aksesibilitas)

- **UX Rationale**: Mencegah kelelahan mata (_Eye Strain_). Mode gelap diatur menggunakan _LocalStorage_ agar prevensi tetap tersimpan saat navigasi halaman. Skrip pengecekan diletakkan secara terisolasi sebelum _render body_ demi mencegah _Flash of Unstyled Content (FOUC)_—di mana layar berkedip putih sebelum berubah gelap—yang sangat mengganggu UX.

---

## 3. Analisis & Perancangan Sistem Informasi (APSI)

Ringkasan arsitektur relasional pada _back-end_:

- **RBAC (Role-Based Access Control)**: Memisahkan secara ketat _routing_ dan hak istimewa _database_ antara 4 aktor (Admin, Pimpinan, Pengajar, Murid).
- **Entity Relationship**: Arsitektur pangkalan data mendukung modul pendaftaran kelas, pengumpulan tugas tersentralisasi, penilaian otomatis pada Kuis Terstruktur, hingga perekaman jejak (_logs_) _real-time_ via Sistem Notifikasi asinkron.
- **Keamanan Kriptografi & Sanitasi**: Form diproteksi dengan CSRF Token, enkripsi otentikasi menggunakan algoritma _password_hash()_, pencegahan _SQL Injection_ via _PDO Prepared Statements_, dan _rate-limiting_ _bruteforce_ pada skrip _Login_.

---

## 💻 Panduan Instalasi (Localhost)

1. Tarik / _Clone_ repositori ini ke folder `htdocs` (XAMPP) atau `www` (Laragon) Anda.
   ```bash
   git clone https://github.com/hilalglitchly/SI_E-Learning_UAS.git
   ```
2. Buat _database_ di MySQL/MariaDB dengan nama **db_elearning**.
3. Import berkas `database.sql` yang disertakan di repositori ini ke dalam _database_ yang baru dibuat.
4. Sesuaikan konfigurasi _username_ dan _password database_ di dalam `includes/koneksi.php` (Secara _default_ menggunakan _root_ dan _password_ kosong).
5. Akses aplikasi melalui _browser_ di `http://localhost/SI_ELearning_UAS/`.

> Catatan: Gunakan _username_ **admin** dan _password_ **12345** untuk masuk sebagai Administrator.

---

_Proyek ini dirancang secara eksklusif untuk evaluasi akademik Ujian Akhir Semester._
