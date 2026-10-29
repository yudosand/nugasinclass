# Perbaiki password e-learning tersimpan

ZIP berisi seluruh kode aplikasi terbaru, termasuk fitur sebelumnya. Tidak perlu menjalankan Artisan. Perubahan ini hanya memperbarui password kampus yang tersimpan di Nugasin, bukan password akun Nugasin maupun password di situs kampus.

## Deploy

1. Backup database dan aplikasi. Upload ZIP ke root aplikasi yang berisi `artisan`, extract dan timpa kode.
2. Pertahankan `.env`, `.installed`, seluruh `storage/` dan `public/storage`.
3. Jika memakai cache route/config, hapus hanya file PHP cache di `bootstrap/cache/` (pertahankan `.gitignore`). Jika tampilan lama masih muncul, hapus hanya file PHP kompilasi di `storage/framework/views/`.

## Memperbaiki salah ketik

1. Buka **Clients → detail client → Buka Akses E-Learning**.
2. Buka **Perbaiki Password E-Learning**.
3. Isi password e-learning yang benar dari client, lalu ketik ulang untuk konfirmasi. NIM/email tetap sama.
4. Isi password admin Anda dan centang konfirmasi bahwa password telah dikonfirmasi oleh client.
5. Klik **Simpan Perbaikan Password**. Setelah berhasil, semua kolom password dikosongkan.
6. Gunakan **Verifikasi & Buka Akses** jika perlu memeriksa akses terbaru. Muat ulang halaman untuk melihat riwayat perubahan terbaru.

Password disimpan terenkripsi; HTML awal, respons sukses dan riwayat perubahan tidak memuat password. Log hanya memuat identitas admin, client dan waktu. Password tidak diisi ulang setelah validasi gagal. Spasi pada password dipertahankan. Jika akses berubah sejak halaman dibuka, sistem menolak penimpaan dan meminta muat ulang.

Pembukaan akses memang mengirimkan password ke browser admin yang sudah diverifikasi; nilai yang sudah dibuka atau diketik dapat dilihat oleh pemilik browser melalui DevTools. Fitur ini tidak menjanjikan menyembunyikan rahasia dari admin yang sedang diizinkan membukanya. Gunakan HTTPS dan perangkat tepercaya.

Tabel riwayat dibuat melalui web setelah verifikasi admin saat koreksi pertama; tidak perlu SQL manual. Penyimpanan password dan log berjalan dalam satu transaksi. Jika izin CREATE tabel ditolak server, perubahan tidak tersimpan.

Pembuatan paket ini tidak mengubah password client di server. Isikan password sebenarnya di form setelah deploy, bukan di chat.
