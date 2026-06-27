document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!confirm(element.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  const initCustomerCombobox = (pickerId, hiddenId, resultsId) => {
    const customerPicker = document.getElementById(pickerId);
    const customerId = document.getElementById(hiddenId);
    const customerResults = document.getElementById(resultsId);
    if (!customerPicker || !customerId || !customerResults) {
      return;
    }

    const resultItems = Array.from(customerResults.querySelectorAll('.customer-result-item'));
    const emptyState = customerResults.querySelector('[data-empty]');

    const normalizeCustomerText = (value) =>
      value.replace(/[۰-۹٠-٩]/g, (ch) => {
        const map = {
          '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
          '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
          '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
          '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
        };
        return map[ch] || ch;
      }).replace(/\u200c/g, '').replace(/[يىئ]/g, 'ی').replace(/ك/g, 'ک').replace(/ة/g, 'ه')
        .replace(/[أإٱآ]/g, 'ا').replace(/ؤ/g, 'و');

    const selectCustomer = (item) => {
      customerPicker.value = item.dataset.label || item.textContent.trim();
      customerId.value = item.dataset.id || '';
      customerPicker.setCustomValidity('');
      customerResults.hidden = true;
    };

    const renderCustomerResults = () => {
      const query = normalizeCustomerText(customerPicker.value.trim().toLowerCase());
      let visibleCount = 0;
      let exactMatch = false;

      resultItems.forEach((item) => {
        const label = item.dataset.label || item.textContent.trim();
        const haystack = normalizeCustomerText((item.dataset.search || label).toLowerCase());
        const matches = query === '' || haystack.includes(query);
        const shouldShow = matches && visibleCount < 20;
        item.hidden = !shouldShow;

        if (matches) {
          visibleCount += 1;
        }
        if (query !== '' && normalizeCustomerText(label.toLowerCase()) === query) {
          exactMatch = true;
          customerId.value = item.dataset.id || '';
        }
      });

      if (emptyState) {
        emptyState.hidden = visibleCount !== 0;
      }
      customerResults.hidden = false;

      if (query === '') {
        customerId.value = '';
        customerPicker.setCustomValidity('');
      } else if (!exactMatch) {
        customerId.value = '';
        customerPicker.setCustomValidity('لطفاً یک مشتری را از لیست انتخاب کنید.');
      } else {
        customerPicker.setCustomValidity('');
      }
    };

    const syncCustomerId = () => {
      if (customerPicker.value.trim() !== '' && customerId.value === '') {
        customerPicker.setCustomValidity('لطفاً یک مشتری را از لیست انتخاب کنید.');
      } else {
        customerPicker.setCustomValidity('');
      }
    };

    customerPicker.addEventListener('input', renderCustomerResults);
    customerPicker.addEventListener('focus', renderCustomerResults);
    resultItems.forEach((item) => {
      item.addEventListener('click', () => selectCustomer(item));
    });
    document.addEventListener('click', (event) => {
      if (!customerResults.contains(event.target) && event.target !== customerPicker) {
        customerResults.hidden = true;
      }
    });
    if (customerPicker.form) {
      customerPicker.form.addEventListener('submit', syncCustomerId);
    }
    syncCustomerId();
  };

  initCustomerCombobox('purchase-customer-picker', 'purchase-customer-id', 'purchase-customer-results');
  initCustomerCombobox('service-customer-picker', 'service-customer-id', 'service-customer-results');
  initCustomerCombobox('followup-customer-picker', 'followup-customer-id', 'followup-customer-results');
  initCustomerCombobox('due-date-customer-picker', 'due-date-customer-id', 'due-date-customer-results');
  initCustomerCombobox('due-date-invoice-picker', 'due-date-purchase-id', 'due-date-invoice-results');

  document.querySelectorAll('[data-money]').forEach((input) => {
    const normalizeDigits = (value) =>
      value.replace(/[۰-۹٠-٩]/g, (ch) => {
        const map = {
          '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
          '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
          '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
          '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
        };
        return map[ch] || ch;
      });

    const formatMoney = () => {
      let raw = normalizeDigits(input.value).replace(/[^\d.]/g, '');
      if (raw.includes('.')) {
        raw = raw.split('.')[0];
      }
      raw = raw.replace(/\D/g, '');
      input.value = raw === '' ? '' : raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    input.addEventListener('input', formatMoney);
    formatMoney();
  });
});
