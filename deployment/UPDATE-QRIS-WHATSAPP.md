# Update QRIS dan konfirmasi WhatsApp

Gunakan **nugasinclass-update-qris-wa.zip** untuk situs yang sudah berhasil diinstal di `https://nugasinclass.web.id`.

## Perubahan

- Rekening contoh BCA/Mandiri pada halaman pembayaran diganti gambar QRIS asli yang diberikan pemilik. Gambar tidak diubah; tersedia tombol unduh dan tautan ukuran penuh.
- Pelanggan membayar sesuai total pesanan, lalu mengunggah bukti JPG/PNG/PDF (maksimal 5 MB).
- Setelah bukti berhasil disimpan, halaman menampilkan **Konfirmasi Pembayaran via WhatsApp**, menuju **+62 858-6101-5161** dengan pesan:

> Halo Kak, saya sudah melakukan pembayaran ya. Mohon dibantu cek dan konfirmasi pembayarannya. Terima kasih 🙏

- Pelanggan tetap menekan Kirim di WhatsApp. Pembayaran masih menunggu persetujuan admin; membuka WhatsApp tidak otomatis menyetujui pembayaran.
- PDF kini diterima sesuai keterangan form. Jika penyimpanan file gagal, status pembayaran tetap belum dibayar dan form menampilkan error.

## Pemasangan tanpa terminal

1. Di CloudPanel **File Manager**, buka root aplikasi situs aktif: folder yang memuat `artisan`, `app`, `public`, dan `resources`. Ikuti path situs yang sekarang; nama folder fisik bisa tetap memakai nama domain sebelumnya.
2. Backup empat file PHP/Blade yang tercantum di bawah ke folder pribadi di luar `public`, untuk rollback bila diperlukan.
3. Upload ZIP ini ke root aplikasi tersebut, lalu **Extract → Overwrite/Replace** untuk mengganti file yang sama. Jangan menaruhnya di dalam folder `public`.
4. ZIP hanya memuat lima file berikut, tanpa folder pembungkus:

```text
app/Http/Controllers/PaymentController.php
app/Http/Controllers/Auth/RegisterController.php
app/Http/Requests/PaymentUploadRequest.php
resources/views/payment/show.blade.php
public/assets/img/qris-nugasin-class.png
```

5. Tidak perlu mengganti `.env`, menjalankan installer, migrasi, Composer, atau npm. ZIP tidak membawa `.env`, database, `storage`, `vendor`, maupun `deploy.php`.
6. Jika tampilan masih lama, melalui File Manager hapus hanya file hasil kompilasi berakhiran `.php` di **`storage/framework/views`**. Pertahankan foldernya dan `.gitignore`. Laravel akan membentuk ulang view saat halaman dibuka. Jangan menghapus `storage/app/public` yang menyimpan bukti pembayaran.
7. Buka pesanan melalui akun pelanggan: pastikan QRIS tampil, upload bukti, lalu cek tombol konfirmasi WhatsApp. Nomor dan pesan harus sama dengan di atas.
8. Setelah selesai, hapus ZIP unggahan di server.

## Verifikasi lokal

8 tes dengan 37 assertions lulus: halaman QRIS sebelum upload, tombol dan pesan WhatsApp setelah upload sukses, unggahan PDF, penolakan file tidak valid, kegagalan storage, pembatasan akses pesanan milik pelanggan lain, serta tes bawaan. ZIP diperiksa ulang agar lima file sesuai sumber dan tidak menimpa konfigurasi/data server. Gambar QRIS yang dikemas identik dengan lampiran asli. Pembayaran sungguhan dan pengiriman pesan WhatsApp tidak dilakukan selama pengujian.

Tautan pesan mengikuti [format resmi WhatsApp click to chat](https://faq.whatsapp.com/5913398998672934).
