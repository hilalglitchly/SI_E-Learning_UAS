# E-Learning Programming (SI_ELearning)

![Neo-Brutalism Theme](https://img.shields.io/badge/UI%2FUX-Neo--Brutalism-FFD700?style=for-the-badge&logo=css3&logoColor=black)
![PHP](https://img.shields.io/badge/Backend-PHP_8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

Proyek ini dibangun sebagai tugas akhir (**UAS**) yang mengintegrasikan pemenuhan untuk tiga mata kuliah utama:

1. **Analisis dan Perancangan Sistem Informasi (APSI)**
2. **Multimedia**
3. **Interaksi Manusia dan Komputer (IMK)**

---

## 1. Analisis dan Perancangan Sistem Informasi (APSI)

Sistem Informasi E-Learning Programming dirancang menggunakan arsitektur relasional yang kokoh untuk mendukung 4 (empat) entitas/role utama: **Murid**, **Pengajar**, **Admin**, dan **Pimpinan**.

### Fitur Utama Sistem:
- **RBAC (Role-Based Access Control)**: Modul *login* dan *dashboard* dirender secara dinamis menyesuaikan hak akses masing-masing *role*.
  - *Admin*: Mengelola *user* (CRUD Pengajar & Murid), *monitoring* data global.
  - *Pengajar*: Membuat kelas, mengunggah materi, memberikan tugas, dan mengelola kuis.
  - *Murid*: Mendaftar kelas (Katalog Kelas), mengakses materi, mengumpulkan tugas secara digital, dan melihat skor progres secara *real-time*.
  - *Pimpinan*: Memantau laporan akademik dan statistik platform secara agregat.
- **Relasi Database Lanjutan**: Relasi *Many-to-Many* (Murid ↔ Kelas melalui `tb_peserta_kelas`), sistem pengumpulan tugas terikat (`tb_tugas` → `tb_kumpul_tugas`), dan sistem kuis.
- **AJAX Polling & API**: Notifikasi sistem diimplementasikan secara *real-time* via *AJAX polling*, memisahkan logika *Backend* (`api/notifikasi.php`) dari *Frontend*.
- **Security Check**: Memiliki proteksi dari *SQL Injection* menggunakan `PDO Prepared Statements`, *password hashing* (`password_hash`), serta keamanan *anti-bruteforce login*.

---

## 2. Multimedia (Desain UI Menarik)

Sistem ini membuang gaya *corporate/material design* yang kaku dan mengadopsi gaya visual **Neo-Brutalism** yang saat ini sedang menjadi tren global.

### Keputusan Estetika:
- **Palet Warna Super Kontras**: Kombinasi warna dominan *Primary* (Merah terang, #FF4C4C), *Secondary* (Kuning E-Learning, #FFD700), dipadukan dengan aksen *Cyan/Magenta*.
- **Garis Tegas & Bayangan Tajam**: Menghindari bayangan *blur* (gaya lama), aplikasi ini menggunakan `box-shadow` solid tebal yang memberikan efek kedalaman (*depth*) layaknya desain 3D kartun (*Pop-Art*).
- **Dark Mode Native**: Terintegrasi langsung dengan mode gelap yang diatur melalui *LocalStorage*. Warna secara otomatis diinversi dengan mulus tanpa mengubah esensi tata letak brutalismenya.

---

## 3. Interaksi Manusia dan Komputer (IMK)

Pengembangan sistem tidak hanya berfokus pada fungsionalitas, namun ditekankan secara mendalam pada bagaimana manusia berinteraksi dengan antarmuka (UX) menggunakan hukum IMK (*HCI Principles*).

### Implementasi IMK pada Sistem:
- **Custom Cursor & Micro-interactions**: Kursor bawaan OS (*default*) digantikan oleh titik interaktif kuning yang secara otomatis membesar dan berubah warna menjadi merah ketika melakukan *hovering* di atas elemen yang dapat di-klik (*buttons*, *links*, *cards*).
- **Immediate Feedback**: 
  - Penggunaan *toast notifications* kustom berwarna cerah di pojok kanan bawah yang memberikan respon instan setiap *user* selesai melakukan tindakan (menyimpan profil, *upload* tugas, mengubah *password*).
  - Terdapat efek '*Glitch/Jitter*' saat tombol kritis disorot untuk memancing intuisi klik *user*.
- **Drag-and-Drop Area**: Sistem konvensional "Pilih File" dipercanggih dengan area unggah *drag-and-drop* raksasa yang berubah warna ketika sebuah *file* (materi atau tugas) ditarik ke atasnya, memberikan *affordance* yang jelas kepada pengguna.
- **Seamless Asynchronous UX**: Fitur hapus notifikasi dapat dieksekusi tanpa *refresh* layar. Tombol **X** pada notifikasi langsung memicu API, menghapus *node* DOM, dan menyesuaikan perhitungan lencana merah di *navbar* secara matematis di *background*.

---

## 💻 Panduan Instalasi (Localhost)

1. Tarik / *Clone* repositori ini ke folder `htdocs` (XAMPP) atau `www` (Laragon) Anda.
   ```bash
   git clone https://github.com/USERNAME/SI_ELearning.git
   ```
2. Buat *database* di MySQL/MariaDB dengan nama **db_elearning**.
3. Import berkas `database.sql` yang disertakan di repositori ini ke dalam *database* yang baru dibuat.
4. Sesuaikan konfigurasi *username* dan *password database* di dalam `includes/koneksi.php` (Secara *default* menggunakan *root* dan *password* kosong).
5. Akses aplikasi melalui *browser* di `http://localhost/SI_ELearning/`.

> Catatan: Gunakan *username* **admin** dan *password* **12345** untuk masuk sebagai Administrator.

---
*Proyek ini dirancang secara eksklusif untuk evaluasi akademik Ujian Akhir Semester.*
