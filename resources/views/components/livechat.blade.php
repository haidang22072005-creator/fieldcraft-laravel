@if(auth()->check() && auth()->user()->role === 'customer')
<style>
    #fieldcraft-livechat-button { position:fixed; right:22px; bottom:22px; z-index:120; border:1px solid var(--neon-green); border-radius:999px; padding:12px 16px; background:var(--neon-green); color:#07110d; font-weight:800; box-shadow:0 12px 28px rgba(0,0,0,.35); cursor:pointer; transition:opacity .18s, transform .18s, visibility .18s; }
    #fieldcraft-livechat-panel { position:fixed; right:22px; bottom:78px; z-index:121; display:none; width:min(380px,calc(100vw - 28px)); height:min(560px,calc(100vh - 110px)); overflow:hidden; flex-direction:column; border:1px solid var(--border-panel); border-radius:14px; background:var(--bg-panel); color:var(--text-main); box-shadow:0 20px 60px rgba(0,0,0,.5); }
    #fieldcraft-livechat-button.is-cart-open, #fieldcraft-livechat-panel.is-cart-open { opacity:0; pointer-events:none; transform:translateY(16px); visibility:hidden; }
    #fieldcraft-livechat-panel.is-open { display:flex; }
    .fieldcraft-livechat-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 16px; border-bottom:1px solid var(--border-panel); }
    .fieldcraft-livechat-head strong { letter-spacing:.08em; font-size:12px; }
    .fieldcraft-livechat-close { border:0; background:transparent; color:var(--text-muted); font-size:22px; cursor:pointer; }
    #fieldcraft-livechat-messages { flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:10px; padding:14px; }
    .fieldcraft-livechat-message { max-width:86%; padding:9px 11px; border-radius:10px; background:var(--bg-panel-sub); color:var(--text-sub); }
    .fieldcraft-livechat-message.is-own { align-self:flex-end; background:var(--neon-green); color:#07110d; }
    .fieldcraft-livechat-meta { display:block; margin-bottom:3px; font-size:10px; color:var(--text-muted); }
    .fieldcraft-livechat-message.is-own .fieldcraft-livechat-meta { color:#38520b; }
    .fieldcraft-livechat-content { white-space:pre-wrap; overflow-wrap:anywhere; }
    .fieldcraft-livechat-status { min-height:20px; padding:0 14px 8px; color:var(--error-text); font-size:11px; }
    .fieldcraft-livechat-form { display:flex; gap:8px; padding:12px; border-top:1px solid var(--border-panel); }
    .fieldcraft-livechat-form textarea { min-height:42px; max-height:100px; flex:1; resize:vertical; border:1px solid var(--border-sub); border-radius:8px; padding:9px; background:var(--bg-input); color:var(--text-main); }
    .fieldcraft-livechat-form button { border:0; border-radius:8px; padding:0 13px; background:var(--neon-green); color:#07110d; font-weight:800; cursor:pointer; }
    .fieldcraft-livechat-form button:disabled { cursor:wait; opacity:.55; }
    @media (max-width:560px) { #fieldcraft-livechat-button { right:14px; bottom:14px; padding:11px 13px; font-size:12px; } #fieldcraft-livechat-panel { right:14px; bottom:68px; height:min(520px,calc(100vh - 88px)); } }
</style>

<button id="fieldcraft-livechat-button" type="button" aria-expanded="false">💬 HỖ TRỢ TRỰC TUYẾN</button>
<section id="fieldcraft-livechat-panel" aria-label="FIELDCRAFT SUPPORT" aria-hidden="true">
    <div class="fieldcraft-livechat-head">
        <strong>FIELDCRAFT SUPPORT</strong>
        <button class="fieldcraft-livechat-close" type="button" aria-label="Đóng">×</button>
    </div>
    <div id="fieldcraft-livechat-messages" aria-live="polite"></div>
    <div id="fieldcraft-livechat-status" class="fieldcraft-livechat-status" role="status"></div>
    <form id="fieldcraft-livechat-form" class="fieldcraft-livechat-form">
        <textarea id="fieldcraft-livechat-input" maxlength="5000" placeholder="Nhập tin nhắn..." aria-label="Tin nhắn"></textarea>
        <button id="fieldcraft-livechat-send" type="submit">GỬI</button>
    </form>
</section>

<script>
(() => {
    const button = document.getElementById('fieldcraft-livechat-button');
    const panel = document.getElementById('fieldcraft-livechat-panel');
    const closeButton = panel.querySelector('.fieldcraft-livechat-close');
    const messagesNode = document.getElementById('fieldcraft-livechat-messages');
    const statusNode = document.getElementById('fieldcraft-livechat-status');
    const form = document.getElementById('fieldcraft-livechat-form');
    const input = document.getElementById('fieldcraft-livechat-input');
    const sendButton = document.getElementById('fieldcraft-livechat-send');
    const currentUserId = @json(auth()->id());
    const urls = { index: @json(route('chat.messages.index')), store: @json(route('chat.messages.store')) };
    const state = { open: false, requestActive: false, timer: null };
    const cartDrawer = document.getElementById('cartDrawer');
    const drawerBackdrop = document.getElementById('drawerBackdrop');

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value
            || '';
    }

    function syncCartVisibility() {
        const cartOpen = cartDrawer?.classList.contains('show') || drawerBackdrop?.classList.contains('show');
        button.classList.toggle('is-cart-open', Boolean(cartOpen));
        panel.classList.toggle('is-cart-open', Boolean(cartOpen));
    }

    [cartDrawer, drawerBackdrop].filter(Boolean).forEach(node => {
        new MutationObserver(syncCartVisibility).observe(node, { attributes: true, attributeFilter: ['class'] });
    });
    syncCartVisibility();

    function setStatus(message) { statusNode.textContent = message || ''; }
    function showLoading() {
        messagesNode.replaceChildren();
        const loading = document.createElement('div');
        loading.className = 'fieldcraft-livechat-status';
        loading.textContent = 'Đang tải cuộc trò chuyện...';
        messagesNode.appendChild(loading);
    }
    function renderMessages(messages) {
        messagesNode.replaceChildren();
        if (messages.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'fieldcraft-livechat-status';
            empty.style.color = 'var(--text-muted)';
            empty.textContent = 'Chưa có tin nhắn. Hãy gửi lời chào tới FIELDCRAFT.';
            messagesNode.appendChild(empty);
            return;
        }
        messages.forEach(messageData => {
            const own = Number(messageData.sender_id) === Number(currentUserId);
            const bubble = document.createElement('div');
            bubble.className = 'fieldcraft-livechat-message' + (own ? ' is-own' : '');
            const meta = document.createElement('span');
            meta.className = 'fieldcraft-livechat-meta';
            meta.textContent = (messageData.sender?.name || (own ? 'Bạn' : 'FIELDCRAFT')) + ' · ' + (messageData.created_at ? new Date(messageData.created_at).toLocaleString('vi-VN') : '');
            const content = document.createElement('div');
            content.className = 'fieldcraft-livechat-content';
            content.textContent = messageData.content || '';
            bubble.append(meta, content);
            messagesNode.appendChild(bubble);
        });
        messagesNode.scrollTop = messagesNode.scrollHeight;
    }
    async function loadConversation(showLoadingState = false) {
        if (state.requestActive || !state.open || document.hidden) return;
        state.requestActive = true;
        if (showLoadingState) showLoading();
        try {
            const response = await fetch(urls.index, { headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không thể tải cuộc trò chuyện.');
            renderMessages(Array.isArray(payload.data) ? payload.data : []);
            setStatus('');
        } catch (error) {
            setStatus(error.message || 'Không thể tải cuộc trò chuyện.');
        } finally {
            state.requestActive = false;
        }
    }
    function stopPolling() { if (state.timer) { clearInterval(state.timer); state.timer = null; } }
    function startPolling() { stopPolling(); if (state.open && !document.hidden) state.timer = setInterval(() => loadConversation(), 3000); }
    function setOpen(open) {
        state.open = open;
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) { loadConversation(true); startPolling(); } else stopPolling();
    }
    button.addEventListener('click', () => setOpen(!state.open));
    closeButton.addEventListener('click', () => setOpen(false));
    document.addEventListener('visibilitychange', () => { if (document.hidden) stopPolling(); else if (state.open) { loadConversation(); startPolling(); } });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || state.requestActive) return;
        state.requestActive = true;
        sendButton.disabled = true;
        setStatus('');
        try {
            const token = csrfToken();
            if (!token) {
                setStatus('Không thể gửi tin nhắn vì thiếu mã CSRF. Hãy tải lại trang.');
                return;
            }
            const response = await fetch(urls.store, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ content }) });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không thể gửi tin nhắn.');
            renderMessages(Array.isArray(payload.data) ? payload.data : []);
            input.value = '';
        } catch (error) {
            setStatus(error.message || 'Không thể gửi tin nhắn.');
        } finally {
            state.requestActive = false;
            sendButton.disabled = false;
        }
    });
})();
</script>
@endif
