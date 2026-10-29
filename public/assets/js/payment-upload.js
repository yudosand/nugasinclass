(() => {
  'use strict';
  document.querySelectorAll('[data-proof-upload]').forEach(form => {
    const input = form.querySelector('input[type="file"]');
    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('[data-upload-status]');
    let busy = false;
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (busy || !form.reportValidity()) return;
      const file = input.files[0];
      const report = (message, error = false) => {
        status.textContent = message;
        status.classList.toggle('text-danger', error);
      };
      if (!file || !file.size || file.size > 5 * 1024 * 1024) {
        report('Pilih file JPG, PNG atau PDF yang tidak kosong, maksimal 5 MB.', true); return;
      }
      busy = true; button.disabled = true;
      report('Menyiapkan bukti pembayaran…');
      try {
        // Read first so a revoked cloud/temporary file handle never navigates to a browser error page.
        const bytes = await file.arrayBuffer();
        const data = new FormData(form);
        data.set('payment_proof', new Blob([bytes], {type: file.type}), file.name);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 90000;
        xhr.upload.onprogress = e => report(e.lengthComputable ? `Mengunggah bukti: ${Math.round(e.loaded / e.total * 100)}%. Tunggu konfirmasi penyimpanan…` : 'Mengunggah bukti…');
        const finish = () => { busy = false; button.disabled = false; };
        xhr.onload = () => {
          let result;
          try { result = JSON.parse(xhr.responseText); } catch (_) { result = {}; }
          if (xhr.status >= 200 && xhr.status < 300 && result.redirect) {
            const destination = new URL(result.redirect, location.href);
            if (destination.origin === location.origin) { location.assign(destination.href); return; }
          }
          const messages = {401:'Silakan login kembali, lalu pilih ulang file bukti.',419:'Sesi formulir berakhir. Muat ulang halaman, lalu pilih ulang file bukti.',413:'File terlalu besar untuk server. Gunakan JPG, PNG atau PDF maksimal 5 MB.',403:'Akses upload ditolak. Pastikan Anda masuk sebagai pemilik order.'};
          report(result.errors ? Object.values(result.errors).flat().join(' ') : messages[xhr.status] || result.message || 'Bukti belum berhasil disimpan. Muat ulang halaman untuk memeriksa status, lalu coba kembali.', true);
          finish();
        };
        xhr.onerror = xhr.ontimeout = () => {
          report('Koneksi upload terputus atau diblokir browser. Muat ulang halaman untuk memeriksa apakah bukti sudah tersimpan. Jika belum, simpan file ke perangkat, pilih ulang, dan coba lagi.', true); finish();
        };
        xhr.send(data);
      } catch (_) {
        report('Browser tidak bisa membaca file ini. Simpan bukti ke Downloads/perangkat, lalu pilih ulang file JPG, PNG atau PDF tersebut.', true);
        busy = false; button.disabled = false;
      }
    });
  });
})();
