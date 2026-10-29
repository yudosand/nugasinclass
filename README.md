# Nugasin Class

Aplikasi Laravel untuk backoffice, client, progres akademik, Study Tracker, marketing/referral, Cuanin, kas/pembayaran bertahap, dan Simulasi UAS.

## Persyaratan dan pengujian

PHP 8.2+, Composer, ekstensi sesuai composer.json, dan PHP GD untuk watermark soal. PHP ZIP diperlukan untuk pembuatan paket deploy. MySQL untuk produksi, SQLite dapat digunakan untuk pengujian lokal. Document root harus menunjuk ke public/.

Untuk instalasi lokal baru: composer install, salin .env.example ke .env, atur database lokal, lalu jalankan php artisan key:generate dan php artisan migrate. Jalankan php artisan serve untuk preview dan php vendor/bin/phpunit untuk pengujian. Jangan gunakan database produksi untuk tes atau mengganti APP_KEY instalasi yang sudah berisi data terenkripsi.

## Materi privat

Bank soal berbayar resources/exams/ tidak disimpan di Git. Salin manifest.json, questions.json, gambar halaman dan gambar soal dari paket deploy privat ke folder tersebut sebelum impor atau aktivasi paket. Jangan menyediakan alias publik ke folder itu.

File dalam public/assets/pdf/ dan public/assets/data/ merupakan penonaktifan materi publik lama; pertahankan saat update hosting lama. Prototipe HTML dan assets/ pada root tidak lagi dilacak. Materi yang sudah ada dalam commit lama tetap ada di riwayat Git.

## Deployment

Lihat [Panduan Git](deployment/PANDUAN-GIT.md) dan [Pembaruan Simulasi UAS](deployment/UPDATE-SIMULASI-UAS.md).

Workflow auto-deploy tersedia untuk push ke main: tes PHP 8.5, build produksi, upload SSH, backup kode, pemasangan dan pemeriksaan website. Lihat [panduan auto-deploy](ops/README.md). Status setiap deployment terlihat di GitHub Actions.

Jangan commit .env, APP_KEY, token, private key, database, file unggahan, log, backup atau ZIP deploy. Saat deployment pertahankan .env, storage/, public/storage, .installed, dan bank soal privat. Jangan menjalankan git clean -fdx di aplikasi produksi.

Pembuatan admin pertama melalui php artisan db:seed memerlukan INITIAL_ADMIN_PHONE dan INITIAL_ADMIN_PASSWORD (minimal 12 karakter) pada .env lokal. INITIAL_ADMIN_NAME dan INITIAL_ADMIN_EMAIL dapat diatur juga. Tidak ada password admin bawaan. Hapus nilai konfigurasi awal tersebut setelah akun dibuat; seeder tidak mengubah admin yang sudah ada.
