# Update Lepas Handler Client

ZIP ini menambahkan tombol **Lepas Client** pada fitur handler Academic Progress.

Yang berubah:

- Admin yang sedang handle client bisa menekan **Lepas** / **Lepas Client**.
- Setelah dilepas, client kembali ke status belum di-handle.
- Admin lain bisa mengambil alih client tersebut dengan tombol **Ambil Client**.
- Admin yang bukan handler client tersebut tidak bisa melepasnya.

Catatan deployment:

- Upload dan ekstrak ZIP ke root aplikasi.
- Timpa file lama, tetapi tetap pertahankan `.env`, `storage/`, `public/storage/`, dan `.installed`.
- Jika route lama masih terbaca, hapus file cache di `bootstrap/cache/*.php` melalui File Manager.
