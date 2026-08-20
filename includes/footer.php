<?php
/**
 * SIMAS-GPS — Footer Global
 */
$base = (function_exists('base_url')) ? base_url() : '/absensi_gps';
$user_role = $_SESSION['role'] ?? '';
?>

    </main><!-- /main -->

    <?php if ($user_role): ?>
    <!-- ========== BOTTOM NAV (Mobile Only) ========== -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-30 flex">
        <?php
        $active_menu = $active_menu ?? '';
        if ($user_role === 'admin'):
            $bottom_menus = [
                ['icon'=>'layout-dashboard','label'=>'Dashboard','href'=>"$base/admin/dashboard.php",'key'=>'dashboard'],
                ['icon'=>'users','label'=>'Siswa','href'=>"$base/admin/kelola_siswa.php",'key'=>'kelola_siswa'],
                ['icon'=>'map-pin','label'=>'Lokasi','href'=>"$base/admin/pengaturan_lokasi.php",'key'=>'pengaturan_lokasi'],
                ['icon'=>'clipboard-list','label'=>'Laporan','href'=>"$base/admin/laporan.php",'key'=>'laporan'],
            ];
        elseif ($user_role === 'guru'):
            $bottom_menus = [
                ['icon'=>'layout-dashboard','label'=>'Dashboard','href'=>"$base/guru/dashboard.php",'key'=>'dashboard'],
                ['icon'=>'clipboard-list','label'=>'Rekap','href'=>"$base/guru/rekap_kelas.php",'key'=>'rekap_kelas'],
                ['icon'=>'check-circle','label'=>'Izin','href'=>"$base/guru/approve_izin.php",'key'=>'approve_izin'],
                ['icon'=>'pen-tool','label'=>'Manual','href'=>"$base/guru/absen_manual.php",'key'=>'absen_manual'],
            ];
        elseif ($user_role === 'siswa'):
            $bottom_menus = [
                ['icon'=>'home','label'=>'Beranda','href'=>"$base/siswa/dashboard.php",'key'=>'dashboard'],
                ['icon'=>'map-pin','label'=>'Absen','href'=>"$base/siswa/absen.php",'key'=>'absen'],
                ['icon'=>'calendar-days','label'=>'Riwayat','href'=>"$base/siswa/riwayat.php",'key'=>'riwayat'],
                ['icon'=>'file-text','label'=>'Izin','href'=>"$base/siswa/ajukan_izin.php",'key'=>'ajukan_izin'],
            ];
        else:
            $bottom_menus = [];
        endif;

        foreach ($bottom_menus as $m):
            $isActive = ($active_menu === $m['key']);
        ?>
        <a href="<?= $m['href'] ?>"
           class="flex-1 flex flex-col items-center justify-center py-2 text-center
                  transition-colors <?= $isActive ? 'text-indigo-600' : 'text-slate-400 hover:text-indigo-500' ?>">
            <i data-lucide="<?= $m['icon'] ?>" class="w-5 h-5"></i>
            <span class="text-[10px] mt-1 font-medium"><?= $m['label'] ?></span>
            <?php if ($isActive): ?>
                <span class="w-1 h-1 rounded-full bg-indigo-600 mt-0.5"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <!-- Space agar konten tidak tertutup bottom nav di mobile -->
    <div class="lg:hidden h-16"></div>
    </div><!-- /main-wrapper (lg:ml-64) -->
    <?php endif; ?>

<!-- ========== FOOTER INFO ========== -->
<footer class="hidden lg:block text-center text-xs text-slate-400 py-3 border-t border-slate-100">
    &copy; <?= date('Y') ?> SIMAS-GPS — SMPN 1 VII Koto Sungai Sarik &bull; Sistem Informasi Monitoring Absensi Siswa Berbasis GPS
</footer>

<!-- Toast Notification Container (dipakai halaman yang butuh notifikasi JS) -->
<div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"></div>

<script>
// Initialize Lucide Icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

/**
 * Global helper: tampilkan toast notification
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration ms
 */
function showToast(message, type = 'info', duration = 3500) {
    const colors = {
        success: 'bg-emerald-500',
        error:   'bg-red-500',
        warning: 'bg-amber-500',
        info:    'bg-indigo-500',
    };
    const icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error:   '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info:    '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-2 px-4 py-3 rounded-xl text-white text-sm
                       shadow-lg ${colors[type] ?? colors.info} transform transition-all duration-300 translate-x-full opacity-0`;
    toast.innerHTML = `${icons[type] ?? icons.info}<span>${message}</span>`;

    document.getElementById('toast-container').appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        });
    });

    // Auto remove
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}
</script>

</body>
</html>
