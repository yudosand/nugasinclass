(() => {
  'use strict';

  const ACCESS_INDEX_KEY = 'nc_access_index_v1';

  function safeJsonParse(raw, fallback) {
    try { return JSON.parse(raw); } catch { return fallback; }
  }

  function loadAccessIndex() {
    const raw = localStorage.getItem(ACCESS_INDEX_KEY);
    const idx = safeJsonParse(raw, {});
    return (idx && typeof idx === 'object') ? idx : {};
  }

  function saveAccessIndex(idx) {
    localStorage.setItem(ACCESS_INDEX_KEY, JSON.stringify(idx || {}));
  }

  function randomChunk(len = 4) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let out = '';
    for (let i = 0; i < len; i++) {
      out += chars[Math.floor(Math.random() * chars.length)];
    }
    return out;
  }

  function generateAccessId(courseId) {
    const prefix = String(courseId || 'COURSE').toUpperCase().replace(/[^A-Z0-9]+/g, '').slice(0, 8) || 'COURSE';
    return `NC-${prefix}-${randomChunk(4)}${randomChunk(4)}`;
  }

  function createAccess(course) {
    const idx = loadAccessIndex();

    let accessId = '';
    for (let i = 0; i < 15; i++) {
      const candidate = generateAccessId(course?.id);
      if (!idx[candidate]) { accessId = candidate; break; }
    }
    if (!accessId) {
      // fallback (extremely unlikely)
      accessId = `${generateAccessId(course?.id)}-${Date.now()}`;
    }

    const record = {
      access_id: accessId,
      course_id: String(course?.id || '').trim(),
      course_title: String(course?.title || '').trim(),
      paket_json: String(course?.json || '').trim(),
      price: Number(course?.price || 0),
      currency: String(course?.currency || 'IDR'),
      paid: true,
      started: false,
      completed: false,
      created_at: new Date().toISOString()
    };

    idx[accessId] = record;
    saveAccessIndex(idx);

    return record;
  }

  function getAccess(accessId) {
    if (!accessId) return null;
    const idx = loadAccessIndex();
    return idx[String(accessId).trim()] || null;
  }

  function updateAccess(accessId, patch) {
    const idx = loadAccessIndex();
    const id = String(accessId || '').trim();
    if (!id || !idx[id]) return null;
    idx[id] = { ...idx[id], ...(patch || {}) };
    saveAccessIndex(idx);
    return idx[id];
  }

  function listAccess() {
    const idx = loadAccessIndex();
    return Object.values(idx);
  }

  // Exam storage helpers
  function clearExamProgress(accessId) {
    const prefix = `nc_exam|${String(accessId || '').trim()}|`;
    const keys = [];
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (k && k.startsWith(prefix)) keys.push(k);
    }
    keys.forEach(k => localStorage.removeItem(k));
  }

  window.NCAccess = {
    createAccess,
    getAccess,
    updateAccess,
    listAccess,
    clearExamProgress
  };
})();
