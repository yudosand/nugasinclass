# Reset password semua jenis akun

ZIP ini mencakup seluruh fitur sebelumnya, termasuk Study Tracker, format Rupiah pencairan, dan perbaikan kalkulator.

## Deploy manual

1. Backup file dan database.
2. Upload ZIP ke root aplikasi yang berisi `artisan`, lalu extract/timpa kode.
3. Pertahankan `.env`, `.installed`, seluruh `storage/`, dan `public/storage`.
4. Hapus hanya file PHP cache di `bootstrap/cache/` (pertahankan `.gitignore`) agar route baru terbaca jika sebelumnya menggunakan route cache. Jika tampilan lama masih muncul, hapus hanya file PHP hasil kompilasi di `storage/framework/views/`.
5. Refresh browser.

Tidak ada perubahan skema database atau perintah Artisan yang diperlukan. Tidak ada installer atau menu Update Sistem yang ditambahkan.

## Memulihkan admin yang lupa password

1. Rekan admin yang masih bisa login membuka **Daftar Admin**.
2. Klik **Reset Password** pada akun yang lupa password.
3. Verifikasi identitas pemilik akun, centang konfirmasi, kemudian masukkan password rekan admin yang sedang login.
4. Klik **Buat Tautan Reset Password**, salin tautan, lalu kirim secara pribadi kepada pemilik akun.
5. Pemilik akun membuka tautan saat belum login (bisa memakai jendela incognito), membuat password baru minimal 8 karakter, lalu login menggunakan nomor HP seperti biasa.

## Client, marketing, dan Study Tracker

- Semua halaman login menyediakan **Lupa password?**, menuju permintaan verifikasi melalui WhatsApp 0858-6101-5161.
- Client: **Clients → Detail Client → Bantu Client Lupa Password**.
- Marketing: **Daftar Marketing → Reset Password**.
- Study Tracker: **Daftar Pengguna → Detail Pengguna → Reset Password**.
- Password baru admin, marketing, dan Study Tracker minimal 8 karakter; minimum client tetap 12 karakter.
- Tautan berlaku 60 menit, sekali pakai, dan hanya untuk akun tujuan. Membuat tautan baru membatalkan tautan sebelumnya.
- Password baru diisi sendiri oleh pemilik akun. Password tersimpan sebagai hash; password lama tidak dapat dilihat.
- Reset mengganti token ingat-saya dan membuat sesi lama tidak berlaku pada akses berikutnya. Role, status akun, data, dan saldo tidak berubah.
- Jika semua admin kehilangan akses, alur bantuan admin ini tidak dapat digunakan. Pemulihan harus dilakukan oleh pemilik hosting melalui akses CloudPanel/database; ZIP ini tidak menambahkan jalan pintas login publik.
