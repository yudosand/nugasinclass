# Update onboarding dan akses e-learning

Paket lengkap: **nugasinclass-full-update-elearning.zip**. Memuat seluruh fitur sebelumnya, termasuk chat admin, QRIS, WhatsApp pembayaran, favicon, pengelolaan admin/client, dan progres sesi.

## Deploy melalui CloudPanel

1. Backup database serta file situs. Simpan backup di lokasi privat di luar document root.
2. Upload ZIP ke root aplikasi yang berisi `.env`, `artisan`, `app`, dan `public`; extract dengan **Overwrite / Replace**.
3. Jangan menghapus seluruh folder situs. Pertahankan `.env`, seluruh `storage/`, `public/storage`, dan `.installed`. ZIP tidak berisi data tersebut atau installer.
4. Hapus hanya file kompilasi `.php` di `storage/framework/views`. Jika ada, hapus `bootstrap/cache/config.php` dan `bootstrap/cache/routes-*.php`. Pertahankan folder dan file `.gitignore`.
5. Pastikan `.env` tetap menggunakan `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL=https://nugasinclass.web.id`. Jangan mengganti `APP_KEY`; kunci ini diperlukan untuk membaca data terenkripsi yang sudah disimpan.
6. Login admin lewat HTTPS. Buka **Clients → Detail Client → Aktifkan Penyimpanan Akses E-Learning** sekali. Ini membuat tabel `client_elearning_credentials` dan `elearning_access_logs`; akun database harus memiliki izin membuat tabel. Aktivasi ulang tidak menghapus data.
7. Refresh browser (Ctrl+F5). Client baru melengkapi onboarding. Client lama dapat membuka **Dashboard Client → Edit Data Akademik** untuk melengkapi/memperbarui akses.
8. Hapus ZIP unggahan. Bila installer awal `public/deploy.php` masih ada di server, hapus file installer tersebut setelah instalasi awal selesai. Document root CloudPanel harus tetap mengarah ke folder **public**, bukan root aplikasi.

**Tidak perlu php artisan, Composer, npm, atau migrasi lewat terminal.** Migrasi standar disertakan untuk penggunaan CLI di masa depan dan aman bila tabel sudah dibuat lewat tombol aktivasi.

## Alur client dan admin

- Pada onboarding, setelah persetujuan dicentang, muncul kolom **NIM / Email E-Learning** dan **Password E-Learning**. Membatalkan centang menutup serta mengosongkan kedua kolom.
- Client wajib memiliki status approved. Persetujuan dan kolom akses divalidasi di server, bukan hanya JavaScript.
- Client yang sudah memiliki akses tersimpan dapat mengosongkan kedua kolom untuk mempertahankannya, atau mengisi keduanya untuk memperbarui. Password sebelumnya tidak dikirim kembali ke formulir.
- Detail client menampilkan status akses tersimpan dan tombol **Buka Akses E-Learning**. NIM/email dan password e-learning tidak dimasukkan ke HTML detail client atau halaman verifikasi awal.
- Admin mengisi password akun admin sendiri. Setelah benar, browser menerima akses melalui POST terproteksi CSRF. Pembukaan dicatat pada `elearning_access_logs` dengan ID admin, ID client, dan waktu; tidak mencatat nilai kredensial.
- Password awalnya disamarkan. Admin dapat memilih **Tampilkan password** atau **Tutup Akses**. Kolom dikosongkan setelah 45 detik, ketika tab disembunyikan, atau meninggalkan halaman.

## Pengamanan yang diterapkan

