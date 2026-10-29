# Update Chat Admin, password 8 karakter, dan Profil Saya

Paket: **nugasinclass-full-update-admin-chat.zip** untuk situs aktif `nugasinclass.web.id`.
ZIP berisi kode aplikasi lengkap dan dependensi produksi, termasuk semua fitur sebelumnya: QRIS, WhatsApp konfirmasi pembayaran, favicon, pemulihan password client, daftar admin, hapus client, dan progres sesi client.

## Pasang melalui CloudPanel tanpa terminal

1. Backup file dan database situs sebelum update.
2. Upload ZIP ke root aplikasi yang berisi `.env`, `artisan`, `app`, dan `public`. Jangan upload ke dalam `public`.
3. Extract dengan **Overwrite / Replace**. **Jangan mengosongkan folder situs.** Pertahankan `.env`, seluruh `storage/`, `public/storage`, dan `.installed`; paket ini tidak menyertakan atau menimpa data tersebut.
4. Di File Manager, hapus hanya file hasil kompilasi berakhiran `.php` di `storage/framework/views`. Pertahankan foldernya dan `.gitignore`.
5. Bila ada, hapus hanya `bootstrap/cache/config.php` dan file `bootstrap/cache/routes-*.php` agar route/config terbaru terbaca. Pertahankan folder `bootstrap/cache` dan file lainnya.
6. Login ke backoffice, buka **Chat Admin**, lalu klik **Aktifkan Chat Admin** sekali. Tombol ini menyiapkan tabel chat di database yang sama; tidak mengubah tabel client, order, atau password. Jika ruang sudah aktif, tombol tidak muncul. Mengaktifkan ulang tidak menghapus riwayat.
7. Muat ulang browser (Ctrl+F5), lalu hapus ZIP unggahan dari server.

**Tidak perlu menjalankan `php artisan`, Composer, npm, atau memasang layanan WebSocket.** Update ini menambahkan satu tabel `admin_chat_messages`, yang disiapkan melalui tombol aktivasi. File migrasi juga disertakan untuk pengelolaan melalui terminal di masa mendatang dan aman bila tabel sudah dibuat lewat tombol.

Jika aktivasi menampilkan kegagalan, periksa log aplikasi dan izin user database untuk membuat tabel. Jangan menjalankan installer awal atau membuat ulang database situs.

## Fitur yang tersedia

### 1. Chat Admin

- Satu ruang bersama khusus akun dengan role admin; client dan pengunjung tidak dapat membaca atau mengirim pesan.
- Pesan teks dan emoji, nama pengirim, waktu pengiriman, serta penanda pesan Anda.
- Pesan baru diperiksa otomatis setiap 5 detik saat tab aktif. Ada tombol **Perbarui** untuk pemeriksaan manual.
- Riwayat tersimpan di database dan tetap ada setelah logout atau update ZIP berikutnya. Backup database menyertakan riwayat chat.
- Awalnya menampilkan 50 pesan terbaru. Klik **Muat pesan sebelumnya** untuk membaca riwayat lebih lama.
- Maksimal 2.000 karakter per pesan dan 30 pengiriman per menit per admin. Pemeriksaan pesan tidak mengurangi batas pengiriman atau batas fitur admin lainnya.
- Pengiriman ulang setelah koneksi terputus menggunakan ID yang sama supaya tidak menggandakan pesan. Jika gagal, teks tetap berada di kolom ketik.
- Pesan diperlakukan sebagai teks biasa; HTML tidak dieksekusi. Versi ini menyediakan chat teks, tanpa lampiran, panggilan, atau notifikasi di luar halaman chat.

### 2. Password admin baru minimal 8 karakter

Di **Daftar Admin → Tambah Admin Baru**, password 8 karakter sudah diterima; 7 karakter ditolak. Konfirmasi password baru dan password admin pembuat tetap diperlukan. Batas ini berlaku pada pembuatan admin baru; aturan password client tetap seperti sebelumnya.

### 3. Admin mengubah nama sendiri

Buka **Profil Saya** di sidebar atau dropdown nama akun, isi **Nama Anda**, lalu klik **Simpan Nama**. Perubahan hanya berlaku pada admin yang sedang login. Nomor HP untuk login, password, role, dan akun admin lain tidak diubah. Nama baru tampil pada navigasi, daftar admin, dan pesan chat saat riwayat dimuat ulang.

## Verifikasi paket

- **27 tes / 307 assertions lulus** pada SQLite terisolasi: akses admin/client/guest, aktivasi chat pada database lama, aktivasi ulang tanpa kehilangan data, validasi pesan, pengiriman ulang, pengirim tidak dapat dipalsukan, pagination/cursor, pembatasan frekuensi, password 8 karakter, ubah nama sendiri, dan regresi seluruh fitur sebelumnya.
- Pengiriman pesan dan perubahan nama diuji melalui browser dengan akun lokal buatan; tidak ada akun atau database produksi yang diubah.
- Semua file ZIP diperiksa ulang dengan SHA-256 dan dependensi produksi dicocokkan dengan `composer.lock`. Data fixture, file `.env`, file upload, dan installer tidak masuk ZIP.
