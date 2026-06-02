document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!confirm(element.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  const searchInput = document.getElementById('purchase-customer-search');
  const customerSelect = document.getElementById('purchase-customer-select');
  if (searchInput && customerSelect) {
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

    const filterOptions = () => {
      const query = normalizeDigits(searchInput.value.trim().toLowerCase());
      let visibleCount = 0;
      [...customerSelect.options].forEach((option, index) => {
        if (index === 0) {
          option.hidden = false;
          return;
        }
        const haystack = normalizeDigits((option.dataset.search || option.textContent || '').toLowerCase());
        const match = query === '' || haystack.includes(query);
        option.hidden = !match;
        if (match) {
          visibleCount += 1;
        }
      });
      if (query !== '' && customerSelect.selectedOptions[0]?.hidden) {
        customerSelect.value = '';
      }
      customerSelect.dataset.visibleCount = String(visibleCount);
    };

    searchInput.addEventListener('input', filterOptions);
    filterOptions();
  }
});
