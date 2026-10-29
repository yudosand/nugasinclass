# Update status sesi untuk semua mata kuliah

Paket berisi seluruh fitur sebelumnya. Tidak perlu Artisan atau perubahan tabel database.

## Deploy

Backup database dan aplikasi, lalu extract ZIP ke root aplikasi yang berisi `artisan` dan timpa kode. Pertahankan `.env`, `.installed`, `storage/` dan `public/storage`. Jika memakai cache route/config, hapus hanya file PHP cache di `bootstrap/cache/` (pertahankan `.gitignore`). Jika tampilan masih lama, hapus hanya file PHP kompilasi di `storage/framework/views/`.

## Cara menggunakan

1. Buka **Academic Progress → Input & Update Progress** pada client yang dimaksud.
2. Di atas daftar mata kuliah, tersedia **Update Sesi Semua Mata Kuliah**.
3. Pilih sesi 1–8. Pilih status Diskusi dan Tugas: Belum Dikerjakan, On Progress, Menunggu Nilai, atau Selesai. Default kedua status adalah Selesai.
4. Klik **Terapkan ke … Mata Kuliah** sekali. Status diterapkan ke sesi yang dipilih pada seluruh mata kuliah client itu.

Contoh: semua diskusi dan tugas sesi 2 sudah dikerjakan → pilih Sesi 2, Diskusi Selesai, Tugas Selesai, lalu klik Terapkan.

Pilih **Biarkan status saat ini** jika salah satu jenis status tidak ingin diubah. Setidaknya satu jenis harus diperbarui. Nilai dan catatan lama tidak berubah; sesi lain, client lain, dan Study Tracker pribadi tidak terpengaruh. Form per mata kuliah tetap tersedia untuk pengecualian atau perbaikan individual.

Ringkasan dashboard mengikuti data yang diperbarui. Jumlah sesi selesai tetap dihitung berurutan dari sesi 1; menyelesaikan sesi 2 saja tidak berarti sesi 1 otomatis selesai.

Aksi ini mencatat progres di Nugasin, bukan mengerjakan atau memverifikasi aktivitas pada situs e-learning. Admin menggunakannya setelah memastikan pekerjaan memang sesuai status yang dipilih.

Perubahan seluruh mata kuliah disimpan dalam satu transaksi; jika penyimpanan gagal, tidak ada sebagian mata kuliah yang terlanjur berubah. Hanya admin yang dapat menggunakan aksi ini. Data produksi belum berubah saat ZIP dibuat.
