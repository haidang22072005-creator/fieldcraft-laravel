<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#07110d">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FIELDCRAFT — Dụng cụ bóng đá chính hãng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #07110d;
            --bg-panel: #0d1e16;
            --bg-panel-sub: #11261c;
            --bg-input: #12281d;
            --border-panel: #1b3829;
            --border-sub: #234633;
            --border-input: #234633;
            --neon-green: #caff39;
            --neon-green-hover: #b8ec2e;
            --text-main: #ffffff;
            --text-sub: #c9d8cc;
            --text-muted: #8ea492;
            --error-text: #ff6b4a;
            --error-bg: #27110a;
            --error-border: #522115;
            --warning-text: #fbbf24;
            --warning-bg: #261f0c;
            --warning-border: #594717;
            --success-bg: rgba(202, 255, 57, 0.12);
            --success-border: rgba(202, 255, 57, 0.25);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--bg-body);
            color: var(--text-main);
            font: 500 14px/1.5 'Manrope', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        button, input, select { font: inherit; }
        button { cursor: pointer; }
        a { color: inherit; text-decoration: none; }

        .shell {
            max-width: 1370px;
            padding: 0 32px;
            margin: auto;
        }

        .eyebrow {
            font: 700 11px/1 'DM Mono', monospace;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--neon-green);
        }

        /* Navigation */
        .site-nav {
            height: 88px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            border-bottom: 1px solid var(--border-panel);
            position: relative;
            z-index: 10;
            background: var(--bg-body);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font: 700 26px/1 'Oswald', sans-serif;
            letter-spacing: .02em;
            color: var(--text-main);
        }
        .brand-mark {
            width: 24px;
            height: 24px;
            border-radius: 3px 13px 3px 13px;
            background: var(--neon-green);
            transform: rotate(-20deg);
            box-shadow: inset 0 0 0 5px var(--bg-body);
        }
        .nav-links {
            display: flex;
            gap: 28px;
            align-items: center;
            font-size: 13px;
            font-weight: 700;
        }
        .nav-links a {
            position: relative;
            color: var(--text-sub);
            transition: color .15s;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: var(--text-main);
        }
        .nav-links a.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -8px;
            height: 2px;
            background: var(--neon-green);
        }

        .nav-actions, .auth-desktop {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search {
            display: flex;
            align-items: center;
            width: 220px;
            height: 42px;
            border: 1px solid var(--border-panel);
            border-radius: 22px;
            padding: 0 14px;
            background: var(--bg-panel);
            color: var(--text-main);
            transition: border-color .15s;
        }
        .search:focus-within {
            border-color: var(--neon-green);
        }
        .search input {
            width: 100%;
            border: 0;
            outline: 0;
            margin-left: 8px;
            background: transparent;
            font-size: 12px;
            color: var(--text-main);
        }
        .search input::placeholder {
            color: var(--text-muted);
        }

        .auth-link {
            padding: 9px 14px;
            border: 1px solid var(--border-panel);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            background: var(--bg-panel);
            transition: border-color .15s, background .15s;
        }
        .auth-link:hover {
            border-color: var(--border-sub);
        }
        .auth-link.primary {
            background: var(--neon-green);
            border-color: var(--neon-green);
            color: #07110d;
        }
        .auth-link.primary:hover {
            background: var(--neon-green-hover);
        }

        .nav-icon {
            width: 42px;
            height: 42px;
            border: 1px solid var(--border-panel);
            border-radius: 50%;
            background: var(--bg-panel);
            color: var(--text-main);
            display: grid;
            place-items: center;
            position: relative;
            transition: border-color .15s;
        }
        .nav-icon:hover {
            border-color: var(--border-sub);
        }
        .cart-count {
            position: absolute;
            top: -3px;
            right: -2px;
            background: var(--neon-green);
            color: #07110d;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font: 700 10px/18px 'DM Mono', monospace;
            text-align: center;
        }

        .user-menu, .mobile-auth {
            position: relative;
        }
        .user-menu summary, .mobile-auth summary {
            list-style: none;
            cursor: pointer;
        }
        .user-menu summary::-webkit-details-marker,
        .mobile-auth summary::-webkit-details-marker {
            display: none;
        }
        .user-name {
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .auth-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 12px);
            min-width: 220px;
            padding: 8px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
            z-index: 50;
        }
        .auth-menu a, .auth-menu button, .mobile-links a, .mobile-links button {
            display: block;
            width: 100%;
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--text-sub);
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            transition: background .15s, color .15s;
        }
        .auth-menu a:hover, .auth-menu button:hover, .mobile-links a:hover, .mobile-links button:hover {
            background: var(--bg-panel-sub);
            color: var(--neon-green);
        }
        .mobile-auth { display: none; }
        .mobile-links {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: min(280px, calc(100vw - 36px));
            padding: 10px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
            z-index: 50;
        }
        .mobile-user {
            padding: 10px 14px;
            font-weight: 800;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-panel);
            margin-bottom: 6px;
        }

        /* Hero */
        .hero {
            margin: 24px auto 0;
            min-height: 620px;
            max-width: 1370px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            color: white;
            background: #091711;
            border: 1px solid var(--border-panel);
        }
        .hero-photo {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(90deg, rgba(6,18,13,.95) 2%, rgba(6,18,13,.65) 45%, rgba(6,18,13,.15) 80%), url('https://legacymedia.sportsplatform.io/image/upload/v1670098871/ippg3ltsfswdh5sjltsx.jpg');
            background-position: center;
            background-size: cover;
            transition: transform 1s ease;
        }
        .hero:hover .hero-photo {
            transform: scale(1.02);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            height: 620px;
            width: min(640px, 100%);
            padding: 100px 7.4%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .hero h1 {
            margin: 14px 0 24px;
            font: 700 clamp(52px, 6.8vw, 98px)/.92 'Oswald', sans-serif;
            letter-spacing: -.02em;
            text-transform: uppercase;
        }
        .hero h1 em {
            color: var(--neon-green);
            font-style: normal;
        }
        .hero p {
            max-width: 420px;
            font-size: 15px;
            color: #d1ded5;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }
        .btn {
            border: 0;
            border-radius: 6px;
            min-height: 48px;
            padding: 12px 24px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font: 700 13px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            transition: background .15s, transform .1s;
        }
        .btn-primary {
            background: var(--neon-green);
            color: #07110d;
        }
        .btn-primary:hover {
            background: var(--neon-green-hover);
            transform: translateY(-1px);
        }
        .btn-outline {
            background: rgba(13, 30, 22, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border-sub);
            color: var(--text-main);
        }
        .btn-outline:hover {
            background: var(--bg-panel);
            border-color: var(--neon-green);
        }
        .hero-stats {
            position: absolute;
            z-index: 2;
            right: 32px;
            bottom: 32px;
            display: flex;
            gap: 2px;
        }
        .hero-stat {
            min-width: 130px;
            padding: 16px 20px;
            background: rgba(10, 22, 16, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-panel);
        }
        .hero-stat:first-child {
            border-radius: 8px 0 0 8px;
        }
        .hero-stat:last-child {
            border-radius: 0 8px 8px 0;
        }
        .hero-stat strong {
            display: block;
            font: 700 24px/1 'Oswald', sans-serif;
            color: var(--neon-green);
        }
        .hero-stat span {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            display: block;
        }

        /* 3 Integrated Feature Cards */
        .feature-grid {
            max-width: 1370px;
            margin: 28px auto 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .feature-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: border-color .2s;
        }
        .feature-card:hover {
            border-color: var(--border-sub);
        }
        .feature-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            color: var(--neon-green);
            display: grid;
            place-items: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .feature-card-content h3 {
            margin: 0 0 4px;
            font: 700 15px/1.2 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--text-main);
        }
        .feature-card-content p {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Sections */
        section {
            padding: 85px 0 0;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 28px;
        }
        .section-head h2 {
            margin: 8px 0 0;
            font: 700 46px/.96 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: -.02em;
            color: var(--text-main);
        }
        .link-arrow {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-weight: 700;
            font-size: 13px;
            color: var(--neon-green);
            transition: gap .15s;
        }
        .link-arrow:hover {
            gap: 12px;
        }

        /* Categories */
        .categories {
            display: grid;
            grid-template-columns: 1.15fr 1fr 1fr 1fr;
            gap: 16px;
        }
        .category {
            min-height: 250px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: white;
            display: flex;
            align-items: flex-end;
        }
        .category.large {
            min-height: 420px;
            grid-row: span 2;
        }
        .category::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, rgba(7, 17, 13, 0.88) 10%, rgba(7, 17, 13, 0.15) 75%);
            z-index: 1;
        }
        .category img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .5s ease;
        }
        .category:hover img {
            transform: scale(1.06);
        }
        .category-info {
            position: relative;
            z-index: 2;
        }
        .category-info b {
            display: block;
            font: 700 28px/1.1 'Oswald', sans-serif;
            text-transform: uppercase;
        }
        .category-info span {
            font: 700 10px 'DM Mono', monospace;
            color: var(--neon-green);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .round-arrow {
            position: absolute;
            z-index: 2;
            top: 18px;
            right: 18px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(13, 30, 22, 0.8);
            border: 1px solid var(--border-sub);
            color: var(--neon-green);
            display: grid;
            place-items: center;
        }

        /* Products Toolbar */
        .products-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 26px;
            overflow-x: auto;
            padding-bottom: 6px;
        }
        .filter-pill {
            border: 1px solid var(--border-panel);
            background: var(--bg-panel);
            color: var(--text-sub);
            padding: 10px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            white-space: nowrap;
            transition: border-color .15s, background .15s, color .15s;
        }
        .filter-pill.active, .filter-pill:hover {
            border-color: var(--neon-green);
            background: var(--neon-green);
            color: #07110d;
        }

        /* Product Cards */
        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }
        .product-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color .2s, transform .2s;
            position: relative;
        }
        .product-card:hover {
            border-color: var(--border-sub);
            transform: translateY(-2px);
        }
        .product-visual {
            height: 280px;
            overflow: hidden;
            position: relative;
            background: var(--bg-panel-sub);
        }
        .product-visual img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .45s ease;
        }
        .product-card:hover .product-visual img {
            transform: scale(1.08);
        }
        .product-badge {
            position: absolute;
            z-index: 2;
            top: 12px;
            left: 12px;
            border-radius: 4px;
            padding: 4px 8px;
            background: var(--neon-green);
            color: #07110d;
            font: 700 10px/1 'DM Mono', monospace;
            text-transform: uppercase;
        }
        .wish {
            position: absolute;
            right: 12px;
            top: 12px;
            z-index: 2;
            border: 1px solid var(--border-sub);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(13, 30, 22, 0.85);
            color: var(--text-muted);
            display: grid;
            place-items: center;
            font-size: 15px;
            transition: color .15s;
        }
        .wish.loved {
            color: var(--error-text);
        }

        .product-meta {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }
        .product-category-tag {
            font: 700 11px 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--neon-green);
            margin-bottom: 6px;
        }
        .product-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 10px;
            line-height: 1.3;
        }
        .product-price-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 12px;
        }
        .product-price {
            font: 700 18px/1 'DM Mono', monospace;
            color: var(--text-main);
        }
        .old-price {
            font: 500 13px 'DM Mono', monospace;
            color: var(--text-muted);
            text-decoration: line-through;
        }
        .stock-tag {
            font-size: 12px;
            font-weight: 600;
            color: var(--neon-green);
            margin-bottom: 14px;
        }
        .stock-tag.warning {
            color: var(--warning-text);
        }
        .stock-tag.danger {
            color: var(--error-text);
        }

        .btn-view-product {
            width: 100%;
            min-height: 44px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            border-radius: 6px;
            color: var(--text-main);
            font: 700 12px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .06em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, border-color .15s, color .15s;
        }
        .btn-view-product:hover {
            background: var(--neon-green);
            border-color: var(--neon-green);
            color: #07110d;
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            border: 1px dashed var(--border-panel);
            border-radius: 14px;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Campaign Banner */
        .campaign {
            margin-top: 105px;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: white;
            overflow: hidden;
            position: relative;
            border-radius: 20px;
        }
        .campaign .shell {
            min-height: 480px;
            display: flex;
            align-items: center;
            position: relative;
        }
        .campaign-copy {
            width: 50%;
            padding: 60px 0;
            position: relative;
            z-index: 2;
        }
        .campaign h2 {
            font: 700 58px/.94 'Oswald', sans-serif;
            text-transform: uppercase;
            margin: 12px 0 20px;
        }
        .campaign h2 span {
            color: var(--neon-green);
        }
        .campaign p {
            color: #d1ded5;
            max-width: 420px;
            font-size: 15px;
            line-height: 1.6;
        }
        .campaign-img {
            position: absolute;
            inset: 0 0 0 45%;
            width: 55%;
            height: 100%;
            object-fit: cover;
            opacity: .65;
        }
        .campaign::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, var(--bg-panel) 40%, rgba(13, 30, 22, 0.2) 75%);
        }

        /* Journal */
        .journal-grid {
            display: grid;
            grid-template-columns: 1.05fr 1fr 1fr;
            gap: 20px;
        }
        .journal-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            overflow: hidden;
            padding: 16px;
        }
        .journal-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }
        .journal-card:first-child img {
            height: 330px;
        }
        .journal-card span {
            display: block;
            margin-top: 14px;
            color: var(--neon-green);
            font: 700 11px 'DM Mono', monospace;
            text-transform: uppercase;
        }
        .journal-card h3 {
            margin: 8px 0 4px;
            font: 700 22px/1.2 'Oswald', sans-serif;
            text-transform: uppercase;
            color: var(--text-main);
        }

        /* Footer */
        .footer {
            margin-top: 105px;
            background: #050d0a;
            border-top: 1px solid var(--border-panel);
            padding: 60px 0 40px;
        }
        .newsletter {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 40px;
            padding-bottom: 48px;
            border-bottom: 1px solid var(--border-panel);
        }
        .newsletter h2 {
            margin: 8px 0 0;
            font: 700 44px/.98 'Oswald', sans-serif;
            text-transform: uppercase;
            color: var(--text-main);
        }
        .newsletter-form {
            width: 400px;
            display: flex;
            border-bottom: 2px solid var(--neon-green);
            padding-bottom: 10px;
        }
        .newsletter-form input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-main);
            font-size: 13px;
        }
        .newsletter-form button {
            border: 0;
            background: transparent;
            color: var(--neon-green);
            font: 700 13px 'Oswald', sans-serif;
            letter-spacing: .05em;
        }
        .footer-bottom {
            padding-top: 28px;
            display: flex;
            justify-content: space-between;
            font: 11px 'DM Mono', monospace;
            color: var(--text-muted);
        }

        /* Cart Drawer */
        .drawer-backdrop, .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(3, 10, 7, 0.75);
            backdrop-filter: blur(8px);
            z-index: 90;
            opacity: 0;
            visibility: hidden;
            transition: .25s;
        }
        .drawer-backdrop.show, .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .cart-drawer {
            position: fixed;
            z-index: 95;
            top: 0;
            right: 0;
            height: 100vh;
            width: min(540px, 100vw);
            background: var(--bg-panel);
            border-left: 1px solid var(--border-panel);
            transform: translateX(105%);
            transition: .33s cubic-bezier(.2,.8,.2,1);
            display: flex;
            flex-direction: column;
            box-shadow: -20px 0 50px rgba(0, 0, 0, 0.6);
        }
        .cart-drawer.show {
            transform: none;
        }
        .drawer-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border-panel);
            background: var(--bg-panel);
        }
        .drawer-head h2 {
            margin: 0;
            font: 700 26px/1 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: .02em;
            color: var(--text-main);
        }
        .close {
            border: 1px solid var(--border-panel);
            background: var(--bg-panel-sub);
            color: var(--text-sub);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            display: grid;
            place-items: center;
            transition: color .15s, border-color .15s;
        }
        .close:hover {
            color: var(--neon-green);
            border-color: var(--border-sub);
        }

        .cart-lines {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
        }
        .cart-empty {
            padding: 70px 10px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
        }
        .cart-line {
            display: grid;
            grid-template-columns: 24px 84px 1fr auto;
            gap: 14px;
            padding: 14px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
            margin-bottom: 12px;
            align-items: center;
        }
        .cart-line img {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--bg-body);
        }
        .cart-line h4 {
            margin: 0 0 4px;
            font-size: 13px;
            color: var(--text-main);
        }
        .cart-line p {
            margin: 0 0 6px;
            color: var(--text-muted);
            font-size: 11px;
        }
        .cart-line .price {
            font: 700 13px 'DM Mono', monospace;
            color: var(--neon-green);
        }
        .cart-check {
            width: 18px;
            height: 18px;
            accent-color: var(--neon-green);
            cursor: pointer;
        }
        .cart-line .quantity {
            display: flex;
            align-items: center;
            width: max-content;
            border: 1px solid var(--border-sub);
            border-radius: 4px;
            background: var(--bg-input);
            margin-top: 6px;
        }
        .cart-line .quantity button {
            width: 28px;
            height: 28px;
            border: 0;
            background: transparent;
            color: var(--text-main);
            font-weight: 700;
        }
        .cart-line .quantity span {
            width: 30px;
            text-align: center;
            font: 700 12px 'DM Mono', monospace;
            color: var(--text-main);
        }
        .delete-line {
            align-self: start;
            border: 0;
            background: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
        }
        .delete-line:hover {
            color: var(--error-text);
        }
        .cart-warning {
            color: var(--error-text);
            font-size: 11px;
            margin-top: 4px;
        }

        .cart-foot {
            border-top: 1px solid var(--border-panel);
            padding: 20px 24px;
            background: var(--bg-panel);
        }
        .select-all-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-main);
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        .selected-note {
            color: var(--text-muted);
            font-size: 12px;
            margin-bottom: 12px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 13px;
            color: var(--text-sub);
        }
        .total-row strong {
            font: 700 18px 'DM Mono', monospace;
            color: var(--neon-green);
        }

        /* Product Quick View / Modal */
        .product-modal {
            position: fixed;
            z-index: 100;
            top: 50%;
            left: 50%;
            width: min(800px, calc(100vw - 32px));
            max-height: 90vh;
            overflow-y: auto;
            transform: translate(-50%, -47%);
            opacity: 0;
            visibility: hidden;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 16px;
            padding: 28px;
            transition: .25s;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.7);
        }
        .product-modal.show {
            transform: translate(-50%, -50%);
            opacity: 1;
            visibility: visible;
        }
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 28px;
        }
        .modal-img {
            height: 380px;
            background: var(--bg-panel-sub);
            border: 1px solid var(--border-sub);
            overflow: hidden;
            border-radius: 10px;
            cursor: zoom-in;
        }
        .modal-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s;
        }
        .modal-img:hover img {
            transform: scale(1.4);
        }
        .modal-info h2 {
            margin: 6px 0 10px;
            font: 700 32px/1.05 'Oswald', sans-serif;
            text-transform: uppercase;
            color: var(--text-main);
        }
        .modal-price-row {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 16px;
        }
        .modal-price {
            font: 700 24px/1 'DM Mono', monospace;
            color: var(--neon-green);
        }
        .choice-label {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 18px 0 8px;
            font: 700 11px/1 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-sub);
        }
        .size-guide-trigger {
            color: var(--neon-green);
            text-decoration: underline;
            cursor: pointer;
            font-size: 11px;
        }
        .swatches, .sizes {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .choice-chip {
            min-height: 40px;
            padding: 8px 14px;
            border: 1px solid var(--border-sub);
            background: var(--bg-panel-sub);
            color: var(--text-main);
            border-radius: 6px;
            font: 700 12px 'DM Mono', monospace;
            cursor: pointer;
            transition: border-color .15s, background .15s, color .15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .choice-chip:hover:not(:disabled) {
            border-color: var(--neon-green);
        }
        .choice-chip.active {
            border-color: var(--neon-green);
            background: rgba(202, 255, 57, 0.15);
            color: var(--neon-green);
        }
        .choice-chip:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            text-decoration: line-through;
            border-color: var(--border-panel);
        }
        .chip-badge {
            font-size: 9px;
            color: var(--warning-text);
        }

        .sku-indicator {
            font: 500 12px 'DM Mono', monospace;
            color: var(--text-muted);
            margin-top: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .modal-actions .btn {
            flex: 1;
            justify-content: center;
        }

        .toast {
            position: fixed;
            z-index: 120;
            left: 50%;
            bottom: 30px;
            transform: translate(-50%, 120px);
            transition: .3s cubic-bezier(.2,.8,.2,1);
            background: var(--neon-green);
            color: #07110d;
            font: 700 13px 'Manrope', sans-serif;
            padding: 14px 22px;
            border-radius: 8px;
            box-shadow: 0 12px 36px rgba(0,0,0,0.6);
            pointer-events: none;
        }
        .toast.show {
            transform: translate(-50%, 0);
        }

        /* Size Guide Modal */
        .size-guide-modal {
            position: fixed;
            z-index: 110;
            top: 50%;
            left: 50%;
            width: min(520px, calc(100vw - 32px));
            max-height: 85vh;
            overflow-y: auto;
            transform: translate(-50%, -47%);
            opacity: 0;
            visibility: hidden;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.8);
            transition: .25s;
        }
        .size-guide-modal.show {
            transform: translate(-50%, -50%);
            opacity: 1;
            visibility: visible;
        }
        .size-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 13px;
        }
        .size-table th, .size-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-panel);
            text-align: center;
        }
        .size-table th {
            font: 700 11px 'DM Mono', monospace;
            color: var(--neon-green);
            text-transform: uppercase;
        }
        .size-table td {
            font-family: 'DM Mono', monospace;
            color: var(--text-sub);
        }

        @media (max-width: 900px) {
            .shell { padding: 0 18px; }
            .nav-links, .auth-desktop { display: none; }
            .mobile-auth { display: block; }
            .hero { min-height: 540px; border-radius: 0; }
            .hero-content { height: 540px; padding: 80px 8%; }
            .feature-grid { grid-template-columns: 1fr; gap: 12px; padding: 0 18px; }
            .categories { grid-template-columns: 1fr 1fr; }
            .category.large { grid-column: span 2; min-height: 320px; }
            .products { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .campaign { margin-top: 70px; border-radius: 0; }
            .campaign-copy { width: 100%; }
            .campaign-img { inset: 0; width: 100%; opacity: .25; }
            .campaign::after { background: rgba(7, 17, 13, 0.7); }
            .journal-grid { grid-template-columns: 1fr; }
            .journal-card:first-child img, .journal-card img { height: 240px; }
            .newsletter { flex-direction: column; align-items: flex-start; }
            .newsletter-form { width: 100%; }
            .footer-bottom { flex-wrap: wrap; gap: 12px; }
            .modal-grid { grid-template-columns: 1fr; gap: 20px; }
            .modal-img { height: 260px; }
            .product-modal { padding: 20px; }
        }

        @media (max-width: 530px) {
            .site-nav { height: 70px; }
            .brand { font-size: 22px; }
            .hero h1 { font-size: 48px; }
            .hero-content { padding: 60px 6%; }
            .hero-stat:nth-child(2) { display: none; }
            .products { grid-template-columns: 1fr; }
            .cart-drawer { width: 100vw; }
            .modal-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    @if(session('success'))
        <div id="orderSuccess" style="position:fixed;z-index:150;top:20px;left:50%;transform:translateX(-50%);background:var(--neon-green);color:#07110d;padding:12px 20px;border-radius:6px;font-weight:800;font-size:13px;box-shadow:0 8px 30px rgba(0,0,0,0.5)">
            ✓ {{ session('success') }}
            <a href="{{ route('purchases') }}" style="margin-left:10px;text-decoration:underline;color:#07110d">Xem đơn →</a>
        </div>
    @endif

    <header class="shell site-nav">
        <a href="#top" class="brand" aria-label="Fieldcraft home">
            <i class="brand-mark"></i>FIELDCRAFT
        </a>

        <nav class="nav-links">
            <a class="active" href="#shop">CỬA HÀNG</a>
            <a href="#collection">BỘ SƯU TẬP</a>
            <a href="#journal">NHẬT KÝ SÂN CỎ</a>
        </nav>

        <div class="nav-actions">
            <label class="search" title="Tìm sản phẩm">
                <span>⌕</span>
                <input id="search" placeholder="Tìm giày, áo, bóng..." autocomplete="off">
            </label>

            <div class="auth-desktop">
                @guest
                    <a class="auth-link" href="{{ route('login') }}">Đăng nhập</a>
                    <a class="auth-link primary" href="{{ route('register') }}">Đăng ký</a>
                @else
                    @if(!auth()->user()->hasVerifiedEmail())
                        <a class="auth-link" style="color:var(--warning-text)" href="{{ route('verification.notice') }}">Xác thực email</a>
                    @endif
                    <details class="user-menu">
                        <summary class="auth-link user-name">{{ auth()->user()->name }} ▾</summary>
                        <div class="auth-menu">
                            <a href="{{ route('settings') }}">Tài khoản & Cài đặt</a>
                            <a href="{{ route('purchases') }}">Đơn hàng của tôi</a>
                            @if(auth()->user()->role === 'super-admin')
                                <a href="{{ route('admin.dashboard') }}">Quản trị viên</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Đăng xuất</button>
                            </form>
                        </div>
                    </details>
                @endguest
            </div>

            <a class="nav-icon" href="{{ route('settings') }}" aria-label="Cài đặt">⚙</a>
            <button class="nav-icon" id="openCart" aria-label="Mở giỏ hàng">
                ⌑<span class="cart-count" id="cartCount">{{ $cartLines->sum('qty') }}</span>
            </button>

            <details class="mobile-auth">
                <summary class="nav-icon" aria-label="Mở menu">☰</summary>
                <div class="mobile-links">
                    <a href="#shop">Cửa hàng</a>
                    @guest
                        <a href="{{ route('login') }}">Đăng nhập</a>
                        <a href="{{ route('register') }}">Đăng ký</a>
                    @else
                        <div class="mobile-user">{{ auth()->user()->name }}</div>
                        @if(!auth()->user()->hasVerifiedEmail())
                            <a style="color:var(--warning-text)" href="{{ route('verification.notice') }}">Xác thực email</a>
                        @endif
                        <a href="{{ route('settings') }}">Tài khoản & Cài đặt</a>
                        <a href="{{ route('purchases') }}">Đơn hàng của tôi</a>
                        @if(auth()->user()->role === 'super-admin')
                            <a href="{{ route('admin.dashboard') }}">Quản trị</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Đăng xuất</button>
                        </form>
                    @endguest
                </div>
            </details>
        </div>
    </header>

    <main id="top">
        <!-- Hero Section -->
        <section class="hero" aria-label="Bộ sưu tập Messi">
            <div class="hero-photo"></div>
            <div class="hero-content">
                <span class="eyebrow">The ultimate football edit — 2026</span>
                <h1>Chạm bóng<br><em>như số 10.</em></h1>
                <p>Trang bị cho khoảnh khắc bạn tạo khác biệt. Từ đôi giày đầu tiên đến cú sút quyết định.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#shop">KHÁM PHÁ NGAY <span>→</span></a>
                    <a class="btn btn-outline" href="#collection">XEM NEMEZIZ</a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><strong>24H</strong><span>Giao nội thành</span></div>
                <div class="hero-stat"><strong>100%</strong><span>Chính hãng</span></div>
            </div>
        </section>

        <!-- 3 Integrated Dark Feature Cards -->
        <div class="shell">
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon-box">✓</div>
                    <div class="feature-card-content">
                        <h3>HÀNG CHÍNH HÃNG 100%</h3>
                        <p>Phát hiện fake hoàn tiền 200%</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-box">↻</div>
                    <div class="feature-card-content">
                        <h3>ĐỔI SIZE SIÊU NHANH</h3>
                        <p>Hỗ trợ đổi trong 7 ngày</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-box">⚡</div>
                    <div class="feature-card-content">
                        <h3>TƯ VẤN TỪ NGƯỜI CHƠI</h3>
                        <p>Tư vấn form chân & mặt sân</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Section -->
        <section class="shell" id="collection">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Find your game</span>
                    <h2>Chọn đúng vũ khí.</h2>
                </div>
                <a href="#shop" class="link-arrow">XEM TẤT CẢ <span>→</span></a>
            </div>
            <div class="categories">
                <a class="category large" href="#shop">
                    <img src="https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=1000&q=85" alt="Giày đinh sân cỏ">
                    <div class="category-info">
                        <span>01 / tốc độ</span>
                        <b>Giày đinh</b>
                    </div>
                    <span class="round-arrow">↗</span>
                </a>
                <a class="category" href="#shop">
                    <img src="https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=800&q=85" alt="Áo đấu bóng đá">
                    <div class="category-info">
                        <span>02 / bản sắc</span>
                        <b>Áo đấu</b>
                    </div>
                    <span class="round-arrow">↗</span>
                </a>
                <a class="category" href="#shop">
                    <img src="https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=800&q=85" alt="Bóng thi đấu">
                    <div class="category-info">
                        <span>03 / kiểm soát</span>
                        <b>Bóng đá</b>
                    </div>
                    <span class="round-arrow">↗</span>
                </a>
                <a class="category" href="#shop">
                    <img src="https://images.unsplash.com/photo-1614632537190-23e4146777db?auto=format&fit=crop&w=800&q=85" alt="Phụ kiện thể thao">
                    <div class="category-info">
                        <span>04 / hoàn thiện</span>
                        <b>Phụ kiện</b>
                    </div>
                    <span class="round-arrow">↗</span>
                </a>
                <a class="category" href="#shop">
                    <img src="https://images.unsplash.com/photo-1487466365202-1afdb86c764e?auto=format&fit=crop&w=800&q=85" alt="Túi thể thao">
                    <div class="category-info">
                        <span>05 / sẵn sàng</span>
                        <b>Túi & đồ tập</b>
                    </div>
                    <span class="round-arrow">↗</span>
                </a>
            </div>
        </section>

        <!-- Shop Section -->
        <section class="shell" id="shop">
            <div class="section-head">
                <div>
                    <span class="eyebrow" id="resultCount">06 sản phẩm nổi bật</span>
                    <h2>Được chọn bởi sân cỏ.</h2>
                </div>
                <a href="#" class="link-arrow" id="resetFilter">LÀM MỚI ↻</a>
            </div>

            <div class="products-toolbar">
                <button class="filter-pill active" data-filter="all">Tất cả</button>
                <button class="filter-pill" data-filter="adidas">adidas</button>
                <button class="filter-pill" data-filter="nike">Nike</button>
                <button class="filter-pill" data-filter="puma">Puma</button>
                <button class="filter-pill" data-category="Giày đinh">Giày đinh</button>
                <button class="filter-pill" data-color="Trắng">Trắng</button>
                <button class="filter-pill" data-max="2000000">Dưới 2 triệu</button>
            </div>

            <div class="products" id="productGrid"></div>
        </section>

        <!-- Campaign Section -->
        <div class="shell">
            <section class="campaign" aria-label="Bộ sưu tập Messi Nemeziz">
                <div class="shell" style="padding:0">
                    <div class="campaign-copy">
                        <span class="eyebrow">LIMITED CAPSULE / MESSI EDIT</span>
                        <h2>NEMEZIZ<br><span>IS BACK.</span></h2>
                        <p>Được tạo cho những người phá vỡ cấu trúc, bộ sưu tập mang DNA của các pha xử lý không thể đoán trước.</p>
                        <a href="#shop" class="btn btn-primary" style="margin-top:20px">KHÁM PHÁ BỘ SƯU TẬP <span>→</span></a>
                    </div>
                    <img class="campaign-img" src="https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=1400&q=85" alt="Cầu thủ bóng đá trên sân">
                </div>
            </section>
        </div>

        <!-- Journal Section -->
        <section class="shell" id="journal">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Field notes</span>
                    <h2>Nhật ký sân cỏ.</h2>
                </div>
                <a href="#journal" class="link-arrow">ĐỌC THÊM <span>→</span></a>
            </div>
            <div class="journal-grid">
                <article class="journal-card">
                    <img src="https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=1000&q=85" alt="Bóng đá sân cỏ">
                    <span>Guide / 04.06.2026</span>
                    <h3>Chọn quả bóng đúng với mặt sân của bạn</h3>
                </article>
                <article class="journal-card">
                    <img src="https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=900&q=85" alt="Giày đá bóng">
                    <span>Field test / 29.05.2026</span>
                    <h3>FG, AG hay TF? Đừng chọn sai đế giày</h3>
                </article>
                <article class="journal-card">
                    <img src="https://images.unsplash.com/photo-1560272564-c83b66b1ad12?auto=format&fit=crop&w=900&q=85" alt="Sân bóng đá">
                    <span>Community / 17.05.2026</span>
                    <h3>Năm sân phủi đáng chơi nhất Sài Gòn</h3>
                </article>
            </div>
        </section>
    </main>

    <!-- Dark Footer -->
    <footer class="footer">
        <div class="shell">
            <div class="newsletter">
                <div>
                    <span class="eyebrow">Join the club</span>
                    <h2>Nhận nhịp đập<br>sân cỏ.</h2>
                </div>
                <div>
                    <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">Ưu đãi thành viên, restock nóng và những câu chuyện từ cộng đồng.</p>
                    <form class="newsletter-form" id="newsletter">
                        <input type="email" required placeholder="Email của bạn">
                        <button type="submit">ĐĂNG KÝ →</button>
                    </form>
                    <div class="message" id="newsletterMessage" style="color:var(--neon-green);margin-top:10px;font-size:12px"></div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2026 FIELDCRAFT STUDIO</span>
                <span>CHÍNH SÁCH &nbsp; / &nbsp; LIÊN HỆ &nbsp; / &nbsp; INSTAGRAM</span>
            </div>
        </div>
    </footer>

    <!-- Cart Drawer -->
    <div class="drawer-backdrop" id="drawerBackdrop"></div>
    <aside class="cart-drawer" id="cartDrawer">
        <div class="drawer-head">
            <div>
                <span class="eyebrow">Your selection</span>
                <h2>Giỏ hàng (<span id="drawerQty">0</span>)</h2>
            </div>
            <button class="close" id="closeCart" aria-label="Đóng">×</button>
        </div>

        <div class="cart-lines" id="cartLines">
            <div class="cart-empty">Giỏ hàng đang trống.<br>Chọn món đồ giúp bạn chơi hay hơn.</div>
        </div>

        <div class="cart-foot">
            <label class="select-all-row">
                <input class="cart-check" id="selectAll" type="checkbox">
                <span>Chọn tất cả</span>
            </label>
            <div class="selected-note" id="selectedCount">Đã chọn 0 sản phẩm</div>
            <div class="total-row">
                <span>Tạm tính giỏ</span>
                <span id="subtotal">0₫</span>
            </div>
            <div class="total-row">
                <strong>TỔNG THANH TOÁN</strong>
                <strong id="total">0₫</strong>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px">
                <a href="{{ route('cart.index') }}" class="btn btn-outline" style="flex:1;justify-content:center">XEM GIỎ</a>
                <button id="checkout" class="btn btn-primary" style="flex:1.4;justify-content:center">ĐẶT HÀNG →</button>
            </div>
        </div>
    </aside>

    <!-- Product Quick View Modal -->
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="product-modal" id="productModal">
        <button class="close" id="closeModal" style="position:absolute;top:16px;right:16px;z-index:5" aria-label="Đóng">×</button>
        <div class="modal-grid">
            <div class="modal-img">
                <img id="modalImage" src="" alt="Sản phẩm">
            </div>
            <div class="modal-info">
                <span class="eyebrow" id="modalCategory"></span>
                <h2 id="modalName"></h2>
                <div class="modal-price-row">
                    <div class="modal-price" id="modalPrice"></div>
                </div>

                <div class="choice-label">
                    <span>Màu sắc: <b id="chosenColor" style="color:var(--text-main)"></b></span>
                </div>
                <div class="swatches" id="colorChoices"></div>

                <div class="choice-label">
                    <span>Kích cỡ: <b id="chosenSize" style="color:var(--text-main)"></b></span>
                    <span class="size-guide-trigger" id="openSizeGuide">Hướng dẫn chọn size</span>
                </div>
                <div class="sizes" id="sizeChoices"></div>

                <div class="choice-label" style="margin-top:18px">
                    <span>Số lượng</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <input id="productQuantity" type="number" min="1" value="1" style="width:70px;height:42px;padding:0 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:6px;color:var(--text-main);font:700 13px 'DM Mono';outline:none">
                    <span id="stockMessage" style="font-size:12px;color:var(--neon-green);font-weight:600"></span>
                </div>

                <div class="sku-indicator" id="skuIndicator"></div>

                <div class="modal-actions">
                    <button id="modalAdd" class="btn btn-outline">THÊM VÀO GIỎ</button>
                    <button id="buyNow" class="btn btn-primary">MUA NGAY</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Size Guide Modal -->
    <div class="size-guide-modal" id="sizeGuideModal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="font:700 20px 'Oswald',sans-serif;text-transform:uppercase;margin:0">Bảng quy đổi kích cỡ giày</h3>
            <button class="close" id="closeSizeGuide">×</button>
        </div>
        <p style="color:var(--text-muted);font-size:12px;margin:0 0 14px">Đo chiều dài từ gót đến ngón chân dài nhất để chọn size chính xác nhất.</p>
        <table class="size-table">
            <thead>
                <tr>
                    <th>Size EU</th>
                    <th>Size US</th>
                    <th>Chiều dài chân (cm)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>39</td><td>6.5</td><td>24.5 cm</td></tr>
                <tr><td>40</td><td>7.0</td><td>25.0 cm</td></tr>
                <tr><td>41</td><td>8.0</td><td>26.0 cm</td></tr>
                <tr><td>42</td><td>8.5</td><td>26.5 cm</td></tr>
                <tr><td>43</td><td>9.5</td><td>27.5 cm</td></tr>
                <tr><td>44</td><td>10.0</td><td>28.0 cm</td></tr>
            </tbody>
        </table>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        const initialProducts = @json($products);
        let products = initialProducts, cart = @json($cartLines), selected = null, selectedVariant = null, selectedSize = '', selectedColor = '';
        const money = value => new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND', maximumFractionDigits:0}).format(value);
        const html = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        const grid = document.getElementById('productGrid');

        function renderProducts(items) {
            document.getElementById('resultCount').textContent = String(items.length).padStart(2,'0') + ' SẢN PHẨM ĐƯỢC CHỌN';
            grid.innerHTML = items.length ? items.map(p => {
                const totalStock = (p.variants || []).reduce((s, v) => s + (v.stock || 0), 0);
                let stockClass = '';
                let stockText = '';
                if (totalStock > 5) {
                    stockText = `✓ Còn ${totalStock} đôi`;
                } else if (totalStock > 0) {
                    stockClass = 'warning';
                    stockText = `⚡ Sắp hết hàng (${totalStock})`;
                } else {
                    stockClass = 'danger';
                    stockText = `Tạm hết hàng`;
                }

                return `
                <article class="product-card">
                    <div class="product-visual">
                        <span class="product-badge">${html(p.badge || 'Chính hãng')}</span>
                        <button class="wish" aria-label="Yêu thích" data-wish="${Number(p.id)}">♡</button>
                        <img src="${html(p.image)}" alt="${html(p.name)}">
                    </div>
                    <div class="product-meta">
                        <div>
                            <div class="product-category-tag">${html(p.category || 'Bóng đá')}</div>
                            <h3 class="product-title">${html(p.name)}</h3>
                            <div class="product-price-row">
                                <span class="product-price">${money(p.price)}</span>
                                ${p.oldPrice ? `<span class="old-price">${money(p.oldPrice)}</span>` : ''}
                            </div>
                            <div class="stock-tag ${stockClass}">${stockText}</div>
                        </div>
                        <button class="btn-view-product" data-add="${Number(p.id)}">CHỌN SIZE / CHI TIẾT →</button>
                    </div>
                </article>`;
            }).join('') : '<div class="no-products">Không tìm thấy sản phẩm phù hợp. Thử một bộ lọc khác nhé.</div>';
        }
        renderProducts(products);

        async function getProducts(params = {}) {
            const url = new URL('{{ route('store.products') }}', window.location.origin);
            Object.entries(params).forEach(([k,v]) => v && url.searchParams.set(k,v));
            const result = await fetch(url).then(r => r.json());
            products = result.data;
            renderProducts(products);
        }

        document.querySelectorAll('.filter-pill').forEach(btn => btn.addEventListener('click', async () => {
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            await getProducts(btn.dataset.filter === 'all' ? {} : {
                brand: btn.dataset.filter,
                category: btn.dataset.category,
                color: btn.dataset.color,
                max: btn.dataset.max
            });
        }));

        document.getElementById('resetFilter').addEventListener('click', e => {
            e.preventDefault();
            document.querySelector('[data-filter="all"]').click();
            document.getElementById('search').value = '';
        });

        let debounce;
        document.getElementById('search').addEventListener('input', e => {
            clearTimeout(debounce);
            debounce = setTimeout(() => getProducts({q: e.target.value}), 220);
        });

        function find(id) {
            return initialProducts.find(p => p.id === Number(id));
        }

        function showToast(message) {
            const el = document.getElementById('toast');
            el.textContent = message;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 2600);
        }

        function openProduct(id) {
            selected = find(id);
            selectedVariant = (selected?.variants || []).find(v => v.stock > 0) || (selected?.variants || [])[0] || null;
            if (!selectedVariant) {
                showToast('Sản phẩm hiện chưa có sẵn biến thể.');
                return;
            }
            selectedColor = selectedVariant.color;
            selectedSize = selectedVariant.size;
            document.getElementById('modalImage').src = selected.image;
            document.getElementById('modalImage').alt = selected.name;
            document.getElementById('modalCategory').textContent = (selected.category || '') + ' / ' + (selected.brand || '').toUpperCase();
            document.getElementById('modalName').textContent = selected.name;
            document.getElementById('productQuantity').value = 1;
            renderVariantChoices();
            document.getElementById('modalBackdrop').classList.add('show');
            document.getElementById('productModal').classList.add('show');
        }

        function renderVariantChoices() {
            const variants = selected?.variants || [];
            const colors = [...new Set(variants.map(v => v.color))];

            document.getElementById('colorChoices').innerHTML = colors.map(c =>
                `<button class="choice-chip ${c === selectedColor ? 'active' : ''}" data-color="${html(c)}">${html(c)}</button>`
            ).join('');

            const sizes = variants.filter(v => v.color === selectedColor);
            if (!sizes.some(v => v.size === selectedSize)) {
                selectedSize = sizes.find(v => v.stock > 0)?.size || sizes[0]?.size || '';
            }
            selectedVariant = sizes.find(v => v.size === selectedSize) || null;

            document.getElementById('sizeChoices').innerHTML = sizes.map(v => {
                const low = v.stock > 0 && v.stock <= 3;
                return `<button class="choice-chip ${v.size === selectedSize ? 'active' : ''}" data-size="${html(v.size)}" ${v.stock < 1 ? 'disabled' : ''}>
                    Size ${html(v.size)}
                    ${low ? '<span class="chip-badge">(Còn ít)</span>' : ''}
                </button>`;
            }).join('');

            document.getElementById('chosenColor').textContent = selectedColor;
            document.getElementById('chosenSize').textContent = selectedSize ? 'Size ' + selectedSize : '';

            const price = document.getElementById('modalPrice');
            price.innerHTML = selectedVariant ? `${money(selectedVariant.price)}${selected.oldPrice ? `<span class="old-price" style="margin-left:10px">${money(selected.oldPrice)}</span>` : ''}` : 'Hết hàng';

            const stockEl = document.getElementById('stockMessage');
            if (selectedVariant && selectedVariant.stock > 0) {
                stockEl.textContent = `✓ Còn ${selectedVariant.stock} đôi sẵn sàng`;
                stockEl.style.color = 'var(--neon-green)';
            } else {
                stockEl.textContent = '⚠ Biến thể đã hết hàng';
                stockEl.style.color = 'var(--error-text)';
            }

            document.getElementById('skuIndicator').textContent = selectedVariant ? `Mã SKU: ${selectedVariant.sku}` : '';
            document.getElementById('productQuantity').max = selectedVariant?.stock || 0;
            updateModalActions();
        }

        grid.addEventListener('click', e => {
            const add = e.target.closest('[data-add]');
            if (add) openProduct(add.dataset.add);
            const wish = e.target.closest('[data-wish]');
            if (wish) {
                wish.classList.toggle('loved');
                wish.textContent = wish.classList.contains('loved') ? '♥' : '♡';
            }
        });

        document.getElementById('colorChoices').addEventListener('click', e => {
            const b = e.target.closest('[data-color]');
            if (!b) return;
            selectedColor = b.dataset.color;
            selectedSize = '';
            renderVariantChoices();
        });

        document.getElementById('sizeChoices').addEventListener('click', e => {
            const b = e.target.closest('[data-size]');
            if (!b) return;
            selectedSize = b.dataset.size;
            renderVariantChoices();
        });

        async function cartRequest(url, method, body) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: body ? JSON.stringify(body) : null
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Không thể cập nhật giỏ hàng');
            return data;
        }

        function selectedQuantity() {
            if (!selectedVariant || selectedVariant.stock < 1) throw new Error('Biến thể đã chọn hiện hết hàng.');
            const quantity = Number(document.getElementById('productQuantity').value);
            if (!Number.isInteger(quantity) || quantity < 1) throw new Error('Số lượng không hợp lệ.');
            if (quantity > selectedVariant.stock) throw new Error('Số lượng vượt quá tồn kho.');
            return quantity;
        }

        function updateModalActions() {
            let valid = true;
            try { selectedQuantity(); } catch (error) { valid = false; }
            document.getElementById('modalAdd').disabled = !valid;
            document.getElementById('buyNow').disabled = !valid;
        }
        document.getElementById('productQuantity').addEventListener('input', updateModalActions);

        async function addCurrent() {
            try {
                const quantity = selectedQuantity();
                await cartRequest('{{ route('cart.add') }}', 'POST', {
                    product_variant_id: selectedVariant.id,
                    quantity
                });
                const line = cart.find(x => x.variantId === selectedVariant.id);
                if (line) line.qty += quantity;
                else cart.push({
                    key: String(selectedVariant.id),
                    variantId: selectedVariant.id,
                    name: selected.name,
                    color: selectedVariant.color,
                    size: selectedVariant.size,
                    price: selectedVariant.price,
                    qty: quantity,
                    stock: selectedVariant.stock,
                    image: selected.image,
                    selected: true,
                    available: true
                });
                renderCart();
                showToast('✓ Đã thêm vào giỏ hàng thành công.');
                return true;
            } catch (error) {
                showToast(error.message || 'Không thể thêm sản phẩm.');
                return false;
            }
        }

        document.getElementById('modalAdd').addEventListener('click', async () => {
            if (await addCurrent()) closeModal();
        });
        document.getElementById('buyNow').addEventListener('click', async () => {
            if (await addCurrent()) window.location.href = '{{ route('checkout') }}';
        });

        function renderCart() {
            const qty = cart.reduce((s, x) => s + x.qty, 0);
            const valid = cart.filter(x => x.available !== false && x.qty <= x.stock);
            const subtotal = valid.reduce((s, x) => s + x.price * x.qty, 0);
            const chosen = valid.filter(x => x.selected !== false);
            const selectedQty = chosen.reduce((s, x) => s + x.qty, 0);
            const selectedTotal = chosen.reduce((s, x) => s + x.price * x.qty, 0);

            document.getElementById('cartCount').textContent = qty;
            document.getElementById('drawerQty').textContent = qty;
            document.getElementById('cartLines').innerHTML = cart.length ? cart.map(x => `
                <div class="cart-line">
                    <input class="cart-check" type="checkbox" data-select="${Number(x.key)}" ${x.selected !== false && x.available !== false ? 'checked' : ''} ${x.available === false || x.qty > x.stock ? 'disabled' : ''}>
                    <img src="${html(x.image)}" alt="${html(x.name)}">
                    <div>
                        <h4>${html(x.name)}</h4>
                        <p>${html(x.color)} / Size ${html(x.size)}</p>
                        <div class="price">${money(x.price)}</div>
                        <div class="quantity">
                            <button data-change="${Number(x.key)}" data-delta="-1">−</button>
                            <span>${Number(x.qty)}</span>
                            <button data-change="${Number(x.key)}" data-delta="1" ${x.qty >= x.stock ? 'disabled' : ''}>+</button>
                        </div>
                        ${x.available === false || x.qty > x.stock ? '<div class="cart-warning">Số lượng vượt quá tồn kho.</div>' : ''}
                    </div>
                    <button class="delete-line" data-delete="${Number(x.key)}" aria-label="Xóa">×</button>
                </div>
            `).join('') : '<div class="cart-empty">Giỏ hàng đang trống.<br>Chọn món đồ giúp bạn chơi hay hơn.</div>';

            const all = document.getElementById('selectAll');
            all.checked = valid.length > 0 && chosen.length === valid.length;
            all.indeterminate = chosen.length > 0 && chosen.length < valid.length;
            document.getElementById('selectedCount').textContent = `Đã chọn ${selectedQty} sản phẩm`;
            document.getElementById('subtotal').textContent = money(subtotal);
            document.getElementById('total').textContent = money(selectedTotal);
            document.getElementById('checkout').setAttribute('aria-disabled', selectedQty === 0 ? 'true' : 'false');
        }

        document.getElementById('cartLines').addEventListener('click', async e => {
            const c = e.target.closest('[data-change]');
            const d = e.target.closest('[data-delete]');
            try {
                if (c) {
                    const i = cart.findIndex(x => x.key === c.dataset.change);
                    const delta = Number(c.dataset.delta);
                    const action = delta > 0 ? 'increase' : 'decrease';
                    await cartRequest(`/cart/items/${cart[i].variantId}/${action}`, 'PATCH');
                    cart[i].qty += delta;
                    if (cart[i].qty < 1) cart.splice(i, 1);
                    renderCart();
                }
                if (d) {
                    const i = cart.findIndex(x => x.key === d.dataset.delete);
                    await cartRequest(`/cart/items/${cart[i].variantId}`, 'DELETE');
                    cart.splice(i, 1);
                    renderCart();
                    showToast('Đã xóa sản phẩm khỏi giỏ');
                }
            } catch (error) {
                showToast(error.message);
            }
        });

        document.getElementById('cartLines').addEventListener('change', async e => {
            const box = e.target.closest('[data-select]');
            if (!box) return;
            const i = cart.findIndex(x => x.key === box.dataset.select);
            try {
                await cartRequest(`/cart/items/${cart[i].variantId}/selection`, 'PATCH', {selected: box.checked});
                cart[i].selected = box.checked;
                renderCart();
                showToast('Đã cập nhật giỏ hàng.');
            } catch (error) {
                box.checked = !box.checked;
                showToast(error.message);
            }
        });

        document.getElementById('selectAll').addEventListener('change', async e => {
            try {
                await cartRequest('/cart/selection', 'PATCH', {selected: e.target.checked});
                cart.forEach(x => x.selected = e.target.checked && x.available !== false && x.qty <= x.stock);
                renderCart();
                showToast('Đã cập nhật giỏ hàng.');
            } catch (error) {
                showToast(error.message);
                renderCart();
            }
        });

        const backdrop = document.getElementById('drawerBackdrop');
        const drawer = document.getElementById('cartDrawer');
        function openCart() { backdrop.classList.add('show'); drawer.classList.add('show'); }
        function closeCart() { backdrop.classList.remove('show'); drawer.classList.remove('show'); }
        document.getElementById('openCart').addEventListener('click', openCart);
        document.getElementById('closeCart').addEventListener('click', closeCart);
        backdrop.addEventListener('click', closeCart);

        function closeModal() {
            document.getElementById('modalBackdrop').classList.remove('show');
            document.getElementById('productModal').classList.remove('show');
        }
        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('modalBackdrop').addEventListener('click', closeModal);
        document.getElementById('checkout').addEventListener('click', () => {
            cart.some(x => x.selected !== false && x.available !== false && x.qty <= x.stock)
                ? window.location.href = '{{ route('checkout') }}'
                : showToast('Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
        });

        // Size Guide Trigger
        const sizeGuideModal = document.getElementById('sizeGuideModal');
        document.getElementById('openSizeGuide').addEventListener('click', () => sizeGuideModal.classList.add('show'));
        document.getElementById('closeSizeGuide').addEventListener('click', () => sizeGuideModal.classList.remove('show'));

        document.getElementById('newsletter').addEventListener('submit', e => {
            e.preventDefault();
            document.getElementById('newsletterMessage').textContent = 'Đã vào đội hình. Hẹn gặp bạn ở hộp thư!';
            e.target.reset();
        });

        setTimeout(() => document.getElementById('orderSuccess')?.remove(), 6500);

        const heroPhoto = document.querySelector('.hero-photo');
        window.addEventListener('scroll', () => {
            if (heroPhoto) heroPhoto.style.transform = `translateY(${Math.min(window.scrollY * .13, 65)}px) scale(1.02)`;
        }, {passive: true});

        renderCart();
    </script>
</body>
</html>
