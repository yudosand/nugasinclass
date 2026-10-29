(() => {
  const form = document.getElementById('access-form');
  if (!form) return;
  const result = document.getElementById('access-result'), error = document.getElementById('access-error');
  const login = document.getElementById('access-login'), secret = document.getElementById('access-password');
  const password = document.getElementById('current_password'), button = document.getElementById('access-open');
  const toggle = document.getElementById('access-toggle');
  const edit = document.getElementById('access-update-form');
  const editMessage = document.getElementById('access-update-message');
  const editButton = document.getElementById('access-update-button');
  let timer, activeRequest, updateRequest;
  const clearEdit = () => {
    if (!edit) return;
    for (const name of ['elearning_password', 'elearning_password_confirmation', 'current_password']) edit.elements[name].value = '';
    edit.elements.confirmed.checked = false;
  };
  const clearAccess = () => {
    clearTimeout(timer); activeRequest?.abort(); activeRequest = null;
    login.value = ''; secret.value = ''; password.value = ''; secret.type = 'password';
    result.hidden = true; toggle.textContent = 'Tampilkan password';
  };
  const clear = () => {
    clearAccess(); updateRequest?.abort(); updateRequest = null; clearEdit();
  };
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = new FormData(form);
    clear(); error.hidden = true; button.disabled = true;
    const controller = new AbortController(); activeRequest = controller;
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }, signal: controller.signal });
      if (!response.ok || response.redirected) throw new Error(response.status === 422 ? 'Password admin tidak sesuai.' : response.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit.' : 'Akses tidak dapat dibuka. Periksa sesi login dan koneksi HTTPS.');
      const payload = await response.json();
      if (document.hidden || controller.signal.aborted) return;
      login.value = payload.login; secret.value = payload.password;
      payload.login = ''; payload.password = '';
      result.hidden = false; timer = setTimeout(clear, 45000);
    } catch (problem) {
      if (!document.hidden) { error.textContent = problem.name === 'AbortError' ? 'Permintaan dibatalkan. Silakan verifikasi ulang.' : problem.message; error.hidden = false; }
    } finally { data.delete('current_password'); clearTimeout(timeout); button.disabled = false; }
  });
  edit?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = new FormData(edit);
    clear(); error.hidden = true; editMessage.hidden = true; editButton.disabled = true;
    const controller = new AbortController(); updateRequest = controller;
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(edit.action, {method:'PUT', body:new URLSearchParams(data), credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}, signal:controller.signal});
      if (response.redirected) throw new Error('Sesi berakhir. Masuk kembali lalu periksa akses tersimpan.');
      if (!response.ok) {
        if (response.status === 422) {
          const payload = await response.json();
          const fields = payload.errors || {};
          throw new Error(fields.current_password ? 'Password admin tidak sesuai.' : fields.confirmed ? 'Konfirmasi dari client wajib dicentang.' : 'Periksa password e-learning: wajib diisi, maksimal 1024 karakter, dan kedua isian harus sama.');
        }
        throw new Error(response.status === 409 ? 'Akses telah berubah. Muat ulang halaman dan periksa akses terbaru.' : response.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit.' : 'Perbaikan belum dapat dikonfirmasi. Muat ulang dan periksa akses tersimpan sebelum mencoba lagi.');
      }
      const payload = await response.json();
      if (document.hidden || controller.signal.aborted) return;
      edit.elements.revision.value = payload.revision;
      editMessage.textContent = payload.message;
      editMessage.className = 'alert alert-success'; editMessage.hidden = false;
    } catch (problem) {
      if (!document.hidden) {
        editMessage.textContent = problem.name === 'AbortError' ? 'Respons terputus. Muat ulang dan periksa akses tersimpan sebelum mencoba lagi.' : problem.message;
        editMessage.className = 'alert alert-danger'; editMessage.hidden = false;
      }
    } finally {
      for (const name of ['elearning_password', 'elearning_password_confirmation', 'current_password']) data.delete(name);
      clearTimeout(timeout); if (updateRequest === controller) updateRequest = null; editButton.disabled = false;
    }
  });
  document.getElementById('access-edit')?.addEventListener('toggle', (event) => {
    // The browser may dispatch opening toggle after typing starts; preserve the new inputs.
    if (event.currentTarget.open) clearAccess(); else clear();
  });
  toggle.addEventListener('click', () => { secret.type = secret.type === 'password' ? 'text' : 'password'; toggle.textContent = secret.type === 'password' ? 'Tampilkan password' : 'Samarkan password'; });
  document.getElementById('access-hide').addEventListener('click', clear);
  document.addEventListener('visibilitychange', () => { if (document.hidden) clear(); });
  window.addEventListener('pagehide', clear);
  window.addEventListener('pageshow', clear);
})();
