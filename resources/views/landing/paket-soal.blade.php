<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  @include('partials.favicon')
  <title>Ujian Simulasi - Nugasin Class</title>

  <script>
    (function () {
      try {
        var t = localStorage.getItem('nc_theme');
        if (!t) t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', t);
      } catch (e) {}
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}" />
</head>

<body class="exam-body">
  <div class="exam-topbar exam-topbar--blue border-bottom">
    <div class="container-fluid px-3 py-2">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <img class="exam-logo" src="{{ asset('assets/img/logo-nugasin-class.png') }}" alt="Logo Nugasin Class" />
          <div>
            <div class="fw-bold small text-body" id="examSubjectTitle">Simulasi UAS</div>
            <div class="small text-secondary" style="font-size:0.75rem;" id="examSessionSubtitle">Nugasin Class CAT</div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3">
          <div class="text-end">
            <div class="small text-secondary" style="font-size:0.75rem;">Sisa Waktu</div>
            <div class="fw-bold font-monospace text-primary fs-5" id="examTimer">--:--</div>
          </div>
          <button id="btnFinishExam" class="btn btn-danger btn-sm rounded-pill px-3">Selesai Ujian</button>
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid px-3 py-3">
    <div class="exam-layout-v2">
      <!-- Sidebar Answer Sheet -->
      <div class="exam-sidebar-v2">
        <div class="exam-sidebar-card">
          <div class="exam-sidebar-header">
            <div class="fw-bold text-body">Lembar Jawaban</div>
            <span id="answeredCounter" class="badge bg-primary rounded-pill">0/50</span>
          </div>

          <div class="answer-sheet">
            <div class="answer-sheet-head">
              <span class="col-no">No</span>
              <span class="col-ans">Jawaban</span>
            </div>
            <div class="answer-sheet-body" id="answerSheetRows">
              <!-- Dynamically populated by paket-soal.js -->
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content (PDF / Soal Viewer) -->
      <div class="exam-main-v2">
        <div class="exam-toolbar">
          <div class="exam-toolbar-left">
            <span class="fw-bold me-2">Soal No <span id="currentQuestionNum">1</span></span>
            <div class="btn-group exam-choice-group" role="group" id="choiceGroup">
              <button type="button" class="btn btn-outline-primary" data-choice="A">A</button>
              <button type="button" class="btn btn-outline-primary" data-choice="B">B</button>
              <button type="button" class="btn btn-outline-primary" data-choice="C">C</button>
              <button type="button" class="btn btn-outline-primary" data-choice="D">D</button>
            </div>
          </div>
          <div class="exam-toolbar-right">
            <button id="btnPrev" class="btn btn-outline-secondary btn-sm rounded-pill">← Prev</button>
            <button id="btnNext" class="btn btn-primary btn-sm rounded-pill px-3">Next →</button>
          </div>
        </div>

        <div class="exam-pdf-card mt-3">
          <div class="pdf-viewer">
            <iframe id="pdfFrame" class="pdf-iframe" src=""></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/js/app.js') }}" defer></script>
  <script src="{{ asset('assets/js/nc-access.js') }}" defer></script>
  <script src="{{ asset('assets/js/paket-soal.js') }}" defer></script>
</body>
</html>
