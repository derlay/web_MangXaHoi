import { initInfiniteTable } from '/public/js/admin_infinite.js';

initInfiniteTable({
  endpoint:'/public/actions/users_fetch.php',
  limit:10,
  rootSelector:'#usersScroll',
  tbodySelector:'#usersTbody',
  sentinelSelector:'#usersSentinel',
  searchSelector:'#userSearch',
  buildRow:(u,esc)=>{
    const tr=document.createElement('tr');
    const statusClass = u.status==='Active'?'pill-green':(u.status==='Banned'?'pill-red':'pill-yellow');
    const actionBtn = (u.role!=='Admin') ?
      `<form method="post" action="/public/actions/admin_toggle_user.php" class="inline">
         <input type="hidden" name="user_id" value="${u.user_id}">
         <button class="btn ${u.status==='Banned'?'btn-blue':'btn-red'}">${u.status==='Banned'?'Unban':'Ban'}</button>
       </form>` : '';
    tr.innerHTML = `
      <td class="mono">${u.user_id}</td>
      <td>
        <div class="stack">
          <strong>${esc(u.username)}</strong>
          <span class="muted-small">${esc(u.full_name)}</span>
          <span class="muted-small">${esc(u.email)}</span>
        </div>
      </td>
      <td><span class="badge">${esc(u.role)}</span></td>
      <td>${esc(u.created_at||'')}</td>
      <td><span class="pill ${statusClass}">${esc(u.status)}</span></td>
      <td class="t-right">${actionBtn}</td>
    `;
    return tr;
  }
});