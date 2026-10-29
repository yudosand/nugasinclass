# Update Client Handler Academic Progress

ZIP ini menambahkan fitur handler client di Backoffice.

Yang berubah:

- Di menu Academic Progress ada tombol **Ambil Client** untuk client yang belum di-handle admin.
- Setelah diambil, admin lain akan melihat siapa admin yang handle client tersebut.
- Dashboard Backoffice menampilkan kolom **Handler** di tabel progres client.
- Detail client juga menampilkan admin yang sedang handle client.

Cara update manual CloudPanel:

1. Backup file dan database terlebih dahulu.
2. Upload dan ekstrak ZIP ini ke root aplikasi.
3. Timpa file lama, tetapi tetap pertahankan `.env`, `storage/`, `public/storage/`, dan `.installed`.
4. Buka Backoffice lalu masuk ke menu Dashboard atau Academic Progress. Sistem akan memastikan kolom handler tersedia.

Catatan:

- Tidak perlu menampilkan atau menyimpan password client.
- Tombol Ambil Client hanya bisa dipakai admin yang sedang login.
- Client yang sudah di-handle admin lain tidak bisa diambil ulang dari tombol ini.
