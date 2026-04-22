// public/js/notif_bell.js
(function () {
  if (window.__notifBellInstalled) return;
  window.__notifBellInstalled = true;

  document.addEventListener('DOMContentLoaded', function () {
    const bell = document.getElementById('notif-bell');
    const popup = document.getElementById('notif-popup');
    if (!bell || !popup) return;

    function closePopup() {
      popup.classList.remove('is-open');
      bell.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', onOutsideClick, true);
      document.removeEventListener('keydown', onKeyDown, true);
    }

    function onOutsideClick(e) {
      if (!popup.contains(e.target) && !bell.contains(e.target)) {
        closePopup();
      }
    }

    function onKeyDown(e) {
      if (e.key === 'Escape') closePopup();
    }

    bell.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const isOpen = popup.classList.contains('is-open');
      if (isOpen) {
        closePopup();
        return;
      }
      popup.classList.add('is-open');
      bell.setAttribute('aria-expanded', 'true');

      setTimeout(() => {
        document.addEventListener('click', onOutsideClick, true);
        document.addEventListener('keydown', onKeyDown, true);
      }, 0);
    });
  });
})();