# Pencarian, upload bukti, dan transaksi uji Simulasi UAS

Paket pada `/simulasi` dapat dicari berdasarkan nama mata kuliah atau kode. Form daftar mempunyai tombol tampil/sembunyikan untuk kedua kolom password.

Upload bukti tetap memakai validasi server JPG/PNG/PDF maksimal 5 MB dan penyimpanan privat. Browser membaca file terlebih dahulu, menampilkan progres unggahan, serta memberi pesan kegagalan tanpa meninggalkan formulir. File tidak disimpan jika format, ukuran, kepemilikan order, atau sesi tidak valid. Form POST biasa tetap bekerja tanpa JavaScript.

## Pengujian tanpa pembayaran nyata

1. Daftar sebagai peserta melalui `/simulasi/daftar`, lalu pilih paket.
2. Di browser/profil lain, login sebagai admin. Buka order peserta melalui Orders atau Client Simulasi UAS.
3. Pada **Uji Alur Pembelian**, pilih **Tandai Sebagai Transaksi Uji**, isi alasan, password admin, dan persetujuan.
4. Peserta muat ulang halaman pembayaran, upload gambar/PDF contoh bertuliskan UJI, lalu buka konfirmasi WhatsApp. Pesan menyatakan bahwa tidak ada transfer uang; pengiriman pesan dilakukan pengguna di WhatsApp.
5. Admin membuka kembali order, mengisi nominal simulasi (misalnya 1000), lalu memilih **Setujui Akses Uji**.
6. Peserta membuka **Paket Saya** untuk mode belajar atau ujian interaktif.
7. Setelah selesai, admin dapat memilih **Cabut Akses Uji**. Akses materi dan hasil ujian terlindungi ikut ditolak setelah pencabutan.

Nominal simulasi disimpan sebagai catatan uji saja. Harga paket tidak diubah. Order uji dikecualikan dari piutang, pencocokan kas awal, dan jumlah order/pembayaran riil pada dashboard. API pembayaran nyata, koreksi harga, dan pembagian Cuanin menolak order uji. Penerimaan atau pembagian yang sudah terverifikasi tidak dapat dikonversi menjadi order uji.

Penandaan tidak dapat dibatalkan menjadi transaksi nyata; gunakan pembelian terpisah untuk transaksi nyata. Riwayat mencatat admin penanda, alasan, waktu, nominal simulasi, admin pemberi akses, dan pencabutan. Persetujuan ulang tidak menggandakan data.

Tabel `exam_test_orders` disiapkan saat tindakan admin pertama kali, di luar transaksi keuangan. Migrasi juga tersedia untuk instalasi baru. Update melalui workflow deploy yang sudah ada tidak membutuhkan perintah Artisan manual.

## Validasi

- PHPUnit: pencarian, pendaftaran, pembelian, pembatasan akses, unggahan privat, validasi berkas, persetujuan uji, ujian, pencabutan, serta kas/Cuanin yang tetap tidak berubah.
- Browser lokal: tampilan mobile, kedua tombol password, upload yang sengaja diputus lalu dicoba ulang, tautan WhatsApp bertanda UJI, approval admin, mode belajar dan ujian. Tidak mengirim pesan WhatsApp nyata.
- Pada diagnosis produksi, percobaan upload pada screenshot tidak tercatat sebagai POST di log server. Penyebab browser/perangkat asli belum dapat dipastikan; alur baru menyediakan pesan yang dapat ditindaklanjuti jika kegagalan terulang.
