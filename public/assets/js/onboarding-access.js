(() => {
  const consent = document.getElementById('agree_terms');
  const fields = document.getElementById('elearning-fields');
  if (!consent || !fields) return;
  const login = document.getElementById('elearning_login');
  const password = document.getElementById('elearning_password');
  const update = () => {
    fields.hidden = !consent.checked;
    fields.disabled = !consent.checked;
    consent.setAttribute('aria-expanded', String(consent.checked));
    const required = consent.checked && (fields.dataset.hasAccess !== '1' || login.value !== '' || password.value !== '');
    login.required = required; password.required = required;
    if (!consent.checked) { login.value = ''; password.value = ''; }
  };
  consent.addEventListener('change', update);
  login.addEventListener('input', update);
  password.addEventListener('input', update);
  window.addEventListener('pagehide', () => { login.value = ''; password.value = ''; });
  update();
})();
