<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Control Center' }} — FIELDCRAFT ADMIN PRO MAX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11281d;
            --bg-input: #091810;
            --border-panel: #1b3829;
            --border-sub: #234633;
            --lime: #caff39;
            --lime-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea395;
            --danger: #ff6b4a;
            --danger-bg: #27110a;
            --danger-border: #522115;
            --warning: #fbbf24;
            --warning-bg: #261f0c;
            --warning-border: #594717;
            --info: #38bdf8;
            --info-bg: rgba(56, 189, 248, 0.12);
            --info-border: rgba(56, 189, 248, 0.3);
            --success: #caff39;
            --success-bg: rgba(202, 255, 57, 0.12);
            --success-border: rgba(202, 255, 57, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 13px/1.5 'Manrope', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }

        /* ── Sidebar ── */
        .side {
            width: 260px;
            background: #050c08;
            border-right: 1px solid var(--border-panel);
            position: fixed;
            inset: 0 auto 0 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border-panel) transparent;
        }
        .side-brand {
            padding: 20px 18px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font: 700 22px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-panel);
        }
        .brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 3px 12px 3px 12px;
            background: var(--lime);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px #050c08;
            flex-shrink: 0;
        }
        .side-pro-tag {
            font: 700 9px/1 'DM Mono', monospace;
            background: var(--lime);
            color: #07110d;
            padding: 3px 6px;
            border-radius: 3px;
            margin-left: auto;
            letter-spacing: .08em;
        }

        .side-nav {
            padding: 14px 10px 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            flex: 1;
        }
        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .nav-heading {
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 6px 10px 4px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            color: var(--text-sub);
            font-size: 12px;
            font-weight: 600;
            transition: all .15s ease;
        }
        .nav-link:hover {
            background: var(--bg-panel-sub);
            color: var(--text-main);
        }
        .nav-link.active {
            background: var(--bg-panel-sub);
            color: var(--lime);
            border-left: 3px solid var(--lime);
            border-radius: 0 6px 6px 0;
            font-weight: 700;
        }
        .nav-icon {
            width: 18px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            flex-shrink: 0;
        }
        .nav-link.active .nav-icon {
            color: var(--lime);
        }
        .nav-badge {
            margin-left: auto;
            background: var(--lime);
            color: #07110d;
            font: 700 10px/1 'DM Mono', monospace;
            padding: 3px 6px;
            border-radius: 10px;
        }
        .nav-badge.warning {
            background: var(--warning);
            color: #07110d;
        }
        .nav-badge.info {
            background: var(--info);
            color: #07110d;
        }

        .side-bottom {
            padding: 14px;
            border-top: 1px solid var(--border-panel);
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #050c08;
        }
        .btn-storefront {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            color: var(--text-sub);
            font: 700 11px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            transition: all .15s ease;
        }
        .btn-storefront:hover {
            border-color: var(--lime);
            color: var(--lime);
        }
        .btn-logout {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px;
            background: transparent;
            border: 1px solid var(--danger-border);
            color: var(--danger);
            border-radius: 6px;
            font: 700 11px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-logout:hover {
            background: var(--danger-bg);
        }

        /* ── Main Layout ── */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
            overflow-x: hidden;
        }
        .admin-topbar {
            height: 64px;
            background: #050c08;
            border-bottom: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            max-width: 620px;
        }

        /* Command Palette trigger box in topbar */
        .cmd-search-box {
            width: 100%;
            display: flex;
            align-items: center;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all .15s ease;
            gap: 10px;
            color: var(--text-muted);
        }
        .cmd-search-box:hover {
            border-color: var(--lime);
            color: var(--text-main);
        }
        .cmd-search-box span.text {
            font-size: 12px;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cmd-badge {
            font: 700 10px/1 'DM Mono', monospace;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            color: var(--lime);
            padding: 3px 6px;
            border-radius: 4px;
            letter-spacing: .06em;
            flex-shrink: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
        }
        .live-clock {
            font: 700 11px/1 'DM Mono', monospace;
            color: var(--lime);
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            padding: 6px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .live-dot {
            width: 6px;
            height: 6px;
            background: var(--lime);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--lime);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        /* Notification trigger & dropdown */
        .noti-btn {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-main);
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all .15s ease;
            font-size: 15px;
        }
        .noti-btn:hover {
            border-color: var(--lime);
            color: var(--lime);
        }
        .noti-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: #ffffff;
            font: 700 9px/1 'DM Mono', monospace;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            border: 2px solid #050c08;
        }

        .noti-dropdown {
            display: none;
            position: absolute;
            top: 48px;
            right: 0;
            width: 380px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            box-shadow: 0 12px 36px rgba(0,0,0,0.6);
            z-index: 200;
            overflow: hidden;
            animation: dropFade .15s ease;
        }
        .noti-dropdown.open { display: block; }
        @keyframes dropFade { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

        .noti-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-panel-sub);
        }
        .noti-head-title {
            font: 700 12px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .noti-mark-read {
            font: 600 11px/1 'Manrope', sans-serif;
            color: var(--lime);
            background: transparent;
            border: 0;
            cursor: pointer;
        }
        .noti-mark-read:hover { text-decoration: underline; }
        .noti-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-panel);
            background: #09150f;
            padding: 4px 8px 0;
            gap: 4px;
        }
        .noti-tab-btn {
            background: transparent;
            border: 0;
            color: var(--text-muted);
            padding: 6px 10px;
            font: 700 10px/1 'DM Mono', monospace;
            text-transform: uppercase;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .noti-tab-btn.active {
            color: var(--lime);
            border-bottom-color: var(--lime);
        }
        .noti-list {
            max-height: 360px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .noti-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-panel);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: background .15s ease;
            cursor: pointer;
        }
        .noti-item:hover { background: var(--bg-panel-sub); }
        .noti-item.unread { background: rgba(202, 255, 57, 0.03); }
        .noti-item-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .noti-item-title {
            font-weight: 700;
            font-size: 12px;
            color: var(--text-main);
            margin-bottom: 2px;
        }
        .noti-item-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.35;
        }
        .noti-item-time {
            font: 700 9px/1 'DM Mono', monospace;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .quick-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-quick {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-sub);
            padding: 6px 12px;
            border-radius: 6px;
            font: 700 11px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-quick:hover {
            border-color: var(--lime);
            color: var(--lime);
        }
        .btn-quick.lime {
            background: var(--lime);
            color: #07110d;
            border-color: var(--lime);
        }
        .btn-quick.lime:hover {
            background: var(--lime-hover);
        }

        .admin-content {
            padding: 28px 32px 60px;
            flex: 1;
            max-width: 1700px;
            width: 100%;
        }

        /* ── Common Components ── */
        .crumb {
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .12em;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .topline h1 {
            font: 700 32px/1.1 'Oswald', sans-serif;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: var(--text-main);
            margin: 0;
        }

        .panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: border-color .15s ease;
        }
        .panel:hover {
            border-color: var(--border-sub);
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .toolbar-title {
            font: 700 16px/1 'Oswald', sans-serif;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notice {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--lime);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .errors {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            border: 0;
            border-radius: 6px;
            padding: 9px 16px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            color: var(--text-main);
            font: 700 11px/1 'Oswald', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all .15s ease;
        }
        .btn:hover {
            background: #173726;
            border-color: var(--border-sub);
        }
        .btn.lime {
            background: var(--lime);
            color: #07110d;
            border-color: var(--lime);
        }
        .btn.lime:hover {
            background: var(--lime-hover);
            transform: translateY(-1px);
        }
        .btn.danger {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger);
        }
        .btn.danger:hover {
            background: #3c140d;
        }
        .btn.small {
            padding: 6px 10px;
            font-size: 10px;
        }

        /* ── 2-Column Desktop Grid ── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: flex-start;
        }
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Reusable Table Responsive (No clipping, horizontal scroll) ── */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--border-panel) transparent;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        .table th {
            text-align: left;
            color: var(--text-muted);
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .08em;
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-panel);
            white-space: nowrap;
            text-transform: uppercase;
        }
        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-panel);
            font-size: 12px;
            vertical-align: middle;
            color: var(--text-sub);
        }
        .table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .table tr:last-child td {
            border-bottom: 0;
        }

        /* ── Timeline Rail & Dots ── */
        .timeline {
            position: relative;
            padding-left: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 6px;
            bottom: 6px;
            left: 5px;
            width: 2px;
            background: var(--border-panel);
        }
        .timeline article {
            position: relative;
        }
        .timeline article::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--lime);
            border: 2px solid var(--bg-panel);
            box-shadow: 0 0 6px rgba(202, 255, 57, 0.4);
        }
        .timeline article:not(:first-child)::before {
            background: var(--border-sub);
            box-shadow: none;
        }

        /* ── Status Badges & Specialized Variant Badges ── */
        .status {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font: 700 10px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .status.completed, .status.paid, .status.approved, .status.delivered {
            background: var(--success-bg);
            color: var(--lime);
            border: 1px solid var(--success-border);
        }
        .status.shipping, .status.confirmed, .status.packing, .status.preparing, .status.delivering, .status.ready_to_pick, .status.picked, .status.transporting, .status.storing, .status.sorting {
            background: var(--info-bg);
            color: var(--info);
            border: 1px solid var(--info-border);
        }
        .status.pending, .status.pending_payment, .status.unpaid, .status.order_created, .status.created, .status.waiting_to_return {
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid var(--warning-border);
        }
        .status.cancelled, .status.failed, .status.rejected, .status.delivery_fail, .status.return_fail, .status.refunded {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        .status.muted {
            background: var(--bg-panel-sub);
            color: var(--text-muted);
            border: 1px solid var(--border-panel);
        }

        /* Football Stud Badges */
        .stud-badge {
            display: inline-flex;
            align-items: center;
            font: 700 9px/1 'DM Mono', monospace;
            padding: 3px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .stud-badge.tf { background: rgba(202, 255, 57, 0.15); color: var(--lime); border: 1px solid rgba(202, 255, 57, 0.3); }
        .stud-badge.fg { background: rgba(56, 189, 248, 0.15); color: var(--info); border: 1px solid rgba(56, 189, 248, 0.3); }
        .stud-badge.ag { background: rgba(251, 191, 36, 0.15); color: var(--warning); border: 1px solid rgba(251, 191, 36, 0.3); }
        .stud-badge.ic { background: rgba(255, 255, 255, 0.1); color: var(--text-main); border: 1px solid var(--border-panel); }

        /* VIP Tier Badges */
        .vip-tier-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font: 700 10px/1 'DM Mono', monospace;
            padding: 4px 8px;
            border-radius: 4px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .vip-tier-badge.rookie { background: var(--bg-panel-sub); color: var(--text-muted); border: 1px solid var(--border-panel); }
        .vip-tier-badge.player { background: rgba(56, 189, 248, 0.15); color: var(--info); border: 1px solid rgba(56, 189, 248, 0.3); }
        .vip-tier-badge.pro { background: rgba(251, 191, 36, 0.15); color: var(--warning); border: 1px solid rgba(251, 191, 36, 0.3); }
        .vip-tier-badge.elite {
            background: linear-gradient(135deg, rgba(202, 255, 57, 0.2), rgba(56, 189, 248, 0.2));
            color: var(--lime);
            border: 1px solid var(--lime);
            box-shadow: 0 0 10px rgba(202, 255, 57, 0.3);
        }

        .muted { color: var(--text-muted); }
        .lime-text { color: var(--lime); }
        .lime-link { color: var(--lime); font-weight: 700; }
        .lime-link:hover { text-decoration: underline; }
        .mono { font-family: 'DM Mono', monospace; }

        .thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 6px;
        }
        .item-image {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
        }
        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-inline {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            align-items: center;
        }
        .form-inline input, .form-inline select {
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            background: var(--bg-input);
            color: var(--text-main);
            padding: 8px 12px;
            font-size: 12px;
            outline: none;
        }
        .form-inline input:focus, .form-inline select:focus {
            border-color: var(--lime);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field label {
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            color: var(--text-sub);
        }
        .field input, .field textarea, .field select {
            border: 1px solid var(--border-panel);
            border-radius: 6px;
            background: var(--bg-input);
            color: var(--text-main);
            padding: 10px;
            font-size: 13px;
            outline: none;
        }
        .field input:focus, .field textarea:focus, .field select:focus {
            border-color: var(--lime);
        }
        .full { grid-column: 1 / -1; }

        /* Mascot protection */
        .mascot-avatar, .brand-mascot, .mascot-float {
            pointer-events: none;
            user-select: none;
            max-width: 100%;
        }

        /* ── Command Palette (Ctrl+K) Styles ── */
        .cmd-palette-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(4, 9, 6, 0.78);
            backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: flex-start;
            justify-content: center;
            padding-top: 80px;
        }
        .cmd-palette-overlay.open {
            display: flex;
            animation: fadeIn .15s ease;
        }
        .cmd-palette-box {
            width: 100%;
            max-width: 680px;
            background: #091710;
            border: 1px solid var(--lime);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8), 0 0 25px rgba(202, 255, 57, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .cmd-search-input-wrap {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-panel);
            background: #06110b;
            gap: 12px;
        }
        .cmd-search-input-wrap input {
            flex: 1;
            background: transparent;
            border: 0;
            color: var(--text-main);
            font: 500 15px/1 'Manrope', sans-serif;
            outline: none;
        }
        .cmd-search-input-wrap .esc-hint {
            font: 700 10px/1 'DM Mono', monospace;
            background: var(--bg-panel-sub);
            color: var(--text-muted);
            border: 1px solid var(--border-panel);
            padding: 4px 8px;
            border-radius: 4px;
        }
        .cmd-filter-chips {
            display: flex;
            gap: 6px;
            padding: 10px 18px;
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border-panel);
            overflow-x: auto;
        }
        .cmd-chip {
            background: transparent;
            border: 1px solid var(--border-panel);
            color: var(--text-muted);
            font: 700 10px/1 'DM Mono', monospace;
            text-transform: uppercase;
            padding: 5px 10px;
            border-radius: 20px;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .cmd-chip:hover { color: var(--text-main); border-color: var(--lime); }
        .cmd-chip.active { background: var(--lime); color: #07110d; border-color: var(--lime); }

        .cmd-results-list {
            max-height: 420px;
            overflow-y: auto;
            scrollbar-width: thin;
            padding: 8px 0;
        }
        .cmd-group-title {
            font: 700 10px/1 'DM Mono', monospace;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 10px 18px 4px;
        }
        .cmd-item {
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            transition: background .12s ease;
        }
        .cmd-item:hover, .cmd-item.selected {
            background: var(--bg-panel-sub);
        }
        .cmd-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cmd-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .cmd-item.selected .cmd-item-icon {
            border-color: var(--lime);
            color: var(--lime);
        }
        .cmd-item-label {
            font-weight: 700;
            font-size: 13px;
            color: var(--text-main);
        }
        .cmd-item-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .cmd-footer {
            padding: 10px 18px;
            background: #06110b;
            border-top: 1px solid var(--border-panel);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font: 600 11px/1 'DM Mono', monospace;
            color: var(--text-muted);
        }

        /* ── Boot Passport Card Component & Modal ── */
        .passport-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(4, 9, 6, 0.85);
            backdrop-filter: blur(6px);
            z-index: 1050;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .passport-modal-overlay.open { display: flex; animation: fadeIn .15s ease; }
        .boot-passport-card {
            width: 100%;
            max-width: 480px;
            background: linear-gradient(145deg, #091911, #0f271c);
            border: 2px solid var(--lime);
            border-radius: 16px;
            padding: 26px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8), 0 0 30px rgba(202, 255, 57, 0.25);
            overflow: hidden;
        }
        .passport-watermark {
            position: absolute;
            right: -20px;
            bottom: -30px;
            font: 900 120px/1 'Oswald', sans-serif;
            color: rgba(202, 255, 57, 0.04);
            pointer-events: none;
            user-select: none;
        }
        .passport-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--border-panel);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .passport-tag {
            font: 700 9px/1 'DM Mono', monospace;
            background: var(--lime);
            color: #07110d;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .passport-code {
            font: 700 16px/1 'DM Mono', monospace;
            color: var(--lime);
            letter-spacing: .06em;
            margin-top: 6px;
        }
        .passport-spec-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }
        .passport-spec-item {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border-panel);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .passport-spec-lbl {
            font: 700 9px/1 'DM Mono', monospace;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .passport-spec-val {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Responsive Breakpoints */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: 0;
            color: var(--text-main);
            font-size: 20px;
            cursor: pointer;
        }
        .side-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(2px);
            z-index: 99;
        }

        @media (max-width: 1024px) {
            .side {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }
            .side.open {
                transform: translateX(0);
            }
            .side-overlay.open {
                display: block;
            }
            .main-wrapper {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: block;
            }
            .admin-topbar {
                padding: 0 16px;
            }
            .admin-content {
                padding: 20px 16px 40px;
            }
            .noti-dropdown {
                width: 320px;
                right: -40px;
            }
        }
    </style>
</head>
<body>
    {{-- Mobile Overlay --}}
    <div class="side-overlay" id="sideOverlay" onclick="toggleSidebar()"></div>

    {{-- Left Navigation Sidebar --}}
    <aside class="side" id="adminSidebar">
        <a class="side-brand" href="{{ route('admin.dashboard') }}">
            <i class="brand-mark"></i>
            <span>FIELDCRAFT</span>
            <span class="side-pro-tag">PRO MAX</span>
        </a>

        @php
            $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
            $pendingReviewsCount = \App\Models\Review::where('status', 'pending')->count();
            $lowStockCount = \App\Models\ProductVariant::where('stock', '<=', 5)->count();
        @endphp

        <nav class="side-nav">
            {{-- Group 1: TỔNG QUAN --}}
            <div class="nav-group">
                <div class="nav-heading">TỔNG QUAN</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && !request()->has('tab') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">▦</span>
                    <span>Bảng điều khiển</span>
                </a>
            </div>

            {{-- Group 2: BÁN HÀNG --}}
            <div class="nav-group">
                <div class="nav-heading">BÁN HÀNG</div>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') && !request()->has('status') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <span class="nav-icon">□</span>
                    <span>Đơn hàng</span>
                    @if($pendingOrdersCount > 0)
                        <span class="nav-badge">{{ $pendingOrdersCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'kanban' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'kanban']) }}#kanban">
                    <span class="nav-icon">▥</span>
                    <span>Kanban</span>
                </a>
                <a class="nav-link {{ (request()->routeIs('admin.orders.*') && request('status') === 'shipping') || (request()->routeIs('admin.dashboard') && request('tab') === 'shipping') ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'shipping']) }}#shipping">
                    <span class="nav-icon">🚚</span>
                    <span>Vận chuyển</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && in_array(request('tab'), ['finance', 'revenue']) ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'finance']) }}#finance">
                    <span class="nav-icon">💳</span>
                    <span>Thanh toán</span>
                </a>
            </div>

            {{-- Group 3: SẢN PHẨM --}}
            <div class="nav-group">
                <div class="nav-heading">SẢN PHẨM</div>
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <span class="nav-icon">◇</span>
                    <span>Sản phẩm</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'inventory' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'inventory']) }}#inventory">
                    <span class="nav-icon">⊞</span>
                    <span>Kho & Variant</span>
                    @if($lowStockCount > 0)
                        <span class="nav-badge warning">{{ $lowStockCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'restock' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'restock']) }}#restock">
                    <span class="nav-icon">⚡</span>
                    <span>Nhập hàng</span>
                </a>
            </div>

            {{-- Group 4: KHÁCH HÀNG --}}
            <div class="nav-group">
                <div class="nav-heading">KHÁCH HÀNG</div>
                <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                    <span class="nav-icon">👤</span>
                    <span>Khách hàng</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'teams' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'teams']) }}#teams">
                    <span class="nav-icon">🛡</span>
                    <span>Đội bóng</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'loyalty' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'loyalty']) }}#loyalty">
                    <span class="nav-icon">👑</span>
                    <span>Hạng thành viên</span>
                </a>
            </div>

            {{-- Group 5: CÁ NHÂN HÓA --}}
            <div class="nav-group">
                <div class="nav-heading">CÁ NHÂN HÓA</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'customization' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'customization']) }}#customization">
                    <span class="nav-icon">🎽</span>
                    <span>In tên & số</span>
                </a>
            </div>

            {{-- Group 6: MARKETING --}}
            <div class="nav-group">
                <div class="nav-heading">MARKETING</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'trends' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'trends']) }}#trends">
                    <span class="nav-icon">🔥</span>
                    <span>Trend Radar</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'matchday' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'matchday']) }}#matchday">
                    <span class="nav-icon">⚽</span>
                    <span>Matchday</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'cross-sell' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'cross-sell']) }}#cross-sell">
                    <span class="nav-icon">🛒</span>
                    <span>Cross-selling</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'abandoned-carts' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'abandoned-carts']) }}#abandoned-carts">
                    <span class="nav-icon">⏳</span>
                    <span>Giỏ hàng bị bỏ quên</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                    <span class="nav-icon">%</span>
                    <span>Mã giảm giá</span>
                </a>
            </div>

            {{-- Group 7: CỘNG ĐỒNG --}}
            <div class="nav-group">
                <div class="nav-heading">CỘNG ĐỒNG</div>
                <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                    <span class="nav-icon">★</span>
                    <span>Đánh giá</span>
                    @if($pendingReviewsCount > 0)
                        <span class="nav-badge warning">{{ $pendingReviewsCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'second-hand' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'second-hand']) }}#second-hand">
                    <span class="nav-icon">♻</span>
                    <span>Second-hand</span>
                </a>
            </div>

            {{-- Group 8: HỆ THỐNG --}}
            <div class="nav-group">
                <div class="nav-heading">HỆ THỐNG</div>
                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}" href="{{ route('admin.accounts.index') }}">
                    <span class="nav-icon">◎</span>
                    <span>Tài khoản</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') && request('tab') === 'activity-log' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'activity-log']) }}#activity-log">
                    <span class="nav-icon">📋</span>
                    <span>Nhật ký hoạt động</span>
                </a>
            </div>
        </nav>

        {{-- Bottom Action Buttons --}}
        <div class="side-bottom">
            <a class="btn-storefront" href="{{ route('store.home') }}" target="_blank">
                <span>↗</span> Xem cửa hàng
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-logout" type="submit">
                    <span>⏻</span> ĐĂNG XUẤT
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">
        {{-- Sticky Top Header --}}
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button" onclick="toggleSidebar()">☰</button>
                <div class="cmd-search-box" onclick="openCommandPalette()">
                    <span>🔍</span>
                    <span class="text">Tìm kiếm nhanh đơn hàng, khách hàng, sản phẩm, SKU...</span>
                    <span class="cmd-badge">Ctrl + K</span>
                </div>
            </div>

            <div class="topbar-right">
                {{-- Notification Center Button --}}
                <div class="noti-btn" id="notiTrigger" onclick="toggleNotiDropdown(event)">
                    <span>🔔</span>
                    <span class="noti-badge" id="notiUnreadBadge">4</span>
                </div>

                {{-- Notification Dropdown --}}
                <div class="noti-dropdown" id="notiDropdown" onclick="event.stopPropagation()">
                    <div class="noti-head">
                        <span class="noti-head-title">🔔 Thông báo vận hành</span>
                        <button type="button" class="noti-mark-read" onclick="markAllNotificationsRead()">Đánh dấu đã đọc</button>
                    </div>
                    <div class="noti-tabs">
                        <button type="button" class="noti-tab-btn active" data-filter="all">Tất cả</button>
                        <button type="button" class="noti-tab-btn" data-filter="order">Đơn hàng</button>
                        <button type="button" class="noti-tab-btn" data-filter="shipping">Vận chuyển</button>
                        <button type="button" class="noti-tab-btn" data-filter="stock">Kho & Review</button>
                    </div>
                    <div class="noti-list" id="notiList">
                        <div class="noti-item unread" data-type="order" onclick="location.href='{{ route('admin.orders.index') }}'">
                            <div class="noti-item-icon" style="color:var(--lime)">📦</div>
                            <div>
                                <div class="noti-item-title">Đơn hàng mới #ORD-8821</div>
                                <div class="noti-item-desc">Khách đặt 1x Nike Zoom Mercurial Vapor 16 (COD) cần xác nhận.</div>
                                <div class="noti-item-time">5 phút trước · Bán hàng</div>
                            </div>
                        </div>
                        <div class="noti-item unread" data-type="shipping" onclick="location.href='{{ route('admin.dashboard', ['tab' => 'shipping']) }}#shipping'">
                            <div class="noti-item-icon" style="color:var(--info)">🚚</div>
                            <div>
                                <div class="noti-item-title">GHN đối soát vận đơn #GHN9821</div>
                                <div class="noti-item-desc">Kiện hàng đã giao thành công tại Cầu Giấy, Hà Nội.</div>
                                <div class="noti-item-time">20 phút trước · GHN Express</div>
                            </div>
                        </div>
                        <div class="noti-item unread" data-type="stock" onclick="location.href='{{ route('admin.dashboard', ['tab' => 'inventory']) }}#inventory'">
                            <div class="noti-item-icon" style="color:var(--warning)">👟</div>
                            <div>
                                <div class="noti-item-title">Cảnh báo tồn kho Size 40/41</div>
                                <div class="noti-item-desc">Predator 24 Elite TF màu Trắng chỉ còn 2 đôi trong kho.</div>
                                <div class="noti-item-time">1 giờ trước · Kho & Variant</div>
                            </div>
                        </div>
                        <div class="noti-item unread" data-type="stock" onclick="location.href='{{ route('admin.reviews.index') }}'">
                            <div class="noti-item-icon" style="color:var(--lime)">★</div>
                            <div>
                                <div class="noti-item-title">Đánh giá 5 sao mới</div>
                                <div class="noti-item-desc">Khách hàng nhận xét: "Giày rất êm và bám sân cỏ nhân tạo."</div>
                                <div class="noti-item-time">2 giờ trước · Customer Voice</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Clock --}}
                <div class="live-clock">
                    <div class="live-dot"></div>
                    <span id="liveClockDisplay">{{ now()->format('d/m/Y H:i:s') }}</span>
                </div>

                {{-- Quick Actions --}}
                <div class="quick-actions">
                    <a class="btn-quick lime" href="{{ route('admin.products.create') }}">+ Sản phẩm</a>
                    <a class="btn-quick" href="{{ route('admin.coupons.create') }}">+ Mã giảm</a>
                </div>
            </div>
        </header>

        {{-- Main Page Content --}}
        <main class="admin-content">
            @if(session('success'))
                <div class="notice">✓ {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="errors">✕ {{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- ── Global Command Palette Modal (Ctrl + K) ── --}}
    <div class="cmd-palette-overlay" id="cmdPaletteOverlay" onclick="closeCommandPalette(event)">
        <div class="cmd-palette-box" onclick="event.stopPropagation()">
            <div class="cmd-search-input-wrap">
                <span style="font-size:16px;color:var(--lime)">🔍</span>
                <input type="text" id="paletteInput" placeholder="Tìm kiếm nhanh: đơn hàng #, tên khách, giày, đội bóng, GHN, mã passport..." autocomplete="off">
                <span class="esc-hint">ESC</span>
            </div>

            <div class="cmd-filter-chips">
                <button type="button" class="cmd-chip active" data-filter="all">Tất cả</button>
                <button type="button" class="cmd-chip" data-filter="orders">Đơn hàng</button>
                <button type="button" class="cmd-chip" data-filter="customers">Khách hàng</button>
                <button type="button" class="cmd-chip" data-filter="products">Sản phẩm</button>
                <button type="button" class="cmd-chip" data-filter="teams">Đội bóng</button>
                <button type="button" class="cmd-chip" data-filter="waybills">Vận đơn GHN</button>
                <button type="button" class="cmd-chip" data-filter="passports">Boot Passport</button>
            </div>

            <div class="cmd-results-list" id="cmdResultsList">
                {{-- Dynamic list populated via JS --}}
            </div>

            <div class="cmd-footer">
                <span>Dùng phím <b style="color:var(--lime)">↑</b> <b style="color:var(--lime)">↓</b> để di chuyển, <b style="color:var(--lime)">Enter</b> để chọn</span>
                <span>FIELDCRAFT CONTROL PALETTE</span>
            </div>
        </div>
    </div>

    {{-- ── Global Boot Passport Preview Modal ── --}}
    <div class="passport-modal-overlay" id="bootPassportModal" onclick="closeBootPassport(event)">
        <div class="boot-passport-card" onclick="event.stopPropagation()">
            <div class="passport-watermark">FIELDCRAFT</div>
            <div class="passport-header">
                <div>
                    <span class="passport-tag">CERTIFIED BOOT PASSPORT</span>
                    <div class="passport-code" id="passModalCode">FC-PASS-8829-VN</div>
                </div>
                <button type="button" style="background:transparent;border:0;color:var(--text-muted);font-size:20px;cursor:pointer" onclick="closeBootPassport()">✕</button>
            </div>

            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px">
                <div style="width:56px;height:56px;border-radius:10px;background:var(--bg-panel-sub);border:1px solid var(--lime);display:flex;align-items:center;justify-content:center;font-size:28px">👟</div>
                <div>
                    <h3 style="font:700 18px/1.1 'Oswald',sans-serif;color:var(--text-main)" id="passModalName">Nike Zoom Mercurial Vapor 16 Pro TF</h3>
                    <div class="muted" style="font-size:12px;margin-top:2px" id="passModalColor">Trắng / Xanh Neon</div>
                </div>
            </div>

            <div class="passport-spec-grid">
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Cỡ giày / Form chân</div>
                    <div class="passport-spec-val" id="passModalSize">Size 41 · Form Bè</div>
                </div>
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Mặt đế / Loại đinh</div>
                    <div class="passport-spec-val" id="passModalStud"><span class="stud-badge tf">TF Cỏ nhân tạo</span></div>
                </div>
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Đơn hàng & Ngày mua</div>
                    <div class="passport-spec-val mono" id="passModalOrder">#ORD-9081 · 12/09/2026</div>
                </div>
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Thời hạn bảo hành</div>
                    <div class="passport-spec-val" style="color:var(--lime)" id="passModalWarranty">Còn 168 ngày (180 ngày)</div>
                </div>
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Đánh giá xác thực</div>
                    <div class="passport-spec-val" style="color:var(--warning)" id="passModalReview">★ 5.0 (Đã kiểm duyệt)</div>
                </div>
                <div class="passport-spec-item">
                    <div class="passport-spec-lbl">Quyền lợi Second-hand</div>
                    <div class="passport-spec-val" style="color:var(--info)" id="passModalSecondHand">Đủ điều kiện ký gửi</div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn small" onclick="closeBootPassport()">Đóng</button>
                <button type="button" class="btn small lime" onclick="alert('✓ Mã Boot Passport đã được đối soát chính hãng trên hệ thống FIELDCRAFT!')">✓ Xác thực chuẩn</button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sideOverlay').classList.toggle('open');
        }

        // Live clock
        setInterval(() => {
            const clockEl = document.getElementById('liveClockDisplay');
            if (clockEl) {
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                clockEl.innerText = `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            }
        }, 1000);

        // Notification Center Dropdown
        function toggleNotiDropdown(e) {
            e.stopPropagation();
            const dd = document.getElementById('notiDropdown');
            if (dd) dd.classList.toggle('open');
        }
        document.addEventListener('click', () => {
            const dd = document.getElementById('notiDropdown');
            if (dd && dd.classList.contains('open')) dd.classList.remove('open');
        });

        function markAllNotificationsRead() {
            document.querySelectorAll('#notiList .noti-item').forEach(item => item.classList.remove('unread'));
            const badge = document.getElementById('notiUnreadBadge');
            if (badge) {
                badge.innerText = '0';
                badge.style.display = 'none';
            }
        }

        // Notification filter tabs
        document.querySelectorAll('.noti-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.noti-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const filter = btn.dataset.filter;
                document.querySelectorAll('#notiList .noti-item').forEach(item => {
                    if (filter === 'all' || item.dataset.type === filter) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // ── Command Palette (Ctrl + K) Implementation ──
        const cmdPaletteOverlay = document.getElementById('cmdPaletteOverlay');
        const paletteInput = document.getElementById('paletteInput');
        const cmdResultsList = document.getElementById('cmdResultsList');

        const paletteDatabase = [
            // Orders
            { type: 'orders', title: 'Đơn hàng #ORD-8821', sub: 'Nguyễn Văn Hải · 1.450.000 ₫ · Đang xử lý', icon: '📦', url: '{{ route("admin.orders.index") }}' },
            { type: 'orders', title: 'Đơn hàng #ORD-7729', sub: 'Trần Minh Đức · 2.890.000 ₫ · Đang giao GHN', icon: '📦', url: '{{ route("admin.orders.index") }}' },
            { type: 'orders', title: 'Đơn hàng #ORD-6610', sub: 'Lê Hoàng Nam · 950.000 ₫ · Hoàn tất', icon: '📦', url: '{{ route("admin.orders.index") }}' },
            // Customers
            { type: 'customers', title: 'Khách hàng: Nguyễn Văn Hải', sub: 'hai.nguyen@example.com · SĐT: 0988123456 · 4 đơn hàng', icon: '👤', url: '{{ route("admin.customers.index") }}' },
            { type: 'customers', title: 'Khách hàng: Trần Minh Đức', sub: 'duc.tran@example.com · SĐT: 0912345678 · VIP PRO', icon: '👤', url: '{{ route("admin.customers.index") }}' },
            // Products
            { type: 'products', title: 'Nike Zoom Mercurial Vapor 16 Pro TF', sub: 'Giày đinh TF sân cỏ nhân tạo · 1.850.000 ₫ · 24 đôi tồn', icon: '👟', url: '{{ route("admin.products.index") }}' },
            { type: 'products', title: 'adidas Predator 24 Elite TF', sub: 'Giày đinh TF kiểm soát bóng · 2.100.000 ₫ · 18 đôi tồn', icon: '👟', url: '{{ route("admin.products.index") }}' },
            { type: 'products', title: 'Puma Future 7 Ultimate FG/AG', sub: 'Giày đinh FG/AG cỏ tự nhiên · 2.400.000 ₫ · 12 đôi tồn', icon: '👟', url: '{{ route("admin.products.index") }}' },
            // Teams
            { type: 'teams', title: 'Đội bóng: FC Saigon United', sub: 'Đội trưởng: Lê Minh · 16 thành viên · Đã tạo hồ sơ in ấn', icon: '🛡', url: '{{ route("admin.dashboard", ["tab" => "teams"]) }}#teams' },
            { type: 'teams', title: 'Đội bóng: Hà Nội Phoenix', sub: 'Đội trưởng: Vũ Hoàng · 14 thành viên · Đang đặt lại áo', icon: '🛡', url: '{{ route("admin.dashboard", ["tab" => "teams"]) }}#teams' },
            // Waybills
            { type: 'waybills', title: 'Vận đơn GHN: #GHN9821001', sub: 'Giao Hàng Nhanh · Đang giao hàng tại Hà Nội', icon: '🚚', url: '{{ route("admin.dashboard", ["tab" => "shipping"]) }}#shipping' },
            { type: 'waybills', title: 'Vận đơn GHN: #GHN7729045', sub: 'Giao Hàng Nhanh · Đã hoàn tất giao thành công', icon: '🚚', url: '{{ route("admin.dashboard", ["tab" => "shipping"]) }}#shipping' },
            // Boot Passports
            { type: 'passports', title: 'Boot Passport: FC-PASS-8829-VN', sub: 'Nike Vapor 16 · Size 41 · Bảo hành còn 168 ngày', icon: '🎫', action: 'showPassport' },
            { type: 'passports', title: 'Boot Passport: FC-PASS-4412-VN', sub: 'adidas Predator 24 · Size 40 · Đã xác thực Second-hand', icon: '🎫', action: 'showPassport' },
        ];

        let currentPaletteFilter = 'all';
        let selectedPaletteIndex = 0;
        let visibleItems = [];

        function renderPaletteResults(query = '') {
            const q = query.trim().toLowerCase();
            visibleItems = paletteDatabase.filter(item => {
                const matchFilter = (currentPaletteFilter === 'all' || item.type === currentPaletteFilter);
                const matchQuery = !q || item.title.toLowerCase().includes(q) || item.sub.toLowerCase().includes(q);
                return matchFilter && matchQuery;
            });

            if (visibleItems.length === 0) {
                cmdResultsList.innerHTML = `
                    <div style="padding:32px 18px;text-align:center;color:var(--text-muted);font-size:13px">
                        Không tìm thấy kết quả phù hợp với từ khóa "${query}".
                    </div>
                `;
                return;
            }

            let html = '';
            visibleItems.forEach((item, idx) => {
                const isSel = idx === selectedPaletteIndex;
                html += `
                    <div class="cmd-item ${isSel ? 'selected' : ''}" data-idx="${idx}" onclick="handlePaletteSelect(${idx})">
                        <div class="cmd-item-left">
                            <div class="cmd-item-icon">${item.icon}</div>
                            <div>
                                <div class="cmd-item-label">${item.title}</div>
                                <div class="cmd-item-sub">${item.sub}</div>
                            </div>
                        </div>
                        <span style="color:var(--lime);font-size:11px">Chọn ↵</span>
                    </div>
                `;
            });
            cmdResultsList.innerHTML = html;
        }

        function openCommandPalette() {
            cmdPaletteOverlay.classList.add('open');
            paletteInput.value = '';
            selectedPaletteIndex = 0;
            renderPaletteResults();
            setTimeout(() => paletteInput.focus(), 50);
        }

        function closeCommandPalette(e) {
            cmdPaletteOverlay.classList.remove('open');
        }

        function handlePaletteSelect(idx) {
            const item = visibleItems[idx];
            if (!item) return;
            closeCommandPalette();
            if (item.action === 'showPassport') {
                openBootPassport('FC-PASS-8829-VN', 'Nike Zoom Mercurial Vapor 16 Pro TF', 'Trắng / Xanh Neon', 'Size 41 · Form Bè', 'TF Cỏ nhân tạo', '#ORD-8821 · 12/09/2026');
            } else if (item.url) {
                window.location.href = item.url;
            }
        }

        // Global hotkey listener: Ctrl+K or Cmd+K or /
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openCommandPalette();
            } else if (e.key === '/' && !['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) {
                e.preventDefault();
                openCommandPalette();
            } else if (e.key === 'Escape' && cmdPaletteOverlay.classList.contains('open')) {
                closeCommandPalette();
            } else if (cmdPaletteOverlay.classList.contains('open')) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (selectedPaletteIndex < visibleItems.length - 1) {
                        selectedPaletteIndex++;
                        renderPaletteResults(paletteInput.value);
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (selectedPaletteIndex > 0) {
                        selectedPaletteIndex--;
                        renderPaletteResults(paletteInput.value);
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    handlePaletteSelect(selectedPaletteIndex);
                }
            }
        });

        if (paletteInput) {
            paletteInput.addEventListener('input', () => {
                selectedPaletteIndex = 0;
                renderPaletteResults(paletteInput.value);
            });
        }

        document.querySelectorAll('.cmd-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.cmd-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                currentPaletteFilter = chip.dataset.filter;
                selectedPaletteIndex = 0;
                renderPaletteResults(paletteInput.value);
            });
        });

        // ── Boot Passport Card Preview ──
        function openBootPassport(code, name, color, size, stud, order) {
            const modal = document.getElementById('bootPassportModal');
            if (code) document.getElementById('passModalCode').innerText = code;
            if (name) document.getElementById('passModalName').innerText = name;
            if (color) document.getElementById('passModalColor').innerText = color;
            if (size) document.getElementById('passModalSize').innerText = size;
            if (order) document.getElementById('passModalOrder').innerText = order;
            if (modal) modal.classList.add('open');
        }
        function closeBootPassport() {
            const modal = document.getElementById('bootPassportModal');
            if (modal) modal.classList.remove('open');
        }
    </script>
</body>
</html>
