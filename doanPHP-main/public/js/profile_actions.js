
(function () {
  const COMMENTS_BATCH = 5;
  const ENDPOINT_GET_COMMENTS   = '/public/actions/get_comments.php?post_id=';
  const ENDPOINT_LIKE_POST      = '/public/actions/like_post.php';
  const ENDPOINT_COMMENT_CREATE = '/public/actions/comment_post.php';

  // Số bình luận đã hiện mỗi post
  const shownCounts    = new Map(); // postId -> number
  // Cache bình luận mỗi post
  const commentsCache  = new Map(); // postId -> comment[]
  // Cờ đang tải bình luận (chống gọi trùng)
  const loadingFlags   = new Map(); // postId -> boolean

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
  }

  function createCommentElement(comment) {
    // comment: { comment_id, content_text, created_at, user_id, full_name, profile_picture_url }
    const el = document.createElement('div');
    el.className = 'comment-item';
    const name = escapeHtml(comment.full_name || 'User');
    const text = escapeHtml(comment.content_text || '');
    el.innerHTML = '<strong class="comment-user">' + name + '</strong> ' + text;
    return el;
  }

  function getCommentsArea(postId) {
    return document.getElementById('comments-' + postId);
  }

  function getExistingContainer(postId) {
    return document.querySelector('#comments-' + postId + ' .existing-comments');
  }

  function getShowMoreBtn(postId) {
    return document.querySelector('.show-more-comments-btn[data-post-id="' + postId + '"]');
  }

  function updateCommentsCountUI(postId, total) {
    const countSpan = document.querySelector('.comments-count[data-post-id="' + postId + '"]');
    if (countSpan) {
      countSpan.textContent = total + ' comments';
    }
  }

  function showLoading(postId) {
    const container = getExistingContainer(postId);
    if (!container) return;
    container.innerHTML = '<div class="comments-loading">Đang tải bình luận...</div>';
  }

  function showError(postId, message) {
    const container = getExistingContainer(postId);
    if (!container) return;
    container.innerHTML = '<div class="comments-error">' + escapeHtml(message) + '</div>';
  }

  function clearContainer(postId) {
    const container = getExistingContainer(postId);
    if (container) container.innerHTML = '';
  }

  function showNextComments(postId, batchSize = COMMENTS_BATCH) {
    const cache = commentsCache.get(postId) || [];
    const shown = shownCounts.get(postId) || 0;
    const container = getExistingContainer(postId);
    const btn = getShowMoreBtn(postId);
    if (!container) return;

    const next = cache.slice(shown, shown + batchSize);
    next.forEach(c => container.appendChild(createCommentElement(c)));

    const newShown = shown + next.length;
    shownCounts.set(postId, newShown);

    // Điều chỉnh nút "Xem thêm"
    if (newShown >= cache.length) {
      if (btn) btn.style.display = 'none';
    } else {
      if (btn) {
        btn.style.display = 'inline-block';
        const remaining = cache.length - newShown;
        btn.textContent = 'Xem thêm (' + Math.min(remaining, batchSize) + ')';
      }
    }

    // Cập nhật đếm
    updateCommentsCountUI(postId, cache.length);
  }

  async function fetchComments(postId) {
    // Nếu đã có cache → trả luôn
    if (commentsCache.has(postId)) {
      return commentsCache.get(postId);
    }
    // Nếu đang tải → chờ
    if (loadingFlags.get(postId)) {
      return commentsCache.get(postId) || [];
    }
    loadingFlags.set(postId, true);
    try {
      const res = await fetch(ENDPOINT_GET_COMMENTS + encodeURIComponent(postId), {
        credentials: 'same-origin'
      });
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error('Phản hồi không phải JSON');
      }
      if (!data.success) {
        throw new Error(data.error || 'Không tải được bình luận');
      }
      const comments = data.comments || [];
      commentsCache.set(postId, comments);
      return comments;
    } finally {
      loadingFlags.set(postId, false);
    }
  }

  async function openComments(postId) {
    const area = getCommentsArea(postId);
    if (!area) return;

    area.style.display = 'block';
    showLoading(postId);

    try {
      const comments = await fetchComments(postId);
      clearContainer(postId);
      shownCounts.set(postId, 0);
      showNextComments(postId, COMMENTS_BATCH);

      // Chuẩn bị nút show-more
      const btn = getShowMoreBtn(postId);
      if (btn) {
        if (comments.length > COMMENTS_BATCH) {
          btn.style.display = 'inline-block';
          btn.textContent = 'Xem thêm (' + Math.min(comments.length - COMMENTS_BATCH, COMMENTS_BATCH) + ')';
        } else {
          btn.style.display = 'none';
        }
      }
    } catch (err) {
      showError(postId, err.message || 'Lỗi tải bình luận');
    }
  }

  function closeComments(postId) {
    const area = getCommentsArea(postId);
    if (!area) return;
    area.style.display = 'none';
  }

  async function likePost(postId, likeBtn) {
    try {
      const res = await fetch(ENDPOINT_LIKE_POST, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'post_id=' + encodeURIComponent(postId),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (!data.success) {
        // Có thể hiển thị popup thay vì console
        return; // bỏ qua nếu lỗi
      }
      const likesSpan = document.querySelector('.likes-count[data-post-id="' + postId + '"]');
      if (likesSpan) likesSpan.textContent = data.likes + ' likes';
      if (data.action === 'liked') {
        likeBtn.classList.add('is-liked');
      } else {
        likeBtn.classList.remove('is-liked');
      }
    } catch {
      // Bỏ qua lỗi mạng tải like
    }
  }

  async function submitComment(postId, content, inputEl) {
    try {
      const res = await fetch(ENDPOINT_COMMENT_CREATE, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'post_id=' + encodeURIComponent(postId) + '&content_text=' + encodeURIComponent(content),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (!data.success) {
        // Có thể hiện thông báo lỗi cạnh input
        return;
      }
      const newComment = data.comment;
      // Bảo đảm vùng bình luận đang mở
      const area = getCommentsArea(postId);
      if (!area) return;

      // Nếu chưa từng load, phải fetch trước (để đồng bộ)
      if (!commentsCache.has(postId)) {
        await fetchComments(postId);
      }

      const cache = commentsCache.get(postId) || [];
      cache.unshift(newComment); // Thêm đầu danh sách
      commentsCache.set(postId, cache);

      // Re-render container
      clearContainer(postId);
      shownCounts.set(postId, 0);
      showNextComments(postId, COMMENTS_BATCH);

      // Cập nhật nút show-more
      const btn = getShowMoreBtn(postId);
      if (btn) {
        if (cache.length > COMMENTS_BATCH) {
          btn.style.display = 'inline-block';
          const remaining = cache.length - COMMENTS_BATCH;
          btn.textContent = 'Xem thêm (' + Math.min(remaining, COMMENTS_BATCH) + ')';
        } else {
          btn.style.display = 'none';
        }
      }

      inputEl.value = '';
    } catch {
      // Lỗi gửi bình luận: có thể báo UI
    }
  }

  document.addEventListener('click', function (e) {
    // Like
    const likeBtn = e.target.closest('.like-btn');
    if (likeBtn) {
      const postId = likeBtn.dataset.postId;
      if (postId) likePost(postId, likeBtn);
      return;
    }

    // Chuyển đổi comments
    const commentToggle = e.target.closest('.comment-toggle');
    if (commentToggle) {
      const postId = commentToggle.dataset.postId;
      if (!postId) return;
      const area = getCommentsArea(postId);
      if (!area) return;
      const hidden = (area.style.display === 'none' || area.style.display === '');
      if (hidden) {
        openComments(postId);
      } else {
        closeComments(postId);
      }
      return;
    }

    // Show more comments
    const showMoreBtn = e.target.closest('.show-more-comments-btn');
    if (showMoreBtn) {
      const postId = showMoreBtn.dataset.postId;
      if (postId) showNextComments(postId, COMMENTS_BATCH);
      return;
    }
  });

  document.addEventListener('keydown', function (e) {
    const input = e.target;
    if (!input.classList || !input.classList.contains('comment-input')) return;
    if (e.key === 'Enter' && input.value.trim() !== '') {
      e.preventDefault();
      const postId = input.dataset.postId;
      const content = input.value.trim();
      if (!postId || !content) return;
      submitComment(postId, content, input);
    }
  });

  
})();
