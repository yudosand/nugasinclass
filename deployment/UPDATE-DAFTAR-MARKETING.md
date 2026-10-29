# Update Daftar Marketing — 15 September 2026

ZIP lengkap: **nugasinclass-full-update-cuanin-marketing-admin.zip**. Termasuk semua fitur versi sebelumnya.

## Fitur

- Menu **Daftar Marketing** pada sidebar admin, alamat `/admin/marketing`.
- Jumlah seluruh akun marketing yang belum dihapus, daftar nama, email, kode referral, jumlah client berhasil, dan tanggal pendaftaran.
- Pencarian nama, email, atau kode referral. Angka total seluruh marketing tetap terlihat ketika hasil pencarian difilter; daftar menggunakan pagination 15 akun per halaman.
- Tombol **Hapus Marketing** membuka halaman konfirmasi. Admin harus mengetik email marketing dan password admin sendiri. Client/marketing tidak dapat membuka atau menggunakan endpoint pengelolaan ini. Admin/client tidak dapat dijadikan target penghapusan marketing.

## Perilaku penghapusan

Marketing hilang dari daftar dan jumlah akun aktif; login, sesi lama, dan pemakaian kode untuk pendaftaran client baru dihentikan. Data identitas akun tetap diarsipkan sebagai `marketing_deleted` untuk menjaga hubungan dengan catatan keuangan. Nama, email, kode, order, client, komisi, pencairan, serta ledger lama tidak dihapus. Email dan kode lama tetap dicadangkan, sehingga tidak dapat dipakai ulang oleh akun lain. Ini adalah penghapusan akses dengan arsip pembukuan, bukan penghapusan seluruh baris riwayat secara permanen.

Penghapusan ditolak bila akun masih memiliki:

- saldo Cuanin atau dana yang ditahan;
- pengajuan pencairan pending;
- bonus yang belum didanai;
- order client referral yang belum disetujui, termasuk order pending/paid/rejected yang masih dapat dilanjutkan.

Selesaikan kewajiban tersebut dahulu. Pemeriksaan diulang di server ketika tombol penghapusan dikirim, memakai lock yang sama dengan pembagian komisi, pendaftaran referral, dan pencairan. Saldo yang masuk setelah halaman konfirmasi dibuka tetap melindungi akun dari penghapusan.

## Deploy tanpa terminal

1. Backup database dan file situs.
2. Upload ZIP ke root aplikasi yang berisi `.env`, `app`, `artisan`, dan `public`, lalu **Extract → Overwrite / Replace**.
3. **Pertahankan `.env`, seluruh `storage/`, `public/storage`, dan `.installed`. Jangan kosongkan folder situs.** Jangan mengganti APP_KEY.
4. Lewat File Manager, hapus hanya file kompilasi `.php` di `storage/framework/views`. Jika ada, hapus `bootstrap/cache/routes-*.php` dan `bootstrap/cache/config.php`; pertahankan folder dan `.gitignore`.
5. Muat ulang backoffice, buka **Daftar Marketing**. Jika Cuanin sebelumnya sudah aktif, tidak ada aktivasi/migrasi tambahan. Jika belum, aktifkan Cuanin dari menu yang tersedia sesuai panduan Cuanin.
6. Hapus ZIP yang diunggah setelah selesai.

**Tidak perlu php artisan.** Update ini memakai skema Cuanin yang sudah ada, tanpa kolom/tabel baru. Riwayat yang diarsipkan tidak perlu dihapus dari phpMyAdmin.

**69 pengujian / 783 assertions lulus**, termasuk 7 pengujian baru untuk daftar/pencarian, pembatasan role, konfirmasi penghapusan, pemutusan sesi, perlindungan saldo, riwayat transaksi, dan kode referral yang dinonaktifkan. Pengujian dilakukan dengan database dan data lokal, bukan pada server produksi. ZIP memuat kode dan vendor produksi serta mengecualikan konfigurasi rahasia, database, dan upload pengguna.
