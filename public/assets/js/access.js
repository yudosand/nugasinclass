(() => {
  'use strict';

  function getQueryParam(key) {
    const url = new URL(window.location.href);
    return url.searchParams.get(key);
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[m]));
  }

  function setResult(box, message, type = 'info') {
    if (!box) return;
    box.className = `alert alert-${type} mt-4`;
    box.innerHTML = message;
    box.classList.remove('d-none');
  }

  function hideResult(box) {
    if (!box) return;
    box.classList.add('d-none');
  }

  function renderAccess(rec) {
    const resultBox = document.getElementById('resultBox');
    const actionBox = document.getElementById('actionBox');
    const btnStart = document.getElementById('btnStart');
    const btnReset = document.getElementById('btnReset');

    if (!resultBox || !actionBox || !btnStart || !btnReset) return;

    const statusPaid = rec.paid ? 'Lunas' : 'Belum bayar';
    const statusExam = rec.completed ? 'Selesai' : (rec.started ? 'Sedang dikerjakan' : 'Belum mulai');

    setResult(
      resultBox,
      `<div class="fw-semibold mb-1">${escapeHtml(rec.course_title || rec.course_id || '-')}</div>
       <div class="small">
         ID Ujian: <span class="fw-semibold">${escapeHtml(rec.access_id)}</span><br/>
         Status: ${escapeHtml(statusPaid)} • ${escapeHtml(statusExam)}
       </div>`,
      rec.paid ? 'success' : 'warning'
    );

    // show actions
    actionBox.classList.remove('d-none');

    // Start only if paid and not completed
    const canStart = !!rec.paid && !rec.completed;
    btnStart.classList.toggle('d-none', !canStart);
    btnStart.href = `paket-soal.html?access_id=${encodeURIComponent(rec.access_id)}`;

    // mark started just before navigating
    btnStart.onclick = () => {
      try {
        window.NCAccess?.updateAccess?.(rec.access_id, {
          started: true,
          last_opened_at: new Date().toISOString()
        });
      } catch {}
      // allow navigation
      return true;
    };

    btnReset.disabled = !rec.access_id;
    btnReset.onclick = () => {
      const ok = confirm('Reset progress untuk ID ini? (Hapus jawaban & status sesi di browser ini)');
      if (!ok) return;
      try {
        window.NCAccess?.clearExamProgress?.(rec.access_id);
        window.NCAccess?.updateAccess?.(rec.access_id, {
          started: false,
          completed: false,
          last_opened_at: null
        });
      } catch {}
      alert('Reset selesai.');
      location.reload();
    };
  }

  function renderRecentList() {
    const wrap = document.getElementById('recentList');
    if (!wrap) return;

    const list = (window.NCAccess?.listAccess?.() || [])
      .slice()
      .sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')))
      .slice(0, 8);

    if (!list.length) {
      wrap.innerHTML = `<div class="text-secondary small">Belum ada ID tersimpan di browser ini.</div>`;
      return;
    }

    wrap.innerHTML = '';

    list.forEach(rec => {
      const div = document.createElement('div');
      div.className = 'border rounded-3 p-3 d-flex flex-wrap justify-content-between align-items-center gap-2';

      const left = document.createElement('div');
      left.innerHTML = `
        <div class="fw-semibold">${escapeHtml(rec.course_title || rec.course_id || '-')}</div>
        <div class="small text-secondary">ID: ${escapeHtml(rec.access_id)} • ${rec.completed ? 'Selesai' : (rec.started ? 'Sedang dikerjakan' : 'Belum mulai')}</div>
      `;

      const right = document.createElement('div');
      right.className = 'd-flex gap-2';

      const btnOpen = document.createElement('a');
      btnOpen.className = 'btn btn-outline-primary btn-sm';
      btnOpen.textContent = 'Buka';
      btnOpen.href = `akses.html?id=${encodeURIComponent(rec.access_id)}`;

      const btnCopy = document.createElement('button');
      btnCopy.type = 'button';
      btnCopy.className = 'btn btn-outline-secondary btn-sm';
      btnCopy.textContent = 'Copy ID';
      btnCopy.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(String(rec.access_id || ''));
          btnCopy.textContent = 'Copied';
          setTimeout(() => (btnCopy.textContent = 'Copy ID'), 1200);
        } catch {
          alert('Gagal copy. Silakan copy manual.');
        }
      });

      right.appendChild(btnOpen);
      right.appendChild(btnCopy);

      div.appendChild(left);
      div.appendChild(right);
      wrap.appendChild(div);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('accessInput');
    const btn = document.getElementById('btnCheck');
    const resultBox = document.getElementById('resultBox');
    const actionBox = document.getElementById('actionBox');

    renderRecentList();

    const initId = getQueryParam('id');
    if (initId && input) input.value = initId;

    function doCheck() {
      const id = String(input?.value || '').trim();
      if (!id) {
        alert('Masukkan ID Ujian.');
        return;
      }

      const rec = window.NCAccess?.getAccess?.(id);
      if (!rec) {
        hideResult(resultBox);
        if (actionBox) actionBox.classList.add('d-none');
        alert('ID tidak ditemukan di browser ini (demo).');
        return;
      }

      renderAccess(rec);
    }

    btn?.addEventListener('click', doCheck);

    input?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') doCheck();
    });

    // auto-check if opened with ?id=
    if (initId) {
      setTimeout(doCheck, 0);
    }
  });
})();
