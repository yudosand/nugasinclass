(() => {
  'use strict';
  const input = document.getElementById('withdraw_amount');
  if (!input) return;

  function formatAmount() {
    input.setCustomValidity('');
    const value = input.value;
    if (!value) return;
    if (!/^[0-9.]+$/.test(value)) {
      input.setCustomValidity('Masukkan nominal Rupiah bulat, contoh 150.000.');
      return;
    }
    // Preserve cursor position when editing a digit in the middle of the amount.
    const digitOffset = value.slice(0, input.selectionStart ?? value.length).replace(/\./g, '').length;
    const digits = value.replace(/\./g, '').replace(/^0+(?=\d)/, '');
    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    let position = 0;
    let count = 0;
    while (position < input.value.length && count < digitOffset) {
      if (input.value[position] !== '.') count++;
      position++;
    }
    input.setSelectionRange(position, position);
    const amount = Number(digits);
    if (!Number.isSafeInteger(amount) || amount < 1) {
      input.setCustomValidity('Nominal pencairan minimal Rp1.');
    } else if (amount > Number(input.dataset.max)) {
      input.setCustomValidity('Nominal pencairan tidak boleh melebihi saldo tersedia.');
    }
  }
  input.addEventListener('input', formatAmount);
  input.form.addEventListener('submit', (event) => {
    formatAmount();
    if (!input.reportValidity()) event.preventDefault();
  });
  formatAmount();
})();
