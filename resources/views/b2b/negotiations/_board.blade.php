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
    $currentUser = auth()->user();
    $currentUserName = $currentUser?->name ?: translate('Portal User');
    $currentUserRole = $portal === 'supplier' ? translate('Supplier Manager') : translate('Buyer Manager');
    $currentUserInitial = strtoupper(mb_substr($currentUserName, 0, 1));
@endphp

@push('styles')
    <style>
        .neg-shell {
            position: relative;
        }
        .neg-board {
            display: grid;
            grid-template-columns: 330px minmax(0, 1fr);
            gap: 22px;
            min-height: calc(100vh - 246px);
        }
        .neg-panel {
            background: #ffffff;
            border: 1px solid #e7edf6;
            border-radius: 30px;
            box-shadow: 0 28px 70px -58px rgba(15, 23, 42, 0.45);
            overflow: hidden;
        }
        .neg-sidebar {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 246px);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }
        .neg-sidebar-top {
            padding: 28px 28px 22px;
            border-bottom: 1px solid #edf2f8;
        }
        .neg-user-card {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .neg-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: linear-gradient(135deg, #cfe0ff 0%, #7aa8ff 100%);
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .neg-user-meta {
            min-width: 0;
            flex: 1;
        }
        .neg-user-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .neg-user-name,
        .neg-thread-name {
            font-size: 24px;
            line-height: 1.1;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
        }
        .neg-user-role,
        .neg-thread-subtitle,
        .neg-user-search-icon,
        .neg-empty-copy,
        .neg-item-preview,
        .neg-item-time,
        .neg-thread-reference,
        .neg-profile-copy,
        .neg-attachment-meta {
            color: #7c8aa5;
        }
        .neg-user-role {
            font-size: 13px;
            margin-top: 4px;
        }
        .neg-badge-dot {
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: #3b82f6;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }
        .neg-search {
            position: relative;
        }
        .neg-search-input {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 999px;
            background: #f3f7fd;
            padding: 0 18px 0 48px;
            font-size: 14px;
            color: #223548;
            outline: none;
        }
        .neg-search-icon {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            font-size: 18px;
            color: #a7b3c7;
            pointer-events: none;
        }
        .neg-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0 16px;
        }
        .neg-list-item {
            display: block;
            padding: 18px 28px;
            color: inherit;
            text-decoration: none;
            border-top: 1px solid #f1f5fb;
            transition: background-color .18s ease, transform .18s ease;
        }
        .neg-list-item:hover,
        .neg-list-item.is-active {
            background: #edf4ff;
            text-decoration: none;
        }
        .neg-list-item.is-active {
            box-shadow: inset 3px 0 0 #3b82f6;
        }
        .neg-item-top,
        .neg-item-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .neg-item-title {
            font-size: 16px;
            font-weight: 700;
            color: #2563eb;
            margin: 0 0 4px;
        }
        .neg-item-subtitle {
            font-size: 13px;
            color: #1f2937;
        }
        .neg-item-preview {
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.5;
        }
        .neg-item-time {
            font-size: 12px;
            white-space: nowrap;
        }
        .neg-status-pill,
        .neg-header-chip,
        .neg-profile-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #e9f1ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }
        .neg-main {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 246px);
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 24%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }
        .neg-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 34px;
            border-bottom: 1px solid #edf2f8;
        }
        .neg-thread-trigger {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            border: 0;
            background: transparent;
            padding: 0;
            text-align: left;
            cursor: pointer;
        }
        .neg-thread-trigger:focus {
            outline: none;
        }
        .neg-thread-name {
            font-size: 22px;
        }
        .neg-thread-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #39d353;
            box-shadow: 0 0 0 4px rgba(57, 211, 83, 0.16);
        }
        .neg-thread-subtitle {
            font-size: 14px;
            margin-top: 5px;
        }
        .neg-thread-reference {
            margin-top: 6px;
            font-size: 12px;
        }
        .neg-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .neg-icon-button {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 50%;
            background: #f3f7fd;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .neg-thread-stream {
            flex: 1;
            overflow-y: auto;
            padding: 28px 34px 20px;
            min-height: 0;
            height: calc(100vh - 430px);
        }
        .neg-thread-date {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #c0cad9;
            font-size: 13px;
            margin: 12px 0 22px;
        }
        .neg-thread-date::before,
        .neg-thread-date::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e7edf6;
        }
        .neg-message {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 18px;
            max-width: 84%;
        }
        .neg-message.is-mine {
            margin-left: auto;
            flex-direction: row-reverse;
        }
        .neg-message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #dbe8ff;
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .neg-message-body {
            min-width: 0;
        }
        .neg-message-bubble {
            border-radius: 22px;
            padding: 14px 16px;
            background: #dfeafe;
            color: #111827;
            font-size: 15px;
            line-height: 1.45;
            box-shadow: 0 18px 38px -34px rgba(37, 99, 235, 0.55);
        }
        .neg-message.is-mine .neg-message-bubble {
            background: #4294ff;
            color: #ffffff;
        }
        .neg-message-meta {
            margin-top: 7px;
            font-size: 11px;
            color: #8fa0b9;
        }
        .neg-message-attachment {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
            margin-top: 10px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.72);
            padding: 10px 12px;
        }
        .neg-message.is-mine .neg-message-attachment {
            background: rgba(255, 255, 255, 0.18);
        }
        .neg-attachment-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .neg-message.is-mine .neg-attachment-icon {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }
        .neg-attachment-name {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: inherit;
            word-break: break-word;
        }
        .neg-attachment-link {
            display: inline-block;
            margin-top: 4px;
            font-size: 12px;
            color: inherit;
            text-decoration: underline;
        }
        .neg-composer {
            padding: 18px 34px 24px;
            border-top: 1px solid #edf2f8;
            background: rgba(225, 237, 255, 0.92);
        }
        .neg-composer-shell {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 8px 8px 16px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: inset 0 0 0 1px #dbe6f5;
        }
        .neg-composer-input {
            flex: 1;
            border: 0;
            outline: none;
            font-size: 15px;
            color: #223548;
            background: transparent;
        }
        .neg-composer-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .neg-attach-label,
        .neg-send-button {
            width: 48px;
            height: 48px;
            border: 0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform .18s ease, opacity .18s ease;
        }
        .neg-attach-label {
            background: #eff5ff;
            color: #3b82f6;
            margin: 0;
        }
        .neg-send-button {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 20px 34px -18px rgba(59, 130, 246, 0.8);
        }
        .neg-attach-label:hover,
        .neg-send-button:hover {
            transform: translateY(-1px);
        }
        .neg-file-input {
            display: none;
        }
        .neg-file-name {
            font-size: 12px;
            color: #6b7280;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .neg-empty {
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
        }
        .neg-empty-title {
            font-size: 20px;
            color: #223548;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .neg-empty-copy {
            font-size: 14px;
            line-height: 1.6;
        }
        .neg-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.28);
            opacity: 0;
            visibility: hidden;
            transition: opacity .22s ease, visibility .22s ease;
            z-index: 1040;
        }
        .neg-shell.is-profile-open .neg-drawer-backdrop {
            opacity: 1;
            visibility: visible;
        }
        .neg-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: min(360px, 100%);
            height: 100vh;
            background: #ffffff;
            box-shadow: -22px 0 60px -34px rgba(15, 23, 42, 0.45);
            transform: translateX(100%);
            transition: transform .24s ease;
            z-index: 1041;
            display: flex;
            flex-direction: column;
        }
        .neg-shell.is-profile-open .neg-drawer {
            transform: translateX(0);
        }
        .neg-drawer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 24px 18px;
            border-bottom: 1px solid #edf2f8;
        }
        .neg-drawer-title {
            font-size: 20px;
            font-weight: 700;
            color: #223548;
            margin: 0;
        }
        .neg-close-button {
            border: 0;
            background: #f3f7fd;
            color: #64748b;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            font-size: 18px;
        }
        .neg-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 28px 24px;
        }
        .neg-profile-hero {
            text-align: center;
            margin-bottom: 28px;
        }
        .neg-profile-avatar {
            width: 116px;
            height: 116px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d7e5ff 0%, #8eb6ff 100%);
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .neg-profile-name {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 6px;
        }
        .neg-profile-copy {
            font-size: 14px;
        }
        .neg-profile-tags {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
        }
        .neg-profile-grid {
            display: grid;
            gap: 16px;
        }
        .neg-profile-card {
            border: 1px solid #edf2f8;
            border-radius: 18px;
            padding: 16px;
            background: #fbfdff;
        }
        .neg-profile-label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 6px;
        }
        .neg-profile-value {
            font-size: 14px;
            color: #1f2937;
            line-height: 1.6;
            word-break: break-word;
        }
        .neg-profile-value a {
            color: #2563eb;
            text-decoration: none;
        }
        .neg-doc-list {
            display: grid;
            gap: 12px;
        }
        .neg-doc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid #edf2f8;
            border-radius: 16px;
            padding: 14px 16px;
            background: #ffffff;
        }
        .neg-doc-name {
            font-size: 13px;
            color: #223548;
            font-weight: 600;
            word-break: break-word;
        }
        .neg-doc-link {
            font-size: 12px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }
        @media (max-width: 1199px) {
            .neg-board {
                grid-template-columns: 300px minmax(0, 1fr);
            }
            .neg-user-name,
            .neg-thread-name {
                font-size: 20px;
            }
        }
        @media (max-width: 991px) {
            .neg-board {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .neg-sidebar,
            .neg-main {
                min-height: auto;
            }
            .neg-thread-stream {
                height: 420px;
            }
        }
        @media (max-width: 767px) {
            .neg-main-header,
            .neg-thread-stream,
            .neg-composer,
            .neg-sidebar-top,
            .neg-list-item {
                padding-left: 18px;
                padding-right: 18px;
            }
            .neg-composer-shell {
                flex-wrap: wrap;
                border-radius: 26px;
            }
            .neg-composer-tools {
                width: 100%;
                justify-content: flex-end;
            }
            .neg-thread-stream {
                height: 360px;
            }
            .neg-drawer {
                width: 100%;
            }
        }
    </style>
@endpush

<div
    id="negotiation-board"
    class="neg-shell"
    data-list-url="{{ $listUrl }}"
    data-show-url-template="{{ $showUrlTemplate }}"
    data-message-url-template="{{ $messageUrlTemplate }}"
    data-initial-id="{{ $initialNegotiationId }}"
    data-loading-list="{{ translate('Loading conversations...') }}"
    data-no-conversations="{{ translate('No conversations found yet.') }}"
    data-select-title="{{ translate('Select a conversation') }}"
    data-select-copy="{{ translate('Choose a conversation from the left to start reading or replying.') }}"
    data-no-messages="{{ translate('No messages yet.') }}"
    data-no-profile="{{ translate('No company profile data available.') }}"
    data-download-attachment="{{ translate('Open file') }}"
    data-view-document="{{ translate('View') }}"
    data-open-profile="{{ translate('Open profile') }}"
    data-message-required="{{ translate('Write a message or attach a file before sending.') }}"
    data-send-failed="{{ translate('Unable to send the message right now.') }}"
    data-load-failed="{{ translate('Unable to load conversations right now.') }}"
    data-verified-supplier="{{ translate('Verified Supplier') }}"
    data-premium-verified="{{ translate('Premium Verified') }}"
    data-featured-supplier="{{ translate('Featured Supplier') }}"
    data-close="{{ translate('Close') }}"
    data-company-profile="{{ translate('Company Profile') }}"
>
    <div class="neg-board">
        <aside class="neg-panel neg-sidebar">
            <div class="neg-sidebar-top">
                <div class="neg-user-card">
                    <div class="neg-avatar">{{ $currentUserInitial }}</div>
                    <div class="neg-user-meta">
                        <div class="neg-user-name-row">
                            <h2 class="neg-user-name">{{ $currentUserName }}</h2>
                            <span class="neg-badge-dot js-neg-total-unread d-none">0</span>
                        </div>
                        <div class="neg-user-role">{{ $currentUserRole }}</div>
                    </div>
                </div>
                <div class="neg-search">
                    <span class="neg-search-icon"><i class="las la-search"></i></span>
                    <input type="search" class="neg-search-input js-neg-search" placeholder="{{ translate('Search here...') }}">
                </div>
            </div>

            <div class="neg-list js-neg-list">
                <div class="neg-empty">
                    <div>
                        <div class="neg-empty-title">{{ translate('Loading') }}</div>
                        <div class="neg-empty-copy">{{ translate('Conversation list is loading.') }}</div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="neg-panel neg-main">
            <div class="neg-main-header">
                <button type="button" class="neg-thread-trigger js-neg-open-profile" disabled>
                    <span class="neg-avatar js-neg-thread-avatar">C</span>
                    <span>
                        <span class="d-flex align-items-center flex-wrap" style="gap:10px;">
                            <span class="neg-thread-name js-neg-thread-name">{{ translate('Select a conversation') }}</span>
                            <span class="neg-header-chip js-neg-active-unread d-none">0</span>
                            <span class="neg-thread-dot d-none js-neg-thread-dot"></span>
                        </span>
                        <span class="neg-thread-subtitle js-neg-thread-subtitle">{{ translate('The latest messages will appear here.') }}</span>
                        <span class="neg-thread-reference js-neg-thread-reference"></span>
                    </span>
                </button>

                <div class="neg-header-actions">
                    <span class="neg-status-pill js-neg-thread-status d-none"></span>
                    <button type="button" class="neg-icon-button" tabindex="-1" aria-hidden="true">
                        <i class="las la-search"></i>
                    </button>
                    <button type="button" class="neg-icon-button" tabindex="-1" aria-hidden="true">
                        <i class="lar la-heart"></i>
                    </button>
                </div>
            </div>

            <div class="neg-thread-stream js-neg-messages">
                <div class="neg-empty">
                    <div>
                        <div class="neg-empty-title">{{ translate('Select a conversation') }}</div>
                        <div class="neg-empty-copy">{{ translate('The latest messages will appear here.') }}</div>
                    </div>
                </div>
            </div>

            <form class="neg-composer js-neg-form" enctype="multipart/form-data">
                <div class="neg-composer-shell">
                    <i class="lar la-comment-dots text-primary fs-20"></i>
                    <input type="text" name="message" class="neg-composer-input js-neg-message-input" placeholder="{{ translate('Write something...') }}" autocomplete="off">
                    <span class="neg-file-name js-neg-file-name"></span>
                    <div class="neg-composer-tools">
                        <label class="neg-attach-label" title="{{ translate('Attach file') }}">
                            <i class="las la-paperclip fs-18"></i>
                            <input type="file" name="attachment" class="neg-file-input js-neg-file-input">
                        </label>
                        <button type="submit" class="neg-send-button js-neg-send-button" title="{{ translate('Send') }}">
                            <i class="las la-paper-plane fs-18"></i>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <div class="neg-drawer-backdrop js-neg-drawer-backdrop"></div>

    <aside class="neg-drawer" aria-hidden="true">
        <div class="neg-drawer-head">
            <h3 class="neg-drawer-title">{{ translate('Company Profile') }}</h3>
            <button type="button" class="neg-close-button js-neg-close-profile" aria-label="{{ translate('Close') }}">
                <i class="las la-times"></i>
            </button>
        </div>
        <div class="neg-drawer-body js-neg-profile">
            <div class="neg-empty">
                <div>
                    <div class="neg-empty-title">{{ translate('No profile selected') }}</div>
                    <div class="neg-empty-copy">{{ translate('Open a conversation and click the name above to view company details.') }}</div>
                </div>
            </div>
        </div>
    </aside>
</div>
