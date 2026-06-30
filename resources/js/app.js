require('./bootstrap');

window.Vue = require('vue');

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

if (document.getElementById('app')) {
    new Vue({
        el: '#app',
    });
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character];
    });
}

function nl2br(value) {
    return escapeHtml(value).replace(/\n/g, '<br>');
}

function getInitials(value) {
    var text = String(value || '').trim();

    if (!text) {
        return '?';
    }

    var parts = text.split(/\s+/).filter(Boolean).slice(0, 2);
    return parts.map(function (part) {
        return part.charAt(0).toUpperCase();
    }).join('');
}

function notify(level, message) {
    if (window.AIZ && window.AIZ.plugins && typeof window.AIZ.plugins.notify === 'function') {
        window.AIZ.plugins.notify(level || 'info', message);
        return;
    }

    if (message) {
        window.alert(message);
    }
}

function initNegotiationBoard(root) {
    if (!root || root.dataset.initialized === '1') {
        return;
    }

    root.dataset.initialized = '1';

    var state = {
        activeId: root.dataset.initialId || '',
        listItems: [],
        thread: null,
        pollingTimer: null,
        searchTerm: '',
        sending: false,
    };

    var listUrl = root.dataset.listUrl;
    var showUrlTemplate = root.dataset.showUrlTemplate;
    var messageUrlTemplate = root.dataset.messageUrlTemplate;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '';

    var labels = {
        loadingList: root.dataset.loadingList || 'Loading conversations...',
        noConversations: root.dataset.noConversations || 'No conversations found yet.',
        selectTitle: root.dataset.selectTitle || 'Select a conversation',
        selectCopy: root.dataset.selectCopy || 'Choose a conversation from the left to start reading or replying.',
        noMessages: root.dataset.noMessages || 'No messages yet.',
        noProfile: root.dataset.noProfile || 'No company profile data available.',
        downloadAttachment: root.dataset.downloadAttachment || 'Open file',
        viewDocument: root.dataset.viewDocument || 'View',
        messageRequired: root.dataset.messageRequired || 'Write a message or attach a file before sending.',
        sendFailed: root.dataset.sendFailed || 'Unable to send the message right now.',
        loadFailed: root.dataset.loadFailed || 'Unable to load conversations right now.',
        verifiedSupplier: root.dataset.verifiedSupplier || 'Verified Supplier',
        premiumVerified: root.dataset.premiumVerified || 'Premium Verified',
        featuredSupplier: root.dataset.featuredSupplier || 'Featured Supplier',
    };

    var listEl = root.querySelector('.js-neg-list');
    var searchEl = root.querySelector('.js-neg-search');
    var totalUnreadEl = root.querySelector('.js-neg-total-unread');
    var threadNameEl = root.querySelector('.js-neg-thread-name');
    var threadSubtitleEl = root.querySelector('.js-neg-thread-subtitle');
    var threadReferenceEl = root.querySelector('.js-neg-thread-reference');
    var threadStatusEl = root.querySelector('.js-neg-thread-status');
    var threadAvatarEl = root.querySelector('.js-neg-thread-avatar');
    var threadDotEl = root.querySelector('.js-neg-thread-dot');
    var activeUnreadEl = root.querySelector('.js-neg-active-unread');
    var messagesEl = root.querySelector('.js-neg-messages');
    var profileEl = root.querySelector('.js-neg-profile');
    var formEl = root.querySelector('.js-neg-form');
    var messageInputEl = root.querySelector('.js-neg-message-input');
    var fileInputEl = root.querySelector('.js-neg-file-input');
    var fileNameEl = root.querySelector('.js-neg-file-name');
    var sendButtonEl = root.querySelector('.js-neg-send-button');
    var openProfileEl = root.querySelector('.js-neg-open-profile');
    var closeProfileEl = root.querySelector('.js-neg-close-profile');
    var backdropEl = root.querySelector('.js-neg-drawer-backdrop');

    function threadUrl(id) {
        return showUrlTemplate.replace('__ID__', id);
    }

    function messageUrl(id) {
        return messageUrlTemplate.replace('__ID__', id);
    }

    function setDrawerOpen(open) {
        if (!state.thread || !state.thread.company_profile) {
            root.classList.remove('is-profile-open');
            return;
        }

        root.classList.toggle('is-profile-open', !!open);
    }

    function isNearBottom() {
        return (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight) < 96;
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function renderEmpty(el, title, copy) {
        el.innerHTML = '' +
            '<div class="neg-empty">' +
                '<div>' +
                    '<div class="neg-empty-title">' + escapeHtml(title) + '</div>' +
                    '<div class="neg-empty-copy">' + escapeHtml(copy) + '</div>' +
                '</div>' +
            '</div>';
    }

    function getActiveListItem() {
        var match = null;

        state.listItems.forEach(function (item) {
            if (String(item.id) === String(state.activeId)) {
                match = item;
            }
        });

        return match;
    }

    function updateUnreadIndicators() {
        var totalUnread = 0;
        var activeItem = getActiveListItem();

        state.listItems.forEach(function (item) {
            totalUnread += Number(item.unread_messages_count || 0);
        });

        if (totalUnread > 0) {
            totalUnreadEl.textContent = totalUnread;
            totalUnreadEl.classList.remove('d-none');
        } else {
            totalUnreadEl.classList.add('d-none');
        }

        var activeUnread = activeItem ? Number(activeItem.unread_messages_count || 0) : 0;
        if (activeUnread > 0) {
            activeUnreadEl.textContent = activeUnread;
            activeUnreadEl.classList.remove('d-none');
        } else {
            activeUnreadEl.classList.add('d-none');
        }
    }

    function renderList() {
        var items = state.listItems.filter(function (item) {
            if (!state.searchTerm) {
                return true;
            }

            var haystack = [
                item.title,
                item.subtitle,
                item.latest_message,
                item.status,
            ].join(' ').toLowerCase();

            return haystack.indexOf(state.searchTerm) !== -1;
        });

        updateUnreadIndicators();

        if (!items.length) {
            renderEmpty(listEl, labels.noConversations, state.searchTerm ? 'Try a different search keyword.' : labels.selectCopy);
            return;
        }

        listEl.innerHTML = items.map(function (item) {
            var unread = Number(item.unread_messages_count || 0);
            var status = item.status ? '<span class="neg-status-pill">' + escapeHtml(item.status) + '</span>' : '';
            var time = item.latest_message_human || item.latest_message_at || '';

            return '' +
                '<a href="' + escapeHtml(item.url || '#') + '" class="neg-list-item' + (String(item.id) === String(state.activeId) ? ' is-active' : '') + '" data-id="' + escapeHtml(item.id) + '">' +
                    '<div class="neg-item-top">' +
                        '<div>' +
                            '<div class="neg-item-title">' + escapeHtml(item.title || labels.selectTitle) + '</div>' +
                            '<div class="neg-item-subtitle">' + escapeHtml(item.subtitle || '') + '</div>' +
                        '</div>' +
                        '<div class="text-right">' +
                            '<div class="neg-item-time">' + escapeHtml(time) + '</div>' +
                            (unread > 0 ? '<div class="mt-2"><span class="neg-badge-dot">' + unread + '</span></div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="neg-item-preview">' + escapeHtml(item.latest_message || '') + '</div>' +
                    '<div class="neg-item-bottom mt-2">' +
                        '<div>' + status + '</div>' +
                        '<div class="neg-item-time">' + escapeHtml(item.latest_message_at || '') + '</div>' +
                    '</div>' +
                '</a>';
        }).join('');

        Array.prototype.forEach.call(listEl.querySelectorAll('.neg-list-item'), function (itemEl) {
            itemEl.addEventListener('click', function (event) {
                event.preventDefault();
                var id = itemEl.getAttribute('data-id');

                if (!id) {
                    return;
                }

                setDrawerOpen(false);
                loadThread(id);
            });
        });
    }

    function renderThreadMeta(thread) {
        var referenceBits = [];
        if (thread.reference) {
            if (thread.reference.rfq) {
                referenceBits.push('RFQ: ' + thread.reference.rfq);
            }
            if (thread.reference.quotation) {
                referenceBits.push('Quotation: ' + thread.reference.quotation);
            }
            if (thread.reference.purchase_order) {
                referenceBits.push('PO: ' + thread.reference.purchase_order);
            }
        }

        threadNameEl.textContent = thread.title || labels.selectTitle;
        threadSubtitleEl.textContent = thread.subtitle || '';
        threadReferenceEl.textContent = referenceBits.join(' | ');
        threadAvatarEl.textContent = getInitials(thread.subtitle || thread.title || 'C');
        openProfileEl.disabled = !thread.company_profile;

        if (thread.status) {
            threadStatusEl.textContent = thread.status;
            threadStatusEl.classList.remove('d-none');
        } else {
            threadStatusEl.textContent = '';
            threadStatusEl.classList.add('d-none');
        }

        if (thread.subtitle) {
            threadDotEl.classList.remove('d-none');
        } else {
            threadDotEl.classList.add('d-none');
        }
    }

    function renderMessages(messages, options) {
        var shouldStickToBottom = options && typeof options.wasNearBottom === 'boolean' ? options.wasNearBottom : true;

        if (!messages.length) {
            renderEmpty(messagesEl, labels.noMessages, labels.selectCopy);
            return;
        }

        var previousScrollBottomOffset = messagesEl.scrollHeight - messagesEl.scrollTop;

        messagesEl.innerHTML = '' +
            '<div class="neg-thread-date">Conversation</div>' +
            messages.map(function (item) {
                var attachment = '';

                if (item.attachment) {
                    attachment = '' +
                        '<div class="neg-message-attachment">' +
                            '<span class="neg-attachment-icon">FILE</span>' +
                            '<div>' +
                                '<span class="neg-attachment-name">' + escapeHtml(item.attachment_name || 'Attachment') + '</span>' +
                                '<span class="neg-attachment-link"><a href="' + escapeHtml(item.attachment) + '" target="_blank" rel="noopener">' + escapeHtml(labels.downloadAttachment) + '</a></span>' +
                            '</div>' +
                        '</div>';
                }

                return '' +
                    '<div class="neg-message' + (item.is_mine ? ' is-mine' : '') + '">' +
                        '<div class="neg-message-avatar">' + escapeHtml(getInitials(item.sender_name || item.sender_role || '?')) + '</div>' +
                        '<div class="neg-message-body">' +
                            '<div class="neg-message-bubble">' +
                                (item.message ? '<div>' + nl2br(item.message) + '</div>' : '') +
                                attachment +
                            '</div>' +
                            '<div class="neg-message-meta">' +
                                escapeHtml(item.sender_name || '') +
                                (item.sender_company ? ' | ' + escapeHtml(item.sender_company) : '') +
                                (item.created_at_human || item.created_at ? ' | ' + escapeHtml(item.created_at_human || item.created_at) : '') +
                            '</div>' +
                        '</div>' +
                    '</div>';
            }).join('');

        if (shouldStickToBottom) {
            scrollToBottom();
        } else {
            messagesEl.scrollTop = Math.max(messagesEl.scrollHeight - previousScrollBottomOffset, 0);
        }
    }

    function renderProfile(profile) {
        if (!profile) {
            renderEmpty(profileEl, labels.noProfile, labels.selectCopy);
            return;
        }

        var tags = [];
        if (profile.verified_supplier_badge) {
            tags.push('<span class="neg-profile-tag">' + escapeHtml(labels.verifiedSupplier) + '</span>');
        }
        if (profile.premium_verified) {
            tags.push('<span class="neg-profile-tag">' + escapeHtml(labels.premiumVerified) + '</span>');
        }
        if (profile.featured_supplier) {
            tags.push('<span class="neg-profile-tag">' + escapeHtml(labels.featuredSupplier) + '</span>');
        }

        var documents = Array.isArray(profile.documents) ? profile.documents : [];
        var documentHtml = documents.length ? (
            '<div class="neg-profile-card">' +
                '<div class="neg-profile-label">Attachments</div>' +
                '<div class="neg-doc-list">' +
                    documents.map(function (document) {
                        return '' +
                            '<div class="neg-doc-item">' +
                                '<div>' +
                                    '<div class="neg-profile-label">' + escapeHtml(document.label || 'Document') + '</div>' +
                                    '<div class="neg-doc-name">' + escapeHtml(document.name || '') + '</div>' +
                                '</div>' +
                                '<a class="neg-doc-link" href="' + escapeHtml(document.url || '#') + '" target="_blank" rel="noopener">' + escapeHtml(labels.viewDocument) + '</a>' +
                            '</div>';
                    }).join('') +
                '</div>' +
            '</div>'
        ) : '';

        var websiteValue = profile.website
            ? '<a href="' + escapeHtml(profile.website) + '" target="_blank" rel="noopener">' + escapeHtml(profile.website) + '</a>'
            : '-';

        var publicProfileValue = profile.public_profile_url
            ? '<a href="' + escapeHtml(profile.public_profile_url) + '" target="_blank" rel="noopener">Open public profile</a>'
            : '-';

        profileEl.innerHTML = '' +
            '<div class="neg-profile-hero">' +
                '<div class="neg-profile-avatar">' + escapeHtml(getInitials(profile.name || 'C')) + '</div>' +
                '<h3 class="neg-profile-name">' + escapeHtml(profile.name || '-') + '</h3>' +
                '<div class="neg-profile-copy">' + escapeHtml(profile.company_type || '-') + '</div>' +
                (tags.length ? '<div class="neg-profile-tags">' + tags.join('') + '</div>' : '') +
            '</div>' +
            '<div class="neg-profile-grid">' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Verification</div><div class="neg-profile-value">' + escapeHtml(profile.verification_status || '-') + '</div></div>' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Location</div><div class="neg-profile-value">' + escapeHtml(profile.location || '-') + '</div></div>' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Business Email</div><div class="neg-profile-value">' + escapeHtml(profile.business_email || '-') + '</div></div>' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Phone</div><div class="neg-profile-value">' + escapeHtml(profile.phone || '-') + '</div></div>' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Website</div><div class="neg-profile-value">' + websiteValue + '</div></div>' +
                '<div class="neg-profile-card"><div class="neg-profile-label">Public Profile</div><div class="neg-profile-value">' + publicProfileValue + '</div></div>' +
                documentHtml +
            '</div>';
    }

    function renderThread(thread, options) {
        state.thread = thread;
        renderThreadMeta(thread);
        renderMessages(Array.isArray(thread.messages) ? thread.messages : [], options || {});
        renderProfile(thread.company_profile || null);
        updateUnreadIndicators();
    }

    function fetchJson(url, options) {
        var settings = Object.assign({
            method: 'get',
            headers: {
                'Accept': 'application/json',
            },
        }, options || {});

        if (window.axios) {
            return window.axios(Object.assign({
                url: url,
            }, settings)).then(function (response) {
                return response.data || {};
            });
        }

        var headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        }, settings.headers || {});

        return window.fetch(url, {
            method: String(settings.method || 'get').toUpperCase(),
            headers: headers,
            body: settings.data || null,
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json();
        });
    }

    function loadList(options) {
        var settings = options || {};

        return fetchJson(listUrl).then(function (payload) {
            state.listItems = Array.isArray(payload.data) ? payload.data : [];
            renderList();

            if (!state.activeId && state.listItems.length) {
                state.activeId = String(root.dataset.initialId || state.listItems[0].id);
            }

            if (!settings.skipThreadLoad && state.activeId) {
                var alreadyRendered = state.thread && String(state.thread.id) === String(state.activeId);
                if (!alreadyRendered) {
                    return loadThread(state.activeId, { silent: true });
                }
            }

            if (!state.activeId && !state.listItems.length) {
                state.thread = null;
                renderEmpty(messagesEl, labels.selectTitle, labels.selectCopy);
                renderEmpty(profileEl, root.dataset.companyProfile || 'Company Profile', labels.selectCopy);
            }

            return null;
        }).catch(function () {
            if (!settings.silent) {
                notify('danger', labels.loadFailed);
                renderEmpty(listEl, labels.noConversations, labels.loadFailed);
            }
        });
    }

    function loadThread(id, options) {
        if (!id) {
            return Promise.resolve();
        }

        var settings = options || {};
        var wasNearBottom = isNearBottom();
        state.activeId = String(id);
        renderList();

        if (!settings.silent) {
            renderEmpty(messagesEl, 'Loading', 'Conversation is loading.');
        }

        return fetchJson(threadUrl(id)).then(function (payload) {
            var thread = payload.data || {};
            renderThread(thread, { wasNearBottom: settings.silent ? wasNearBottom : true });
            return loadList({ silent: true, skipThreadLoad: true });
        }).catch(function () {
            if (!settings.silent) {
                notify('danger', labels.loadFailed);
            }
        });
    }

    function refreshActiveThreadSilently() {
        if (!state.activeId) {
            return;
        }

        loadThread(state.activeId, { silent: true });
    }

    function startPolling() {
        if (state.pollingTimer) {
            window.clearInterval(state.pollingTimer);
        }

        state.pollingTimer = window.setInterval(function () {
            loadList({ silent: true, skipThreadLoad: true });
            refreshActiveThreadSilently();
        }, 15000);
    }

    formEl.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!state.activeId || state.sending) {
            return;
        }

        var hasMessage = messageInputEl.value.trim() !== '';
        var hasAttachment = fileInputEl.files && fileInputEl.files.length > 0;
        if (!hasMessage && !hasAttachment) {
            notify('warning', labels.messageRequired);
            return;
        }

        var formData = new window.FormData(formEl);
        state.sending = true;
        sendButtonEl.disabled = true;

        fetchJson(messageUrl(state.activeId), {
            method: 'post',
            data: formData,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'multipart/form-data',
            },
        }).then(function () {
            formEl.reset();
            fileNameEl.textContent = '';
            return loadThread(state.activeId, { silent: true });
        }).catch(function (error) {
            var responseMessage = error && error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : labels.sendFailed;
            notify('danger', responseMessage);
        }).then(function () {
            state.sending = false;
            sendButtonEl.disabled = false;
            messageInputEl.focus();
        });
    });

    fileInputEl.addEventListener('change', function () {
        fileNameEl.textContent = fileInputEl.files && fileInputEl.files[0] ? fileInputEl.files[0].name : '';
    });

    if (searchEl) {
        searchEl.addEventListener('input', function () {
            state.searchTerm = String(searchEl.value || '').trim().toLowerCase();
            renderList();
        });
    }

    openProfileEl.addEventListener('click', function () {
        if (state.thread && state.thread.company_profile) {
            setDrawerOpen(true);
        }
    });

    closeProfileEl.addEventListener('click', function () {
        setDrawerOpen(false);
    });

    backdropEl.addEventListener('click', function () {
        setDrawerOpen(false);
    });

    loadList();
    startPolling();
}

document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('#negotiation-board'), function (root) {
        initNegotiationBoard(root);
    });
});
