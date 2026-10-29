# Update Study Tracker

ZIP ini menambahkan modul **Study Tracker** untuk mengganti arah fitur Joki Tugas.

Yang tersedia:

- Halaman publik `/study-tracker`.
- Register dan login khusus Study Tracker.
- Dashboard user dengan ringkasan mata kuliah, diskusi belum selesai, deadline dekat, overdue, dan progress.
- Input mata kuliah manual oleh user.
- Tracker sesi 1 sampai 8.
- Tugas otomatis aktif di sesi 3, 5, dan 7.
- User tetap bisa menyalakan tugas di sesi lain jika dibutuhkan.
- Kalkulator nilai sederhana dari rata-rata nilai diskusi dan tugas yang sudah diinput.
- Backoffice menu **Daftar Pengguna** untuk melihat pengguna Study Tracker.

Catatan penting:

- Study Tracker tidak membaca langsung data e-learning UT.
- Data progres berasal dari input dan checklist user.
- Google Auth belum diaktifkan di versi ini karena membutuhkan konfigurasi Client ID, Client Secret, dan callback domain dari Google Cloud.

Cara update manual CloudPanel:

1. Backup file dan database.
2. Upload dan ekstrak ZIP ini ke root aplikasi.
3. Timpa file lama, tetapi pertahankan `.env`, `storage/`, `public/storage/`, dan `.installed`.
4. Jika route/view lama masih terbaca, hapus file cache di `bootstrap/cache/*.php` melalui File Manager.
5. Buka `/study-tracker` untuk mengecek halaman publik.
