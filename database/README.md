# Database Import Guide

## Cara Import Database Notely

### Metode 1: Menggunakan phpMyAdmin

1. Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
2. Klik tab **"Import"** di menu atas
3. Klik **"Choose File"** dan pilih file `notely_db.sql`
4. Pastikan **"Format"** adalah **"SQL"**
5. Klik tombol **"Go"** atau **"Import"**
6. Tunggu hingga proses selesai
7. Database `notely_db` akan dibuat beserta semua tabelnya

### Metode 2: Menggunakan Command Line (MySQL)

1. Buka Command Prompt atau Terminal
2. Masuk ke direktori database:
   ```bash
   cd C:\xampp\htdocs\Notely\database
   ```
3. Jalankan perintah import:
   ```bash
   mysql -u root -p < notely_db.sql
   ```
   Atau jika tidak ada password:
   ```bash
   mysql -u root < notely_db.sql
   ```

### Metode 3: Menggunakan MySQL Workbench

1. Buka MySQL Workbench
2. Connect ke server MySQL (localhost)
3. File → Open SQL Script
4. Pilih file `notely_db.sql`
5. Klik tombol Execute (⚡) atau tekan Ctrl+Shift+Enter
6. Tunggu hingga proses selesai

### Verifikasi Import

Setelah import selesai, verifikasi dengan:

1. Buka phpMyAdmin
2. Pilih database `notely_db`
3. Pastikan ada 10 tabel:
   - users
   - notes
   - tasks
   - schedules
   - groups
   - group_members
   - group_notes
   - group_tasks
   - messages
   - notifications

### Troubleshooting

**Error: Database already exists**
- Hapus database `notely_db` terlebih dahulu di phpMyAdmin
- Atau edit file SQL dan hapus baris `CREATE DATABASE`

**Error: Access denied**
- Pastikan user MySQL memiliki hak akses untuk membuat database
- Gunakan user `root` atau user dengan privileges lengkap

**Error: Foreign key constraint**
- Pastikan semua tabel dibuat dalam urutan yang benar
- File SQL sudah mengatur urutan dengan benar

### Catatan

- File SQL ini menggunakan `CREATE TABLE IF NOT EXISTS`, jadi aman untuk dijalankan berkali-kali
- Semua tabel menggunakan charset `utf8mb4` untuk mendukung emoji dan karakter khusus
- Foreign keys menggunakan `ON DELETE CASCADE` untuk menjaga integritas data

