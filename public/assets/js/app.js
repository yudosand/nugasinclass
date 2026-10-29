(() => {
  'use strict';

  const AUTH_KEY = 'nc_role';
  const THEME_KEY = 'nc_theme';

  function setActiveNav() {
    const current = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
    document.querySelectorAll('[data-nav]')
      .forEach((a) => {
        const href = (a.getAttribute('href') || '').toLowerCase();
        if (href === current) {
          a.classList.add('active');
          a.setAttribute('aria-current', 'page');
        }
      });
  }

  function setYear() {
    document.querySelectorAll('[data-year]')
      .forEach(el => { el.textContent = String(new Date().getFullYear()); });
  }

  // ===== Theme (Bootstrap 5.3 Color Modes) =====
  function getPreferredTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    if (stored === 'light' || stored === 'dark') return stored;
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    return prefersDark ? 'dark' : 'light';
  }

  function getCurrentTheme() {
    return document.documentElement.getAttribute('data-bs-theme') || getPreferredTheme();
  }

  function setTheme(theme) {
    const t = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', t);
    localStorage.setItem(THEME_KEY, t);
    updateThemeButton(t);
  }

  function updateThemeButton(theme) {
    const btn = document.getElementById('btnTheme');
    if (!btn) return;

    // Show action text (what will happen when clicked)
    if (theme === 'dark') {
      btn.textContent = '☀️';
      btn.setAttribute('title', 'Ganti ke Light Mode');
      btn.setAttribute('aria-label', 'Ganti ke Light Mode');
    } else {
      btn.textContent = '🌙';
      btn.setAttribute('title', 'Ganti ke Dark Mode');
      btn.setAttribute('aria-label', 'Ganti ke Dark Mode');
    }
  }

  function initTheme() {
    // Head script may have already set data-bs-theme; ensure stored theme is consistent.
    const t = getCurrentTheme();
    setTheme(t);

    const btn = document.getElementById('btnTheme');
    btn?.addEventListener('click', () => {
      const next = getCurrentTheme() === 'dark' ? 'light' : 'dark';
      setTheme(next);
    });
  }

  // ===== Demo-only auth helpers (replace with real API auth later) =====
  window.NCAUTH = {
    isVIP() {
      return localStorage.getItem(AUTH_KEY) === 'vip';
    },
    setVIP() {
      localStorage.setItem(AUTH_KEY, 'vip');
    },
    logout() {
      localStorage.removeItem(AUTH_KEY);
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
        button.title = button.getAttribute('aria-label');
        button.querySelector('[data-eye-slash]')?.setAttribute('style', visible ? '' : 'display:none');
      });
      button.querySelector('[data-eye-slash]')?.setAttribute('style', 'display:none');
    });
    setYear();
    setActiveNav();
    initTheme();
  });
})();
