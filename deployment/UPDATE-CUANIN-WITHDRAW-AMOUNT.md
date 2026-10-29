# Update Cuanin: Nominal Pencairan Bisa Diatur

Isi ZIP ini adalah full code update. ZIP tidak membawa `.env`, `storage/`, `public/storage`, `.installed`, atau data runtime lain.

## Cara deploy manual di CloudPanel

1. Backup file dan database produksi terlebih dahulu.
2. Di File Manager, upload ZIP ini ke root aplikasi Nugasin Class.
3. Hapus file/folder code lama yang akan ditimpa, tetapi jangan hapus:
   - `.env`
   - `.installed`
   - `storage/`
   - `public/storage`
4. Extract ZIP ini di root aplikasi.
5. Jika CloudPanel menampilkan cache lama, hapus file PHP di `bootstrap/cache/` kecuali `.gitignore`.

## Perubahan fitur

- Form Cuanin sekarang memiliki input nominal pencairan.
- Nominal boleh lebih kecil dari saldo tersedia.
- Server menolak nominal kosong, nol, negatif, atau lebih besar dari saldo tersedia.
- Saldo yang ditahan hanya sesuai nominal pengajuan, bukan otomatis seluruh saldo.
- Request key tetap idempotent: jika tombol terkirim ulang, pengajuan lama yang sama dipakai lagi, sehingga nominal tidak bisa diubah lewat retry atau inspect.
- Pesan WhatsApp memakai nominal pengajuan yang sudah divalidasi server.

## Setelah deploy

Tidak perlu menjalankan migrasi baru untuk update ini, karena tidak ada perubahan struktur database.
