# Koreksi paket dan harga order

ZIP ini berisi seluruh kode aplikasi beserta fitur sebelumnya. Tidak perlu menjalankan Artisan.

## Pasang melalui CloudPanel

1. Backup database dan folder aplikasi.
2. Upload ZIP ke root aplikasi yang berisi `artisan`, extract dan timpa kode lama.
3. Tetap simpan `.env`, `.installed`, `storage/`, dan `public/storage`. Jangan hapus data unggahan.
4. Jika menggunakan cache route/config, hapus hanya file PHP cache di `bootstrap/cache/` (pertahankan `.gitignore`). Bila tampilan belum berubah, hapus hanya file PHP hasil kompilasi di `storage/framework/views/`.

Pencocokan kas awal yang sudah selesai tidak perlu diulang. Tabel riwayat koreksi dibuat saat admin menyimpan koreksi pertama melalui web, setelah validasi password.

## Kasus Bobby

1. Buka Orders → detail order Bobby, cocokkan nama lengkap dan nomor order.
2. Buka **Koreksi Paket / Harga Order** di atas form pembayaran.
3. Pilih paket layanan yang benar (misalnya All Prodi jika itu layanan yang disepakati), isi total harga baru **500.000**.
4. Tulis alasan, misalnya “Bukan layanan Full SI; harga disepakati Rp500.000”. Isi password admin, centang konfirmasi, klik **Simpan Koreksi Order**.
5. Periksa **Diterima & Terverifikasi**:
   - Jika masih Rp0: catat pembayaran baru Rp500.000 melalui **Catat Pembayaran Diterima**, dengan tanggal dan referensi transfer sebenarnya. Verifikasi uang telah diterima, lalu simpan.
   - Jika sudah Rp500.000: pembayaran tidak perlu dicatat lagi. Koreksi harga langsung menjadikan order lunas.

Hasil akhir: total Rp500.000, diterima Rp500.000, sisa Rp0, status Lunas.

## Perilaku koreksi

- Koreksi harga tidak membuat uang masuk atau mengubah saldo Cuanin. Hanya pencatatan pembayaran baru yang menambah kas dan pembagian.
- Paket, harga lama/baru, pembayaran saat koreksi, alasan, admin dan waktu tersimpan di Riwayat Koreksi Order.
- Harga baru tidak boleh lebih kecil dari uang yang sudah diterima. Fitur ini berlaku untuk order yang masih memiliki sisa tagihan; refund atau pembatalan pembayaran bukan bagian dari koreksi harga.
- Harga katalog paket tidak berubah; harga kesepakatan hanya berlaku pada order yang dikoreksi.
- Perubahan bersamaan dan pengiriman ulang ditangani agar tidak menimpa koreksi admin lain atau menggandakan riwayat.
- Jika koreksi membuat order referral lunas, client dihitung sebagai referral berhasil tanpa menambahkan komisi pembayaran kedua. Bonus yang baru memenuhi syarat didanai melalui pembayaran masuk berikutnya.

Pembuatan ZIP tidak mengubah order atau saldo di server. Langkah koreksi Bobby dilakukan setelah ZIP dipasang.
