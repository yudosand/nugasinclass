(() => {
  'use strict';

  const COURSES_JSON = 'assets/data/courses.json';

  function getQueryParam(key) {
    const url = new URL(window.location.href);
    return url.searchParams.get(key);
  }

  function formatRupiah(value) {
    const n = Number(value || 0);
    return 'Rp ' + n.toLocaleString('id-ID');
  }

  async function loadCourses() {
    const res = await fetch(COURSES_JSON, { cache: 'no-store' });
    if (!res.ok) throw new Error('courses.json not found');
    const data = await res.json();
    if (!Array.isArray(data)) throw new Error('courses.json invalid');
    return data;
  }

  function findCourse(list, id) {
    const key = String(id || '').trim().toLowerCase();
    return (list || []).find(c => String(c?.id || '').trim().toLowerCase() === key) || null;
  }

  function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function showAlert(msg, type = 'info') {
    const el = document.getElementById('checkoutAlert');
    if (!el) return;
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const courseId = getQueryParam('course');
    if (!courseId) {
      showAlert('Course tidak ditemukan. Kembali ke halaman Simulasi UAS.', 'warning');
      return;
    }

    let courses = [];
    try {
      courses = await loadCourses();
    } catch (e) {
      console.error(e);
      showAlert('Gagal memuat daftar mata kuliah. Jalankan via Live Server.', 'danger');
      return;
    }

    const course = findCourse(courses, courseId);
    if (!course) {
      showAlert('Mata kuliah tidak ditemukan.', 'warning');
      return;
    }

    setText('courseName', course.title || '-');
    setText('courseMeta', `${Number(course.total_sessions || 3)} sesi • ${Number(course.questions_per_session || 50)} soal/sesi`);
    setText('coursePrice', formatRupiah(course.price || 0));

    const btnPay = document.getElementById('btnPay');
    const modalEl = document.getElementById('paidModal');
    const accessIdText = document.getElementById('accessIdText');
    const btnCopy = document.getElementById('btnCopy');
    const btnGoAccess = document.getElementById('btnGoAccess');

    const paidModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    btnPay?.addEventListener('click', async () => {
      // TODO: Integrasi payment gateway pihak ke-3.
      // Untuk saat ini: simulate success.

      const rec = window.NCAccess?.createAccess?.({
        id: course.id,
        title: course.title,
        json: course.json,
        price: course.price,
        currency: course.currency
      });

      if (!rec) {
        showAlert('Gagal membuat ID Ujian. Coba lagi.', 'danger');
        return;
      }

      if (accessIdText) accessIdText.textContent = rec.access_id;
      if (btnGoAccess) btnGoAccess.href = `akses.html?id=${encodeURIComponent(rec.access_id)}`;

      btnCopy?.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(rec.access_id);
          btnCopy.textContent = 'Copied';
          setTimeout(() => { btnCopy.textContent = 'Copy'; }, 1200);
        } catch {
          alert('Gagal copy. Silakan copy manual.');
        }
      }, { once: true });

      paidModal?.show();
    });
  });
})();
