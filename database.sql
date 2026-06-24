CREATE DATABASE IF NOT EXISTS db_elearning;
USE db_elearning;

-- Tabel User
CREATE TABLE tb_user (
    id_user INT AUTO_INCREMENT PRIMARY KEY, 
    username VARCHAR(50) UNIQUE, 
    password VARCHAR(255), 
    role VARCHAR(20)
);

-- Tabel Pengajar
CREATE TABLE tb_pengajar (
    nidn VARCHAR(20) PRIMARY KEY, 
    nama_pengajar VARCHAR(100), 
    email VARCHAR(100) UNIQUE, 
    id_user INT,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- Tabel Murid
CREATE TABLE tb_murid (
    nim VARCHAR(20) PRIMARY KEY, 
    nama_murid VARCHAR(100), 
    email VARCHAR(100) UNIQUE, 
    id_user INT,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- Tabel Kelas
CREATE TABLE tb_kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY, 
    nama_kelas VARCHAR(100), 
    deskripsi TEXT, 
    nidn VARCHAR(20),
    FOREIGN KEY (nidn) REFERENCES tb_pengajar(nidn) ON DELETE SET NULL
);

-- Tabel Peserta Kelas (Relasi Many-to-Many Murid & Kelas)
CREATE TABLE tb_peserta_kelas (
    id_kelas INT, 
    nim VARCHAR(20), 
    PRIMARY KEY (id_kelas, nim),
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (nim) REFERENCES tb_murid(nim) ON DELETE CASCADE
);

-- Tabel Materi
CREATE TABLE tb_materi (
    id_materi INT AUTO_INCREMENT PRIMARY KEY, 
    id_kelas INT, 
    judul_materi VARCHAR(150), 
    deskripsi TEXT, 
    file_materi VARCHAR(255), 
    tgl_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE CASCADE
);

-- Tabel Tugas
CREATE TABLE tb_tugas (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY, 
    id_kelas INT, 
    judul_tugas VARCHAR(150), 
    deskripsi TEXT, 
    file_tugas VARCHAR(255),
    deadline DATETIME,
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE CASCADE
);

-- Tabel Submission (Jawaban Tugas Murid)
CREATE TABLE tb_submission (
    id_submission INT AUTO_INCREMENT PRIMARY KEY, 
    id_tugas INT, 
    nim VARCHAR(20), 
    file_jawaban VARCHAR(255), 
    tgl_kumpul DATETIME DEFAULT CURRENT_TIMESTAMP, 
    nilai INT DEFAULT NULL, 
    catatan TEXT,
    FOREIGN KEY (id_tugas) REFERENCES tb_tugas(id_tugas) ON DELETE CASCADE,
    FOREIGN KEY (nim) REFERENCES tb_murid(nim) ON DELETE CASCADE
);

-- DUMMY DATA FOR E-LEARNING PROGRAMMING

-- 1. Insert Users (Admin, Teachers, Students)
-- Passwords are plain text as designed in the system.
INSERT IGNORE INTO tb_user (id_user, username, password, role) VALUES
-- Dosen
(13, 'dosen_rina', 'password123', 'Pengajar'),
(14, 'dosen_andi', 'password123', 'Pengajar'),
(15, 'dosen_siti', 'password123', 'Pengajar'),
(16, 'dosen_eko', 'password123', 'Pengajar'),
(17, 'dosen_dewi', 'password123', 'Pengajar'),
(18, 'dosen_fajar', 'password123', 'Pengajar'),
(19, 'dosen_hendra', 'password123', 'Pengajar'),
-- Murid
(20, 'student_adit', 'password123', 'Murid'),
(21, 'student_bagus', 'password123', 'Murid'),
(22, 'student_citra', 'password123', 'Murid'),
(23, 'student_dina', 'password123', 'Murid'),
(24, 'student_fahri', 'password123', 'Murid'),
(25, 'student_gita', 'password123', 'Murid'),
(26, 'student_heri', 'password123', 'Murid'),
(27, 'student_indah', 'password123', 'Murid'),
(28, 'student_joko', 'password123', 'Murid'),
(29, 'student_kania', 'password123', 'Murid');

-- 2. Insert Pengajar
INSERT IGNORE INTO tb_pengajar (nidn, nama_pengajar, email, id_user) VALUES
('0401018801', 'Rina Wijaya, M.T.', 'rina.wijaya@dosen.local', 13),
('0401018902', 'Andi Hidayat, M.Cs.', 'andi.hidayat@dosen.local', 14),
('0401019003', 'Siti Rahma, M.Kom.', 'siti.rahma@dosen.local', 15),
('0401019104', 'Eko Prasetyo, M.T.', 'eko.prasetyo@dosen.local', 16),
('0401019205', 'Dewi Lestari, M.Kom.', 'dewi.lestari@dosen.local', 17),
('0401019306', 'Fajar Nugroho, M.Cs.', 'fajar.nugroho@dosen.local', 18),
('0401019407', 'Hendra Wijaya, Ph.D.', 'hendra.wijaya@dosen.local', 19);

-- 3. Insert Murid
INSERT IGNORE INTO tb_murid (nim, nama_murid, email, id_user) VALUES
('2201010011', 'Adit Pratama', 'adit@student.local', 20),
('2201010012', 'Bagus Saputra', 'bagus@student.local', 21),
('2201010013', 'Citra Lestari', 'citra@student.local', 22),
('2201010014', 'Dina Ananda', 'dina@student.local', 23),
('2201010015', 'Fahri Ramadhan', 'fahri@student.local', 24),
('2201010016', 'Gita Cahyani', 'gita@student.local', 25),
('2201010017', 'Heri Setiawan', 'heri@student.local', 26),
('2201010018', 'Indah Permata', 'indah@student.local', 27),
('2201010019', 'Joko Susilo', 'joko@student.local', 28),
('2201010020', 'Kania Dewi', 'kania@student.local', 29);

-- 4. Insert Kelas
INSERT IGNORE INTO tb_kelas (id_kelas, nama_kelas, deskripsi, nidn) VALUES
(8, 'Pemrograman Python untuk Data Science', 'Mempelajari dasar-dasar pemrograman Python untuk analisis data, manipulasi data menggunakan Pandas, dan visualisasi data menggunakan Matplotlib.', '0401018801'),
(9, 'Pengembangan Aplikasi Mobile Kotlin', 'Kursus praktis membangun aplikasi Android modern menggunakan bahasa pemrograman Kotlin dan framework Jetpack Compose.', '0401018902'),
(10, 'Sistem Basis Data PostgreSQL', 'Mempelajari perancangan database relasional, query SQL tingkat lanjut, indexing, dan optimasi performa PostgreSQL.', '0401019003'),
(11, 'Modern JavaScript & React', 'Menguasai ES6+, konsep asynchronous JS, serta pembuatan antarmuka web interaktif menggunakan library React.js.', '0401019104'),
(12, 'Keamanan Aplikasi Web (Cybersecurity)', 'Membahas celah keamanan aplikasi web populer (OWASP Top 10) seperti SQL Injection, XSS, CSRF, serta cara menanggulanginya.', '0401019205'),
(13, 'Struktur Data dan Algoritma (C++)', 'Membahas konsep pointer, array, linked list, stack, queue, tree, graph, serta algoritma sorting dan searching di C++.', '0401019306'),
(14, 'Pengantar Kecerdasan Buatan (AI)', 'Pengenalan konsep dasar kecerdasan buatan, machine learning, supervised learning, dan implementasi algoritma dasar.', '0401019407'),
(15, 'Pemrograman Berorientasi Objek dengan Java', 'Mempelajari konsep Object-Oriented Programming (OOP) meliputi inheritance, polymorphism, encapsulation, dan abstraction menggunakan Java.', '0401018902'),
(16, 'Desain Pola Arsitektur Perangkat Lunak', 'Mempelajari software design patterns (Singleton, Factory, Observer) serta arsitektur kode bersih (Clean Architecture).', '0401019306'),
(17, 'Pengembangan Web API dengan Node.js', 'Belajar membuat server API RESTful yang aman, cepat, dan scalable menggunakan Express.js dan Node.js.', '0401019104');

-- 5. Insert Materi
INSERT IGNORE INTO tb_materi (id_materi, id_kelas, judul_materi, deskripsi, file_materi) VALUES
(14, 8, 'Pengenalan Sintaks Python & Tipe Data', 'Materi ini membahas sintaks dasar Python, variabel, tipe data dasar (number, string, boolean), serta list, tuple, dan dictionary.', 'pengenalan_python.pdf'),
(15, 8, 'Struktur Kontrol: Percabangan & Perulangan', 'Mempelajari penggunaan if-else, nested if, perulangan for dan while, serta break dan continue pada Python.', 'kontrol_python.pdf'),
(16, 9, 'Pengenalan Jetpack Compose', 'Pengenalan Jetpack Compose sebagai toolkit UI modern dari Google untuk merancang aplikasi Android yang deklaratif.', 'jetpack_compose_dasar.pdf'),
(17, 10, 'Dasar SQL: DDL dan DML', 'Mempelajari cara membuat tabel (Data Definition Language) dan memanipulasi data (Data Manipulation Language) menggunakan SQL.', 'dasar_sql.pdf'),
(18, 11, 'Dasar React: Component, State, & Props', 'Memahami arsitektur berbasis komponen pada React, perbedaan props dan state, serta siklus hidup komponen.', 'react_state_props.pdf'),
(19, 12, 'Konsep OWASP Top 10', 'Mengidentifikasi 10 kerentanan keamanan web paling kritis berdasarkan standar keamanan internasional OWASP.', 'owasp_top_10.pdf'),
(20, 13, 'Konsep Pointer & Alokasi Memori', 'Mempelajari pointer, alamat memori, serta alokasi memori dinamis menggunakan keyword new dan delete di C++.', 'pointer_memori.pdf'),
(21, 14, 'Pengantar Machine Learning & Supervised Learning', 'Mempelajari perbedaan Machine Learning dengan pemrograman tradisional, konsep data training/testing, serta Supervised vs Unsupervised.', 'intro_ml.pdf'),
(22, 15, 'Enkapsulasi, Inheritansi, dan Polimorfisme', 'Memahami tiga pilar utama OOP: membatasi akses data (enkapsulasi), pewarisan kelas (inheritansi), dan banyak bentuk objek (polimorfisme).', 'konsep_oop.pdf'),
(23, 16, 'Pola Desain MVC & Repository Pattern', 'Membahas arsitektur Model-View-Controller (MVC) serta memisahkan logika akses data menggunakan Repository Pattern.', 'pattern_mvc_repo.pdf'),
(24, 17, 'RESTful API Concept & Express.js Router', 'Mempelajari metode HTTP (GET, POST, PUT, DELETE), kode status HTTP, dan pengaturan routing menggunakan Express Router.', 'express_router.pdf');

-- 6. Insert Tugas
INSERT IGNORE INTO tb_tugas (id_tugas, id_kelas, judul_tugas, deskripsi, deadline) VALUES
(3, 8, 'Tugas 1: Membuat Kalkulator Sederhana', 'Buatlah program Python interaktif CLI untuk melakukan operasi matematika dasar (tambah, kurang, kali, bagi) berdasarkan input user.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(4, 9, 'Tugas 1: Membuat Layout Profil Sederhana', 'Buatlah layout profil pengguna menggunakan Jetpack Compose, yang terdiri dari foto profil, nama, deskripsi diri, dan tombol aksi.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(5, 10, 'Tugas 1: Merancang ERD Toko Online', 'Rancanglah Entity Relationship Diagram (ERD) untuk sistem e-commerce sederhana dengan entitas Customer, Order, Product, dan Payment.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(6, 11, 'Tugas 1: Membuat Todo-List App', 'Buatlah aplikasi catatan tugas (Todo-List) sederhana berbasis React yang mendukung tambah data, centang selesai, dan hapus data.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(7, 12, 'Tugas 1: Analisis Kerentanan SQL Injection', 'Tulis laporan analisis mengenai bahaya SQL Injection, bagaimana penyerang mengeksploitasinya, dan langkah mitigasi menggunakan Prepared Statements.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(8, 13, 'Tugas 1: Implementasi Linked List di C++', 'Tuliskan kode program lengkap menggunakan C++ untuk membuat Single Linked List beserta fungsi insertNode, deleteNode, dan printList.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(9, 14, 'Tugas 1: Implementasi K-Nearest Neighbors', 'Gunakan library scikit-learn Python untuk melatih model K-Nearest Neighbors (KNN) menggunakan dataset Iris, lalu ukur akurasinya.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(10, 15, 'Tugas 1: Membuat Simulasi Sistem Bank', 'Buatlah program simulasi perbankan menggunakan prinsip OOP. Harus memiliki class Account dengan enkapsulasi saldo, deposit(), dan withdraw().', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(11, 16, 'Tugas 1: Implementasi Singleton & Factory Pattern', 'Implementasikan pola desain Singleton dan Factory Pattern dalam bahasa pemrograman pilihan Anda (PHP / Java / JS). Sertakan contoh kasus penggunaannya.', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(12, 17, 'Tugas 1: Membuat REST API CRUD Product', 'Buatlah RESTful API sederhana dengan Node.js & Express untuk menyimpan data Product (id, nama, harga, stok). Harus mendukung operasi CRUD lengkap.', DATE_ADD(NOW(), INTERVAL 7 DAY));

-- Tabel Diskusi
CREATE TABLE tb_diskusi (
    id_diskusi INT AUTO_INCREMENT PRIMARY KEY, 
    id_kelas INT, 
    id_user INT, 
    judul_diskusi VARCHAR(150), 
    isi_diskusi TEXT, 
    tgl_post DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- Tabel Balasan Diskusi
CREATE TABLE tb_balasan_diskusi (
    id_balasan INT AUTO_INCREMENT PRIMARY KEY, 
    id_diskusi INT, 
    id_user INT, 
    isi_balasan TEXT, 
    tgl_balasan DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_diskusi) REFERENCES tb_diskusi(id_diskusi) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES tb_user(id_user) ON DELETE CASCADE
);

-- Tabel Kuis
CREATE TABLE tb_kuis (
    id_kuis INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    judul_kuis VARCHAR(150),
    deskripsi TEXT,
    durasi_menit INT,
    tgl_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE CASCADE
);

-- Tabel Soal Kuis
CREATE TABLE tb_soal_kuis (
    id_soal INT AUTO_INCREMENT PRIMARY KEY,
    id_kuis INT,
    pertanyaan TEXT,
    opsi_a VARCHAR(255),
    opsi_b VARCHAR(255),
    opsi_c VARCHAR(255),
    opsi_d VARCHAR(255),
    jawaban_benar ENUM('A', 'B', 'C', 'D'),
    FOREIGN KEY (id_kuis) REFERENCES tb_kuis(id_kuis) ON DELETE CASCADE
);

-- Tabel Nilai Kuis
CREATE TABLE tb_nilai_kuis (
    id_nilai INT AUTO_INCREMENT PRIMARY KEY,
    id_kuis INT,
    nim VARCHAR(20),
    nilai INT,
    tgl_dikerjakan DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kuis) REFERENCES tb_kuis(id_kuis) ON DELETE CASCADE,
    FOREIGN KEY (nim) REFERENCES tb_murid(nim) ON DELETE CASCADE
);
