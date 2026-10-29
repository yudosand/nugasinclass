# Simulasi UAS — ujian interaktif dan mode belajar

Paket ini berisi seluruh kode aplikasi terbaru, vendor produksi, dan materi privat hasil pengolahan ZIP soal. Dibuat untuk memperbarui website Nugasin yang sudah terpasang. Website produksi dan database hosting belum diubah oleh proses pembuatan paket ini.

## Pembaruan admin 29 Oktober 2026

- **Simpan Semua Paket:** ubah harga, durasi, jumlah soal dan status di beberapa kartu, lalu tekan satu tombol di atas atau bawah daftar. Seluruh paket disimpan dalam satu transaksi. Jika ada input tidak valid, berkas paket aktif belum lengkap, atau pengaturan telah diubah admin lain, penyimpanan dibatalkan seluruhnya. Harga order yang sudah dibuat tetap.
- **Demo dengan akun admin:** buka Paket Simulasi UAS, pilih **Demo Belajar** atau **Demo Ujian** pada kartu. Paket draft juga dapat diuji. Akun admin yang aktif juga dapat login melalui `/simulasi/masuk`; tidak perlu membuat akun demo terpisah atau membeli paket. Riwayat demo tersimpan pada akun admin sendiri, tanpa membuat order, pembayaran, keanggotaan pembeli, atau pembagian Cuanin. Tombol **Demo & Riwayat Ujian Saya** membuka daftar demo dan hasilnya.
- **Hapus client batal membeli:** order berstatus rejected yang belum pernah menerima pembayaran tidak lagi dianggap sebagai utang yang menghalangi penghapusan. Akun dengan sisa tagihan dari order lain, pembayaran sebagian, atau riwayat penerimaan yang tidak konsisten tetap dilindungi. Konfirmasi memakai nomor HP client yang dihapus dan password admin yang sedang login. Jika browser mengisi nomor HP admin otomatis, ganti dengan nomor HP client yang ditampilkan pada halaman.

Untuk pembaruan dari ZIP Simulasi UAS sebelumnya, cukup timpa file aplikasi sesuai langkah pemasangan. **Tidak ada perubahan skema database baru, tidak perlu impor ulang paket atau menjalankan Artisan.** Jangan menghapus data transaksi untuk melewati pemeriksaan penghapusan client.

## Pemasangan lewat File Manager

1. Backup database dan aplikasi hosting terlebih dahulu. Simpan backup di luar folder publik.
2. Upload lalu extract ZIP ke **root aplikasi yang berisi `artisan`**, bukan ke dalam `public`. Timpa seluruh file yang terdapat dalam ZIP.
3. **Pertahankan `.env`, `APP_KEY` yang lama, `.installed`, seluruh `storage/`, dan `public/storage`. Jangan hapus folder aplikasi beserta data tersebut.** ZIP tidak berisi data pengguna, kredensial hosting, database lokal, ataupun file unggahan pengguna.
4. Pastikan document root Nginx/CloudPanel tetap menunjuk ke subfolder **`public`**, HTTPS aktif, `APP_URL=https://nugasinclass.web.id`, dan `APP_DEBUG=false`. Jangan membuat alias publik ke `resources`, `storage/app`, atau root aplikasi.
5. PHP minimal 8.2 dan **ekstensi GD** harus aktif untuk watermark. Jika GD belum aktif, aktifkan melalui konfigurasi PHP hosting/CloudPanel. Tidak diperlukan Python, OCR, Node, atau PDF renderer di hosting; semua gambar sudah disiapkan dalam ZIP.
6. Bila route/config lama dicache, hapus hanya file `.php` cache di `bootstrap/cache/` melalui File Manager; pertahankan folder dan `.gitignore`. Bila tampilan masih lama, hapus hanya file `.php` kompilasi di `storage/framework/views/`. Restart PHP melalui panel hanya jika OPcache hosting menahan kode lama.
7. Login admin → **Paket Simulasi UAS** → **Impor Bank Soal Bawaan**. Tabel baru dibuat otomatis saat menu dibuka; akun database membutuhkan izin CREATE TABLE dan foreign key. Impor dapat diulang tanpa menggandakan paket atau mengganti harga yang telah diatur.
8. Periksa materi melalui **Tinjau / Koreksi Soal**, tentukan harga, durasi, jumlah soal, lalu ubah status paket menjadi **Aktif / dijual**. Paket hasil impor awal adalah **draft** dengan harga contoh **Rp15.000**, bukan harga jual yang ditetapkan untuk Anda. Sistem menolak aktivasi jika GD atau berkas paket belum lengkap.
9. Uji satu pembelian melalui `/simulasi`. Setelah pembayaran benar-benar diterima, buka **Client Simulasi UAS → Verifikasi Pembayaran**, catat nominal dan referensi transfer pada form kas seperti order lain. Paket terbuka setelah lunas dan disetujui.

