# 📝 Notely - Aplikasi Manajemen Catatan dan Produktivitas

Notely adalah aplikasi manajemen catatan dan pengingat yang dirancang untuk membantu pengguna mengatur aktivitas, menyelesaikan tugas, serta berkolaborasi dengan orang lain secara efektif.

## 🎯 Fitur Utama

- ✏️ **Catatan Personal** - Buat dan kelola catatan dengan kategori dan warna
- 📝 **To-Do List** - Kelola tugas dengan prioritas dan deadline
- 📅 **Jadwal Kegiatan** - Atur jadwal dengan pengingat otomatis
- ⏰ **Pengingat Otomatis** - Notifikasi sebelum deadline
- 📊 **Prioritas Tugas** - Sistem prioritas (rendah, sedang, tinggi)
- 🗂️ **Kategorisasi** - Organisir catatan, tugas, dan jadwal
- 👥 **Grup Kolaboratif** - Buat grup untuk kolaborasi tim
- 💬 **Chat/Obrolan** - Komunikasi antar pengguna dan grup
- 🔔 **Notifikasi Real-time** - Notifikasi untuk pesan dan pengingat
- 🔒 **Keamanan** - Sistem autentikasi dan keamanan data
- 📱 **Responsif** - Tampilan yang responsif untuk web dan mobile

## 🚀 Instalasi

### Persyaratan
- XAMPP (PHP 7.4+ dan MySQL)
- Web browser modern

### Langkah Instalasi

1. **Clone atau extract proyek ke folder htdocs**
   ```
   C:\xampp\htdocs\Notely
   ```

2. **Jalankan XAMPP**
   - Start Apache
   - Start MySQL

3. **Akses aplikasi**
   - Buka browser dan kunjungi: `http://localhost/Notely`
   - Database akan dibuat otomatis saat pertama kali diakses

4. **Registrasi akun**
   - Klik "Daftar" untuk membuat akun baru
   - Atau akses langsung: `http://localhost/Notely/auth/register.php`

## 📁 Struktur Proyek

```
Notely/
├── api/                    # API endpoints
│   ├── send_message.php
│   ├── update_task_status.php
│   ├── check_reminders.php
│   └── ...
├── assets/
│   ├── css/
│   │   └── style.css      # Styling utama
│   └── js/
│       ├── main.js        # JavaScript utama
│       └── chat.js        # Fungsi chat
├── auth/                   # Autentikasi
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/
│   ├── config.php         # Konfigurasi aplikasi
│   └── database.php       # Koneksi database
├── includes/
│   ├── header.php         # Header template
│   └── footer.php         # Footer template
├── index.php              # Dashboard
├── notes.php              # Halaman catatan
├── tasks.php              # Halaman tugas
├── schedules.php          # Halaman jadwal
├── groups.php             # Halaman grup
├── chat.php               # Halaman chat
├── notifications.php      # Halaman notifikasi
├── profile.php            # Halaman profil
└── README.md
```

## 🗄️ Database

Database akan dibuat otomatis dengan nama `notely_db` saat pertama kali aplikasi diakses. Tabel yang dibuat:

- `users` - Data pengguna
- `notes` - Catatan personal
- `tasks` - Tugas/To-do list
- `schedules` - Jadwal kegiatan
- `groups` - Grup kolaboratif
- `group_members` - Anggota grup
- `group_notes` - Catatan grup
- `group_tasks` - Tugas grup
- `messages` - Pesan chat
- `notifications` - Notifikasi

## 🔐 Konfigurasi Database

Jika perlu mengubah konfigurasi database, edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'notely_db');
```

## 📖 Cara Penggunaan

### 1. Registrasi & Login
- Daftar akun baru di halaman registrasi
- Login dengan username/email dan password

### 2. Dashboard
- Lihat ringkasan catatan, tugas, dan jadwal
- Akses cepat ke fitur utama

### 3. Catatan
- Buat catatan dengan kategori dan warna
- Pin catatan penting
- Edit dan hapus catatan

### 4. Tugas
- Buat tugas dengan prioritas dan deadline
- Filter berdasarkan status atau prioritas
- Tandai tugas sebagai selesai

### 5. Jadwal
- Buat jadwal kegiatan dengan waktu
- Set pengingat sebelum jadwal
- Lihat jadwal harian

### 6. Grup
- Buat grup kolaboratif
- Bergabung dengan grup menggunakan ID grup
- Buat catatan dan tugas bersama di grup

### 7. Chat
- Kirim pesan langsung ke pengguna lain
- Chat di grup
- Lihat riwayat percakapan

### 8. Notifikasi
- Terima notifikasi untuk pesan baru
- Dapatkan pengingat deadline dan jadwal
- Tandai notifikasi sebagai dibaca

## 🔧 Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (XAMPP)

## 🛡️ Keamanan

- Password di-hash menggunakan `password_hash()`
- Input disanitasi untuk mencegah XSS
- Prepared statements untuk mencegah SQL injection
- Session management untuk autentikasi
- Validasi input di server-side

## 📝 Lisensi

Proyek ini dibuat untuk keperluan edukasi dan penggunaan pribadi.

## 👨‍💻 Pengembangan

Untuk pengembangan lebih lanjut, pertimbangkan:
- Implementasi WebSocket untuk chat real-time
- Sistem pencarian yang lebih canggih
- Export/Import data
- Integrasi dengan kalender eksternal
- Mobile app (React Native/Flutter)

## 📞 Support

Jika ada pertanyaan atau masalah, silakan buat issue di repository atau hubungi developer.

---

**Notely** - Kelola produktivitas Anda dengan lebih baik! 🚀

