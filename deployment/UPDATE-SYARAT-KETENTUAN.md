# Persetujuan Syarat dan Ketentuan Layanan

Paket berisi seluruh kode terbaru dan fitur sebelumnya, termasuk tambah mata kuliah massal. S&K mengikuti teks yang diberikan, versi 2026-10-15.

## Deploy tanpa Artisan

Backup aplikasi dan database. Extract ZIP ke root aplikasi (folder yang berisi artisan), lalu timpa kode. Pertahankan `.env`, `.installed`, `storage/` dan `public/storage`. Jangan menghapus folder data tersebut.

Jika hosting memakai cache route/config, hapus hanya file PHP cache di `bootstrap/cache/` melalui File Manager. Jika tampilan masih lama, hapus hanya file PHP kompilasi di `storage/framework/views/`.

Tidak perlu menjalankan Artisan. Tabel `client_terms_acceptances` dibuat otomatis ketika pendaftaran client baru yang valid diproses pertama kali. Akun database aplikasi membutuhkan izin CREATE TABLE dan foreign key seperti instalasi fitur sebelumnya. Migration idempotent juga disertakan untuk instalasi normal. Jika pembuatan tabel gagal, pendaftaran tidak disimpan tanpa catatan persetujuan.

## Perilaku

- Halaman publik `/syarat-ketentuan` berisi enam bagian S&K lengkap. Tautan tersedia pada pendaftaran client dan footer; menu navbar atas tetap.
- Pada `/register`, client harus mencentang persetujuan sebelum melanjutkan pembayaran. Kotak tidak dicentang otomatis, termasuk saat form ditampilkan ulang setelah kesalahan.
- Tautan S&K membuka tab baru supaya isian pendaftaran tidak hilang. Tidak ada syarat scroll palsu yang dianggap sebagai bukti membaca.
- Server menolak persetujuan kosong/tidak disetujui dan versi S&K yang berbeda. Menghapus atribut required melalui browser tidak melewati validasi server.
- Akun, order, dan catatan persetujuan disimpan dalam satu transaksi. Versi, hash dokumen, dan waktu persetujuan berasal dari server.
- Admin dapat melihat tanggal dan versi persetujuan pada detail client. Akun lama tidak ditandai menyetujui secara otomatis.
- Persetujuan akses e-learning pada onboarding tetap terpisah. Registrasi marketing dan Study Tracker tidak diubah karena dokumen ini khusus layanan klien.

Untuk revisi S&K di masa depan, buat file dokumen versi baru dan ubah versi aktif di ServiceTerms. Pertahankan file versi lama agar catatan persetujuan dapat dicocokkan dengan dokumen yang disetujui.

ZIP belum mengubah website produksi sampai diunggah ke hosting.
