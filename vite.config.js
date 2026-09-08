import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Catatan hardening (persiapan offline / NativePHP Mobile):
//
// Opsi `server` di bawah ini HANYA dipakai oleh `npm run dev` (Vite dev
// server dengan Hot Module Replacement). Opsi ini TIDAK pernah dipakai
// oleh `npm run build` (build production), jadi tidak memengaruhi build
// offline/APK sama sekali.
//
// Sebelumnya host HMR di-hardcode ke IP LAN laptop tertentu
// (192.168.1.36). Itu membuat konfigurasi tidak portable ke komputer
// developer lain. Sekarang host HMR hanya diaktifkan jika developer
// secara eksplisit mengisi VITE_DEV_LAN_HOST di .env (misalnya saat
// ingin mengetes tampilan dari HP lewat Wi-Fi LAN pada tahap
// pengembangan). Jika tidak diisi, Vite dev server berjalan normal di
// localhost tanpa konfigurasi host/HMR khusus.
const lanHost = process.env.VITE_DEV_LAN_HOST || null;

export default defineConfig({
    server: lanHost
        ? {
              host: '0.0.0.0',
              hmr: {
                  host: lanHost,
              },
          }
        : {},

    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
