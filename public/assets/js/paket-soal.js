(() => {
  'use strict';

  // ==========================================================
  //  EXAM (PDF Mode) - Nugasin Class
  //  - 1 ID Ujian = 1 MK = 1x ujian
  //  - 3 sesi, 50 soal/sesi
  //  - soal tampil via PDF (PDF.js)
  //  - mapping nomor -> halaman + answer_key di JSON paket
  //  - remedial untuk nomor yang salah/kosong, hasil sesi di-REPLACE
  // ==========================================================

  const ACCESS = () => window.NCAccess;

  // --------------------------
  // PDF.js setup
  // --------------------------
  const pdfjs = window.pdfjsLib;
  if (pdfjs) {
    pdfjs.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  }

  // --------------------------
  // DOM helpers
  // --------------------------
  const $ = (id) => document.getElementById(id);

  function setText(id, text) {
    const el = $(id);
    if (el) el.textContent = text;
  }

  function showAlert(msg, type = 'warning') {
    const el = $('examAlert');
    if (!el) return;
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  function hideAlert() {
    const el = $('examAlert');
    if (!el) return;
    el.classList.add('d-none');
  }

  function getQueryParam(key) {
    const url = new URL(window.location.href);
    return url.searchParams.get(key);
  }

  function clamp(n, min, max) {
    return Math.min(Math.max(n, min), max);
  }

  function uniq(arr) {
    return Array.from(new Set(arr || [])).sort((a, b) => a - b);
  }

  // Timer sengaja dihapus (revisi): tidak ada countdown di UI.

  // --------------------------
  // LocalStorage keys (scoped by access_id)
  // --------------------------
  function prefix(accessId) {
    return `nc_exam|${String(accessId || '').trim()}|`;
  }

  function keyAnswer(accessId, sessionNo, qNo) {
    return `${prefix(accessId)}s${sessionNo}|q${qNo}|ans`;
  }

  function keyEnded(accessId, sessionNo) {
    return `${prefix(accessId)}s${sessionNo}|ended`;
  }

  function keyStartTs(accessId, sessionNo) {
    return `${prefix(accessId)}s${sessionNo}|start_ts`;
  }

  function keyScore(accessId, sessionNo) {
    return `${prefix(accessId)}s${sessionNo}|score`;
  }

  function keyCurrentSession(accessId) {
    return `${prefix(accessId)}current_session`;
  }

  function getLS(key) {
    try { return localStorage.getItem(key); } catch { return null; }
  }

  function setLS(key, val) {
    try { localStorage.setItem(key, String(val)); } catch {}
  }

  function delLS(key) {
    try { localStorage.removeItem(key); } catch {}
  }

  function getAns(accessId, sessionNo, qNo) {
    return getLS(keyAnswer(accessId, sessionNo, qNo));
  }

  function setAns(accessId, sessionNo, qNo, val) {
    setLS(keyAnswer(accessId, sessionNo, qNo), String(val || ''));
  }

  function isEnded(accessId, sessionNo) {
    return getLS(keyEnded(accessId, sessionNo)) === '1';
  }

  function setEnded(accessId, sessionNo, ended) {
    setLS(keyEnded(accessId, sessionNo), ended ? '1' : '0');
  }

  function ensureStartTs(accessId, sessionNo) {
    const k = keyStartTs(accessId, sessionNo);
    const v = Number(getLS(k) || 0);
    if (v) return v;
    const now = Date.now();
    setLS(k, String(now));
    return now;
  }

  function saveScore(accessId, sessionNo, stats) {
    try { localStorage.setItem(keyScore(accessId, sessionNo), JSON.stringify(stats || {})); } catch {}
  }

  function loadScore(accessId, sessionNo) {
    try {
      const raw = localStorage.getItem(keyScore(accessId, sessionNo));
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }

  function answeredCount(accessId, sessionNo, perSession) {
    let c = 0;
    for (let i = 1; i <= perSession; i++) {
      if (getAns(accessId, sessionNo, i)) c++;
    }
    return c;
  }

  // --------------------------
  // Paket loader
  // --------------------------
  async function loadJson(path) {
    const res = await fetch(path, { cache: 'no-store' });
    if (!res.ok) throw new Error(`Gagal load JSON: ${path}`);
    return await res.json();
  }

  function getSessionObj(paket, sessionNo) {
    const sessions = Array.isArray(paket.sessions) ? paket.sessions : [];
    return sessions.find(s => Number(s?.session) === Number(sessionNo)) || sessions[sessionNo - 1] || null;
  }

  // --------------------------
  // Stats (benar/salah/kosong)
  // --------------------------
  function computeStats(accessId, paket, sessionNo) {
    const perSession = Number(paket.questions_per_session || 50);
    const sess = getSessionObj(paket, sessionNo);
    const questions = Array.isArray(sess?.questions) ? sess.questions : [];

    let correct = 0;
    let wrong = 0;
    let blank = 0;
    let answered = 0;

    const wrongNos = [];
    const blankNos = [];

    for (let i = 1; i <= perSession; i++) {
      const q = questions[i - 1] || {};
      const key = String(q.answer_key || '').trim().toUpperCase();
      const ans = String(getAns(accessId, sessionNo, i) || '').trim().toUpperCase();

      if (!ans) {
        blank++;
        // kalau punya kunci, bisa remedial
        if (key) blankNos.push(i);
        continue;
      }

      answered++;
      if (!key) {
        // belum ada kunci -> dianggap tidak dinilai
        continue;
      }

      if (ans === key) correct++;
      else {
        wrong++;
        wrongNos.push(i);
      }
    }

    return {
      session: Number(sessionNo),
      total: perSession,
      answered,
      correct,
      wrong,
      blank,
      wrongNos: uniq(wrongNos),
      blankNos: uniq(blankNos)
    };
  }

  // --------------------------
  // PDF rendering
  // --------------------------
  async function loadPdf(url) {
    if (!pdfjs) throw new Error('PDF.js tidak tersedia');
    // handle file name with spaces etc
    const safeUrl = encodeURI(url);
    const task = pdfjs.getDocument({ url: safeUrl });
    return await task.promise;
  }

  async function renderPdfPage(pdfDoc, canvas, pageNo, scale) {
    const ctx = canvas.getContext('2d');
    const page = await pdfDoc.getPage(pageNo);
    const viewport = page.getViewport({ scale });
    canvas.width = Math.floor(viewport.width);
    canvas.height = Math.floor(viewport.height);
    const task = page.render({ canvasContext: ctx, viewport });
    await task.promise;
  }

  // --------------------------
  // Main
  // --------------------------
  document.addEventListener('DOMContentLoaded', async () => {
    const accessId = String(getQueryParam('access_id') || '').trim();
    if (!accessId) {
      showAlert('Akses ditolak. access_id tidak ditemukan. Buka dari halaman Akses Ujian.', 'warning');
      return;
    }

    const rec = ACCESS()?.getAccess?.(accessId);
    if (!rec || !rec.paid) {
      showAlert('Akses ditolak. ID Ujian tidak valid (atau belum bayar).', 'danger');
      return;
    }

    setText('uiAccessId', rec.access_id);
    setText('uiCourseTitle', rec.course_title || rec.course_id || '-');

    // Theme button
    const btnTheme = $('btnTheme');
    if (btnTheme) {
      // set initial icon
      try {
        const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
        btnTheme.textContent = current === 'dark' ? '🌞' : '🌙';
      } catch {}

      btnTheme.addEventListener('click', () => {
        try {
          const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
          const next = current === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-bs-theme', next);
          localStorage.setItem('nc_theme', next);
          btnTheme.textContent = next === 'dark' ? '🌞' : '🌙';
        } catch {}
      });
    }

    // Load paket
    let paket;
    try {
      paket = await loadJson(rec.paket_json);
    } catch (e) {
      console.error(e);
      showAlert('Gagal memuat paket JSON. Pastikan jalankan lewat Live Server dan path JSON benar.', 'danger');
      return;
    }

    const totalSessions = Number(paket.total_sessions || 3);
    const perSession = Number(paket.questions_per_session || 50);
    // Timer dihapus: duration_minutes_per_session tetap bisa dipakai nanti bila dibutuhkan.
    const durationMin = Number(paket?.rules?.duration_minutes_per_session || 50);

    // Determine current session
    let sessionNo = 1;
    const savedSess = Number(getLS(keyCurrentSession(accessId)) || 0);
    if (savedSess >= 1 && savedSess <= totalSessions) sessionNo = savedSess;

    // If saved session ended, move to next available
    while (sessionNo <= totalSessions && isEnded(accessId, sessionNo)) sessionNo++;
    if (sessionNo > totalSessions) {
      // all ended
      showAlert('Ujian sudah selesai untuk ID ini. (1 ID = 1x ujian)', 'warning');
      // mark completed (demo)
      ACCESS()?.updateAccess?.(accessId, { completed: true, started: true });
      setText('uiSession', `${totalSessions}/${totalSessions}`);
      return;
    }

    setLS(keyCurrentSession(accessId), String(sessionNo));
    ACCESS()?.updateAccess?.(accessId, { started: true });

    // State
    let questionNo = 1;
    let remedialMode = false;
    let remedialList = [];

    let pdfDoc = null;
    let currentPdfPage = 1;
    let pdfScale = 1.2;
    let currentPdfUrl = '';
    let useIframeViewer = false;

    // DOM refs
    const answerGrid = $('answerGrid');
    const pdfCanvas = $('pdfCanvas');
    const pdfIframe = $('pdfIframe');
    const pdfLoading = $('pdfLoading');
    const btnPrevQ = $('btnPrevQ');
    const btnNextQ = $('btnNextQ');
    const btnFinishSession = $('btnFinishSession');
    const btnFinishRemedial = $('btnFinishRemedial');

    const modalEl = $('resultModal');
    const resultContent = $('resultContent');
    const btnModalRemedial = $('btnModalRemedial');
    const btnModalEndSession = $('btnModalEndSession');
    const resultModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    function getMappedPage(sessNo, qNo) {
      const sess = getSessionObj(paket, sessNo);
      const q = (sess?.questions || [])[qNo - 1] || {};
      const p = Number(q.page || 1);
      return p >= 1 ? p : 1;
    }

    function canAnswerThisNumber(no) {
      if (!remedialMode) return true;
      return remedialList.includes(no);
    }

    function renderHeader() {
      setText('uiSession', `${sessionNo}/${totalSessions}`);
      const mode = remedialMode ? 'Remedial' : 'Normal';
      setText('uiMode', mode);
      const answered = answeredCount(accessId, sessionNo, perSession);
      setText('uiAnswered', String(answered));
      setText('uiTotal', String(perSession));
    }

    function renderAnswerGrid() {
      if (!answerGrid) return;
      answerGrid.innerHTML = '';

      for (let i = 1; i <= perSession; i++) {
        const ans = getAns(accessId, sessionNo, i);
        const ended = isEnded(accessId, sessionNo) || !!rec.completed;
        const disabled = ended || !canAnswerThisNumber(i);

        const row = document.createElement('div');
        row.className = `answer-row ${i === questionNo ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
        row.setAttribute('role', 'button');
        row.setAttribute('tabindex', disabled ? '-1' : '0');
        row.setAttribute('aria-label', `Soal ${i}`);

        const colNo = document.createElement('div');
        colNo.className = 'col-no';
        colNo.textContent = String(i);

        const colAns = document.createElement('div');
        colAns.className = 'col-ans';
        colAns.textContent = ans ? String(ans).toUpperCase() : '-';

        row.appendChild(colNo);
        row.appendChild(colAns);

        const onOpen = async () => {
          if (disabled) return;
          await openQuestion(i);
        };

        row.addEventListener('click', onOpen);
        row.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            onOpen();
          }
        });

        answerGrid.appendChild(row);
      }
    }

    async function loadSessionPdf(sessNo) {
      const sess = getSessionObj(paket, sessNo);
      const pdfPath = String(sess?.pdf || '').trim();

      currentPdfUrl = pdfPath;
      // meta akan diupdate saat renderPdf/openQuestion

      if (!pdfPath) {
        showAlert('PDF untuk sesi ini belum di-set di JSON paket.', 'warning');
        pdfDoc = null;
        useIframeViewer = false;
        return;
      }

      if (pdfLoading) pdfLoading.classList.remove('d-none');
      // default: pakai PDF.js canvas. jika gagal (sering karena CORS untuk link luar), fallback ke iframe.
      useIframeViewer = false;
      try {
        pdfDoc = await loadPdf(pdfPath);
        hideAlert();
        if (pdfCanvas) pdfCanvas.classList.remove('d-none');
        if (pdfIframe) pdfIframe.classList.add('d-none');
      } catch (e) {
        console.error(e);
        pdfDoc = null;
        useIframeViewer = true;
        // Fallback: iframe biasanya tetap bisa menampilkan PDF dari URL luar.
        if (pdfIframe) {
          pdfIframe.src = pdfPath;
          pdfIframe.classList.remove('d-none');
        }
        if (pdfCanvas) pdfCanvas.classList.add('d-none');
        showAlert(
          'PDF tidak bisa dimuat via PDF.js (kemungkinan CORS / akses lintas domain). Saya gunakan mode fallback (iframe). Jika masih blank, pastikan URL PDF bisa dibuka publik dan server mengizinkan embed.',
          'warning'
        );
      } finally {
        if (pdfLoading) pdfLoading.classList.add('d-none');
      }
    }

    async function renderPdf(pageNo) {
      const safePage = Math.max(1, Number(pageNo || 1));
      currentPdfPage = safePage;

      // Update meta text
      setText('uiQuestionMeta', `NO. ${questionNo} DARI ${perSession}`);

      // If PDF.js available
      if (pdfDoc && pdfCanvas && !useIframeViewer) {
        const bounded = clamp(safePage, 1, pdfDoc.numPages);
        currentPdfPage = bounded;
        if (pdfLoading) pdfLoading.classList.remove('d-none');
        await renderPdfPage(pdfDoc, pdfCanvas, bounded, pdfScale);
        if (pdfLoading) pdfLoading.classList.add('d-none');
        setText('uiPdfMeta', `Halaman: ${bounded}/${pdfDoc.numPages}`);
        return;
      }

      // Fallback iframe viewer
      if (pdfIframe && currentPdfUrl) {
        // Banyak PDF viewer mendukung #page=... untuk pindah halaman
        pdfIframe.src = `${currentPdfUrl}#page=${safePage}`;
      }
      setText('uiPdfMeta', `Halaman: ${safePage}`);
    }

    async function openQuestion(no) {
      questionNo = clamp(Number(no), 1, perSession);
      renderHeader();
      renderAnswerGrid();

      const mapped = getMappedPage(sessionNo, questionNo);
      await renderPdf(mapped);
    }

    function setAnswer(choice) {
      if (isEnded(accessId, sessionNo) || rec.completed) return;
      if (!canAnswerThisNumber(questionNo)) return;
      setAns(accessId, sessionNo, questionNo, choice);
      renderHeader();
      renderAnswerGrid();
    }

    // Timer dihapus (revisi): tidak ada stopTimer/startTimer.

    function enterRemedialMode(list) {
      remedialMode = true;
      remedialList = uniq(list || []);
      setText('uiMode', 'Remedial');
      if (btnFinishSession) btnFinishSession.classList.add('d-none');
      if (btnFinishRemedial) btnFinishRemedial.classList.remove('d-none');

      renderHeader();
      renderAnswerGrid();
      if (remedialList.length) openQuestion(remedialList[0]);
    }

    function exitRemedialMode() {
      remedialMode = false;
      remedialList = [];
      setText('uiMode', 'Normal');
      if (btnFinishRemedial) btnFinishRemedial.classList.add('d-none');
      if (btnFinishSession) btnFinishSession.classList.remove('d-none');
      renderHeader();
      renderAnswerGrid();
    }

    function showResultModal() {
      if (!resultModal || !resultContent || !btnModalEndSession || !btnModalRemedial) return;

      const stats = computeStats(accessId, paket, sessionNo);
      saveScore(accessId, sessionNo, stats);

      const needRemedial = uniq([...(stats.wrongNos || []), ...(stats.blankNos || [])]);

      const blocks = [];
      blocks.push(`<div class="fw-bold">Sesi ${sessionNo} selesai</div>`);
      blocks.push(`<div class="small text-secondary">Terjawab: ${stats.answered}/${stats.total} • Kosong: ${stats.blank}</div>`);
      blocks.push(`<div class="small text-secondary">Benar: ${stats.correct} • Salah: ${stats.wrong}</div>`);
      if (needRemedial.length) {
        blocks.push(`<div class="small">Perlu remedial: <span class="fw-semibold">${needRemedial.join(', ')}</span></div>`);
      } else {
        blocks.push(`<div class="alert alert-success mb-0">Semua jawaban sudah benar / tidak ada yang perlu diperbaiki.</div>`);
      }

      resultContent.innerHTML = blocks.join('');

      btnModalRemedial.classList.toggle('d-none', needRemedial.length === 0);

      btnModalRemedial.onclick = () => {
        resultModal.hide();
        enterRemedialMode(needRemedial);
      };

      btnModalEndSession.onclick = () => {
        resultModal.hide();
        finalizeSession();
      };

      resultModal.show();
    }

    function finalizeSession() {
      // Replace score is already saved (last computeStats) -> final
      setEnded(accessId, sessionNo, true);
      exitRemedialMode();

      // Next session
      let next = sessionNo + 1;
      while (next <= totalSessions && isEnded(accessId, next)) next++;

      if (next > totalSessions) {
        // Done all
        ACCESS()?.updateAccess?.(accessId, { completed: true, started: true });
        showAlert('Ujian selesai. Terima kasih.', 'success');
        return;
      }

      sessionNo = next;
      setLS(keyCurrentSession(accessId), String(sessionNo));
      questionNo = 1;

      // load new pdf and restart
      (async () => {
        renderHeader();
        renderAnswerGrid();
        await loadSessionPdf(sessionNo);
        await openQuestion(1);
      })();
    }

    // --------------------------
    // Events
    // --------------------------
    document.querySelectorAll('[data-choice]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const c = String(btn.getAttribute('data-choice') || '').trim().toUpperCase();
        if (!c) return;
        setAnswer(c);
      });
    });

    // Navigasi soal hanya pakai tombol < dan >
    function gotoNeighborQuestion(delta) {
      if (remedialMode) {
        const idx = Math.max(0, remedialList.indexOf(questionNo));
        const nextIdx = clamp(idx + delta, 0, Math.max(0, remedialList.length - 1));
        const target = remedialList[nextIdx] || questionNo;
        openQuestion(target);
        return;
      }
      openQuestion(clamp(questionNo + delta, 1, perSession));
    }

    btnPrevQ?.addEventListener('click', () => gotoNeighborQuestion(-1));
    btnNextQ?.addEventListener('click', () => gotoNeighborQuestion(1));

    btnFinishSession?.addEventListener('click', () => {
      if (isEnded(accessId, sessionNo) || rec.completed) return;
      const ok = window.confirm(`Selesaikan Sesi ${sessionNo}? (Kamu masih bisa remedial kalau ada yang salah/kosong)`);
      if (!ok) return;
      showResultModal();
    });

    btnFinishRemedial?.addEventListener('click', () => {
      // selesai remedial -> hasil sesi di-replace (hitung ulang) lalu final
      const ok = window.confirm('Selesaikan Remedial dan akhiri sesi?');
      if (!ok) return;
      // compute & save latest score
      const stats = computeStats(accessId, paket, sessionNo);
      saveScore(accessId, sessionNo, stats);
      finalizeSession();
    });

    // --------------------------
    // Initial render
    // --------------------------
    renderHeader();
    renderAnswerGrid();
    await loadSessionPdf(sessionNo);
    await openQuestion(1);
    // Timer dihapus.
  });
})();
