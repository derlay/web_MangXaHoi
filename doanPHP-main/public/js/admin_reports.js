import { initInfiniteTable } from '/public/js/admin_infinite.js';

initInfiniteTable({
  endpoint: '/public/actions/reports_fetch.php',
  limit: 10,
  rootSelector: '#reportsScroll',
  tbodySelector: '#reportsTbody',
  sentinelSelector: '#reportsSentinel',
  searchSelector: '#reportSearch',
  buildRow: (r, esc) => {
    const statusClass = r.status==='pending' ? 'pill-red'
                     : (r.status==='resolved' ? 'pill-green' : 'pill-yellow');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="mono">${r.report_id}</td>
      <td><strong>${esc(r.reporter)}</strong></td>
      <td class="mono">${esc(r.target_type)} #${r.target_id}</td>
      <td class="truncate" title="${esc(r.reason)}">${esc(r.reason)}</td>
      <td><span class="pill ${statusClass}">${esc(r.status)}</span></td>
      <td class="muted-small">${esc(r.created_at)}</td>
      <td class="t-right">
        ${r.status==='pending' ? `
          <form method="post" action="/public/actions/admin_report_action.php" class="inline">
            <input type="hidden" name="report_id" value="${r.report_id}">
            <input type="hidden" name="action" value="resolve">
            <button class="btn btn-red">Resolve</button>
          </form>
          <form method="post" action="/public/actions/admin_report_action.php" class="inline">
            <input type="hidden" name="report_id" value="${r.report_id}">
            <input type="hidden" name="action" value="dismiss">
            <button class="btn">Dismiss</button>
          </form>` : `<span class="muted-small">-</span>`
        }
      </td>
    `;
    return tr;
  }
});