document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!confirm(element.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  const customerPicker = document.getElementById('purchase-customer-picker');
  const customerId = document.getElementById('purchase-customer-id');
  const customerOptions = document.getElementById('purchase-customer-options');
  if (customerPicker && customerId && customerOptions) {
    const optionsByLabel = new Map(
      [...customerOptions.options].map((option) => [option.value, option.dataset.id || ''])
    );

    const syncCustomerId = () => {
      customerId.value = optionsByLabel.get(customerPicker.value.trim()) || '';
      if (customerPicker.value.trim() !== '' && customerId.value === '') {
        customerPicker.setCustomValidity('لطفاً یک مشتری را از لیست انتخاب کنید.');
      } else {
        customerPicker.setCustomValidity('');
      }
    };

    customerPicker.addEventListener('input', syncCustomerId);
    customerPicker.form?.addEventListener('submit', syncCustomerId);
    syncCustomerId();
  }

  document.querySelectorAll('[data-money]').forEach((input) => {
    const normalizeDigits = (value) =>
      value.replace(/[۰-۹٠-٩]/g, (ch) => {
        const map = {
          '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
          '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
          '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
          '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
        };
        return map[ch] ?? ch;
      });

    const formatMoney = () => {
      const raw = normalizeDigits(input.value).replace(/[^\d]/g, '');
      input.value = raw === '' ? '' : raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    input.addEventListener('input', formatMoney);
    formatMoney();
  });
});
