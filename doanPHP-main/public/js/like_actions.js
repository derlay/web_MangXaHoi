// like_actions.js - chỉ xử lý Like, tránh xung đột với overlay comment
(function(){
  document.addEventListener('click', (e)=>{
    const likeBtn = e.target.closest('.like-btn');
    if (!likeBtn) return;
    const postId = likeBtn.dataset.postId;
    if (!postId) return;

    fetch('/public/actions/like_post.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'post_id=' + encodeURIComponent(postId),
      credentials: 'include'
    }).then(r=>r.json()).then(data=>{
      if (!data || !data.success) return;
      const likesSpan = document.querySelector('.likes-count[data-post-id="'+postId+'"]');
      if (likesSpan) likesSpan.textContent = (data.likes||0) + ' likes';
      if (data.action === 'liked') likeBtn.classList.add('is-liked'); else likeBtn.classList.remove('is-liked');
    }).catch(()=>{ /* im lặng */ });
  });
})();