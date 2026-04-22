// public/js/profile_post_delete.js
// Profile: ⋯ -> Xóa bài viết (AJAX)

(function () {
  // avoid double install
  if (window.__profilePostDeleteInstalled === true) return;
  window.__profilePostDeleteInstalled = true;

  let menuEl = null;
  let toggleBtn = null;

  function closeMenu() {
    if (menuEl) menuEl.remove();
    menuEl = null;
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn = null;

    document.removeEventListener('click', onDocClick, true);
    document.removeEventListener('keydown', onKeyDown, true);
  }

  function onDocClick(e) {
    if (!menuEl || !toggleBtn) return;
    if (!menuEl.contains(e.target) && !toggleBtn.contains(e.target)) closeMenu();
  }

  function onKeyDown(e) {
    if (e.key === 'Escape') closeMenu();
  }

  async function deletePost(postId) {
    if (!postId) return;
    if (!confirm('Bạn có chắc muốn xóa bài viết này?')) return;

    const form = new FormData();
    form.append('post_id', String(postId));

    let res;
    try {
      res = await fetch('/public/profile.php?action=delete_post', {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
      });
    } catch (err) {
      console.error(err);
      alert('Không thể kết nối server.');
      return;
    }

    const data = await res.json().catch(() => null);
    if (!data || !data.ok) {
      alert('Xóa thất bại: ' + (data?.error || 'unknown_error'));
      return;
    }

    // remove post from DOM
    const postEl =
      document.querySelector(`.post[data-post-id="${postId}"]`) ||
      document.getElementById(`post-${postId}`);
    if (postEl) postEl.remove();

    closeMenu();
  }

  function openMenu(btn) {
    closeMenu();

    const postId = Number(btn.dataset.postId || 0);
    const canDelete = (btn.dataset.canDelete || '0') === '1';
    if (!postId || !canDelete) return;

    const postEl = btn.closest('.post');
    if (!postEl) return;

    // contain absolute menu
    const cs = getComputedStyle(postEl);
    if (cs.position === 'static') postEl.style.position = 'relative';

    const m = document.createElement('div');
    m.className = 'post-menu';
    m.setAttribute('role', 'menu');

    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'post-menu__item post-menu__item--danger';
    del.setAttribute('role', 'menuitem');
    del.textContent = 'Xóa bài viết';
    del.addEventListener('click', (e) => {
      e.stopPropagation();
      deletePost(postId);
    });

    m.appendChild(del);
    postEl.appendChild(m);

    // position below the ⋯ button
    const btnRect = btn.getBoundingClientRect();
    const postRect = postEl.getBoundingClientRect();
    const leftInPost = btnRect.left - postRect.left;
    const topInPost = btnRect.top - postRect.top;

    m.style.position = 'absolute';
    m.style.top = (topInPost + btn.offsetHeight + 6) + 'px';
    const rightInPost = postRect.width - (leftInPost + btn.offsetWidth);
    m.style.right = Math.max(8, rightInPost) + 'px';
    m.style.zIndex = '999';

    menuEl = m;
    toggleBtn = btn;
    btn.setAttribute('aria-expanded', 'true');

    setTimeout(() => {
      document.addEventListener('click', onDocClick, true);
      document.addEventListener('keydown', onKeyDown, true);
      del.focus();
    }, 0);
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.post__more--profile');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    if (toggleBtn === btn && menuEl) {
      closeMenu();
      return;
    }
    openMenu(btn);
  }, true);
})();