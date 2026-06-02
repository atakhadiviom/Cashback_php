document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!confirm(element.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });
});
