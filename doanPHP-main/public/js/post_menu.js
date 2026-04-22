// public/js/post_menu.js
// Menu cho nút 3 chấm (.post__more) trên mỗi post.
// FIX: đặt menu absolute trong post để không bị lệch vị trí.

(function () {
  if (window.__postMenuInstalled) return;
  window.__postMenuInstalled = true;

  let currentMenu = null;
  let currentToggle = null;

  function closeMenu() {
    if (currentMenu) {
      currentMenu.remove();
      currentMenu = null;
    }
    if (currentToggle) {
      currentToggle.setAttribute('aria-expanded', 'false');
      currentToggle = null;
    }
    document.removeEventListener('click', onDocClick, true);
    document.removeEventListener('keydown', onKeyDown, true);
  }

  function onDocClick(e) {
    if (!currentMenu || !currentToggle) return;
    if (!currentMenu.contains(e.target) && !currentToggle.contains(e.target)) closeMenu();
  }

  function onKeyDown(e) {
    if (e.key === 'Escape') closeMenu();
  }

  function openMenu(toggleBtn) {
    closeMenu();

    const href = toggleBtn.dataset.profileHref || toggleBtn.getAttribute('data-profile-href');
    if (!href) return;

    const postEl = toggleBtn.closest('.post');
    if (!postEl) return;

    // đảm bảo post là containing block
    const postComputed = window.getComputedStyle(postEl);
    if (postComputed.position === 'static') {
      postEl.style.position = 'relative';
    }

    const menu = document.createElement('div');
    menu.className = 'post-menu';
    menu.setAttribute('role', 'menu');

    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'post-menu__item';
    item.setAttribute('role', 'menuitem');
    item.textContent = 'Xem trang cá nhân';
    item.addEventListener('click', function (ev) {
      ev.stopPropagation();
      window.location.href = href;
    });

    menu.appendChild(item);

    // đặt menu vào post để tránh lệch layout
    postEl.appendChild(menu);

    // position absolute relative to the post, based on button offsets
    const btnRect = toggleBtn.getBoundingClientRect();
    const postRect = postEl.getBoundingClientRect();

    // khoảng cách tính theo tọa độ trong post
    const leftInPost = btnRect.left - postRect.left;
    const topInPost = btnRect.top - postRect.top;

    // đặt menu canh phải nút và nằm dưới một chút
    menu.style.position = 'absolute';
    menu.style.top = (topInPost + toggleBtn.offsetHeight + 6) + 'px';

    // canh theo "right" để đỡ bị tràn, nhưng vẫn dựa theo vị trí nút
    // right = postWidth - (leftInPost + btnWidth)
    const rightInPost = (postRect.width - (leftInPost + toggleBtn.offsetWidth));
    menu.style.right = Math.max(8, rightInPost) + 'px';
    menu.style.zIndex = '999';

    currentMenu = menu;
    currentToggle = toggleBtn;
    toggleBtn.setAttribute('aria-expanded', 'true');

    setTimeout(() => {
      document.addEventListener('click', onDocClick, true);
      document.addEventListener('keydown', onKeyDown, true);
      item.focus();
    }, 0);
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.post__more--home');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    if (currentToggle === btn && currentMenu) {
      closeMenu();
      return;
    }
    openMenu(btn);
  });
})();