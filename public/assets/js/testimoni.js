(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    if (!modal || !modalImg) return;

    modal.addEventListener('show.bs.modal', (event) => {
      const btn = event.relatedTarget;
      const src = btn?.getAttribute('data-img');
      if (src) modalImg.src = src;
    });

    modal.addEventListener('hidden.bs.modal', () => {
      modalImg.src = '';
    });
  });
})();
