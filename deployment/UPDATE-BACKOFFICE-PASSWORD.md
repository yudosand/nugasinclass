# Pembaruan password client dan backoffice

Paket: **nugasinclass-full-update-backoffice.zip**. Ini ZIP kode aplikasi lengkap untuk situs aktif `nugasinclass.web.id`, termasuk dependensi PHP, QRIS, konfirmasi pembayaran WhatsApp, dan favicon Nugasin.

## Cara memasang tanpa terminal

1. Backup file dan database situs melalui CloudPanel.
2. Upload ZIP ke root aplikasi yang berisi `artisan`, `app`, `public`, dan `.env`.
3. Extract dengan **Overwrite/Replace**. Jangan mengosongkan folder situs atau menaruh ZIP di dalam `public`.
4. Pertahankan `.env`, seluruh `storage/`, `public/storage`, dan `.installed`. ZIP tidak menimpa file/folder tersebut dan tidak membawa installer.
5. Melalui File Manager, hapus hanya file hasil kompilasi berakhiran `.php` di **`storage/framework/views`** supaya menu dan halaman terbaru langsung terbaca. Pertahankan foldernya serta `.gitignore`.
6. Jika sebelumnya menggunakan cache route/config, hapus **`bootstrap/cache/routes-*.php`** dan **`bootstrap/cache/config.php`** bila ada. Jangan hapus folder `bootstrap/cache`.
7. Muat ulang situs. Jika ada sesi yang meminta login kembali, masuk kembali seperti biasa. Hapus ZIP unggahan setelah selesai.

**Tidak perlu menjalankan php artisan, migrasi, Composer, atau npm untuk pembaruan ini.** Semua tabel yang dipakai sudah dibuat oleh instalasi awal, termasuk `password_reset_tokens` dan `sessions`.

## 1. Lupa / ubah password

### Client masih bisa login

Klik **Ubah Password** pada navigasi atau dashboard client. Isi password lama, password baru minimal 12 karakter, dan konfirmasinya. Setelah berhasil, login kembali dengan password baru. Token reset sebelumnya dibatalkan dan remember-me lama tidak berlaku.

### Client lupa password

Karena akun menggunakan nomor HP dan situs belum memiliki email pemulihan/OTP, alurnya melalui bantuan admin:

1. Client membuka **Login → Lupa password? → Minta Reset via WhatsApp**, menuju nomor admin `+62 858-6101-5161`.
2. Admin memverifikasi kepemilikan akun melalui nomor terdaftar dan data pesanan. Jangan meminta password lama atau OTP.
3. Admin membuka **Backoffice → Clients → Detail Client → Bantu Client Lupa Password**.
4. Centang konfirmasi identitas, masukkan password admin sendiri, lalu klik **Buat Tautan Reset Password**.
5. Salin tautan yang muncul dan kirim secara pribadi kepada pemilik akun yang sudah diverifikasi. Aplikasi tidak mengirim WhatsApp otomatis.
6. Client membuka tautan, mengisi nomor HP terdaftar dan password baru, lalu login kembali.

Tautan reset disimpan sebagai hash, berlaku **60 menit** berdasarkan konfigurasi bawaan, hanya dapat dipakai sekali, dan hanya untuk client terkait. Membuat tautan baru membatalkan tautan sebelumnya. Alur publik tidak dapat meminta token atau mengganti password hanya dengan mengetahui nomor HP. `APP_URL` pada `.env` harus tetap `https://nugasinclass.web.id` agar tautan reset menuju domain yang benar.

## 2. Daftar dan tambah admin

Menu **Daftar Admin** menampilkan nama, nomor HP untuk login, tanggal daftar, pencarian, dan pagination. Klik **Tambah Admin Baru**, isi nama/nomor HP/password baru dan konfirmasinya, lalu masukkan password admin Anda sendiri.

Nomor HP harus unik di seluruh akun. Admin baru mendapatkan akses penuh backoffice dan dapat login langsung. Akun client tidak dapat membuka atau memanggil fitur ini.

## 3. Hapus client

Pada daftar/detail client, klik **Hapus / Hapus Client** untuk membuka halaman konfirmasi. Ketik nomor HP client tersebut dan password admin Anda.

Penghapusan bersifat permanen: akun, profil, pesanan, mata kuliah, progres, token reset, dan bukti pembayaran client terkait dihapus. Paket layanan dan client lain dipertahankan. Endpoint ini menolak penghapusan akun admin. Bila sebagian file bukti gagal dihapus karena permission, aplikasi menampilkan informasi untuk pemeriksaan File Manager.

## 4. Progres per client di dashboard

Dashboard memiliki tabel **Progres Sesi per Client**, dengan pencarian nama/nomor HP, pagination, serta tautan ke halaman progres:

- **Aktivitas Terakhir**: nomor sesi tertinggi yang diskusi/tugasnya sudah mulai dikerjakan atau sudah memiliki nilai. Baris sesi 1–8 yang baru dibuat otomatis tidak dihitung sebagai aktivitas.
- **Selesai Seluruh Matkul**: jumlah sesi berurutan sejak sesi 1 yang diskusi dan tugasnya sudah `Selesai` pada seluruh mata kuliah client.
- **Rincian Mata Kuliah**: sesi terakhir dan jumlah sesi selesai untuk masing-masing mata kuliah.

Contoh: satu matkul mulai sesi 4 tetapi matkul lain baru selesai sesi 1, dashboard menunjukkan **aktivitas sesi 4** dan **selesai seluruh matkul 1/8**. Sesi 4 yang selesai sementara sesi 2/3 belum selesai tidak membuat hitungan selesai melompati sesi tersebut.

## Verifikasi

19 tes aplikasi dengan 146 assertions lulus: perubahan password, verifikasi password lama, token reset hash/expiry/sekali pakai/pembatasan akun, throttle reset, pembuatan admin dan duplikat nomor, pembatasan akses, penghapusan berantai dan file bukti, invalidasi sesi yang menyimpan hash password lama, progres banyak matkul, serta regresi QRIS/upload pembayaran.

Pengujian dijalankan pada database SQLite terisolasi di komputer. Tidak ada akun atau database produksi yang diubah, dan tidak ada pesan WhatsApp yang dikirim. ZIP memeriksa kecocokan setiap file dan dependensi produksi dengan lock file sebelum diserahkan.
