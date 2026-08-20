/**
 * SIMAS-GPS — Device Binding JS
 * 
 * Tugas file ini:
 * 1. Generate "device token" unik dan simpan ke localStorage (permanen di browser HP ini)
 * 2. Generate "browser fingerprint" dari karakteristik HP (tanpa sensor sidik jari fisik)
 * 3. Kirim keduanya saat form login disubmit, agar server bisa memverifikasi HP
 */

const SIMASDevice = (() => {

    const TOKEN_KEY       = 'simas_device_token';
    const FINGERPRINT_KEY = 'simas_device_fp';

    // ─────────────────────────────────────────────
    //  1. DEVICE TOKEN (disimpan di localStorage)
    //  Token ini bersifat permanen selama localStorage
    //  tidak dihapus / browser tidak di-reset.
    // ─────────────────────────────────────────────

    function generateToken() {
        const arr = new Uint8Array(32);
        crypto.getRandomValues(arr);
        return Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
    }

    function getOrCreateToken() {
        let token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            token = generateToken();
            localStorage.setItem(TOKEN_KEY, token);
        }
        return token;
    }

    // ─────────────────────────────────────────────
    //  2. BROWSER FINGERPRINT
    //  Dibuat dari kombinasi karakteristik HP/browser.
    //  Bukan sensor sidik jari fisik — murni data teknis.
    // ─────────────────────────────────────────────

    function getCanvasHash() {
        try {
            const canvas = document.createElement('canvas');
            const ctx    = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font         = '14px Arial';
            ctx.fillStyle    = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle    = '#069';
            ctx.fillText('SIMAS-GPS 🏫', 2, 15);
            ctx.fillStyle    = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('SIMAS-GPS 🏫', 4, 17);
            return canvas.toDataURL().slice(-50); // ambil tail untuk efisiensi
        } catch (e) {
            return 'canvas_blocked';
        }
    }

    async function generateFingerprint() {
        const raw = [
            navigator.userAgent         || '',
            navigator.language          || '',
            navigator.languages         ? navigator.languages.join(',') : '',
            screen.width + 'x' + screen.height,
            screen.colorDepth           || '',
            new Date().getTimezoneOffset(),
            navigator.hardwareConcurrency || '',
            navigator.deviceMemory       || '',
            navigator.platform           || '',
            getCanvasHash(),
        ].join('|');

        // Hash dengan SHA-256 menggunakan Web Crypto API (built-in, tanpa library)
        try {
            const msgBuffer  = new TextEncoder().encode(raw);
            const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
            const hashArray  = Array.from(new Uint8Array(hashBuffer));
            const hashHex    = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

            // Simpan cache di localStorage
            localStorage.setItem(FINGERPRINT_KEY, hashHex);
            return hashHex;
        } catch (e) {
            // Fallback jika browser tidak support Web Crypto (sangat jarang)
            const fallback = btoa(raw).slice(0, 64);
            localStorage.setItem(FINGERPRINT_KEY, fallback);
            return fallback;
        }
    }

    function getCachedFingerprint() {
        return localStorage.getItem(FINGERPRINT_KEY) || '';
    }

    // ─────────────────────────────────────────────
    //  3. INJECT ke FORM LOGIN
    //  Tambahkan hidden input token + fingerprint
    //  sebelum form disubmit ke server.
    // ─────────────────────────────────────────────

    async function injectIntoForm(formElement) {
        if (!formElement) return;

        const token       = getOrCreateToken();
        const fingerprint = getCachedFingerprint() || await generateFingerprint();

        // Tambahkan atau update hidden field
        let tokenInput = formElement.querySelector('input[name="device_token"]');
        if (!tokenInput) {
            tokenInput      = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'device_token';
            formElement.appendChild(tokenInput);
        }
        tokenInput.value = token;

        let fpInput = formElement.querySelector('input[name="device_fingerprint"]');
        if (!fpInput) {
            fpInput      = document.createElement('input');
            fpInput.type = 'hidden';
            fpInput.name = 'device_fingerprint';
            formElement.appendChild(fpInput);
        }
        fpInput.value = fingerprint;
    }

    // ─────────────────────────────────────────────
    //  4. INIT — dipanggil di halaman login
    // ─────────────────────────────────────────────

    async function init() {
        // Pre-generate fingerprint di background saat halaman dibuka
        await generateFingerprint();

        // Attach ke semua form login normal (bukan device mode)
        const loginForm = document.getElementById('form-login-normal');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await injectIntoForm(loginForm);
                loginForm.submit();
            });
        }
    }

    return { init, getOrCreateToken, generateFingerprint };

})();

// Jalankan saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    SIMASDevice.init();
});
