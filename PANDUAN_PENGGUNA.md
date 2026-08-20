# 📖 PANDUAN PENGGUNAAN SIMAS-GPS
**Sistem Informasi Monitoring Absensi Siswa Berbasis GPS**

Panduan ini berfokus pada penggunaan fitur keamanan terbaru: **Device Binding (Penguncian Perangkat)**.

---

## 🔒 1. Apa itu Fitur Device Binding?
Fitur ini mencegah siswa untuk "titip absen" menggunakan HP temannya. 
Setiap akun Siswa **hanya bisa digunakan di 1 (satu) HP/Perangkat** yang pertama kali mereka gunakan untuk login. 

Jika mereka mencoba login menggunakan HP teman, komputer warnet, atau meminjamkan akunnya, sistem akan secara otomatis memblokir akses dan memunculkan peringatan.

---

## 📱 2. Panduan Untuk Siswa (Cara Kerja)

Siswa **tidak perlu melakukan pengaturan apapun**. Sistem bekerja otomatis di belakang layar.

1. **Login Pertama Kali:** Saat siswa pertama kali membuka web absensi dan login, sistem akan membaca *"Fingerprint"* unik dari HP tersebut dan mendaftarkannya secara permanen ke akun siswa.
2. **Absensi Sehari-hari:** Siswa melakukan absen masuk/pulang seperti biasa menggunakan HP yang sama.
3. **Mencoba Curang:** Jika siswa mencoba login di HP temannya, layar akan menampilkan error:
   > ❌ **Login Gagal!** Akun ini sudah terikat dengan perangkat/HP lain.

---

## 🛠️ 3. Panduan Untuk Admin / Guru (Cara Reset Device)

Akan ada kasus di mana siswa benar-benar **ganti HP baru** atau HP lamanya rusak. Jika ini terjadi, mereka tidak akan bisa login di HP baru mereka.

Sebagai Admin, Anda bisa me-reset kuncian perangkat mereka agar mereka bisa mendaftarkan HP baru.

**Langkah-langkah Reset Perangkat:**
1. Login ke aplikasi menggunakan akun **Admin** (contoh: `admin` / `admin123`).
2. Di menu sebelah kiri, klik **Kelola Siswa**.
3. Cari nama siswa yang bersangkutan.
4. Di kolom paling kanan (kolom aksi), lihat status perangkatnya:
   - 🟢 **Terikat:** Berarti siswa sudah mengunci akunnya di sebuah HP.
   - 🔴 **Belum:** Berarti siswa belum pernah login sama sekali.
5. Klik tombol berwarna kuning bertuliskan **"Reset Device"** di sebelah nama siswa tersebut.
6. Akan muncul pop-up konfirmasi: *"Yakin ingin menghapus data perangkat untuk siswa ini?"*, klik **OK**.
7. Selesai! Suruh siswa tersebut login kembali di HP barunya. HP baru tersebut akan otomatis menjadi perangkat yang terikat.

---

## 🌐 4. Mengubah Link Aplikasi (Railway)

Jika Anda ingin mengubah alamat web agar lebih mudah diingat (contoh: `simas-gps-sekolah.up.railway.app`):
1. Buka dashboard Railway (railway.app)
2. Klik Service **"web"** (yang berlogo GitHub).
3. Buka tab **Settings**.
4. Gulir ke bagian **Networking** > **Public Networking**.
5. Klik icon Pensil di sebelah nama domain saat ini.
6. Ketikkan nama baru (tanpa spasi).
7. Tekan **Enter**. Selesai!
