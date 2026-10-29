# Kas Nugasin dan pembayaran bertahap

Paket ini mencakup seluruh fitur sebelumnya. Tidak perlu SSH, terminal, atau menjalankan Artisan. Data produksi belum berubah hanya karena ZIP dibuat di komputer ini.

## Deploy melalui CloudPanel

1. Backup database melalui Export phpMyAdmin dan backup folder aplikasi.
2. Upload ZIP ke root aplikasi (folder yang berisi `artisan`), extract, lalu timpa kode lama.
3. Pertahankan `.env`, `.installed`, seluruh `storage/`, dan `public/storage`.
4. Hapus hanya file cache PHP di `bootstrap/cache/` jika sebelumnya memakai cache route/config (pertahankan `.gitignore`). Bila tampilan lama masih muncul, hapus hanya file PHP hasil kompilasi di `storage/framework/views/`. Jangan hapus folder storage atau bukti pembayaran.
5. Masuk sebagai admin → **Orders → Cocokkan Kas Awal**.
6. Masukkan password admin dan klik **Mulai Pencocokan Kas**. Tabel kas dibuat melalui web; belum ada saldo yang berubah. Selama proses ini, pengajuan pencairan baru dan persetujuan lama ditunda. Pengajuan pencairan yang sudah pending harus diselesaikan atau ditolak terlebih dahulu melalui Cuanin.
7. Isi nominal **sudah diterima** per order serta **total pencairan selesai** per admin/marketing. Cocokkan seluruh order yang tampil, termasuk transaksi tambahan setelah laporan berikut dibuat.
8. Klik **Lihat Pratinjau, Belum Simpan**, periksa saldo kas dan saldo Cuanin setiap orang. Centang konfirmasi, masukkan password, lalu simpan hanya jika semua sudah cocok.

Pencocokan awal hanya sekali. Sistem menolak pratinjau yang sudah tertinggal karena transaksi/data berubah. Histori lama tidak dihapus; perubahan saldo Cuanin diberi entri penyesuaian. Penerimaan dan pencairan awal dicatat sebagai rekap saat konfirmasi, bukan sebagai tanggal transfer individual yang tidak diketahui. Hasilnya dapat dibuka kembali melalui **Lihat pencocokan awal**.

## Acuan laporan yang Anda konfirmasi

| Client | Harga order | Sudah diterima | Sisa tagihan |
|---|---:|---:|---:|
| Eka | 500000 | 500000 | 0 |
| Nurdin | 500000 | 500000 | 0 |
| Putri | 500000 | 500000 | 0 |
| Iis | 500000 | 500000 | 0 |
| Angel Ilkom | 500000 | 0 | 500000 |
| Bobby | 600000 | 0 | 600000 |
| Joevel | 600000 | 600000 | 0 |
| Laila | 850000 | 500000 | 350000 |

Cocokkan identitas dengan nomor order di server. Nama lengkap di aplikasi bisa berbeda dari nama pendek dalam laporan. Jangan memilih berdasarkan kemiripan nama saja. Harga Laila harus tetap Rp850.000; masukkan Rp500.000 pada kolom diterima.

Total pencairan: **Yudo Rp999.999, Faisal Rp999.999, Indra Rp550.000**. Jika akun Yudo bernama `kojelzs`, pastikan identitas akun tersebut sebelum mengisi.

Isi angka total kumulatif ini, bukan menambahkan lagi di atas pencairan yang sudah ada. Misalnya sistem sudah mencatat Rp300.000 dari total nyata Rp550.000, kolom total tetap diisi **550000**.

Jika tidak ada transaksi tambahan, pratinjau seharusnya menunjukkan:

- Harga seluruh order Rp4.550.000.
- Uang diterima Rp3.100.000.
- Total pencairan Rp2.549.998.
- **Saldo kas Rp550.002.**
- **Sisa tagihan Rp1.450.000.**

Sistem membagi penerimaan sesuai snapshot admin dari pembagian lama. Order yang belum punya snapshot memakai admin aktif saat pencocokan. Saldo Cuanin baru = bagian dari penerimaan nyata dikurangi total pencairan. Jika pencairan seseorang lebih besar dari hak atas penerimaan terverifikasi, sistem menolak penyimpanan dan meminta pemeriksaan; tidak membuat saldo negatif atau mengarang uang tambahan.

## Pembayaran berikutnya

1. Buka **Orders → Periksa & Process**.
2. Isi **Catat Pembayaran Diterima** dengan nominal pembayaran BARU, tanggal WIB, dan referensi transfer unik. Boleh memakai format `350.000`.
3. Periksa uang masuk di rekening/QRIS, centang verifikasi, dan masukkan password admin.
4. Centang izin layanan jika client sudah boleh mulai/lanjut walaupun baru membayar sebagian.
5. Simpan: kas dan Cuanin bertambah hanya sebesar penerimaan baru, sisa tagihan berkurang. Pembayaran Laila Rp350.000 berikutnya membuat Laila lunas dan kas menjadi Rp900.002 bila tidak ada transaksi lain.

- Pengiriman bukti saja belum menambah kas.
- Referensi transfer yang sama ditolak agar tidak terhitung dua kali. Pengiriman ulang request yang sama tidak menggandakan penerimaan.
- Pembayaran melebihi sisa tagihan ditolak.
- Komisi referral 10% dihitung dari penerimaan nyata dengan pembulatan kumulatif per order.
- Bonus jumlah client dihitung sekali per client setelah order lunas; pendanaan bonus tetap berasal dari kas masuk.
- Daftar penerima bagian admin disimpan per order sehingga cicilan berikutnya memakai daftar yang sama.
- Pengajuan pencairan menahan saldo Cuanin; kas berkurang hanya saat transfer dikonfirmasi selesai. Penolakan pengajuan tidak mengurangi kas.
- Client dapat meng-upload bukti pelunasan tanpa kehilangan akses layanan yang sudah diizinkan.
- Client yang masih mempunyai sisa tagihan tidak dapat dihapus. Riwayat kas dan bukti penerimaan yang sudah tercatat dipertahankan setelah penghapusan client lunas.
- Catatan bendahara hanya catatan. Menulis rencana marketing tidak memotong kas atau Cuanin. Versi ini mencatat kas masuk client dan kas keluar pencairan; belum menyediakan modul biaya operasional/refund.
- Angka kas berasal dari catatan yang diverifikasi admin, bukan sinkronisasi otomatis rekening/QRIS.
