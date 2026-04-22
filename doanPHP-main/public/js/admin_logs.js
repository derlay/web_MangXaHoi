import { initInfiniteTable } from '/public/js/admin_infinite.js';

initInfiniteTable({
  endpoint: '/public/actions/logs_fetch.php',
  limit: 15,
  rootSelector: '#logsScroll',
  tbodySelector: '#logsTbody',
  sentinelSelector: '#logsSentinel',
  searchSelector: '#logSearch',
  buildRow: (l, esc) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="mono">${l.log_id}</td>
      <td class="bold blue">${esc(l.admin)}</td>
      <td><span class="chip grey">${esc(l.action_type)}</span></td>
      <td class="mono">${esc(l.target_type)} #${l.target_id}</td>
      <td>${esc(l.details)}</td>
      <td class="muted-small">${esc(l.created_at)}</td>
    `;
    return tr;
  }
});