**Untuk website yang sudah terpasang ini tidak perlu menjalankan Artisan.** Migration idempotent tetap disertakan untuk instalasi/pipeline normal. Tidak ada menu Update Sistem baru atau installer publik. Jika kas belum pernah diaktifkan pada instalasi lama, selesaikan pencocokan kas awal yang sudah tersedia sebelum menerima pembayaran.

ZIP tidak mengandung konfigurasi Nginx dan tidak dapat memperbaiki document root/HTTPS yang salah secara otomatis. Hapus ZIP yang telah diunggah dari hosting setelah selesai extract. Jika memakai CDN/cache statis, purge `/assets/pdf/*` dan `/assets/data/*` agar salinan materi publik lama tidak tersaji dari cache.

## Alur pembeli

- Daftar melalui `/simulasi/daftar` dengan nama, nomor HP, email opsional, password dan persetujuan S&K. Akun client/Study Tracker yang sudah ada dapat memakai login yang sama.
- Pilih paket, bayar menggunakan QRIS yang sudah ada, unggah bukti, lalu buka WhatsApp admin. Pesan memuat nomor order, nama dan paket. Pengguna sendiri mengirim pesan WhatsApp; aplikasi tidak mengirim pesan tanpa tindakannya.
- Akun boleh login untuk melihat tagihan, tetapi soal tetap terkunci sebelum pembayaran penuh disetujui. Pembayaran sebagian tidak membuka akses Simulasi UAS, meskipun layanan akademik lain mengizinkan cicilan.
- Setelah disetujui, **Paket UAS Saya** menyediakan mode belajar dan ujian. Paket tambahan membutuhkan pembelian sendiri. Harga order lama tetap walaupun harga katalog diubah.
- Mode belajar menampilkan halaman materi asli beserta jawaban yang ada pada sumber, dengan tombol halaman dan perbesar gambar.
- Ujian memilih soal acak, menyimpan jawaban di server, memiliki batas waktu dan dapat dilanjutkan. Timer/skor ditentukan server. Soal dengan kunci tersedia dinilai otomatis; uraian dan soal tanpa kunci tersimpan untuk tinjauan mandiri. Riwayat hasil dapat dibuka kembali.
- Ini simulasi latihan mandiri, bukan sistem pengawasan ujian resmi. Pembeli tetap dapat mempelajari sumber yang sudah dibelinya.

## Admin dan pencatatan uang

- **Paket Simulasi UAS**: harga, draft/aktif, durasi, jumlah soal; pratinjau materi; koreksi teks/pilihan/kunci atau nonaktifkan soal. Perubahan soal berlaku pada ujian baru, bukan salinan ujian yang sudah dimulai.
- **Client Simulasi UAS**: jumlah pengguna terdaftar, pencarian, paket/order, pembayaran, dan status akses; termasuk akun yang belum membeli paket.
- Pembayaran terverifikasi masuk ke kas dan pembagian Cuanin yang sudah ada. Pembayaran ulang dengan referensi/request yang sama tidak menggandakan penerimaan.
- Status akses dihitung dari kepemilikan paket, persetujuan order, dan pelunasan; membuka URL paket milik orang lain ditolak.

