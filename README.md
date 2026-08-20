# SIMAS-GPS
**Sistem Informasi Monitoring Absensi Siswa Berbasis GPS**

Aplikasi web absensi siswa berbasis GPS untuk sekolah, dibangun dengan PHP & MySQL.
Mendukung validasi lokasi real-time, pengajuan izin digital, dan pembatasan perangkat per siswa.

---

## ✨ Fitur Utama

- 📍 **Absensi GPS** — Validasi jarak siswa ke koordinat sekolah secara real-time
- 📱 **Device Binding** — Satu akun siswa hanya bisa digunakan dari satu HP terdaftar
- 👨‍🏫 **Mode Device Kelas** — Satu tablet/HP khusus untuk satu kelas (tanpa HP pribadi siswa)
- 📋 **Pengajuan Izin Digital** — Siswa upload bukti, guru verifikasi secara online
- 📊 **Laporan & Export CSV** — Rekap kehadiran per kelas, per periode
- 🔒 **Keamanan** — Proteksi CSRF, XSS, SQL Injection, auto logout 2 jam

## 👥 Role Pengguna

| Role | Akses |
|---|---|
| **Admin** | Kelola semua data, pengaturan lokasi, laporan global, reset device siswa |
| **Guru** | Rekap kelas sendiri, verifikasi izin siswa |
| **Siswa** | Absen GPS, ajukan izin, lihat riwayat |

---

## 🚀 Setup Lokal (XAMPP)

### 1. Clone & Pindahkan
```bash
git clone https://github.com/username/absensi_gps.git
# Pindahkan ke: C:\xampp\htdocs\absensi_gps
```

### 2. Import Database
- Buka **phpMyAdmin** → Buat database baru: `simas_gps`
- Import file: `database/simas_gps.sql`
- Jalankan juga migration: `database/migration_device_token.sql`

### 3. Konfigurasi Environment
```bash
# Salin template
cp .env.example .env

# Edit .env sesuai konfigurasi lokal Anda
```

Isi `.env` untuk XAMPP lokal:
```
DB_HOST=localhost
DB_NAME=simas_gps
DB_USER=root
DB_PASS=
APP_ENV=development
BASE_PATH=/absensi_gps
```

### 4. Akses Aplikasi
Buka browser: `http://localhost/absensi_gps`

**Kredensial default:**
- Admin: `admin` / `admin123`
- Guru: `budiguru` / `guru123`
- Siswa: `andi` / `siswa123`

---

## ☁️ Deploy ke Railway (Gratis)

### 1. Push ke GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/username/absensi_gps.git
git push -u origin main
```

### 2. Buat Proyek di Railway
1. Buka [railway.app](https://railway.app) → Login dengan GitHub
2. Klik **New Project** → **Deploy from GitHub repo**
3. Pilih repository `absensi_gps`

### 3. Tambah MySQL Database
1. Di dashboard Railway, klik **+ New** → **Database** → **MySQL**
2. Klik tab **Variables** di service MySQL → catat nilai `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

### 4. Set Environment Variables di Railway
Di service PHP Anda, buka tab **Variables** dan tambahkan:

| Key | Value |
|---|---|
| `DB_HOST` | (dari MySQL service) |
| `DB_PORT` | `3306` |
| `DB_NAME` | (dari MySQL service) |
| `DB_USER` | (dari MySQL service) |
| `DB_PASS` | (dari MySQL service) |
| `APP_ENV` | `production` |
| `BASE_PATH` | *(kosongkan)* |

### 5. Import Database ke Railway MySQL
Gunakan MySQL client atau TablePlus untuk konek ke Railway MySQL, lalu import:
- `database/simas_gps.sql`
- `database/migration_device_token.sql`

### 6. Deploy Otomatis
Setiap kali Anda `git push` ke branch `main`, Railway otomatis build dan deploy ulang. ✅

---

## 📱 Fitur Device Binding

Fitur ini membatasi satu akun siswa hanya bisa login dari **satu HP terdaftar**:

- **Pertama login** → HP otomatis terdaftar sebagai perangkat resmi
- **Login dari HP lain** → Ditolak dengan pesan error
- **Ganti HP** → Admin klik tombol **🔄 Reset Device** di menu Kelola Siswa

Teknologi yang digunakan: Device Token (localStorage) + Browser Fingerprint (SHA-256 hash)

---

## 🛡️ Keamanan

- Password di-hash dengan **bcrypt**
- Semua query menggunakan **PDO Prepared Statements** (anti SQL Injection)
- Proteksi **CSRF Token** di setiap form POST
- Output di-escape dengan `htmlspecialchars` (anti XSS)
- **Auto logout** setelah 2 jam tidak aktif
- Upload file divalidasi tipe dan ukuran

---

## 📁 Struktur Proyek

```
absensi_gps/
├── admin/          # Halaman-halaman admin
├── guru/           # Halaman-halaman guru
├── siswa/          # Halaman-halaman siswa
├── api/            # Endpoint API (proses absen, device check)
├── assets/
│   └── js/
│       └── device.js   # Device binding logic
├── config/
│   └── database.php    # Koneksi DB (baca dari ENV)
├── database/
│   ├── simas_gps.sql              # Schema + data awal
│   └── migration_device_token.sql # Migration device binding
├── includes/
│   ├── auth.php    # Auth, session, CSRF, device token functions
│   ├── header.php
│   └── footer.php
├── uploads/        # Upload bukti izin (di-.gitignore)
├── .env.example    # Template environment variables
├── nixpacks.toml   # Konfigurasi Railway
└── Procfile        # Start command Railway
```
