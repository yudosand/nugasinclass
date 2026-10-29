# Repository Nugasin Class

Remote: https://github.com/yudosand/nugasinclass.git, branch main.

Kode Laravel diteruskan dari riwayat website statis yang sudah ada. Tidak dilakukan force-push atau penghapusan riwayat.

## Isi repository

Source aplikasi, konfigurasi tanpa rahasia, migration, seeder, pengujian, aset publik, dependency lock dan panduan update disimpan di Git.

Yang dikecualikan: .env, token/kunci, penanda instalasi, database, storage pengguna, cache/log, vendor/node_modules, ZIP deploy, output OCR, screenshot, fixture preview, bank soal resources/exams/, dan prototipe assets/ serta HTML root.

Clone saja belum mencakup materi Simulasi UAS. Bank soal harus tetap tersedia secara terpisah di hosting dan backup privat.

## Alur perubahan

1. Periksa git status dan git diff.
2. Jalankan pengujian dengan database lokal terpisah.
3. Tambahkan file relevan dengan git add, lalu tinjau git diff --cached.
4. Buat commit dan push ke origin main.

Jangan memakai git add -f untuk melewati aturan pengabaian data privat.

## Auto-deploy

Workflow Test and deploy Nugasin kini menyiapkan deployment untuk push main melalui SSH dengan secret terenkripsi. Lihat ops/README.md untuk target server, backup dan cara menjeda deployment. Keberhasilan pemasangan harus dilihat dari hasil run GitHub Actions.

Deployment perlu mempertahankan konfigurasi/data, menyiapkan dependency produksi dan materi privat, memeriksa versi yang dipasang, serta memeriksa website setelah update. Jangan menjalankan git clean -fdx di produksi karena dapat menghapus .env, storage dan bank soal.

Tinjau perubahan skema per rilis. Rollback kode tidak otomatis membatalkan perubahan data atau skema.

## Materi dalam riwayat lama

Repository sebelumnya sudah berisi PDF dan JSON soal. Menghapus dari commit terbaru tidak menghapus commit lama, clone atau fork. Pembersihan riwayat membutuhkan pekerjaan terpisah yang disetujui pemilik repository dan koordinasi seluruh clone. Pembaruan ini tidak menulis ulang riwayat tersebut.
