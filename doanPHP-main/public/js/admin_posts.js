import { initInfiniteTable } from '/public/js/admin_infinite.js';

initInfiniteTable({
  endpoint: '/public/actions/posts_fetch.php',
  limit: 10,
  rootSelector: '#postsScroll',
  tbodySelector: '#postsTbody',
  sentinelSelector: '#postsSentinel',
  searchSelector: '#postSearch',
  buildRow: (p, esc) => {
    const tr = document.createElement('tr');
    const media = p.media_type === 'image'
      ? '<span class="muted-small blue">Image</span>'
      : (p.media_type === 'video' ? '<span class="muted-small blue">Video</span>' : '<span class="muted-small">None</span>');
    const statusClass = p.status === 'Flagged' ? 'pill-yellow' : 'pill-green';
    tr.innerHTML = `
      <td class="mono">${p.post_id}</td>
      <td class="bold">${esc(p.username)}</td>
      <td class="truncate" title="${esc(p.content_text)}">${esc(p.content_text)}</td>
      <td>${media}</td>
      <td><span class="chip">${esc(p.privacy)}</span></td>
      <td><span class="pill ${statusClass}">${esc(p.status)}</span></td>
      <td class="t-right">
        <form method="post" action="/public/actions/admin_delete_post.php" class="inline" onsubmit="return confirm('Xóa bài này?')">
          <input type="hidden" name="post_id" value="${p.post_id}">
          <button class="icon-btn red" title="Xóa" type="submit">
            <i data-lucide="trash" class="nav-icon-trash"></i>
          </button>
        </form>
      </td>
    `;
    return tr;
  }
});