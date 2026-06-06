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
});
