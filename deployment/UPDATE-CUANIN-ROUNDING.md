# Update Cuanin Pembulatan Admin

ZIP ini merapikan pembagian sisa rupiah Cuanin untuk order berikutnya.

Yang berubah:

- Jika nominal admin tidak habis dibagi rata, sisa rupiah tidak selalu diberikan ke admin urutan awal.
- Sisa rupiah akan diputar antar admin pada transaksi berikutnya agar total jangka panjang lebih adil.
- Label di daftar order diubah menjadi **Pool admin dibagi X** supaya tidak terbaca sebagai nominal per admin.

Contoh:

- 1 order Rp500.000 dibagi 3 admin tetap menjadi Rp166.667, Rp166.667, Rp166.666.
- 4 order Rp500.000 dibagi 3 admin menjadi total Rp666.667, Rp666.667, Rp666.666.

Catatan:

- Histori Cuanin yang sudah dibukukan tidak diubah otomatis.
- Jika ada saldo lama yang ingin dikoreksi, lakukan sebagai koreksi finansial terpisah agar riwayat IN/OUT tetap jelas.