- NIM/email dan password e-learning disimpan dengan enkripsi Laravel yang mengautentikasi ciphertext. Field disembunyikan dari serialisasi model. Akun login Nugasin tetap memakai hash password; akses e-learning menggunakan enkripsi karena admin perlu membacanya untuk layanan yang disetujui. [Dokumentasi enkripsi Laravel](https://laravel.com/docs/12.x/encryption).
- Nilai akses dikecualikan dari old input/session ketika validasi gagal. Password tidak diisi ulang lewat Blade, hidden input, data attribute, atau localStorage. Spasi pada password e-learning dipertahankan.
- Login, simpan onboarding, aktivasi, dan pembukaan akses memiliki pembatasan frekuensi. Pembukaan akses memerlukan verifikasi password admin setiap kali, maksimal 5 percobaan per menit per admin.
- Di production, GET HTTP diarahkan ke HTTPS dan POST HTTP ditolak. Cookie sesi Secure/HttpOnly/SameSite=Lax dan debug nonaktif dipaksakan. HSTS ditambahkan pada respons HTTPS.
- Respons terautentikasi dan respons kesalahan menggunakan `Cache-Control: no-store`. Perlindungan frame, MIME sniffing, dan referrer ditambahkan.
- Halaman input/buka akses memakai aset lokal dan CSP yang membatasi skrip, koneksi, serta pengiriman formulir ke origin sendiri. Tidak memuat skrip/font dari CDN pada kedua halaman tersebut. Stylesheet Bootstrap lokal sudah diverifikasi dengan SHA-384.
- Penghapusan client ikut menghapus ciphertext akses melalui foreign key. Audit pembukaan tetap tersimpan tanpa nilai kredensial.

## Batas perlindungan dan pengaturan server

Tidak mungkin menyembunyikan password dari pemilik browser yang sedang mengetiknya, atau dari admin berwenang setelah password dikirim untuk ditampilkan. DevTools/Network dapat melihat respons yang telah diotorisasi. Mengosongkan kolom dan `no-store` tidak menghapus rekaman DevTools, screenshot, atau salinan yang sudah dibuat. Perlindungan yang diterapkan adalah mencegah pengiriman password tersimpan sebelum otorisasi, bukan memblokir Inspect.

Enkripsi database bergantung pada kerahasiaan `APP_KEY`. Lindungi `.env`, akses CloudPanel, akun admin, server, dan backup. Jangan membagikan kunci/credential lewat screenshot atau chat. Jangan menjalankan key:generate pada situs yang sudah menyimpan data terenkripsi; rotasi kunci perlu direncanakan agar data lama tetap terbaca. [Panduan pengelolaan secrets OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html).

Pemeriksaan HTTPS publik pada 14 September 2026 berhasil dengan validasi sertifikat normal. Selalu gunakan URL HTTPS yang valid; jangan melewati peringatan sertifikat. Jika setelah update terjadi redirect berulang, periksa konfigurasi CloudPanel/FastCGI agar Laravel menerima status HTTPS yang benar. Jangan mempercayai `X-Forwarded-Proto` dari sembarang IP; aplikasi tidak mengaktifkan wildcard trusted proxy.

## Hasil verifikasi

- **37 tes, 421 assertions lulus** di SQLite terisolasi, termasuk enkripsi, persetujuan, larangan akses lintas role, validasi, tidak ada secret dalam HTML/model JSON/old input, pembatasan percobaan, penolakan ciphertext rusak, audit akses, CSRF aktif, HTTP/HTTPS, aktivasi ulang, penghapusan client, dan regresi fitur sebelumnya.
- Browser lokal: form muncul setelah checkbox; penyimpanan berhasil; detail client tidak memuat secret; pembukaan dengan password admin berhasil; penutupan mengosongkan kolom; tidak ada error CSP/JavaScript yang teramati.
- `composer audit --no-dev --locked` tidak menemukan advisory maupun paket abandoned saat pemeriksaan. Ini pemeriksaan dependensi yang dikenal, bukan bukti bahwa seluruh aplikasi/server bebas celah.
- Semua file payload ZIP diverifikasi dengan SHA-256; dependensi produksi cocok dengan `composer.lock`. Fixture lokal, `.env`, data upload, dan installer tidak disertakan.

Tidak ada akun, credential, atau database produksi yang diubah selama pengujian. Audit infrastruktur CloudPanel/OS/konfigurasi Nginx dan penetration test menyeluruh belum dilakukan; tidak ada jaminan mutlak bebas peretasan.
