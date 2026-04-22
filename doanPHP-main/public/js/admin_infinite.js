export function initInfiniteTable(cfg){
  const {
    endpoint, limit = 10,
    rootSelector, tbodySelector, sentinelSelector, searchSelector,
    buildRow
  } = cfg;

  const root     = document.querySelector(rootSelector);
  const tbody    = document.querySelector(tbodySelector);
  const sentinel = document.querySelector(sentinelSelector);
  const searchEl = document.querySelector(searchSelector);

  let offset = 0, q = '';
  let loading = false, hasMore = true;

  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
  function rowMsg(txt, err=false){
    const tr = document.createElement('tr');
    tr.className = 'loading-row' + (err?' error-row':'');
    tr.innerHTML = `<td colspan="99">${escapeHtml(txt)}</td>`;
    tbody.appendChild(tr);
    return tr;
  }
  async function fetchJson(url){
    const res  = await fetch(url, { credentials:'same-origin' });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch {
      console.error('Not JSON:', text);
      throw new Error('Phản hồi không phải JSON');
    }
    if (data.error) throw new Error(data.error);
    return data;
  }
  async function loadMore(){
    if (loading || !hasMore) return;
    loading = true;
    const loadingTr = rowMsg('Đang tải...');
    try {
      const url = `${endpoint}?offset=${offset}&limit=${limit}&q=${encodeURIComponent(q)}`;
      const data = await fetchJson(url);
      tbody.removeChild(loadingTr);

      const frag = document.createDocumentFragment();
      data.items.forEach(item => {
        const tr = buildRow(item, escapeHtml);
        frag.appendChild(tr);
      });
      tbody.appendChild(frag);
      //lucide
      if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons({ attrs: { class: 'nav-icon', stroke: 'currentColor', 'stroke-width': 1.8 } });
      }

      offset  = data.next_offset;
      hasMore = data.has_more;
      if (!hasMore) rowMsg(`Hết dữ liệu (total: ${data.total})`);
    } catch(e){
      tbody.removeChild(loadingTr);
      rowMsg('Lỗi tải dữ liệu', true);
      console.error(e);
      hasMore = false;
    } finally { loading = false; }
  }

  const io = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) loadMore();
    });
  }, { root, threshold: 0.1 });
  io.observe(sentinel);

  let timer;
  searchEl?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      q = searchEl.value.trim();
      offset = 0; hasMore = true;
      tbody.innerHTML = '';
      loadMore();
    }, 300);
  });

  loadMore();
}