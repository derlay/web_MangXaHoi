(function () {
  const API_BASE = (window.API_BASE || '').replace(/\/+$/, '');
  const ENDPOINT_LIST   = `${API_BASE || ''}/public/actions/comments.php`;
  const ENDPOINT_CREATE = `${API_BASE || ''}/public/actions/comment_post.php`;
  const CLOUD_NAME = (window.CLOUDINARY_CLOUD_NAME || '').trim();
  const UP_PRESET  = (window.CLOUDINARY_UNSIGNED_PRESET || '').trim();

  if (window.__cmtOvInitialized) return;
  window.__cmtOvInitialized = true;

  (function ensureCss() {
    if (document.getElementById('cmt-ov-fallback-style')) return;
    const style = document.createElement('style');
    style.id = 'cmt-ov-fallback-style';
    style.textContent = `
      #cmt-ov{position:fixed;inset:0;display:none;pointer-events:none;z-index:1000;}
      #cmt-ov.show{display:block;pointer-events:auto;}
      #cmt-ov .cmt-ov__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.45);}
      #cmt-ov .cmt-ov__panel{position:absolute;right:0;top:0;bottom:0;width:min(540px,100%);background:#fff;display:flex;flex-direction:column;}
    `;
    document.head.appendChild(style);
  })();

  function buildOverlayMarkup() {
    return `
      <div class="cmt-ov__backdrop" data-action="close"></div>
      <div class="cmt-ov__panel" role="dialog" aria-modal="true" aria-labelledby="cmt-ov-title">
        <div class="cmt-ov__head">
          <h3 id="cmt-ov-title" class="cmt-ov__title">Bình luận</h3>
          <button id="cmt-ov-close" class="cmt-ov__close" type="button" aria-label="Đóng">✕</button>
        </div>
        <div id="cmt-ov-post" class="cmt-ov__post">
          <div id="cmt-ov-thumb" class="cmt-ov__thumb"></div>
          <div id="cmt-ov-snippet" class="cmt-ov__snippet"></div>
        </div>
        <div id="cmt-ov-body" class="cmt-ov__body">
          <div id="cmt-ov-list" class="cmt-ov__list"></div>
          <div id="cmt-ov-sentinel" class="cmt-ov__sentinel"></div>
        </div>
        <form id="cmt-ov-form" class="cmt-ov__form">
          <textarea id="cmt-ov-input" class="cmt-ov__input" placeholder="Viết bình luận..." maxlength="1000"></textarea>
          <div class="cmt-ov__tools">
            <button class="btn" id="cmt-ov-upload" type="button">Ảnh/Video</button>
            <input type="file" id="cmt-ov-file" accept="image/*,video/*" class="d-none">
            <img id="cmt-ov-preview" class="cmt-ov__preview" alt="">
            <input type="hidden" id="cmt-ov-media-url">
            <button class="btn btn-primary" id="cmt-ov-send" type="submit">Gửi</button>
          </div>
        </form>
      </div>
    `;
  }

  function ensureOverlay() {
    let ov = document.getElementById('cmt-ov');
    if (!ov) { ov = document.createElement('div'); ov.id = 'cmt-ov'; document.body.appendChild(ov); }
    const needIds = ['cmt-ov-title','cmt-ov-body','cmt-ov-list','cmt-ov-sentinel','cmt-ov-form','cmt-ov-input','cmt-ov-upload','cmt-ov-file','cmt-ov-preview','cmt-ov-media-url','cmt-ov-post','cmt-ov-thumb','cmt-ov-snippet','cmt-ov-close'];
    if (needIds.some(id => !document.getElementById(id))) ov.innerHTML = buildOverlayMarkup();
    return ov;
  }

  // Utilities
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const br  = (s) => esc(s).replace(/\n/g,'<br>');
  const normText = (s) => String(s||'').trim().replace(/\s+/g,' ');
  const relTime = (ts) => {
    const d = new Date(ts); if (isNaN(d)) return esc(ts);
    const s = Math.floor((Date.now() - d.getTime())/1000);
    if (s < 60) return 'vừa xong';
    const m = Math.floor(s/60); if (m < 60) return `${m} phút`;
    const h = Math.floor(m/60); if (h < 24) return `${h} giờ`;
    const day = Math.floor(h/24); if (day < 7) return `${day} ngày`;
    const w = Math.floor(day/7); if (w < 4) return `${w} tuần`;
    return d.toLocaleDateString('vi-VN');
  };

  function getAvatarUrl(c) {
    if (c.avatar_url) return c.avatar_url;
    if (c.profile_picture_url) return c.profile_picture_url;
    const pid = c.profile_picture_public_id;
    if (pid && CLOUD_NAME) {
      const clean = String(pid).replace(/\.(jpg|jpeg|png|gif|webp)$/i,'');
      return `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/w_40,h_40,c_fill,g_face,r_max/${encodeURIComponent(clean)}.jpg`;
    }
    return '/public/img/default_avatar.jpg';
  }

  const sigOf = (c) => {
    const uid = c.user_id || '';
    const txt = normText(c.content_text || c.content || '');
    let ts = '';
    if (c.created_at){const d=new Date(c.created_at); ts=isNaN(d)? String(c.created_at): d.toISOString().slice(0,19);}
    return `${uid}|${txt}|${ts}`;
  };

  
function rowHtml(c){
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const normText = (s) => String(s||'').trim().replace(/\s+/g,' ');
  const relTime = (ts) => {
    const d = new Date(ts); if (isNaN(d)) return esc(ts);
    const s = Math.floor((Date.now() - d.getTime())/1000);
    if (s < 60) return 'vừa xong';
    const m = Math.floor(s/60); if (m < 60) return `${m} phút`;
    const h = Math.floor(m/60); if (h < 24) return `${h} giờ`;
    const day = Math.floor(h/24); if (day < 7) return `${day} ngày`;
    const w = Math.floor(day/7); if (w < 4) return `${w} tuần`;
    return d.toLocaleDateString('vi-VN');
  };
  const br = (s) => esc(s).replace(/\n/g,'<br>');

  // avatar
  const avatarUrl = (()=>{
    if (c.avatar_url) return c.avatar_url;
    if (c.profile_picture_url) return c.profile_picture_url;
    const CLOUD_NAME = (window.CLOUDINARY_CLOUD_NAME || '').trim();
    const pid = c.profile_picture_public_id;
    if (pid && CLOUD_NAME) {
      const clean = String(pid).replace(/\.(jpg|jpeg|png|gif|webp)$/i,'');
      return `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/w_40,h_40,c_fill,g_face,r_max/${encodeURIComponent(clean)}.jpg`;
    }
    return '/public/img/default_avatar.jpg';
  })();

  const name   = esc(c.display_name || c.full_name || c.username || 'Người dùng');
  const when   = relTime(c.created_at || '');
  const cid    = c.comment_id || '';
  const sig    = `${c.user_id||''}|${normText(c.content_text||c.content||'')}|${c.created_at||''}`;
  const media  = (c.media_url && typeof c.media_url === 'string') ? c.media_url : '';

  const likeIcon = `
    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M7 10v12"></path>
      <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
    </svg>`;
  const replyIcon = `
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-reply-icon lucide-reply"><path d="M20 18v-2a4 4 0 0 0-4-4H4"/><path d="m9 17-5-5 5-5"/></svg>`;

  const mediaBlock = media ? `
    <div class="cmt-media">
      ${/\.mp4|\.webm|\.ogg$/i.test(media)
        ? `<video controls playsinline src="${esc(media)}"></video>`
        : `<img src="${esc(media)}" alt="">`
      }
    </div>` : '';

  return `
    <div class="cmt-row" data-id="${esc(String(cid))}" data-sig="${esc(sig)}">
      <div class="cmt-avatar" style="background-image:url('${avatarUrl}')"></div>
      <div class="cmt-bubble">
        <div class="cmt-bubble__head">
          <span class="cmt-user">${name}</span>
          <span class="cmt-dot">•</span>
          <time class="cmt-time" title="${esc(c.created_at || '')}">${esc(when)}</time>
        </div>
        <div class="cmt-text">${br(c.content_text||c.content||'')}</div>
        ${mediaBlock}
        <div class="cmt-actions">
          <button class="cmt-btn" type="button">${likeIcon} Thích</button>
          <button class="cmt-btn" type="button">${replyIcon} Phản hồi</button>
        </div>
      </div>
    </div>`;
}
  async function fetchJson(url, opts){
    const res = await fetch(url, { credentials:'include', ...(opts||{}) });
    const text = await res.text();
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0,120)}`);
    let data; try { data = JSON.parse(text); } catch { throw new Error('Phản hồi không phải JSON'); }
    if (data.success === false) throw new Error(data.error || 'API lỗi');
    return data;
  }

  function getOrCreateEndEl(listEl){
    let end = listEl.querySelector('#cmt-ov-end');
    if (!end) { end = document.createElement('div'); end.id = 'cmt-ov-end'; end.className = 'cmt-end'; }
    return end;
  }

  function appendOneIfNew(listEl, c, toStart = false){
    const cid = c.comment_id;
    const sig = sigOf(c);
    if (cid && listEl.querySelector(`.cmt-row[data-id="${CSS.escape(String(cid))}"]`)) return false;
    if (sig && listEl.querySelector(`.cmt-row[data-sig="${CSS.escape(sig)}"]`)) return false;
    const wrap = document.createElement('div');
    wrap.innerHTML = rowHtml(c);
    const node = wrap.firstElementChild;
    if (toStart) listEl.insertBefore(node, listEl.firstChild);
    else listEl.appendChild(node);
    return true;
  }

  // Lấy text + ảnh từ bài post để show ở header
  function fillPostInfo(postEl) {
    const thumb = document.getElementById('cmt-ov-thumb');
    const snip  = document.getElementById('cmt-ov-snippet');
    if (!thumb || !snip) return;

    const textEl = postEl?.querySelector?.('.post__text');
    const txt = textEl ? textEl.textContent.trim() : '';
    snip.textContent = txt || '—';

    const img = postEl?.querySelector?.('.post__media img');
    const video = postEl?.querySelector?.('.post__media video');
    let url = '';
    if (img?.src) url = img.src;
    else if (video?.poster) url = video.poster;

    if (url) { thumb.style.backgroundImage = `url('${url}')`; thumb.style.display='block'; }
    else { thumb.style.display='none'; }
  }

  window.openCommentsOverlay = function(postId, opts = {}){
    const ov = ensureOverlay();

    // Query node sau khi ensure
    const title    = document.getElementById('cmt-ov-title');
    const list     = document.getElementById('cmt-ov-list');
    const body     = document.getElementById('cmt-ov-body');
    const input    = document.getElementById('cmt-ov-input');
    const form     = document.getElementById('cmt-ov-form');
    const closeBtn = document.getElementById('cmt-ov-close');
    const sentinel = document.getElementById('cmt-ov-sentinel');
    const uploadBtn= document.getElementById('cmt-ov-upload');
    const fileI    = document.getElementById('cmt-ov-file');
    const preview  = document.getElementById('cmt-ov-preview');
    const mediaUrlI= document.getElementById('cmt-ov-media-url');

    title.textContent = 'Bình luận';
    ov.dataset.postId = String(postId);
    list.innerHTML = '';
    input.value = '';
    preview.style.display = 'none';
    preview.src = '';
    mediaUrlI.value = '';

    if (opts.postElement) fillPostInfo(opts.postElement); else fillPostInfo(null);
    ov.classList.add('show');

    // State
    let offset = 0;
    const limit = 12;
    let loading = false;
    let hasMore = true;
    let submitting = false;

    // Load theo trang
    const io = new IntersectionObserver((entries)=> {
      entries.forEach(en=>{ if (en.isIntersecting) loadMore(); });
    }, { root: body, threshold: 0.05 });
    io.observe(sentinel);

    async function loadMore(){
      if (loading || !hasMore) return;
      loading = true;
      const oldEnd = list.querySelector('#cmt-ov-end'); if (oldEnd) oldEnd.remove();

      try {
        const q = new URLSearchParams({ post_id:String(postId), offset:String(offset), limit:String(limit) });
        const data = await fetchJson(`${ENDPOINT_LIST}?${q.toString()}`);
        for (const c of (data.items || [])) appendOneIfNew(list, c, false);
        offset  = data.next_offset ?? (offset + (data.items?.length || 0));
        hasMore = !!data.has_more;

        if (!hasMore) {
          io.disconnect();
          const end = getOrCreateEndEl(list);
          end.classList.remove('error');
          end.textContent = `Hết bình luận${typeof data.total === 'number' ? ` (total: ${data.total})` : ''}`;
          list.appendChild(end);
        }
      } catch(e){
        io.disconnect();
        const end = getOrCreateEndEl(list);
        end.classList.add('error');
        end.textContent = 'Lỗi tải bình luận';
        list.appendChild(end);
        hasMore = false;
      } finally {
        loading = false;
      }
    }

    // Upload: Cloudinary nếu có, fallback input
    function setPreview(url){
      if (!url) { preview.style.display='none'; preview.src=''; mediaUrlI.value=''; return; }
      preview.src = url; preview.style.display='block'; mediaUrlI.value = url;
    }
    if (window.cloudinary && CLOUD_NAME && UP_PRESET) {
      const widget = window.cloudinary.createUploadWidget({
        cloudName: CLOUD_NAME,
        uploadPreset: UP_PRESET,
        sources: ['local','url','camera'],
        multiple: false,
        clientAllowedFormats: ['image','video'],
      }, (err, result) => {
        if (err) return;
        if (result?.event === 'success' && result.info?.secure_url) setPreview(result.info.secure_url);
      });
      uploadBtn.onclick = () => widget.open();
    } else {
      uploadBtn.onclick = () => fileI.click();
      fileI.onchange = () => {
        const f = fileI.files?.[0]; if (!f) return setPreview('');
        const r = new FileReader(); r.onload = () => setPreview(String(r.result || '')); r.readAsDataURL(f);
      };
    }

    // Gửi bình luận
    form.onsubmit = async (e)=>{
      e.preventDefault();
      const content = input.value.trim();
      const media   = mediaUrlI.value.trim();
      if (!content && !media) return;
      submitting = true; input.disabled = true;

      try {
        const fd = new FormData();
        fd.append('post_id', String(postId));
        fd.append('content_text', content);
        fd.append('content', content); // tương thích backend cũ
        if (media) fd.append('media_url', media);

        const data = await fetchJson(ENDPOINT_CREATE, { method:'POST', body: fd });
        const c = data.comment || data.data;
        if (c) {
          const end = list.querySelector('#cmt-ov-end'); if (end) end.remove();
          const added = appendOneIfNew(list, c, true);
          if (added) offset += 1;
          input.value = ''; setPreview('');
        }
      } catch(err){
        alert(err.message || 'Không gửi được bình luận');
      } finally {
        input.disabled = false; submitting = false;
      }
    };

    // Đóng overlay
    const close = () => { ov.classList.remove('show'); io.disconnect(); };
    document.getElementById('cmt-ov-close').onclick = close;
    ov.querySelector('.cmt-ov__backdrop').onclick = close;

    // Nạp batch đầu
    loadMore();
  };

  // Móc click mở overlay
  if (!window.__cmtOvClickHooked) {
    document.addEventListener('click', (e)=>{
      const btn = e.target.closest('.post-action[data-action="comment"], .comment-toggle');
      if (!btn) return;
      const post = btn.closest('.post');
      const postId = parseInt(post?.dataset.postId || btn.dataset.postId, 10);
      if (!postId) return;
      window.openCommentsOverlay(postId, { postElement: post || null });
    });
    window.__cmtOvClickHooked = true;
  }
})();