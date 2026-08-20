# Panduan Penggunaan SIMAS-GPS
**Sistem Informasi Monitoring Absensi Siswa Berbasis GPS**

Sistem ini dirancang untuk mempermudah pencatatan kehadiran siswa dengan validasi lokasi (GPS) secara presisi, serta memfasilitasi pengajuan izin dan pemantauan dari pihak guru maupun admin.

---

## 1. Akses Login Default
Sistem memiliki 3 tingkatan hak akses (Role). Berikut adalah kredensial default untuk mengakses sistem pertama kali:

- **Admin**
  - Username: `admin`
  - Password: `admin123`
- **Guru / Wali Kelas**
  - Username: (Dibuat oleh admin, password default: `guru123`)
- **Siswa**
  - Username: (Dibuat oleh admin, password default: `siswa123`)

---

## 2. Panduan Penggunaan: ADMINISTRATOR

Peran utama Admin adalah menyiapkan pengaturan awal sekolah dan mengelola data induk.

### A. Pengaturan Lokasi Sekolah (Wajib Dilakukan Pertama Kali)
Agar siswa dapat melakukan absensi, titik koordinat sekolah harus di-set terlebih dahulu.
1. Login sebagai Admin.
2. Buka menu **Pengaturan Lokasi**.
3. Pastikan Anda (Admin) sedang **berada secara fisik di sekolah**.
4. Klik tombol **"Ambil Lokasi Saat Ini"**. Sistem akan meminta izin GPS browser dan otomatis mengisi form Latitude & Longitude.
5. Tentukan **Radius Toleransi** (misal: `50` meter).
6. Tentukan jadwal **Absen Masuk** dan **Absen Pulang**.
7. Klik **Simpan Pengaturan**.

### B. Kelola Data Master
1. **Kelola Guru**: Tambahkan data guru. Guru ini nantinya dapat ditugaskan sebagai Wali Kelas.
2. **Kelola Kelas**: Buat kelas baru dan tentukan siapa Guru Walinya. 
   > **Penting (Mode Absen):** Pada pembuatan kelas, Anda bisa memilih "Mode Individu" (siswa login sendiri-sendiri) atau "Mode Device Kelas" (menggunakan 1 HP/Tablet khusus di kelas).
3. **Kelola Siswa**: Masukkan data siswa dan tetapkan mereka ke dalam kelas yang sudah dibuat. 

### C. Laporan Absensi
1. Buka menu **Laporan**.
2. Anda bisa melihat seluruh absensi siswa dari semua kelas berdasarkan filter tanggal.
3. Klik **Export CSV** untuk mendownload data dalam format Microsoft Excel.

---

## 3. Panduan Penggunaan: GURU (Wali Kelas)

Guru yang telah ditunjuk sebagai Wali Kelas oleh admin memiliki wewenang untuk memantau kelasnya sendiri.

### A. Rekapitulasi Kelas
1. Buka menu **Rekap Kelas**.
2. Pilih tanggal absensi.
3. Anda akan melihat daftar siswa Anda beserta jam kedatangan dan jam kepulangannya.
4. Anda dapat mengekspor rekap kelas spesifik ini ke format **CSV**.

### B. Verifikasi Izin / Sakit
1. Buka menu **Verifikasi Izin**.
2. Daftar siswa yang mengajukan izin/sakit pada kelas Anda akan muncul di tabel berstatus *Menunggu*.
3. Anda dapat mengklik gambar thumbnail bukti surat untuk melihat aslinya.
4. Klik **Setujui** atau **Tolak**. 
   > Jika disetujui, sistem akan otomatis mencatatkan status absensi (Izin/Sakit) untuk siswa tersebut pada tanggal yang bersangkutan.

---

## 4. Panduan Penggunaan: SISWA

Terdapat dua cara bagi siswa untuk melakukan absensi, bergantung pada pengaturan Mode Kelas yang ditetapkan Admin.

### Metode 1: Login Individu (Bawa HP Sendiri)
1. Siswa mengakses sistem menggunakan HP masing-masing dan login menggunakan *Username* dan *Password*.
2. Buka halaman **Absen GPS**.
3. Pastikan izin lokasi/GPS pada browser (Chrome/Safari) **telah diaktifkan**.
4. Klik tombol **Rekam Absen Masuk** saat baru tiba di sekolah, dan **Rekam Absen Pulang** saat jam sekolah berakhir.
5. Sistem akan menghitung jarak siswa ke titik koordinat sekolah. Jika melebihi *Radius Toleransi*, absensi akan ditolak.

### Metode 2: Mode Device Kelas (Shared Tablet/HP)
*Digunakan jika sekolah melarang siswa membawa HP pribadi.*
1. Guru / Admin login menggunakan HP atau Tablet sekolah.
2. Pada halaman Login, klik tombol **Buka Mode Device Kelas**.
3. Pilih nama Kelas yang dituju.
4. Layar akan berubah menampilkan daftar seluruh nama siswa di kelas tersebut.
5. HP/Tablet kemudian diletakkan di meja guru.
6. Siswa yang hadir cukup mendatangi HP tersebut, mencari namanya, mengklik/tap namanya, lalu mengklik tombol **Absen Masuk**.
7. Setelah selesai, layar akan otomatis kembali ke daftar nama, sehingga siswa lain dapat langsung melakukan absensi secara estafet tanpa perlu login ulang.

### C. Mengajukan Izin / Sakit
1. Jika siswa berhalangan hadir, buka menu **Ajukan Izin**.
2. Tentukan Tanggal, Jenis (Izin/Sakit), dan tuliskan Alasan.
3. Wajib melampirkan **Upload Bukti** (Foto surat keterangan dokter atau surat dari orang tua) maksimal ukuran 2MB.
4. Tunggu hingga Wali Kelas menyetujuinya. Status pengajuan dapat dipantau di halaman yang sama.

---

## 5. Fitur Keamanan dan Troubleshooting

- **Gagal Deteksi GPS:** Pastikan pengaturan "Location Services" atau "Layanan Lokasi" di menu Settings HP siswa aktif, dan browser telah diizinkan (Allow) mengakses lokasi.
- **Auto Logout:** Sesi pengguna akan berakhir secara otomatis (timeout) jika aplikasi dibiarkan tidak ada aktivitas selama 2 jam, guna melindungi privasi akun yang lupa di-logout.
- **Keamanan Data:** Aplikasi dilindungi dari serangan *SQL Injection*, *Cross-Site Scripting (XSS)*, *Cross-Site Request Forgery (CSRF)*, dan menolak unggahan file berbahaya / virus pada fitur upload izin.
