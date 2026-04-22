(() => {
  const list = document.getElementById('posts-list');
  if (!list) return;

  // Đọc config từ data-attributes
  const context = list.dataset.context || 'home';
  const profileUserId = list.dataset.userId || '';
  let offset = parseInt(list.dataset.initialOffset || '0', 10) || 0;
  const limit = parseInt(list.dataset.limit || '10', 10) || 10;

  // Tạo sentinel nếu chưa có
  let sentinel = document.getElementById('postsSentinel');
  if (!sentinel) {
    sentinel = document.createElement('div');
    sentinel.id = 'postsSentinel';
    sentinel.className = 'sentinel';
    list.after(sentinel);
  }

  // Trạng thái load
  let loading = false;
  let hasMore = true;

  // Tạo article HTML (giữ style bạn đang dùng)
  function postHtml(p){
    const likedClass = p.liked ? 'is-liked' : '';
    const media = p.media_url
      ? (p.media_type === 'video'
          ? `
            <div class="post__media">
              <video class="lazy-video" controls playsinline preload="none" data-src="${escapeAttr(p.media_url)}"></video>
            </div>`
          : `
            <div class="post__media">
              <img class="lazy-img" loading="lazy" src="${escapeAttr(p.media_url)}" alt="">
            </div>`)
      : '';

    return `
    <article class="card fb-card post" data-post-id="${p.post_id}">
      <div class="post__header">
        <div class="post__avatar" style="background-image:url('${escapeAttr(p.author_avatar_url)}')"></div>
        <div class="post__meta">
          <div class="post__author">${escapeHtml(p.full_name || p.username || 'User')}</div>
          <div class="post__time">${formatTime(p.created_at)} · ${escapeHtml(p.privacy||'public')}</div>
        </div>
        <button class="post__more">⋯</button>
      </div>

      ${p.content_text ? `<div class="post__text">${escapeBreak(escapeHtml(p.content_text))}</div>` : ''}

      ${media}

      <div class="post__actions">
        <button class="post-action like-btn ${likedClass}" data-action="like" data-post-id="${p.post_id}"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-up-icon lucide-thumbs-up"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
        Like</button>
        <button class="post-action comment-toggle" data-action="comment" data-post-id="${p.post_id}"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle-icon lucide-message-circle"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/></svg>
        Comment</button>
        <button class="post-action share-btn" data-action="share" data-post-id="${p.post_id}"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-icon lucide-share"><path d="M12 2v13"/><path d="m16 6-4-4-4 4"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/></svg>
        Share </button>
      </div>

      <div class="post__stats">
        <span class="likes-count" data-post-id="${p.post_id}">${p.like_count||0} likes</span>
        <span class="comments-count" data-post-id="${p.post_id}">${p.comment_count||0} comments</span>
        <span class="shares-count" data-post-id="${p.post_id}">${p.share_count||0} shares</span>
      </div>
    </article>`;
  }

  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function escapeAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }
  function escapeBreak(s){ return s.replace(/\n/g,'<br>'); }
  function formatTime(ts){
    const d = new Date(ts);
    if (isNaN(d)) return escapeHtml(ts||'');
    return d.toLocaleString('vi-VN');
    // Nếu muốn giữ định dạng d/m/Y H:i, render từ server hoặc tự custom
  }

  async function fetchJson(url){
    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    try { return JSON.parse(text); } catch {
      console.error('Not JSON:', text); throw new Error('Server trả về không phải JSON');
    }
  }

  async function loadMore(){
    if (loading || !hasMore) return;
    loading = true;

    const params = new URLSearchParams({
      context, offset, limit
    });
    if (context === 'profile' && profileUserId) params.set('user_id', profileUserId);

    try{
      const data = await fetchJson(`/public/actions/feed_fetch.php?${params.toString()}`);
      const frag = document.createDocumentFragment();
      (data.items||[]).forEach(p => {
        const wrap = document.createElement('div');
        wrap.innerHTML = postHtml(p);
        frag.appendChild(wrap.firstElementChild);
      });
      list.appendChild(frag);

      // Lazy-load video: đặt src khi vào viewport
      setupLazyVideos();

      offset  = data.next_offset;
      hasMore = data.has_more;

      if (!hasMore) {
        showEndRow(list, `Hết dữ liệu (total: ${data.total||0})`);
      }
    } catch(e){
      console.error(e);
      showEndRow(list, 'Lỗi tải dữ liệu', true);
      hasMore = false;
    } finally {
      loading = false;
    }
  }

  function showEndRow(container, msg, err=false){
    const div = document.createElement('div');
    div.className = 'loading-row' + (err?' error-row':'');
    div.textContent = msg;
    container.appendChild(div);
  }

  function setupLazyVideos(){
    const vids = list.querySelectorAll('video.lazy-video[data-src]');
    if (!vids.length) return;
    const io = new IntersectionObserver(entries=>{
      entries.forEach(en=>{
        if (en.isIntersecting) {
          const v = en.target;
          if (v.dataset.src) {
            v.src = v.dataset.src;
            v.removeAttribute('data-src');
          }
          io.unobserve(v);
        }
      });
    }, { root: null, threshold: 0.1 });
    vids.forEach(v=>io.observe(v));
  }

  // Observer cuộn
  const io = new IntersectionObserver(entries=>{
    entries.forEach(en=>{
      if (en.isIntersecting) loadMore();
    });
  }, { root: null, threshold: 0.2 });
  io.observe(sentinel);

  // Event: mở overlay comment
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.post-action[data-action="comment"]');
    if (!btn) return;
    const postEl = btn.closest('.post');
    if (!postEl) return;
    const postId = parseInt(postEl.dataset.postId, 10);
    openCommentsOverlay(postId, { postElement: postEl });
  });

  // Khởi tạo lần đầu (nếu server đã render sẵn một phần, offset đã đặt)
  loadMore();
})();