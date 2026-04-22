// public/js/feed_actions.js
document.addEventListener('click', function (e) {
  // Like button
  const likeBtn = e.target.closest('.like-btn');
  if (likeBtn) {
    const postId = likeBtn.dataset.postId;
    fetch('/public/actions/like_post.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'post_id=' + encodeURIComponent(postId)
    }).then(r=>r.json()).then(data=>{
      if (!data.success) return console.error(data.error);
      // cập nhật UI
      const likesSpan = document.querySelector('.likes-count[data-post-id="'+postId+'"]');
      if (likesSpan) likesSpan.textContent = data.likes + ' likes';
      // toggle class
      if (data.action === 'liked') likeBtn.classList.add('is-liked'); else likeBtn.classList.remove('is-liked');
    }).catch(console.error);
  }
});

// // trong feed_actions.js, phần commentToggle xử lý open
// loadComments(postId).then(comments => {
//   // comments now sorted newest-first (server returns DESC)
//   const container = commentsArea.querySelector('.existing-comments');
//   container.innerHTML = '';
//   commentsCache.set(postId, comments);
//   shownCounts.set(postId, 0);
//   // show newest 5
//   showNextComments(postId, 5);
//   const btn = commentsArea.querySelector('.show-more-comments-btn');
//   if (btn) {
//     if (comments.length > 5) {
//       btn.style.display = 'inline-block';
//       btn.textContent = 'Xem thêm (' + Math.min(comments.length - 5, 5) + ')';
//     } else btn.style.display = 'none';
//   }
//   commentsArea.style.display = 'block';
// })
// // Gửi comment khi nhấn Enter trong input
// document.addEventListener('keydown', function (e) {
//   const input = e.target;
//   if (!input.classList.contains('comment-input')) return;
//   if (e.key === 'Enter' && input.value.trim() !== '') {
//     e.preventDefault();
//     const postId = input.dataset.postId;
//     const content = input.value.trim();
//     fetch('/public/actions/comment_post.php', {
//       method: 'POST',
//       headers: {'Content-Type':'application/x-www-form-urlencoded'},
//       body: 'post_id=' + encodeURIComponent(postId) + '&content_text=' + encodeURIComponent(content)
//     }).then(r=>r.json()).then(data=>{
//       if (!data.success) return console.error(data.error);
//       // thêm comment vào danh sách hiển thị (nếu có)
//       const commentsContainer = document.querySelector('#comments-'+postId+' .existing-comments');
//       if (commentsContainer && data.comment) {
//         const c = data.comment;
//         const el = document.createElement('div');
//         el.className = 'comment-item';
//         el.innerHTML = '<strong>'+escapeHtml(c.full_name)+'</strong> '+escapeHtml(c.content_text);
//         commentsContainer.appendChild(el);
//       }
//       // cập nhật comment count
//       const commentsCount = document.querySelector('.comments-count[data-post-id="'+postId+'"]');
//       if (commentsCount) commentsCount.textContent = data.comments_count + ' comments';
//       input.value = '';
//     }).catch(console.error);
//   }
// });

// helper escape
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }