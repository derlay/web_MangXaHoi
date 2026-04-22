// Cloudinary // Giữ nguyên logic đã gửi trước đó.
// Ghi chú bổ sung để đảm bảo scroll đúng khi append tin mới:
(function () {
  const API_BASE = window.API_BASE || 'http://localhost:8000';
  const WS_URL = window.CHAT_WS_URL || 'ws://localhost:8080';
  const CURRENT_USER_ID = window.CURRENT_USER_ID ? Number(window.CURRENT_USER_ID) : 0;
  const BATCH = 20;

  const DEFAULT_AVATAR = '/public/img/default_avatar.jpg';

  const qs = (s, ctx) => (ctx || document).querySelector(s);
  const qsa = (s, ctx) => Array.from((ctx || document).querySelectorAll(s));

  let currentOtherId =
    Number((qs('#chat-messages') && qs('#chat-messages').dataset.otherId) || 0);

  // Lưu id đã render để chống trùng
  const renderedIds = new Set();

  function formatTime(ts) {
    if (!ts) return '';
    try {
      return new Date(ts).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  }

const CURRENT_USER_AVATAR = window.CURRENT_USER_AVATAR || '/public/img/default_avatar.jpg';

function renderMessageBubble(msg) {
  const isMe = Number(msg.sender_id) === CURRENT_USER_ID;
  const row = document.createElement('div');
  row.className = 'chat-message-row ' + (isMe ? 'me' : 'other');

  const body = document.createElement('div');
  const bubble = document.createElement('div');
  bubble.className = 'chat-message-bubble';
  bubble.textContent = msg.content_text || '';

  const ts = document.createElement('div');
  ts.className = 'chat-message-time';
  ts.textContent = msg.sent_at
    ? new Date(msg.sent_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    : '';

  if (!isMe) {
    const wrap = document.createElement('div');
    wrap.className = 'chat-message-with-avatar';

    const av = document.createElement('div');
    av.className = 'chat-message-avatar';
    const img = document.createElement('img');

    // Bên OTHER: ảnh của người gửi (sender của message)
    img.src = msg.sender_avatar_url || DEFAULT_AVATAR;
    img.alt = '';
    img.loading = 'eager';
    img.decoding = 'sync';
    img.onerror = () => { img.src = DEFAULT_AVATAR; };

    av.appendChild(img);
    body.appendChild(bubble);
    body.appendChild(ts);
    wrap.appendChild(av);
    wrap.appendChild(body);
    row.appendChild(wrap);
  } else {
    // Bên ME: ảnh của chính mình (không đặt trong bubble nếu bạn không muốn hiển thị avatar)
    // Nếu muốn hiển thị avatar “me”, thêm block giống trên nhưng dùng CURRENT_USER_AVATAR.
    body.appendChild(bubble);
    body.appendChild(ts);
    row.appendChild(body);
  }

  return row;
}

  function appendMessage(msg) {
    const id = Number(msg.id ?? msg.message_id);
    if (id && renderedIds.has(id)) return; // dedupe

    const box = qs('#chat-messages');
    if (!box) return;

    box.appendChild(renderMessageBubble(msg));
    if (id) renderedIds.add(id);
    box.scrollTop = box.scrollHeight;
  }

  async function loadMessages(otherId, { limit = BATCH, offset = 0 } = {}) {
    if (!otherId) return;
    const box = qs('#chat-messages');
    if (!box) return;

    try {
      const url =
        API_BASE +
        '/public/actions/get_messages.php?user_id=' +
        encodeURIComponent(otherId) +
        '&limit=' + limit +
        '&offset=' + offset;

      const res = await fetch(url, { credentials: 'include' });
      const data = await res.json();

      if (!data || data.success !== true) {
        console.error('get_messages_error', data && data.error, data && data.detail);
        return;
      }

      const messages = data.messages || [];
      if (offset === 0) {
        box.innerHTML = '';
        renderedIds.clear();
      }

      const frag = document.createDocumentFragment();
      messages.forEach((m) => {
        const id = Number(m.id ?? m.message_id);
        if (id) renderedIds.add(id); // đánh dấu lịch sử đã render
        frag.appendChild(renderMessageBubble(m));
      });
      box.appendChild(frag);

      // cuộn xuống cuối khi load mới
      if (offset === 0) box.scrollTop = box.scrollHeight;
    } catch (e) {
      console.error('loadMessages error', e);
    }
  }

  async function sendMessage(otherId, text) {
    if (!otherId || !text) return false;
    try {
      const body =
        'user_id=' + encodeURIComponent(otherId) +
        '&content_text=' + encodeURIComponent(text);

      const res = await fetch(API_BASE + '/public/actions/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'include',
        body,
      });
      const data = await res.json();
      if (!data || data.success !== true) {
        console.error('send_message error', data && data.error, data && data.detail);
        return false;
      }

      const msg = data.message;

      // hiển thị ngay trên máy gửi (optimistic)
      const box = qs('#chat-messages');
      if (box && Number(otherId) === currentOtherId) {
        const optimistic = {
          id: msg.message_id,
          message_id: msg.message_id,
          sender_id: msg.sender_id,
          content_text: msg.content_text,
          sent_at: msg.sent_at,
          sender_avatar_url: msg.sender_avatar_url,
          other_avatar_url: null,
        };
        appendMessage(optimistic);
      }

      return true;
    } catch (e) {
      console.error('sendMessage error', e);
      return false;
    }
  }

  let ws = null;
  function connectWebSocket() {
    if (!CURRENT_USER_ID || !WS_URL) return;
    try {
      ws = new WebSocket(WS_URL + '?user_id=' + encodeURIComponent(CURRENT_USER_ID));
    } catch (e) {
      console.error('WS connect error', e);
      return;
    }
    ws.addEventListener('open', () => console.log('Chat WS connected'));
    ws.addEventListener('close', () => {
      console.log('Chat WS closed, retry in 5s');
      setTimeout(connectWebSocket, 5000);
    });
    ws.addEventListener('error', (e) => console.error('WS error', e));
    ws.addEventListener('message', (e) => {
      let payload;
      try { payload = JSON.parse(e.data); } catch { return; }
      if (payload.type === 'message' && payload.message) {
        const msg = payload.message;

        // Dedupe: nếu id đã render (optimistic trước đó), bỏ qua
        const id = Number(msg.id ?? msg.message_id);
        if (id && renderedIds.has(id)) return;

        // Xác định cuộc trò chuyện tương ứng đang mở
        const senderId = Number(msg.sender_id);
        const otherIdCandidate =
          senderId === CURRENT_USER_ID
            ? (Array.isArray(payload.recipients)
                ? Number(payload.recipients.find((id) => Number(id) !== CURRENT_USER_ID) || 0)
                : currentOtherId)
            : senderId;

        // Chỉ render nếu đúng cuộc trò chuyện đang mở
        if (otherIdCandidate === currentOtherId) {
          appendMessage(msg);
        }
      }
    });
  }

  document.addEventListener('click', (e) => {
    const sendBtn = e.target.closest('#chat-send-btn');
    if (sendBtn) {
      const input = qs('#chat-input');
      if (!input) return;
      const text = input.value.trim();
      if (!text || !currentOtherId) return;
      sendMessage(currentOtherId, text).then((ok) => {
        if (ok) input.value = '';
      });
    }

    const thread = e.target.closest('.chat-thread-item');
    if (thread) {
      const uid = Number(thread.dataset.userId || 0);
      if (!uid || uid === currentOtherId) return;
      currentOtherId = uid;
      qsa('.chat-thread-item').forEach((it) =>
        it.classList.toggle('active', Number(it.dataset.userId) === uid),
      );
      const box = qs('#chat-messages');
      if (box) {
        box.dataset.otherId = String(uid);
        box.innerHTML = '';
        renderedIds.clear();
      }
      loadMessages(uid, { limit: BATCH, offset: 0 });
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.shiftKey) return;
    const input = e.target.closest('#chat-input');
    if (!input) return;
    e.preventDefault();
    const text = input.value.trim();
    if (!text || !currentOtherId) return;
    sendMessage(currentOtherId, text).then((ok) => {
      if (ok) input.value = '';
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    if (currentOtherId) {
      loadMessages(currentOtherId, { limit: BATCH, offset: 0 });
    }
    connectWebSocket();
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('.chat-panel');
  const box   = document.getElementById('chat-messages');
  if (panel) {
    // Ép thiết lập kích thước và reflow
    panel.style.width = '100%';
    panel.getBoundingClientRect();
  }
  if (box) {
    // Kéo xuống cuối sau khi render
    box.scrollTop = box.scrollHeight;
  }
  // Kích hoạt reflow như khi thu/phóng DevTools
  window.dispatchEvent(new Event('resize'));
});
