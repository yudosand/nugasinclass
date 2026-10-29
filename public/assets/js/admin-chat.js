(() => {
  'use strict';
  const root = document.getElementById('admin-chat');
  if (!root) return;
  const byId = (id) => document.getElementById('chat-' + id);
  const history = byId('history'), messages = byId('messages'), body = byId('body');
  const send = byId('send'), older = byId('older'), status = byId('status'), error = byId('error');
  const items = new Map();
  let newest = 0, oldest = 0, initialized = false, loading = false, sending = false, pending = null;
  const showError = (text) => { error.textContent = text; error.hidden = !text; };
  const bottom = () => { history.scrollTop = history.scrollHeight; byId('new').hidden = true; };
  const uuid = () => {
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 15) | 64;
    bytes[8] = (bytes[8] & 63) | 128;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
  };

  async function request(url, options = {}) {
    const abort = new AbortController();
    const timer = setTimeout(() => abort.abort(), 15000);
    try {
      const response = await fetch(url, {
        ...options, credentials: 'same-origin', cache: 'no-store', signal: abort.signal,
        headers: { Accept: 'application/json', ...options.headers },
      });
      if (response.status === 401 || response.status === 419 || response.redirected) throw new Error('Sesi berakhir. Muat ulang halaman dan login kembali.');
      if (response.status === 403) throw new Error('Akses Chat Admin tidak tersedia untuk akun ini.');
      if (response.status === 429) throw new Error('Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.');
      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(response.status === 422 ? Object.values(data.errors || {}).flat().join(' ') : 'Chat belum dapat diakses. Muat ulang atau coba lagi.');
      }
      return await response.json();
    } catch (problem) {
      if (problem.name === 'AbortError' || problem instanceof TypeError) throw new Error('Koneksi terputus atau lambat. Pesan Anda tetap di kolom ketik; coba lagi.');
      throw problem;
    } finally { clearTimeout(timer); }
  }

  function render(rows, previous = false) {
    const wasBottom = history.scrollHeight - history.scrollTop - history.clientHeight < 90;
    const oldHeight = history.scrollHeight, oldTop = history.scrollTop;
    let added = false;
    rows.forEach((row) => { if (!items.has(row.id)) added = true; items.set(row.id, row); });
    const sorted = Array.from(items.values()).sort((a, b) => a.id - b.id);
    const fragment = document.createDocumentFragment();
    sorted.forEach((row) => {
      const wrapper = document.createElement('article');
      wrapper.className = 'd-flex mb-3 ' + (row.mine ? 'justify-content-end' : 'justify-content-start');
      const bubble = document.createElement('div');
      bubble.className = 'border rounded-4 p-3 ' + (row.mine ? 'bg-primary-subtle' : 'bg-body-tertiary');
      bubble.style.maxWidth = '92%';
      const label = document.createElement('div');
      label.className = 'small fw-bold mb-1';
      label.textContent = row.name + (row.mine ? ' (Anda)' : '');
      const text = document.createElement('div');
      text.style.whiteSpace = 'pre-wrap';
      text.textContent = row.body;
      const time = document.createElement('time');
      time.className = 'small text-secondary d-block mt-2';
      time.dateTime = row.created_at;
      time.textContent = new Date(row.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
      bubble.append(label, text, time); wrapper.append(bubble); fragment.append(wrapper);
    });
    messages.replaceChildren(fragment);
    byId('empty').hidden = sorted.length > 0;
    byId('empty').textContent = 'Belum ada pesan. Mulai percakapan dengan tim Anda.';
    if (sorted.length) { oldest = sorted[0].id; newest = sorted[sorted.length - 1].id; }
    if (previous) history.scrollTop = oldTop + history.scrollHeight - oldHeight;
    else if (wasBottom || !initialized) bottom();
    else if (added) byId('new').hidden = false;
  }

  async function refresh(previous = false) {
    if (loading) return;
    loading = true; older.disabled = true;
    try {
      const url = new URL(root.dataset.messagesUrl);
      if (previous && oldest) url.searchParams.set('before', oldest);
      else if (initialized) url.searchParams.set('after', newest);
      const data = await request(url);
      if (!initialized || previous) older.hidden = !data.has_more;
      if (data.messages.length || !initialized) render(data.messages, previous);
      initialized = true;
      send.disabled = sending;
      status.textContent = 'Pesan diperbarui otomatis setiap 5 detik';
      // Catch up in bounded batches without skipping messages sent by other admins.
      if (!previous && data.has_more && url.searchParams.has('after')) setTimeout(() => refresh(), 600);
    } catch (problem) {
      status.textContent = problem.message;
    } finally { loading = false; older.disabled = false; }
  }

  byId('form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (sending || !initialized) return;
    const text = body.value.trim();
    if (!text) { showError('Tulis pesan terlebih dahulu.'); return; }
    if (!pending || pending.body !== text) pending = { body: text, client_message_id: uuid() };
    sending = true; send.disabled = true; body.disabled = true; send.textContent = 'Mengirim…'; showError('');
    try {
      await request(root.dataset.sendUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
        body: JSON.stringify(pending),
      });
      body.value = ''; pending = null;
      // Fetch from the last read cursor instead of jumping to our own message ID.
      await refresh();
      bottom();
    } catch (problem) { showError(problem.message); }
    finally { sending = false; send.disabled = false; body.disabled = false; send.textContent = 'Kirim Pesan'; body.focus(); }
  });
  older.addEventListener('click', () => refresh(true));
  byId('refresh').addEventListener('click', () => refresh());
  byId('new').addEventListener('click', bottom);
  history.addEventListener('scroll', () => { if (history.scrollHeight - history.scrollTop - history.clientHeight < 40) byId('new').hidden = true; });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  const timer = setInterval(() => { if (!document.hidden) refresh(); }, 5000);
  window.addEventListener('pagehide', () => clearInterval(timer));
  refresh();
})();
