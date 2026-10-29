# Perbaikan bukti pembayaran 404 Nginx

Paket lengkap: **nugasinclass-full-update-bukti.zip**. Semua fitur dari update sebelumnya tetap disertakan.

## Yang diperbaiki

URL lama `/storage/payment_proofs/nama-file.jpg` meminta Nginx membaca file publik secara langsung. Screenshot membuktikan Nginx mengembalikan 404 pada URL tersebut; belum memastikan apakah penyebabnya symlink, lokasi/permission, atau file yang sudah hilang.

Semua tombol dan preview bukti pada halaman pembayaran, daftar order, detail order, dan detail client kini memakai **`/payment/{id}/proof`**. Laravel memeriksa login serta kepemilikan pesanan sebelum mengirim file. Admin dan pemilik pesanan dapat mengaksesnya; client lain ditolak.

- Tidak bergantung pada `public/storage` atau `php artisan storage:link`.
- Bukti lama tetap dibaca dari `storage/app/public/payment_proofs` berdasarkan referensi dalam database.
- Upload baru disimpan di `storage/app/private/payment_proofs`. Referensi database diawali `private/payment_proofs/`; tidak perlu kolom atau migrasi baru.
- JPG/PNG dapat ditampilkan langsung. PDF diunduh agar file unggahan tidak menjalankan konten aktif dalam origin aplikasi.
- Nama/path divalidasi, path traversal dan symlink keluar dari disk ditolak, MIME diperiksa, respons menggunakan `no-store` dan `nosniff`.
- Penghapusan client menangani file bukti privat dan lama, dengan tetap menjaga file milik client lain.

## Deploy tanpa terminal

1. Backup file dan database situs.
2. Upload ZIP ke root aplikasi (folder yang berisi `.env`, `artisan`, `app`, dan `public`). Extract dengan **Overwrite / Replace**.
3. **Pertahankan `.env`, seluruh `storage/`, `public/storage`, dan `.installed`. Jangan kosongkan folder situs.** Data bukti pembayaran berada di `storage/`, bukan di ZIP kode.
4. Melalui File Manager, hapus hanya file kompilasi `.php` di `storage/framework/views`. Jika ada, hapus `bootstrap/cache/routes-*.php` dan `bootstrap/cache/config.php`; jangan hapus folder cache atau `.gitignore`.
5. Muat ulang halaman admin (Ctrl+F5), kemudian klik **Lihat Bukti** dari halaman terbaru. Alamat baru berakhir `/payment/ID/proof`, bukan `.jpg` atau `/storage/...`.
6. Hapus ZIP unggahan setelah selesai.

**Tidak perlu artisan, migrasi, membuat symlink, atau mengubah konfigurasi Nginx untuk perbaikan ini.** Gunakan HTTPS dan login ke akun yang berhak mengakses pesanan tersebut.

## Jika bukti lama masih belum muncul

Perbaikan kode tidak dapat memulihkan file yang terhapus. Melalui File Manager, periksa file yang dirujuk pesanan:

- Bukti lama: `storage/app/public/payment_proofs/<nama-file>`.
- Bukti baru: `storage/app/private/payment_proofs/<nama-file>`.

Nama file lama dapat dilihat pada URL di screenshot; jangan mengganti namanya sembarangan karena database menyimpan referensi nama tersebut. Pastikan file dapat dibaca oleh site user/PHP. Jangan menggunakan permission 777 atau memindahkan seluruh isi `storage` ke `public`.

Jika file hilang, pulihkan dari backup dengan nama/lokasi semula atau minta client mengunggah ulang. URL lama yang disimpan di bookmark tidak otomatis berubah; buka bukti dari tombol terbaru di aplikasi. Perubahan ini tidak memindahkan/menghapus file lama. Bila symlink publik pernah diaktifkan, file lama masih berada di disk publik; rute terproteksi tidak membatalkan akses langsung Nginx yang sudah dikonfigurasi terpisah.

## Verifikasi

**44 tes, 480 assertions lulus**: file lama tanpa symlink, upload privat, akses admin/pemilik, penolakan guest/client lain, MIME palsu, PDF, file hilang/path traversal, seluruh tautan bukti, penghapusan file client, dan regresi semua fitur sebelumnya. Pengujian menggunakan database dan file fixture lokal, tanpa mengubah data server produksi. ZIP memverifikasi hash setiap file kode dan kecocokan vendor produksi dengan `composer.lock`.
