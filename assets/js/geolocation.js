/**
 * SIMAS-GPS — Geolocation AJAX
 * Logic ambil lokasi dari browser & kirim ke server
 */

function prosesAbsen(jenis, csrf_token) {
    const btn = document.getElementById('btn-absen-' + jenis);
    const resultBox = document.getElementById('result-box');
    
    if (!navigator.geolocation) {
        tampilkanHasil('error', 'Browser Anda tidak mendukung deteksi lokasi (GPS).');
        return;
    }

    // Tampilkan loading
    btn.disabled = true;
    const oriText = btn.innerHTML;
    btn.innerHTML = `<span class="animate-spin inline-block mr-2">⏳</span> Memproses...`;
    
    // Opsi akurasi tinggi
    const geoOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Kirim ke server via Fetch API
            fetch('../api/proses_absen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    lat: lat,
                    lng: lng,
                    jenis: jenis,
                    csrf_token: csrf_token
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    tampilkanHasil('success', `${data.message} (Jarak: ${data.jarak}m)`);
                    setTimeout(() => {
                        if (data.is_device) {
                            window.location.href = 'pilih_siswa.php'; // Kembali ke daftar kelas
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    tampilkanHasil('error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = oriText;
                }
            })
            .catch(err => {
                tampilkanHasil('error', 'Terjadi kesalahan jaringan atau server.');
                btn.disabled = false;
                btn.innerHTML = oriText;
            });
        },
        function(error) {
            let msg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak. Harap izinkan akses lokasi di browser."; break;
                case error.POSITION_UNAVAILABLE: msg = "Informasi lokasi GPS tidak tersedia saat ini."; break;
                case error.TIMEOUT: msg = "Waktu permintaan GPS habis, pastikan sinyal stabil."; break;
                default: msg = "Terjadi kesalahan tidak diketahui."; break;
            }
            tampilkanHasil('error', msg);
            btn.disabled = false;
            btn.innerHTML = oriText;
        },
        geoOptions
    );
}

function tampilkanHasil(status, pesan) {
    const box = document.getElementById('result-box');
    box.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-red-50', 'text-red-700', 'border-emerald-200', 'border-red-200');
    
    if (status === 'success') {
        box.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
        box.innerHTML = `<div class="flex items-start gap-3"><span class="text-xl">✅</span><p class="text-sm font-medium mt-0.5">${pesan}</p></div>`;
    } else {
        box.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
        box.innerHTML = `<div class="flex items-start gap-3"><span class="text-xl">⚠️</span><p class="text-sm font-medium mt-0.5">${pesan}</p></div>`;
    }
}
