# Update format Rupiah dan kalkulator

- Nominal pencairan tampil dengan awalan Rp dan pemisah ribuan titik, untuk admin maupun marketing.
- Server menerima nominal bulat biasa atau format ribuan Indonesia dan tetap menolak jumlah melebihi saldo tersedia.
- Kalkulator terhubung kembali dengan tombol Hitung Estimasi Nilai. Mendukung nilai desimal, validasi 0–100, dan perhitungan ulang.
- Seluruh fitur sebelumnya termasuk Study Tracker dan perapihan navbar disertakan.

## Deploy melalui File Manager

1. Backup file dan database terlebih dahulu.
2. Upload dan extract ZIP ke root aplikasi (folder yang berisi artisan), kemudian timpa file kode.
3. Pertahankan `.env`, `.installed`, seluruh `storage/`, dan `public/storage`. Jangan hapus folder aplikasi seluruhnya.
4. Jika tampilan Blade lama masih muncul, hapus hanya file hasil kompilasi `*.php` di `storage/framework/views/`. Jangan hapus folder storage atau file upload.
5. Muat ulang browser dengan Ctrl+Shift+R.

Update ini tidak mengubah database dan tidak membutuhkan perintah Artisan. Tidak ada installer atau menu Update Sistem yang ditambahkan.
