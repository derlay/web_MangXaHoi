// public/js/friends.js
// - Search user từ ô .search-input
// - Gửi friend request / accept / cancel
// NOTE: chỉ xử lý UI cơ bản, bạn có thể style sau.

(function () {
  const searchInputSelector = '.search-input';
  const resultsContainerClass = 'search-results'; // div mình sẽ thêm trong header

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  }

  function createResultsContainer(parent) {
    let c = parent.querySelector('.' + resultsContainerClass);
    if (!c) {
      c = document.createElement('div');
      c.className = resultsContainerClass;
      parent.appendChild(c);
    }
    return c;
  }

  function renderResults(container, users) {
    if (!container) return;
    if (!users || !users.length) {
      container.innerHTML = '<div class="search-empty">Không tìm thấy người dùng</div>';
      return;
    }
    const html = users.map(u => {
      const status = u.friend_status || 'none';
      let btnLabel = '';
      let btnClass = '';
      switch (status) {
        case 'accepted':
          btnLabel = 'Message';
          btnClass = 'friend-msg-btn';
          break;
        case 'pending_sent':
          btnLabel = 'Đã gửi';
          btnClass = 'friend-pending';
          break;
        case 'pending_received':
          btnLabel = 'Chấp nhận';
          btnClass = 'friend-accept-btn';
          break;
        case 'blocked':
          btnLabel = 'Đã chặn';
          btnClass = 'friend-blocked';
          break;
        default:
          btnLabel = 'Kết bạn';
          btnClass = 'friend-add-btn';
      }

      const fullName = escapeHtml(u.full_name || '');
      const username = escapeHtml(u.username || '');
      return `
        <div class="search-result-item" data-user-id="${u.user_id}">
          <div class="sr-main">
            <div class="sr-name">${fullName}</div>
            <div class="sr-username">@${username}</div>
          </div>
          <button class="sr-action-btn ${btnClass}" data-user-id="${u.user_id}">
            ${btnLabel}
          </button>
        </div>
      `;
    }).join('');
    container.innerHTML = html;
  }

  async function searchUsers(query, container) {
    if (!query) {
      container.innerHTML = '';
      return;
    }
    try {
      const res = await fetch('/public/actions/search_users.php?q=' + encodeURIComponent(query));
      const data = await res.json();
      if (!data.success) {
        console.error('search_users error', data.error);
        container.innerHTML = '<div class="search-error">Lỗi tìm kiếm</div>';
        return;
      }
      renderResults(container, data.results || []);
    } catch (err) {
      console.error('searchUsers failed', err);
      container.innerHTML = '<div class="search-error">Lỗi mạng</div>';
    }
  }

  async function sendFriendRequest(userId) {
    const body = 'to_user_id=' + encodeURIComponent(userId);
    const res = await fetch('/public/actions/friend_request.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body
    });
    return res.json();
  }

  async function acceptFriend(userId) {
    const body = 'user_id=' + encodeURIComponent(userId);
    const res = await fetch('/public/actions/friend_accept.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body
    });
    return res.json();
  }

  async function cancelFriend(userId) {
    const body = 'user_id=' + encodeURIComponent(userId);
    const res = await fetch('/public/actions/friend_cancel.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body
    });
    return res.json();
  }

  // Gõ trong ô search
  let searchTimeout = null;
  document.addEventListener('input', function (e) {
    const input = e.target.closest(searchInputSelector);
    if (!input) return;

    const wrapper = input.closest('.search-wrapper') || input.parentElement;
    const container = createResultsContainer(wrapper);
    const q = input.value.trim();

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      if (!q) {
        container.innerHTML = '';
        return;
      }
      searchUsers(q, container);
    }, 300); // debounce 300ms
  });

  // Click trên dropdown hoặc trong sidebar
  document.addEventListener('click', function (e) {
    // Kết bạn / chấp nhận / nhắn tin từ dropdown search
    const addBtn = e.target.closest('.friend-add-btn');
    const acceptBtn = e.target.closest('.friend-accept-btn');
    const msgBtn = e.target.closest('.friend-msg-btn');

    if (addBtn) {
      const uid = addBtn.dataset.userId;
      if (!uid) return;
      sendFriendRequest(uid).then(data => {
        if (!data.success) {
          console.error(data.error);
          return;
        }
        addBtn.textContent = 'Đã gửi';
        addBtn.classList.remove('friend-add-btn');
        addBtn.classList.add('friend-pending');
      }).catch(console.error);
      return;
    }

    if (acceptBtn) {
      const uid = acceptBtn.dataset.userId;
      if (!uid) return;
      acceptFriend(uid).then(data => {
        if (!data.success) {
          console.error(data.error);
          return;
        }
        acceptBtn.textContent = 'Message';
        acceptBtn.classList.remove('friend-accept-btn');
        acceptBtn.classList.add('friend-msg-btn');
      }).catch(console.error);
      return;
    }

    if (msgBtn) {
      const uid = msgBtn.dataset.userId;
      if (!uid) return;
      // TODO: đổi đường dẫn này theo route chat của bạn
      window.location.href = '/public/chat.php?user_id=' + encodeURIComponent(uid);
      return;
    }

    // Nút + trong Friend Suggestions (sidebar_right)
    const suggestBtn = e.target.closest('.suggest-add-btn');
    if (suggestBtn) {
      const uid = suggestBtn.dataset.userId;
      if (!uid) return;
      sendFriendRequest(uid).then(data => {
        if (!data.success) {
          console.error(data.error);
          return;
        }
        suggestBtn.textContent = 'Đã gửi';
        suggestBtn.disabled = true;
      }).catch(console.error);
      return;
    }
  });

})();