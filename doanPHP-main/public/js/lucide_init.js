// lucide_init.js - Khởi tạo icon Lucide không gây lag
// GỢI Ý: load sau cùng (defer) sau khi DOM chính đã có.
// Yêu cầu: <script src="https://unpkg.com/lucide@latest"></script> đã được include trước.

(function() {
  if (!window.lucide) return;

  // Đánh dấu đã khởi tạo để tránh nạp lại
  if (window.__lucideSafeInit) return;
  window.__lucideSafeInit = true;

  // Hàm render chỉ quét phần tử i[data-lucide] chưa được thay thế
  function renderNewIcons(root = document) {
    const targets = root.querySelectorAll('i[data-lucide]:not([data-lucide-processed])');
    if (!targets.length) return;
    targets.forEach(el => el.setAttribute('data-lucide-processed', '1'));
    lucide.createIcons({
      attrs: { class: 'lucide', stroke: 'currentColor', 'stroke-width': 1.8 },
      nameAttr: 'data-lucide'
    });
  }

  // Render initial
  renderNewIcons(document);

  // Throttle render cho các mutations
  let scheduled = false;
  const observer = new MutationObserver(muts => {
    // Kiểm tra có phần tử i[data-lucide] mới không
    let need = false;
    for (const m of muts) {
      for (const n of m.addedNodes) {
        if (n.nodeType !== 1) continue;
        if (n.matches?.('i[data-lucide]:not([data-lucide-processed])')) { need = true; break; }
        if (n.querySelector?.('i[data-lucide]:not([data-lucide-processed])')) { need = true; break; }
      }
      if (need) break;
    }
    if (!need) return;

    if (!scheduled) {
      scheduled = true;
      // Dùng requestIdleCallback nếu có, fallback requestAnimationFrame
      const cb = () => {
        try { renderNewIcons(document); }
        finally { scheduled = false; }
      };
      (window.requestIdleCallback || window.requestAnimationFrame)(cb);
    }
  });

  observer.observe(document.getElementById('posts-list') || document.body, {
    childList: true,
    subtree: true
    // KHÔNG quan sát attributes để tránh vòng lặp
  });

  // Safe API để script infinite scroll tự thông báo thay vì dựa vào MutationObserver:
  window.renderLucideFor = function(container) {
    renderNewIcons(container);
  };
})();