## Isi dan mutu materi

Lihat **INVENTARIS-SOAL-UAS.md** untuk jumlah per paket. Seluruh 42 PDF/1.400 halaman tersedia dalam mode belajar sebagai gambar privat. Dokumen dengan kode mata kuliah sama dikelompokkan ke satu paket sehingga terdapat 40 paket.

Konversi interaktif mempertahankan gambar soal, tabel, rumus, dan pilihan sejauh format sumber dapat dipisahkan. Pilihan berwarna yang menunjukkan jawaban pada sumber dipisahkan menjadi teks. Sebagian halaman uraian berisi beberapa nomor dalam satu lembar jawaban. Materi yang belum dapat dipisahkan menjadi soal mandiri tetap tersedia di mode belajar.

**Hasil ekstraksi/OCR belum menggantikan pemeriksaan akademik/editorial.** Beberapa soal merujuk gambar, bacaan, atau konteks halaman lain. Periksa dan nonaktifkan/perbaiki soal tersebut melalui editor sebelum menjual paket. Kunci yang belum terbaca atau tidak konsisten tidak ditebak dan tidak masuk skor otomatis. Sebagian dokumen memakai kunci hasil analisis, dan sebagian uraian tidak menyertakan jawaban; sistem tidak mengarang jawaban yang tidak ada. Jumlah soal yang dinilai otomatis ditampilkan sebelum pembelian dan pada hasil ujian.

## Perlindungan materi dan batasnya

- PDF asli dan JSON kunci baru tidak diletakkan di `public` dan tidak dikirim ke browser. Materi privat berada di `resources/exams`.
- Setiap permintaan gambar memeriksa login, pemilik pembelian, pelunasan, dan sesi perangkat. Watermark akun dibakar ke piksel gambar; bukan sekadar lapisan HTML yang bisa dihapus lewat Inspect.
- Satu sesi materi aktif per akun. Perangkat lain harus menunggu akses ditutup atau 10 menit tanpa aktivitas. Halaman yang sedang terlihat mengirim heartbeat agar sesi membaca/menulis panjang tetap aktif.
- Respons privat tidak dicache. Halaman materi memakai CSP, menolak iframe, menyembunyikan konten saat tab ditinggalkan, serta membatasi cetak/drag/klik kanan sebagai penghambat sederhana.
- **Screenshot OS, rekaman layar, kamera, berbagi akun secara bergantian, dan penyalinan gambar oleh pembeli sah tidak bisa dicegah 100% oleh website.** Inspect tetap bisa melihat konten yang memang dikirim untuk pengguna berhak. Perlindungan ini membatasi akses dan memberi identitas pada salinan; bukan jaminan materi mustahil dibocorkan.
- Tujuh PDF demo dan delapan JSON kunci publik versi lama diganti dengan berkas kosong/pesan retired pada path yang sama. File penutup ini sengaja disertakan agar deploy dengan cara timpa turut menutup URL lama. Jangan mengunggah ulang PDF/JSON demo dari ZIP terdahulu. Salinan yang pernah diunduh orang sebelumnya tidak bisa ditarik kembali.

## Validasi sebelum rilis

Pengujian otomatis mencakup persetujuan S&K, harga dari server, pembelian berulang, kepemilikan materi, pembayaran sebagian/lunas, Cuanin, satu perangkat aktif, batas waktu, penilaian di server, jawaban uraian, koreksi soal, dan pembatasan admin. Alur browser diuji pada tampilan desktop dan ponsel. Ini pengujian aplikasi lokal; bukan klaim audit penetrasi menyeluruh pada hosting produksi.
