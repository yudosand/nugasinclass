# Update Marketing & Cuanin — Nugasin Class

Paket penuh: **nugasinclass-full-update-cuanin.zip**. Domain tetap **https://nugasinclass.web.id**, halaman partner **/marketing**. ZIP berisi seluruh kode aplikasi dan vendor produksi, termasuk fitur sebelumnya. Tidak berisi database, konfigurasi rahasia, atau file upload client.

## Deploy melalui File Manager, tanpa SSH / artisan

1. Backup database melalui phpMyAdmin dan backup file situs sebelum update.
2. Upload ZIP ke root aplikasi, yaitu folder berisi `.env`, `artisan`, `app`, dan `public`. Extract dengan **Overwrite / Replace**. Jangan mengekstrak ke dalam `public`.
3. **Jangan hapus `.env`, seluruh `storage/`, `public/storage`, atau `.installed`. Jangan kosongkan seluruh folder situs.** Data foto/bukti dan sesi ada di `storage`; `APP_KEY` lama diperlukan untuk membuka data terenkripsi.
4. Melalui File Manager, hapus hanya file kompilasi `.php` dalam `storage/framework/views`. Jika ada, hapus `bootstrap/cache/config.php` dan `bootstrap/cache/routes-*.php`. Pertahankan folder dan `.gitignore`.
5. Login sebagai admin, buka menu **Cuanin**. Pada pemasangan pertama, baca aturan yang ditampilkan, centang persetujuan, isi password admin saat ini, lalu klik **Aktifkan Cuanin & Marketing**. Aktivasi membuat tabel dan menyesuaikan kolom akun lewat aplikasi. Tidak perlu menjalankan `php artisan` atau installer lagi. Database user perlu izin CREATE/ALTER; jika aktivasi terhenti, periksa izin dan ulangi. Jangan menghapus database.
6. Buka **https://nugasinclass.web.id/marketing**, uji pendaftaran/login partner. Cek kembali nominal dan penerima sebelum menyetujui pembayaran nyata pertama.
7. Hapus ZIP unggahan setelah selesai. Jika halaman masih memakai kode lama setelah cache dibersihkan, restart PHP melalui pengaturan CloudPanel bila tersedia.

