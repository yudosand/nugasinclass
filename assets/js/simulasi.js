(() => {
  'use strict';

  const COURSES_JSON = 'assets/data/courses.json';

  // Fallback jika courses.json gagal dibaca (mis. tidak dijalankan via Live Server)
  const FALLBACK_COURSES = [
    { id: 'agama_islam', title: 'Agama Islam', price: 15000 },
    { id: 'algoritma_pemrograman', title: 'Algoritma Pemrograman', price: 15000 },
    { id: 'aljabar_linear_elementer', title: 'Aljabar Linear Elementer', price: 15000 },
    { id: 'bahasa_indonesia', title: 'Bahasa Indonesia', price: 15000 },
    { id: 'pengantar_statiska',title: 'Pengantar Statiska',price: 15000}

  ].map(x => ({
    ...x,
    total_sessions: 3,
    questions_per_session: 50,
    currency: 'IDR',
    json: ''
  }));

  let allCourses = [];

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"]/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'
    }[m]));
  }

  function formatRupiah(value) {
    const n = Number(value || 0);
    return 'Rp ' + n.toLocaleString('id-ID');
  }

  function render(list) {
    const grid = document.getElementById('courseGrid');
    const noResult = document.getElementById('noResult');
    if (!grid) return;

    grid.innerHTML = '';

    if (!list.length) {
      noResult?.classList.remove('d-none');
      return;
    }
    noResult?.classList.add('d-none');

    list.forEach((c) => {
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-lg-3';

      const title = c?.title || 'Mata Kuliah';
      const id = c?.id || '';
      const totalSessions = Number(c?.total_sessions || 3);
      const perSession = Number(c?.questions_per_session || 50);
      const price = formatRupiah(c?.price || 0);

      col.innerHTML = `
        <div class="card h-100 border-0 shadow-sm card-hover">
          <div class="card-body p-4 d-flex flex-column">
            <div class="fs-3">📘</div>
            <div class="fw-bold mt-2">${escapeHtml(title)}</div>
            <p class="text-secondary small mt-2 mb-2">${totalSessions} sesi - ${perSession} soal/sesi (total ${totalSessions * perSession}).</p>
            <div class="small text-secondary mb-4">Harga: <span class="fw-semibold">${price}</span></div>
            <button type="button" class="btn btn-primary mt-auto" data-action="start" data-course-id="${escapeHtml(id)}">Mulai Ujian</button>
          </div>
        </div>
      `;
      grid.appendChild(col);
    });
  }

  function filterCourses(q) {
    const query = (q || '').trim().toLowerCase();
    if (!query) return allCourses;
    return allCourses.filter(c => String(c?.title || '').toLowerCase().includes(query));
  }

  function goToCheckout(courseId) {
    const id = courseId || 'course';
    window.location.href = `checkout.html?course=${encodeURIComponent(id)}`;
  }

  async function loadCourses() {
    try {
      const res = await fetch(COURSES_JSON, { cache: 'no-store' });
      if (!res.ok) throw new Error('courses.json not found');
      const data = await res.json();
      if (!Array.isArray(data)) throw new Error('courses.json invalid');

      allCourses = data
        .filter(x => x && typeof x === 'object')
        .map(x => ({
          id: String(x.id || '').trim(),
          title: String(x.title || '').trim(),
          total_sessions: Number(x.total_sessions || 3),
          questions_per_session: Number(x.questions_per_session || 50),
          json: String(x.json || '').trim(),
          price: Number(x.price || 0),
          currency: String(x.currency || 'IDR')
        }))
        .filter(x => x.id && x.title);

      return true;
    } catch (e) {
      console.warn('Gagal load courses.json, pakai fallback list.', e);
      allCourses = FALLBACK_COURSES;
      return false;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');

    loadCourses().finally(() => {
      render(allCourses);
    });

    searchInput?.addEventListener('input', (e) => {
      render(filterCourses(e.target.value));
    });

    document.getElementById('courseGrid')?.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action="start"]');
      if (!btn) return;
      const courseId = btn.getAttribute('data-course-id') || '';
      goToCheckout(courseId);
    });
  });
})();
