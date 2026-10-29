# Koreksi admin penerima per order

Paket berisi seluruh kode fitur sebelumnya. Tidak perlu Artisan. Data server belum berubah hanya karena ZIP dibuat.

1. Backup database dan aplikasi. Upload ZIP ke root aplikasi yang berisi `artisan`, extract dan timpa kode.
2. Pertahankan `.env`, `.installed`, `storage/`, dan `public/storage`. Jangan hapus bukti atau data unggahan.
3. Jika memakai cache route/config, hapus hanya file PHP cache di `bootstrap/cache/`, pertahankan `.gitignore`. Jika tampilan masih lama, hapus hanya file PHP kompilasi di `storage/framework/views/`.
4. Buka Orders → detail Laila, nomor order **ORD-20260917-3WEYS**.
5. Buka **Ubah Admin Penerima Pembagian**, centang **kojelzs** dan **Faisal**, hilangkan centang **Indra**. Cocokkan nama akun dengan daftar admin server.
6. Isi alasan kesepakatan, klik **Pratinjau Pembagian Baru**. Periksa perubahan bagian dan saldo setiap orang.
7. Centang konfirmasi, isi password admin, lalu simpan koreksi. Riwayat dan tabel pendukung dibuat melalui web.

Untuk pembayaran Laila Rp500.000 tanpa referral/bonus: bagian baru kojelzs Rp250.000, Faisal Rp250.000, Indra Rp0. Sesuai screenshot, selisihnya kojelzs +Rp83.333, Faisal +Rp83.334, Indra -Rp166.667. Angka pratinjau mengikuti catatan nyata server, bukan angka screenshot yang ditanam dalam kode.

Harga tetap Rp850.000 dan sisa tagihan tetap Rp350.000. Jika pelunasan Rp350.000 nanti dicatat, bagian kedua admin bertambah Rp175.000 per orang (tanpa komisi/bonus lain). Pembagian khusus ini tidak mengubah order lain.

Koreksi berlaku pada seluruh bagian admin dari pembayaran order yang sudah tercatat, serta penerima pembayaran berikutnya. Komisi referral dan bonus yang sudah dibagikan tetap. Tabel pembagian menampilkan hasil terbaru; salinan pembagian lama dan perubahan saldo disimpan dalam riwayat koreksi. History Cuanin memiliki entri penyesuaian IN/OUT, tanpa menambah atau mengurangi kas riil.

Jika saldo tersedia penerima lama tidak mencukupi (sudah dicairkan atau sedang ditahan), sistem menolak seluruh koreksi. Jangan mengubah saldo langsung dari database atau mencatat pembayaran fiktif. Periksa status pencairan dan selesaikan pengembalian/kesepakatan dana terlebih dahulu; pengembalian uang nyata belum memiliki form tersendiri pada fitur ini.

Pratinjau yang tertinggal karena pembayaran, pencairan atau koreksi lain harus dibuat ulang. Pengiriman ulang konfirmasi tidak menggandakan koreksi. Pencocokan kas awal tidak perlu diulang.
