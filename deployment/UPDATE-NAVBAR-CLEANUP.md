# Update Navbar & Study Landing Cleanup

Isi ZIP ini adalah full code update. ZIP tidak membawa `.env`, `.installed`, `storage/`, atau `public/storage`.

## Perubahan

- Menu **Update Sistem** di backoffice dihapus kembali.
- Route `/admin/system-update` dan file updater web dihapus dari aplikasi.
- Navbar atas dibuat lebih ringkas dan konsisten: hanya Beranda, Daftar Harga, Testimoni, dan Jadi Partner sebagai link utama.
- Link Kalkulator dan Study Tracker tidak tampil lagi di navbar atas.
- Link Study Tracker di footer diganti menjadi Jadi Partner.
- Kartu dummy **Prioritas Minggu Ini** di landing publik Study Tracker diganti menjadi penjelasan cara mulai. Data prioritas hanya tampil setelah user login dan memiliki data mata kuliah.

## Deploy manual

1. Backup file dan database produksi.
2. Upload ZIP ke root aplikasi.
3. Extract/timpa code lama, tetapi jangan hapus `.env`, `.installed`, `storage/`, dan `public/storage`.
4. Jika masih melihat tampilan lama, hapus file PHP di `bootstrap/cache/` kecuali `.gitignore`.
