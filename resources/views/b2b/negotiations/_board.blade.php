@php
    $portal = $portal ?? 'buyer';
    $initialNegotiationId = $initialNegotiationId ?? null;
    $listUrl = $portal === 'buyer' ? route('b2b.negotiations.data') : route('seller.b2b.negotiations.data');
    $showUrlTemplate = $portal === 'buyer'
        ? route('b2b.negotiations.show.data', ['id' => '__ID__'])
        : route('seller.b2b.negotiations.show.data', ['id' => '__ID__']);
    $messageUrlTemplate = $portal === 'buyer'
        ? route('b2b.negotiations.messages.store', ['id' => '__ID__'])
        : route('seller.b2b.negotiations.messages.store', ['id' => '__ID__']);
@endphp

<style>
    .neg-board { display: grid; grid-template-columns: 320px minmax(0, 1fr) 300px; gap: 18px; min-height: 72vh; }
    .neg-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 18px 45px -40px rgba(15, 23, 42, 0.25); }
    .neg-list-head, .neg-thread-head, .neg-profile-head { padding: 18px 18px 14px; border-bottom: 1px solid #eef2f7; }
    .neg-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
    .neg-subtitle { font-size: 13px; color: #64748b; margin-top: 6px; }
    .neg-list { max-height: 72vh; overflow: auto; }
    .neg-item { display: block; padding: 16px 18px; border-top: 1px solid #f1f5f9; color: inherit; text-decoration: none; cursor: pointer; }
    .neg-item:hover, .neg-item.is-active { background: #fff7ed; text-decoration: none; }
    .neg-item-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
    .neg-item-meta, .neg-item-copy { font-size: 12px; color: #64748b; }
    .neg-item-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
    .neg-pill { display: inline-flex; align-items: center; border-radius: 999px; background: #f1f5f9; color: #334155; padding: 5px 9px; font-size: 11px; font-weight: 700; }
    .neg-badge { min-width: 22px; height: 22px; border-radius: 999px; background: #f97316; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
    .neg-thread-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .neg-thread-messages { padding: 18px; height: calc(72vh - 208px); overflow: auto; background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); }
    .neg-message { max-width: 78%; margin-bottom: 14px; }
    .neg-message.is-mine { margin-left: auto; }
    .neg-bubble { border-radius: 18px; padding: 12px 14px; background: #fff; border: 1px solid #e5e7eb; }
    .neg-message.is-mine .neg-bubble { background: #ffedd5; border-color: #fdba74; }
    .neg-message-meta { font-size: 11px; color: #64748b; margin-top: 6px; }
    .neg-thread-form { padding: 16px 18px; border-top: 1px solid #eef2f7; }
    .neg-thread-actions { display: flex; gap: 10px; align-items: center; }
    .neg-thread-actions .form-control { min-height: 48px; }
    .neg-empty { padding: 26px 20px; color: #64748b; text-align: center; }
    .neg-profile-body { padding: 18px; }
    .neg-profile-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
    .neg-profile-value { font-size: 14px; color: #0f172a; margin-bottom: 14px; word-break: break-word; }
    .neg-profile-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .neg-profile-link { color: #ea580c; text-decoration: none; font-weight: 700; }
    .neg-profile-doc { display: flex; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid #f1f5f9; padding: 12px 0; }
    @media (max-width: 1199px) { .neg-board { grid-template-columns: 280px minmax(0, 1fr); } .neg-profile { grid-column: 1 / -1; } }
    @media (max-width: 767px) { .neg-board { grid-template-columns: 1fr; } .neg-list, .neg-thread-messages { max-height: none; height: auto; } }
</style>

<div
    id="negotiation-board"
    class="neg-board"
    data-list-url="{{ $listUrl }}"
    data-show-url-template="{{ $showUrlTemplate }}"
    data-message-url-template="{{ $messageUrlTemplate }}"
    data-initial-id="{{ $initialNegotiationId }}"
    data-csrf="{{ csrf_token() }}"
>
    <div class="neg-card">
        <div class="neg-list-head">
            <h2 class="neg-title">{{ translate('Negotiations') }}</h2>
            <div class="neg-subtitle">{{ translate('Review discussions, quotations, and supplier documents in one workspace.') }}</div>
        </div>
        <div class="neg-list" id="negotiation-list">
            <div class="neg-empty">{{ translate('Loading conversations...') }}</div>
        </div>
    </div>

    <div class="neg-card">
        <div class="neg-thread-head">
            <div>
                <h3 class="neg-title" id="negotiation-thread-title">{{ translate('Select a conversation') }}</h3>
                <div class="neg-subtitle" id="negotiation-thread-subtitle">{{ translate('The latest messages will appear here.') }}</div>
            </div>
            <span class="neg-pill" id="negotiation-thread-status" style="display:none;"></span>
        </div>
        <div class="neg-thread-messages" id="negotiation-thread-messages">
            <div class="neg-empty">{{ translate('No conversation selected.') }}</div>
        </div>
        <form class="neg-thread-form" id="negotiation-thread-form" enctype="multipart/form-data">
            <div class="neg-thread-actions">
                <input type="text" name="message" class="form-control" placeholder="{{ translate('Write your message...') }}" autocomplete="off">
                <input type="file" name="attachment" class="form-control-file">
                <button type="submit" class="btn btn-primary">{{ translate('Send') }}</button>
            </div>
        </form>
    </div>

    <div class="neg-card neg-profile">
        <div class="neg-profile-head">
            <h3 class="neg-title">{{ translate('Company Profile') }}</h3>
            <div class="neg-subtitle">{{ translate('Verification and document details for the counterparty.') }}</div>
        </div>
        <div class="neg-profile-body" id="negotiation-profile-body">
            <div class="neg-empty">{{ translate('Select a conversation to view company details.') }}</div>
        </div>
    </div>
</div>

<script>
    (function () {
        var root = document.getElementById('negotiation-board');
        if (!root) return;

        var listUrl = root.dataset.listUrl;
        var showUrlTemplate = root.dataset.showUrlTemplate;
        var messageUrlTemplate = root.dataset.messageUrlTemplate;
        var initialId = root.dataset.initialId || '';
        var csrf = root.dataset.csrf;
        var listEl = document.getElementById('negotiation-list');
        var titleEl = document.getElementById('negotiation-thread-title');
        var subtitleEl = document.getElementById('negotiation-thread-subtitle');
        var statusEl = document.getElementById('negotiation-thread-status');
        var messagesEl = document.getElementById('negotiation-thread-messages');
        var profileEl = document.getElementById('negotiation-profile-body');
        var formEl = document.getElementById('negotiation-thread-form');
        var activeId = null;

        function escapeHtml(value) {
            return (value || '').replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
            });
        }

        function threadUrl(id) { return showUrlTemplate.replace('__ID__', id); }
        function messageUrl(id) { return messageUrlTemplate.replace('__ID__', id); }

        function renderList(items) {
            if (!items.length) {
                listEl.innerHTML = '<div class="neg-empty">{{ translate('No negotiations found.') }}</div>';
                return;
            }

            listEl.innerHTML = items.map(function (item) {
                var unread = item.unread_messages_count > 0 ? '<span class="neg-badge">' + item.unread_messages_count + '</span>' : '';
                var status = item.status ? '<span class="neg-pill">' + escapeHtml(item.status) + '</span>' : '';
                return '' +
                    '<a href="' + escapeHtml(item.url || '#') + '" class="neg-item' + (String(item.id) === String(activeId) ? ' is-active' : '') + '" data-id="' + item.id + '">' +
                        '<div class="neg-item-row"><div class="neg-item-title">' + escapeHtml(item.title) + '</div>' + unread + '</div>' +
                        '<div class="neg-item-meta">' + escapeHtml(item.subtitle || '') + '</div>' +
                        '<div class="neg-item-copy mt-2">' + escapeHtml(item.latest_message || '') + '</div>' +
                        '<div class="neg-item-row mt-2">' + status + '<div class="neg-item-meta">' + escapeHtml(item.latest_message_human || '') + '</div></div>' +
                    '</a>';
            }).join('');

            Array.prototype.forEach.call(listEl.querySelectorAll('.neg-item'), function (itemEl) {
                itemEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    loadThread(itemEl.getAttribute('data-id'));
                });
            });
        }

        function renderMessages(items) {
            if (!items.length) {
                messagesEl.innerHTML = '<div class="neg-empty">{{ translate('No messages yet.') }}</div>';
                return;
            }

            messagesEl.innerHTML = items.map(function (item) {
                var attachment = item.attachment ? '<div class="mt-2"><a class="neg-profile-link" href="' + escapeHtml(item.attachment) + '" target="_blank" rel="noopener">' + escapeHtml(item.attachment_name || '{{ translate('Download attachment') }}') + '</a></div>' : '';
                return '' +
                    '<div class="neg-message' + (item.is_mine ? ' is-mine' : '') + '">' +
                        '<div class="neg-bubble">' +
                            (item.message ? '<div>' + escapeHtml(item.message) + '</div>' : '') +
                            attachment +
                        '</div>' +
                        '<div class="neg-message-meta">' + escapeHtml(item.sender_name || '') + (item.sender_company ? ' · ' + escapeHtml(item.sender_company) : '') + ' · ' + escapeHtml(item.created_at_human || item.created_at || '') + '</div>' +
                    '</div>';
            }).join('');

            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function renderProfile(profile) {
            if (!profile) {
                profileEl.innerHTML = '<div class="neg-empty">{{ translate('No company profile data available.') }}</div>';
                return;
            }

            var tags = [];
            if (profile.verified_supplier_badge) tags.push('<span class="neg-pill">{{ translate('Verified Supplier') }}</span>');
            if (profile.premium_verified) tags.push('<span class="neg-pill">{{ translate('Premium Verified') }}</span>');
            if (profile.featured_supplier) tags.push('<span class="neg-pill">{{ translate('Featured Supplier') }}</span>');

            var docs = (profile.documents || []).map(function (doc) {
                return '' +
                    '<div class="neg-profile-doc">' +
                        '<div><div class="neg-profile-label">' + escapeHtml(doc.label || '') + '</div><div class="neg-profile-value mb-0">' + escapeHtml(doc.name || '') + '</div></div>' +
                        '<a href="' + escapeHtml(doc.url || '#') + '" class="neg-profile-link" target="_blank" rel="noopener">{{ translate('View') }}</a>' +
                    '</div>';
            }).join('');

            var publicLink = profile.public_profile_url ? '<a href="' + escapeHtml(profile.public_profile_url) + '" class="neg-profile-link">{{ translate('Open public profile') }}</a>' : '';

            profileEl.innerHTML = '' +
                '<div class="neg-profile-label">{{ translate('Company') }}</div><div class="neg-profile-value">' + escapeHtml(profile.name || '') + '</div>' +
                '<div class="neg-profile-tags">' + tags.join('') + '</div>' +
                '<div class="neg-profile-label">{{ translate('Type / Status') }}</div><div class="neg-profile-value">' + escapeHtml((profile.company_type || '-') + ' / ' + (profile.verification_status || '-')) + '</div>' +
                '<div class="neg-profile-label">{{ translate('Location') }}</div><div class="neg-profile-value">' + escapeHtml(profile.location || '-') + '</div>' +
                '<div class="neg-profile-label">{{ translate('Contact') }}</div><div class="neg-profile-value">' + escapeHtml(profile.business_email || '-') + (profile.phone ? '<br>' + escapeHtml(profile.phone) : '') + '</div>' +
                (profile.website ? '<div class="neg-profile-label">{{ translate('Website') }}</div><div class="neg-profile-value"><a href="' + escapeHtml(profile.website) + '" class="neg-profile-link" target="_blank" rel="noopener">' + escapeHtml(profile.website) + '</a></div>' : '') +
                publicLink +
                (docs ? '<div class="mt-3">' + docs + '</div>' : '');
        }

        function loadThread(id) {
            activeId = id;
            fetch(threadUrl(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    var data = payload.data || {};
                    titleEl.textContent = data.title || '{{ translate('Conversation') }}';
                    subtitleEl.textContent = data.subtitle || '';
                    if (data.status) {
                        statusEl.style.display = 'inline-flex';
                        statusEl.textContent = data.status;
                    } else {
                        statusEl.style.display = 'none';
                        statusEl.textContent = '';
                    }
                    renderMessages(data.messages || []);
                    renderProfile(data.company_profile || null);
                    refreshList();
                });
        }

        function refreshList() {
            fetch(listUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    var items = payload.data || [];
                    renderList(items);
                    if (!activeId && items.length) {
                        activeId = initialId || items[0].id;
                        loadThread(activeId);
                    }
                });
        }

        formEl.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!activeId) return;

            var formData = new FormData(formEl);
            fetch(messageUrl(activeId), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function () {
                    formEl.reset();
                    loadThread(activeId);
                });
        });

        refreshList();
    })();
</script>
