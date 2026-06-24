# 🚀 E-Learning Programming (Neo-Brutalism Edition)

![Neo-Brutalism Theme](https://img.shields.io/badge/UI%2FUX-Neo--Brutalism-FFD700?style=for-the-badge&logo=css3&logoColor=black)
![PHP](https://img.shields.io/badge/Backend-PHP_8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

Proyek Sistem Informasi E-Learning ini dibangun sebagai representasi tugas akhir (**UAS**) multidisipliner yang mengintegrasikan tiga mata kuliah utama: **Analisis & Perancangan Sistem Informasi (APSI)**, **Multimedia**, dan **Interaksi Manusia Komputer (IMK)**.

Sistem ini menolak keras desain antarmuka *corporate* (seperti Material Design) yang membosankan, dan dengan berani mengadopsi gaya **Neo-Brutalism** yang memberikan pengalaman pengguna (*User Experience*) yang sangat interaktif, agresif secara visual, namun tetap sangat fungsional.

---

## 1. Multimedia (Desain Visual & Estetika)
Fokus pada elemen multimedia meliputi pemilihan tema, hierarki tipografi, komposisi warna, hingga *micro-animations*.

### A. Tema Visual: Neo-Brutalism
Neo-Brutalism adalah gaya desain *web modern* yang menggabungkan elemen retro web tahun 90-an dengan standar modern. Ditandai dengan warna-warna mencolok, garis batas (border) hitam yang sangat tebal, dan ketiadaan gradien atau bayangan buram (*blur*).
* **Background Grid**: Menggunakan pola kotak-kotak (*graph paper*) bergaya *blueprint* teknik, merepresentasikan "ruang kerja" dan "pemrograman" yang terstruktur.
* **Solid Shadows**: Bayangan jatuh (`box-shadow`) tidak diburamkan, melainkan menggunakan warna blok solid `#000000` dengan *offset* tajam (seperti 8px ke kanan dan bawah), memberikan ilusi 3D *pop-up card* yang *cartoony* namun tegas.

### B. Palet Warna (Color Theory)
Sistem ini menggunakan palet kontras super tinggi (*High-Contrast*) untuk menarik perhatian (*Attention Grabbing*):
* **Primary (Merah Terang - #FF4C4C)**: Digunakan untuk aksi destruktif atau tombol utama (Call-to-Action). Menciptakan urgensi dan semangat.
* **Secondary (Kuning E-Learning - #FFD700)**: Warna peringatan yang ramah dan energetik. Digunakan pada elemen sorotan, kursor, dan *header navbar*.
* **Background & Foreground**: Monokromatik ekstrem (Putih terang atau Hitam pekat) untuk memastikan *Readability* (keterbacaan) teks tidak pernah terkompromi.

### C. Tipografi & Teks
* **Typeface**: Menggunakan *font* Google **Space Grotesk** yang berkarakter *tech-savvy*, geometris, namun sangat mudah dibaca.
* **Hierarki Teks**: Elemen teks judul dibuat berukuran raksasa (`font-weight: 900`) dengan penulisan huruf kapital (*UPPERCASE*), sedangkan teks isi tetap menggunakan ukuran standar untuk keseimbangan (*balance*).

### D. Animasi Dinamis
Animasi dirancang tidak untuk memperlambat *user*, melainkan memberikan nyawa pada elemen antarmuka:
* **Glitch / Jitter Effect**: Efek getar visual bergaya *cyberpunk* yang muncul ketika *mouse* mendekati teks hero.
* **Brutal-Hover**: Kartu-kartu kelas atau tombol akan meloncat (`translate: -5px -5px`) dan bayangannya akan memanjang ketika disorot (*hover*), memberikan ilusi fisik bahwa elemen tersebut ditekan/terangkat.
* **Reveal Animation**: Elemen akan muncul perlahan dari bawah ke atas saat *user* melakukan *scrolling* (*Intersection Observer API*), menjaga mata *user* tetap fokus pada konten yang baru masuk layar.

---

## 2. Interaksi Manusia & Komputer (Prinsip UI/UX)
Fokus pada bagaimana sistem dirancang untuk menjembatani kognisi manusia dengan mesin secara efektif (*Gulf of Execution* & *Gulf of Evaluation*).

### A. Custom Global Cursor (Penanda Affordance)
Kursor standar OS diganti dengan elemen kursor kustom (titik kuning melayang) yang sangat reaktif.
* **UX Rationale**: Saat *mouse* diarahkan ke elemen *clickable* (Tautan, Tombol, Label, Area Form), kursor otomatis membesar ukurannya (`scale: 1.5`) dan berubah warna menjadi merah. Ini memecahkan masalah *Hick's Law* dengan memberikan **Affordance** (petunjuk penggunaan) yang instan, sehingga otak *user* tidak perlu menebak mana yang bisa diklik.

### B. Drag-and-Drop Area yang Pemaaf (Forgiving UI)
Alih-alih menggunakan tombol `<input type="file">` yang sempit dan usang, sistem menyediakan kanvas area lepas-tarik (*Drag-and-Drop*) raksasa berdesain '*Dashed Border*'.
* **UX Rationale (Feedback Loop)**: Menerapkan prinsip *Direct Manipulation*. Saat *user* menarik *file* masuk, layar berubah menjadi ungu (*Hover state*). Saat *file* berhasil dijatuhkan, layar seketika berubah hijau terang (*Success state*) beserta kemunculan detail ukuran dan nama *file*. Ini memotong kebingungan (Gulf of Evaluation) agar *user* langsung tahu tindakannya sukses.

### C. Immediate Feedback (Toast Notifications)
Setiap aksi asinkron di sistem (menambah balasan forum, gagal *login*, berhasil menyimpan profil) langsung dikomunikasikan via `NeoToast` (notifikasi mengambang di pojok layar).
* **UX Rationale**: Menerapkan heuristik Nielsen: *Visibility of System Status*. Manusia sangat bergantung pada timbal-balik cepat. *Toast* yang muncul selama 3 detik lalu menghilang sendiri mencegah *user* melakukan *double-submit* karena panik.

### D. WYSIWYG Rich Text Editor (Forgiving Text Input)
Integrasi editor teks kaya (QuillJS) pada pembuatan materi dan balasan forum diskusi.
* **UX Rationale**: Menghilangkan hambatan teknis (*technical barrier*). Pengguna (khususnya Pengajar non-IT) tidak perlu menghafal tag HTML atau *markdown* untuk menebalkan teks atau membuat *list*. Apa yang mereka lihat di layar editor adalah apa yang akan mereka dapatkan (*What You See Is What You Get*).

### E. Native Dark Mode (Aksesibilitas)
* **UX Rationale**: Mencegah kelelahan mata (*Eye Strain*). Mode gelap diatur menggunakan *LocalStorage* agar prevensi tetap tersimpan saat navigasi halaman. Skrip pengecekan diletakkan secara terisolasi sebelum *render body* demi mencegah *Flash of Unstyled Content (FOUC)*—di mana layar berkedip putih sebelum berubah gelap—yang sangat mengganggu UX.

---

## 3. Analisis & Perancangan Sistem Informasi (APSI)
Ringkasan arsitektur relasional pada *back-end*:

* **RBAC (Role-Based Access Control)**: Memisahkan secara ketat *routing* dan hak istimewa *database* antara 4 aktor (Admin, Pimpinan, Pengajar, Murid).
* **Entity Relationship**: Arsitektur pangkalan data mendukung modul pendaftaran kelas, pengumpulan tugas tersentralisasi, penilaian otomatis pada Kuis Terstruktur, hingga perekaman jejak (*logs*) *real-time* via Sistem Notifikasi asinkron.
* **Keamanan Kriptografi & Sanitasi**: Form diproteksi dengan CSRF Token, enkripsi otentikasi menggunakan algoritma *password_hash()*, pencegahan *SQL Injection* via *PDO Prepared Statements*, dan *rate-limiting* *bruteforce* pada skrip *Login*.

---

### Instalasi Repositori
```bash
git clone https://github.com/USERNAME/SI_ELearning_UAS.git
# Buat database db_elearning dan import database.sql
# Setting akun koneksi di includes/koneksi.php
```