Root Directory CloudPanel tetap menunjuk folder `public` aplikasi. Gunakan HTTPS dengan sertifikat valid dan `APP_URL=https://nugasinclass.web.id`, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`. Jangan mengganti `APP_KEY` saat update. Screenshot sebelumnya menunjukkan peringatan sertifikat; validitas SSL server harus diselesaikan melalui SSL/TLS CloudPanel jika masih muncul.

## Semua fitur dalam update ini

- Halaman marketing dengan poster asli, komisi 10%, seluruh tingkat bonus, harga layanan, cara bergabung, dan tombol daftar/login.
- Pendaftaran partner hanya nama, email, kode referral buatan sendiri, dan password (minimal 8 karakter). Kode 4–24 karakter, unik, huruf/angka/-/_. Login marketing memakai email.
- Dashboard marketing hanya Cuanin dan Client. Daftar client dibatasi ke pemakai kode miliknya, menampilkan nama, paket, order, nominal, dan status. Marketing tidak memperoleh data rekening orang lain, kontak client, bukti pembayaran, atau akses e-learning.
- Cuanin admin/marketing: saldo tersedia, saldo ditahan, rekening, kode/tautan referral, riwayat IN/OUT, pengajuan pencairan, dan status proses.
- Kode referral opsional pada pendaftaran client. Tautan referral mengisi kode otomatis. Admin juga memperoleh kode sendiri melalui Cuanin.
- Pembagian otomatis ketika admin menyetujui pembayaran yang sudah memiliki bukti. Rincian tersimpan di Orders/Payments: harga transaksi, komisi referral, bonus, total bagian admin, serta nominal masing-masing penerima.
- Admin memproses pencairan melalui Cuanin → Kelola Pengajuan Pencairan. Perubahan rekening, pengajuan, dan penyelesaian memerlukan password akun pelaku.

## Aturan uang yang digunakan

Aturan ini juga ditampilkan pada layar aktivasi agar dapat ditinjau sebelum transaksi baru dibukukan.

1. Uang dihitung dalam **Rupiah bulat**. Upload bukti belum menghasilkan saldo; approval admin menjadi titik pembukuan. Klik Approve ulang tidak menghasilkan kredit lagi.
2. Komisi pemilik referral adalah **10% dari nominal order**, dibulatkan ke bawah ke Rupiah. Tanpa referral komisi nol.
3. Bonus marketing mengikuti **total kumulatif sepanjang umur akun**, berdasarkan client unik yang pembayarannya disetujui: 10=Rp50.000, 20=Rp100.000, 30=Rp200.000, 40=Rp300.000, 50=Rp500.000, 75=Rp750.000, 100=Rp1.000.000. Pada kenaikan tingkat hanya selisih target yang ditambahkan. Contoh: setelah menerima Rp50.000 pada 10 client, mencapai 20 client menambah Rp50.000. Order berulang dari client sama tidak menambah jumlah client unik.
4. Komisi dipotong lebih dahulu, lalu bonus yang sudah menjadi hak marketing didanai dari sisa penerimaan. Jika dana belum cukup, kekurangan tercatat sebagai **bonus belum didanai** dan didanai dari transaksi berikutnya menurut urutan bonus terlama. Bonus belum didanai belum dapat dicairkan. Pendanaan bonus dapat membuat bagian admin pada transaksi tertentu menjadi nol.
5. Sisa setelah komisi dan bonus dibagi rata ke seluruh akun admin berstatus approved **saat approval**, bukan berdasarkan jumlah admin sekarang. Sisa pembulatan Rp1 diberikan menurut urutan ID admin. Penambahan admin hanya memengaruhi transaksi berikutnya; rincian lama tetap.
6. Contoh tanpa bonus, 3 admin: order Rp500.000 tanpa referral → Rp166.667/Rp166.667/Rp166.666. Dengan referral → pemilik kode Rp50.000, masing-masing admin Rp150.000.
7. Transaksi yang **sudah approved sebelum pemasangan Cuanin tidak dibagikan ulang**. Order lama yang baru di-approve setelah aktivasi menggunakan aturan baru. Tidak ada saldo historis yang ditebak atau diisi otomatis.
8. Pembayaran approved tidak dapat ditolak atau diunggah ulang untuk mengulang pembagian. Penghapusan client tidak menghapus ledger, pembagian yang telah tercatat, atau hitungan referral berhasil. Paket yang memiliki order harus dinonaktifkan, bukan dihapus. Fitur refund/reversal belum termasuk update ini.

## Alur pencairan

Isi rekening → masukkan password → **Ajukan Pencairan**. Sistem menahan seluruh saldo tersedia dan langsung membuka WhatsApp **+62 858-6101-5161** dengan nominal, nama bank, nomor rekening, nama pemilik rekening, dan nomor pengajuan. Anda tetap menekan Kirim di WhatsApp. Bila WhatsApp tidak terbuka, buka riwayat pengajuan lalu gunakan tombol kirim ulang.

Pengajuan tidak melakukan transfer otomatis. Setelah transfer bank benar-benar selesai, admin membuka detail pengajuan, memasukkan referensi transfer, password, dan konfirmasi lalu mencatat selesai. Baru saat itu ledger mencatat OUT. Jika ditolak, saldo ditahan dilepas tanpa membuat OUT. Satu wallet hanya memiliki satu pengajuan pending; saldo baru tetap dapat masuk selama menunggu. Nomor rekening pengajuan disimpan sesuai saat diajukan, sehingga edit rekening berikutnya tidak mengubah tujuan pengajuan lama.

## Pengamanan dan pemeriksaan

- Pengecekan role, pemilik wallet, dan referral dilakukan di server; nominal pencairan tidak dipercaya dari browser.
- Password akun di-hash. Rekening dan salinan rekening pencairan dienkripsi menggunakan APP_KEY; tidak dimasukkan ke serialisasi model. Password tidak dimunculkan kembali dalam HTML setelah validasi gagal. Nomor rekening pada ringkasan dimasking; detail pencairan hanya untuk pemilik dan admin.
- CSRF, pembatasan percobaan, verifikasi ulang password, session regeneration, dan respons tanpa cache melindungi alur sensitif. Bootstrap untuk formulir disajikan dari aset lokal dengan versi/hash yang diverifikasi.
- Pembagian dan perubahan status order berada dalam satu transaksi database dengan lock dan kunci unik. Pengajuan/pencairan berulang tidak menggandakan saldo. Pengujian juga memeriksa rollback ketika pembagian gagal.
- Pengujian otomatis menggunakan SQLite dan fixture lokal; pemeriksaan browser dilakukan pada server lokal. Server produksi/MySQL dan transfer bank nyata tidak dijalankan dari lingkungan ini. Keamanan tetap bergantung pada konfigurasi HTTPS, izin server, kerahasiaan APP_KEY/kredensial, dan pengelolaan akun admin; tidak ada jaminan bebas celah mutlak.

**62 pengujian / 684 assertions lulus**, meliputi seluruh fitur sebelumnya, referral, pembagian, bonus, saldo ditahan, pencairan sekali, pembatasan akses, rollback, dan upgrade skema lama. ZIP diverifikasi integritasnya per file, vendor produksi cocok dengan composer.lock, dan file runtime/rahasia dikecualikan. File `.sha256` di sebelah ZIP dapat digunakan untuk memeriksa hasil unduhan.
