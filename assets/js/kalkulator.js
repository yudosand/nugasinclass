(() => {
  'use strict';

  const gradeRules = {
    mudah: [
      { min: 80, grade: 'A', mutu: '4.0', desc: 'Sangat Baik' },
      { min: 75, grade: 'A-', mutu: '3.5', desc: 'Sangat Baik' },
      { min: 70, grade: 'B', mutu: '3.0', desc: 'Baik' },
      { min: 60, grade: 'B-', mutu: '2.5', desc: 'Baik' },
      { min: 55, grade: 'C', mutu: '2.0', desc: 'Cukup' },
      { min: 50, grade: 'C-', mutu: '1.5', desc: 'Cukup' },
      { min: 40, grade: 'D', mutu: '1.0', desc: 'Kurang' },
      { min: -Infinity, grade: 'E', mutu: '0.0', desc: 'Gagal' }
    ],
    sedang: [
      { min: 75, grade: 'A', mutu: '4.0', desc: 'Sangat Baik' },
      { min: 70, grade: 'A-', mutu: '3.5', desc: 'Sangat Baik' },
      { min: 65, grade: 'B', mutu: '3.0', desc: 'Baik' },
      { min: 55, grade: 'B-', mutu: '2.5', desc: 'Baik' },
      { min: 50, grade: 'C', mutu: '2.0', desc: 'Cukup' },
      { min: 45, grade: 'C-', mutu: '1.5', desc: 'Cukup' },
      { min: 35, grade: 'D', mutu: '1.0', desc: 'Kurang' },
      { min: -Infinity, grade: 'E', mutu: '0.0', desc: 'Gagal' }
    ],
    sulit: [
      { min: 70, grade: 'A', mutu: '4.0', desc: 'Sangat Baik' },
      { min: 65, grade: 'A-', mutu: '3.5', desc: 'Sangat Baik' },
      { min: 60, grade: 'B', mutu: '3.0', desc: 'Baik' },
      { min: 50, grade: 'B-', mutu: '2.5', desc: 'Baik' },
      { min: 45, grade: 'C', mutu: '2.0', desc: 'Cukup' },
      { min: 40, grade: 'C-', mutu: '1.5', desc: 'Cukup' },
      { min: 30, grade: 'D', mutu: '1.0', desc: 'Kurang' },
      { min: -Infinity, grade: 'E', mutu: '0.0', desc: 'Gagal' }
    ]
  };

  function toggleInputMode() {
    const layanan = document.getElementById('layanan')?.value;
    const standar = document.getElementById('inputStandar');
    const praktik = document.getElementById('inputPraktik');
    if (!standar || !praktik) return;

    if (layanan === 'Berpraktik') {
      standar.classList.add('d-none');
      praktik.classList.remove('d-none');
    } else {
      standar.classList.remove('d-none');
      praktik.classList.add('d-none');
    }
  }

  function updateCard(type, score, rules) {
    const result = rules.find(r => score >= r.min) || rules[rules.length - 1];
    const suffix = type.charAt(0).toUpperCase() + type.slice(1);
    const gradeEl = document.getElementById(`grade${suffix}`);
    const descEl = document.getElementById(`desc${suffix}`);
    const mutuEl = document.getElementById(`mutu${suffix}`);
    if (gradeEl) gradeEl.textContent = result.grade;
    if (descEl) descEl.textContent = result.desc;
    if (mutuEl) mutuEl.textContent = `Angka Mutu: ${result.mutu}`;
  }

  function showResult() {
    document.getElementById('inputSection')?.classList.add('d-none');
    document.getElementById('resultSection')?.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function showInput() {
    document.getElementById('resultSection')?.classList.add('d-none');
    document.getElementById('inputSection')?.classList.remove('d-none');
  }

  function resetForm() {
    const ids = ['benarUas', 'nilaiTugas', 't1', 't2', 't3', 'matkul'];
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
  }

  function hitungHasil() {
    const matkul = document.getElementById('matkul')?.value || 'Mata Kuliah';
    const layanan = document.getElementById('layanan')?.value || '-';
    const benar = parseFloat(document.getElementById('benarUas')?.value);
    const totalSoal = parseFloat(document.getElementById('jumlahSoal')?.value);

    if (Number.isNaN(benar)) {
      alert('Mohon isi Jumlah Jawaban Benar UAS.');
      return;
    }
    if (!Number.isFinite(totalSoal) || totalSoal <= 0) {
      alert('Jumlah soal UAS tidak valid.');
      return;
    }
    if (benar > totalSoal) {
      alert('Jumlah benar tidak boleh melebihi jumlah soal!');
      return;
    }

    // Nilai tugas
    let finalTugas = 0;
    if (layanan === 'Berpraktik') {
      const t1 = parseFloat(document.getElementById('t1')?.value) || 0;
      const t2 = parseFloat(document.getElementById('t2')?.value) || 0;
      const t3 = parseFloat(document.getElementById('t3')?.value) || 0;
      finalTugas = (t1 + t2 + t3) / 3;
    } else {
      finalTugas = parseFloat(document.getElementById('nilaiTugas')?.value) || 0;
    }

    // Nilai UAS murni
    const nilaiUASRaw = (benar / totalSoal) * 100;

    let nilaiAkhir = 0;
    let formulaDisplay = '';
    let noteDisplay = '';

    // ATURAN 30%
    if (nilaiUASRaw < 30) {
      nilaiAkhir = nilaiUASRaw;
      formulaDisplay = `Nilai UAS (${nilaiUASRaw.toFixed(2)}) < 30.`;
      noteDisplay = 'Nilai Tugas TIDAK berkontribusi karena UAS < 30 (Gagal Mutu).';
    } else {
      nilaiAkhir = (0.3 * finalTugas) + (0.7 * nilaiUASRaw);
      formulaDisplay = `(30% x ${finalTugas.toFixed(2)}) + (70% x ${nilaiUASRaw.toFixed(2)})`;
      noteDisplay = 'Nilai tugas berkontribusi penuh karena UAS >= 30.';
    }

    // Render hasil
    const resMatkul = document.getElementById('resMatkul');
    const resScore = document.getElementById('resScore');
    const resType = document.getElementById('resType');
    const formulaText = document.getElementById('formulaText');
    const contributionText = document.getElementById('contributionText');

    if (resMatkul) resMatkul.textContent = matkul;
    if (resScore) resScore.textContent = nilaiAkhir.toFixed(2);
    if (resType) resType.textContent = `Jenis: ${layanan}`;
    if (formulaText) formulaText.textContent = formulaDisplay;
    if (contributionText) contributionText.textContent = noteDisplay;

    updateCard('mudah', nilaiAkhir, gradeRules.mudah);
    updateCard('sedang', nilaiAkhir, gradeRules.sedang);
    updateCard('sulit', nilaiAkhir, gradeRules.sulit);

    showResult();
  }

  document.addEventListener('DOMContentLoaded', () => {
    toggleInputMode();

    document.getElementById('layanan')?.addEventListener('change', toggleInputMode);

    document.getElementById('btnHitung')?.addEventListener('click', (e) => {
      e.preventDefault();
      hitungHasil();
    });

    document.getElementById('btnReset')?.addEventListener('click', (e) => {
      e.preventDefault();
      resetForm();
      showInput();
      toggleInputMode();
    });
  });
})();
