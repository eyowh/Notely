# Website Notely – Aplikasi Catatan & Tugas  
**Intan Tri Yulianti – Teknik Informatika – Universitas Pamulang**

Website **Notely** adalah aplikasi berbasis web yang digunakan untuk **mengelola catatan, tugas, dan jadwal aktivitas secara digital**. Sistem ini dirancang untuk membantu pengguna dalam mencatat informasi penting, mengatur daftar tugas, serta meningkatkan keteraturan dan produktivitas sehari-hari.

Aplikasi ini dikembangkan menggunakan **PHP Native dan MySQL** dengan tampilan antarmuka sederhana, terstruktur, dan mudah digunakan sebagai proyek pembelajaran pemrograman web.

---

## Tujuan Pengembangan

Proyek **Notely** dikembangkan untuk:
- Mempermudah pencatatan aktivitas dan informasi secara digital  
- Mengurangi penggunaan catatan manual  
- Membantu pengelolaan tugas dan jadwal harian  
- Menerapkan konsep CRUD, autentikasi, dan pengelolaan database  
- Sebagai media pembelajaran pemrograman web berbasis PHP Native  

---

## Fitur Sistem Website Notely

### Fitur Pengguna

<!-- Screenshot Halaman Registrasi -->
<!-- ![Registrasi](path_gambar_disini) -->

<!-- Screenshot Halaman Login -->
<!-- ![Login](path_gambar_disini) -->

<!-- Screenshot Dashboard -->
<!-- ![Dashboard](path_gambar_disini) -->

- Registrasi akun pengguna  
- Login dan logout sistem  
- Menambahkan catatan (notes)  
- Mengelola daftar tugas (tasks)  
- Mengedit dan menghapus catatan serta tugas  
- Menampilkan daftar catatan dan tugas secara terstruktur  

---

## Alur Sistem

1. Pengguna melakukan registrasi akun  
2. Pengguna login ke dalam sistem  
3. Sistem menampilkan dashboard utama  
4. Pengguna menambahkan catatan atau tugas  
5. Sistem menyimpan data ke database  
6. Pengguna dapat mengedit atau menghapus data  
7. Pengguna logout dari sistem  

---


URL Aplikasi:  http://localhost/Notely/

## Struktur Direktori

Notely/
│
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── config/
│   └── database.php
│
├── database/
│   └── notely.sql
│
├── notes/
│   ├── index.php
│   ├── tambah.php
│   ├── edit.php
│   └── hapus.php
│
├── tasks/
│   ├── index.php
│   ├── tambah.php
│   ├── edit.php
│   └── hapus.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── dashboard.php
├── index.php
└── README.md



---

## Teknologi yang Digunakan

- PHP Native  
- MySQL  
- HTML  
- CSS  
- JavaScript  

---

## Instalasi dan Konfigurasi

1. Clone repository
git clone https://github.com/eyowh/Notely.git


2. Pindahkan folder ke direktori server lokal  
Contoh: `htdocs` (XAMPP)

3. Import database
- Buka phpMyAdmin  
- Buat database baru (misalnya: `notely`)  
- Import file `notely.sql`

4. Konfigurasi koneksi database  
Edit file `config/database.php` sesuai pengaturan database lokal

5. Jalankan aplikasi melalui browser
http://localhost/Notely/


---

## Keunggulan Sistem

- Alur sistem sederhana dan mudah dipahami  
- Struktur kode rapi dan terorganisir  
- Cocok sebagai media pembelajaran PHP Native  
- Mendukung manajemen catatan dan tugas harian  

---

## Catatan

Aplikasi **Notely** dikembangkan menggunakan PHP Native tanpa framework. Sistem ini cocok digunakan sebagai proyek pembelajaran, tugas kuliah, maupun dasar pengembangan aplikasi manajemen catatan yang lebih kompleks.

---

## Author

**Muhammad Ario Ardhi**  
Teknik Informatika  
Universitas Pamulang  


