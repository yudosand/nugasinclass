# Pengecualian saldo minus — hanya koreksi Laila

Pengecualian ini mengikuti persetujuan khusus pemilik: order `ORD-20260917-3WEYS`, harga Rp850.000, penerimaan Rp500.000, penerima lama kojelzs (user 1), Faisal (user 8), Indra (user 14, wallet 4), menjadi kojelzs dan Faisal saja. Pengecualian dibatasi sekali, maksimum utang Rp50.001 pada Indra, tanpa saldo ditahan. Nama dan identitas akun ikut diverifikasi. Tidak tersedia pilihan umum untuk mengizinkan minus pada order lain.

## Deploy dan gunakan

1. Backup database dan aplikasi. Extract ZIP di root aplikasi (folder `artisan`) dan timpa kode.
2. Pertahankan `.env`, `.installed`, `storage/`, `public/storage`. Jika route/config dicache, hapus hanya file PHP cache di `bootstrap/cache/`. Jika tampilan lama masih muncul, hapus hanya file PHP kompilasi di `storage/framework/views/`.
3. Buka detail order Laila → **Ubah Admin Penerima Pembagian**. Pilih kojelzs dan Faisal, hilangkan centang Indra.
4. Tulis alasan kesepakatan, lalu buat **pratinjau baru**. Jangan memakai pratinjau yang dibuat sebelum update.
5. Periksa bagian masing-masing Rp250.000 dan saldo Indra -Rp50.001 (bila saldo awalnya masih Rp116.666).
6. Centang konfirmasi khusus saldo minus Laila serta konfirmasi pembagian, masukkan password admin dan simpan.

Tidak perlu Artisan atau SQL manual. Saat konfirmasi yang sah, kolom saldo MySQL diperbarui agar mendukung angka bertanda (signed BIGINT), di luar transaksi uang. Koreksi saldo selanjutnya berjalan atomik: entri jurnal, riwayat dan pembagian ikut disimpan, atau semuanya dibatalkan. Akun database membutuhkan izin ALTER seperti saat aktivasi fitur sebelumnya. Jika server menolak izin, jangan mengubah saldo secara manual; koreksi belum berhasil.

## Hasil

- Indra Rp116.666 dikurangi Rp166.667 menjadi -Rp50.001, ditandai sebagai utang penyesuaian.
- Bagian Laila untuk kojelzs dan Faisal masing-masing menjadi Rp250.000. Kas riil tidak berubah.
- Penerimaan cuan Indra selanjutnya mengurangi minus secara otomatis. Contoh +Rp30.000 → -Rp20.001; +Rp70.000 lagi → Rp49.999.
- Pencairan tetap memerlukan saldo tersedia positif dan dibatasi kas nyata. Minus tidak dapat dicairkan, debit biasa tidak boleh membuat saldo negatif.
- Penerimaan pelunasan Laila selanjutnya dibagi hanya ke dua admin terpilih.
- Jika saldo/data telah berubah, hasil pratinjau mengikuti data terbaru. Kekurangan melebihi Rp50.001, pencairan yang masih ditahan, perubahan identitas, atau penggunaan ulang pengecualian akan ditolak.
- Riwayat menandai koreksi khusus dan identitas admin yang mengonfirmasi. Jangan menghapus jurnal atau mengisi saldo manual.

Kode telah diuji melalui suite Laravel/SQLite dan browser lokal. Perintah perubahan kolom MySQL diverifikasi menggunakan grammar Laravel; database produksi tidak diakses atau diubah dari komputer ini.
