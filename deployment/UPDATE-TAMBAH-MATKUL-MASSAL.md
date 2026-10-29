# Tambah banyak mata kuliah

ZIP ini berisi seluruh kode terbaru, termasuk update status sesi massal dan fitur sebelumnya. Tidak perlu Artisan atau perubahan tabel untuk fitur ini.

## Deploy

Backup aplikasi dan database. Extract ZIP ke root aplikasi (folder yang berisi artisan), lalu timpa kode. Pertahankan `.env`, `.installed`, `storage/` dan `public/storage`; jangan hapus data tersebut. Jika memakai cache route/config, hapus hanya file PHP cache di `bootstrap/cache/` melalui File Manager. Jika tampilan masih lama, hapus hanya file PHP kompilasi di `storage/framework/views/`.

## Penggunaan

1. Buka Academic Progress, lalu client yang dituju.
2. Klik **Tambah Banyak Mata Kuliah**.
3. Tempel daftar nama: satu mata kuliah per baris, maksimal 50. Kode opsional dengan format `KODE | Nama Mata Kuliah`.
4. Klik **Simpan Semua Mata Kuliah** sekali.

Setiap mata kuliah baru mendapatkan sesi 1–8 dengan status Belum Dikerjakan. Nama/kode yang sama pada client tersebut dilewati, termasuk perbedaan huruf besar/kecil atau spasi berulang. Jumlah tambahan dan duplikat ditampilkan setelah menyimpan. Progres, nilai, dan catatan mata kuliah lama tidak diubah. Semua baris diperiksa sebelum penyimpanan, dan disimpan dalam satu transaksi.

Untuk Ahmad Maulana, salin isi file pendamping MATA-KULIAH-AHMAD-MAULANA.txt ke form. Daftar delapan nama disalin dari screenshot; nomor kelas dan persentase progres e-learning tidak dimasukkan. File pendamping hanya untuk disalin, tidak perlu diunggah ke hosting. Data Ahmad di server belum berubah sampai Anda menyimpan form tersebut.
