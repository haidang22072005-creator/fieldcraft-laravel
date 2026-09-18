@extends('layouts.admin')

@section('content')
<style>
    .livechat-admin-grid { display:grid; grid-template-columns:minmax(240px,320px) minmax(0,1fr); gap:16px; min-height:620px; }
    .livechat-admin-panel { min-width:0; display:flex; flex-direction:column; background:var(--bg-panel); border:1px solid var(--border-panel); border-radius:10px; overflow:hidden; }
    .livechat-admin-panel-head { padding:16px; border-bottom:1px solid var(--border-panel); }
    .livechat-admin-panel-head h2 { margin:0; font-size:16px; }
    #chat-customers-list { flex:1; overflow-y:auto; padding:8px; }
    .livechat-customer-button { display:flex; width:100%; gap:10px; align-items:center; border:0; border-radius:8px; padding:11px 10px; background:transparent; color:var(--text-main); text-align:left; cursor:pointer; }
    .livechat-customer-button:hover, .livechat-customer-button.is-selected { background:var(--bg-panel-sub); }
    .livechat-customer-avatar { display:grid; flex:0 0 34px; width:34px; height:34px; place-items:center; border-radius:50%; background:var(--lime); color:#07110d; font-weight:800; }
    .livechat-customer-copy { min-width:0; flex:1; }
    .livechat-customer-name, .livechat-customer-preview { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .livechat-customer-name { font-weight:800; }
    .livechat-customer-preview, .livechat-customer-time { color:var(--text-muted); font-size:11px; }
    .livechat-customer-unread { min-width:20px; padding:3px 6px; border-radius:12px; background:var(--lime); color:#07110d; font:700 10px 'DM Mono',monospace; text-align:center; }
    #chat-messages-thread { flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:10px; padding:16px; }
    .livechat-admin-message { max-width:78%; padding:10px 12px; border-radius:10px; background:var(--bg-panel-sub); color:var(--text-sub); }
    .livechat-admin-message.is-own { align-self:flex-end; background:var(--lime); color:#07110d; }
    .livechat-admin-message-meta { display:block; margin-bottom:3px; color:var(--text-muted); font-size:10px; }
    .livechat-admin-message.is-own .livechat-admin-message-meta { color:#38520b; }
    .livechat-admin-message-content { white-space:pre-wrap; overflow-wrap:anywhere; }
    .livechat-admin-status { padding:12px 16px; color:var(--danger); font-size:12px; }
    #chat-reply-form { display:flex; gap:8px; padding:12px 16px; border-top:1px solid var(--border-panel); }
    #chat-reply-input { min-height:44px; max-height:130px; flex:1; resize:vertical; border:1px solid var(--border-sub); border-radius:8px; padding:10px; background:var(--bg-input); color:var(--text-main); }
    #chat-reply-send { border:0; border-radius:8px; padding:0 16px; background:var(--lime); color:#07110d; font-weight:800; cursor:pointer; }
    #chat-reply-send:disabled { opacity:.55; cursor:wait; }
    @media (max-width:760px) { .livechat-admin-grid { grid-template-columns:1fr; min-height:0; } .livechat-admin-panel:first-child { min-height:220px; } .livechat-admin-panel:last-child { min-height:500px; } }
</style>

<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap">
    <div>
        <div class="eyebrow">CUSTOMER COMMUNICATION</div>
        <h1 style="margin-top:6px">CHAT NHANH</h1>
        <p style="margin-top:6px;color:var(--text-muted)">Trao đổi trực tiếp với khách hàng. Yêu cầu hỗ trợ vẫn nằm riêng trong Support Center. Hiển thị tối đa 100 cuộc trò chuyện gần nhất; trạng thái chưa đọc dùng chung cho các quản trị viên.</p>
    </div>
</div>

<div id="chat-admin-status" class="livechat-admin-status" role="status"></div>
<div class="livechat-admin-grid">
    <aside class="livechat-admin-panel">
        <div class="livechat-admin-panel-head"><h2>Khách hàng đã chat</h2></div>
        <div id="chat-customers-list"><div style="padding:16px;color:var(--text-muted)">Đang tải danh sách...</div></div>
    </aside>
    <section class="livechat-admin-panel">
        <div class="livechat-admin-panel-head"><h2 id="chat-selected-title">Chọn một khách hàng</h2></div>
        <div id="chat-messages-thread" aria-live="polite"><div style="color:var(--text-muted)">Chọn khách hàng để xem cuộc trò chuyện.</div></div>
        <form id="chat-reply-form">
            <textarea id="chat-reply-input" maxlength="5000" placeholder="Nhập phản hồi..." aria-label="Phản hồi"></textarea>
            <button id="chat-reply-send" type="submit" disabled>GỬI</button>
        </form>
    </section>
</div>

<script>
(() => {
    const listNode = document.getElementById('chat-customers-list');
    const threadNode = document.getElementById('chat-messages-thread');
    const titleNode = document.getElementById('chat-selected-title');
    const statusNode = document.getElementById('chat-admin-status');
    const form = document.getElementById('chat-reply-form');
    const input = document.getElementById('chat-reply-input');
    const sendButton = document.getElementById('chat-reply-send');
    const currentAdminId = @json(auth()->id());
    const urls = { list: @json(route('admin.chat.customers.index')), show: @json(route('admin.chat.customers.messages', ['user' => '__CUSTOMER__'])), store: @json(route('admin.chat.customers.messages.store', ['user' => '__CUSTOMER__'])) };
    const state = { selectedId: null, customers: [], listRequestActive: false, conversationRequestActive: false, sendActive: false, pollActive: false };

    function setStatus(message) { statusNode.textContent = message || ''; }
    function endpoint(template, customerId) { return template.replace('__CUSTOMER__', encodeURIComponent(String(customerId))); }
    function initials(name) { return String(name || '?').trim().split(/\s+/).slice(-2).map(part => part.charAt(0)).join('').toUpperCase() || '?'; }
    function emptyNode(text) { const node = document.createElement('div'); node.style.color = 'var(--text-muted)'; node.style.padding = '16px'; node.textContent = text; return node; }

    function renderCustomers(customers) {
        listNode.replaceChildren();
        if (customers.length === 0) { listNode.appendChild(emptyNode('Chưa có khách hàng nào bắt đầu chat.')); return; }
        customers.forEach(customer => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'livechat-customer-button' + (Number(customer.id) === Number(state.selectedId) ? ' is-selected' : '');
            button.dataset.customerId = customer.id;
            const avatar = document.createElement('span');
            avatar.className = 'livechat-customer-avatar';
            avatar.textContent = initials(customer.name);
            const copy = document.createElement('span');
            copy.className = 'livechat-customer-copy';
            const name = document.createElement('span');
            name.className = 'livechat-customer-name';
            name.textContent = customer.name || 'Khách hàng';
            const preview = document.createElement('span');
            preview.className = 'livechat-customer-preview';
            preview.textContent = customer.last_message_content || 'Chưa có nội dung';
            const time = document.createElement('span');
            time.className = 'livechat-customer-time';
            time.textContent = customer.last_chat_message_at ? new Date(customer.last_chat_message_at).toLocaleString('vi-VN') : '';
            copy.append(name, preview, time);
            button.append(avatar, copy);
            if (Number(customer.unread_messages_count) > 0) { const unread = document.createElement('span'); unread.className = 'livechat-customer-unread'; unread.textContent = customer.unread_messages_count; button.appendChild(unread); }
            button.addEventListener('click', () => selectCustomer(customer));
            listNode.appendChild(button);
        });
    }

    function renderConversation(messages) {
        threadNode.replaceChildren();
        if (messages.length === 0) { threadNode.appendChild(emptyNode('Chưa có tin nhắn trong cuộc trò chuyện này.')); return; }
        messages.forEach(messageData => {
            const own = Number(messageData.sender_id) === Number(currentAdminId);
            const bubble = document.createElement('div');
            bubble.className = 'livechat-admin-message' + (own ? ' is-own' : '');
            const meta = document.createElement('span');
            meta.className = 'livechat-admin-message-meta';
            meta.textContent = (messageData.sender?.name || (own ? 'Bạn' : 'Khách hàng')) + ' · ' + (messageData.created_at ? new Date(messageData.created_at).toLocaleString('vi-VN') : '');
            const content = document.createElement('div');
            content.className = 'livechat-admin-message-content';
            content.textContent = messageData.content || '';
            bubble.append(meta, content);
            threadNode.appendChild(bubble);
        });
        threadNode.scrollTop = threadNode.scrollHeight;
    }

    async function loadCustomers() {
        if (state.listRequestActive) return [];
        state.listRequestActive = true;
        try {
            const response = await fetch(urls.list, { headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không thể tải danh sách khách hàng.');
            const customers = Array.isArray(payload.data) ? payload.data : [];
            state.customers = customers;
            renderCustomers(state.customers);
            const selected = customers.find(customer => Number(customer.id) === Number(state.selectedId));
            if (!selected && customers[0]) await selectCustomer(customers[0]);
            return state.customers;
        } catch (error) {
            setStatus(error.message || 'Không thể tải danh sách khách hàng.');
            return [];
        } finally { state.listRequestActive = false; }
    }

    async function loadConversation() {
        if (!state.selectedId || state.conversationRequestActive) return;
        state.conversationRequestActive = true;
        try {
            const response = await fetch(endpoint(urls.show, state.selectedId), { headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không thể tải cuộc trò chuyện.');
            renderConversation(Array.isArray(payload.data) ? payload.data : []);
            setStatus('');
            return true;
        } catch (error) { setStatus(error.message || 'Không thể tải cuộc trò chuyện.'); }
        finally { state.conversationRequestActive = false; }
        return false;
    }

    async function selectCustomer(customer) {
        state.selectedId = customer.id;
        titleNode.textContent = customer.name || 'Khách hàng';
        sendButton.disabled = false;
        renderCustomers(state.customers);
        if (await loadConversation()) {
            state.customers = state.customers.map(item => Number(item.id) === Number(state.selectedId)
                ? { ...item, unread_messages_count: 0 }
                : item);
            renderCustomers(state.customers);
        }
    }

    async function refresh() {
        if (document.hidden || state.pollActive) return;
        state.pollActive = true;
        await loadCustomers();
        if (state.selectedId) await loadConversation();
        state.pollActive = false;
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || !state.selectedId || state.sendActive) return;
        state.sendActive = true;
        sendButton.disabled = true;
        try {
            const response = await fetch(endpoint(urls.store, state.selectedId), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify({ content }) });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không thể gửi phản hồi.');
            renderConversation(Array.isArray(payload.data) ? payload.data : []);
            input.value = '';
            await loadCustomers();
        } catch (error) { setStatus(error.message || 'Không thể gửi phản hồi.'); }
        finally { state.sendActive = false; sendButton.disabled = !state.selectedId; }
    });

    loadCustomers();
    setInterval(refresh, 3000);
})();
</script>
@endsection
