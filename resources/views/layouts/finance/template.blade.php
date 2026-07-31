<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-finance-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Finance Dashboard'))</title>

    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}">

    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-finance-theme', 'light');
                document.documentElement.style.colorScheme = 'light';

                const collapsed = localStorage.getItem('finance-sidebar-collapsed') === 'true';

                if (collapsed) {
                    document.documentElement.classList.add('finance-sidebar-collapsed-preload');
                }
            } catch (error) {
                document.documentElement.setAttribute('data-finance-theme', 'light');
            }
        })();
    </script>

    @stack('head')

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Icons --}}
    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

    {{-- Existing project styles --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ecobank-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style1.css') }}">

    {{-- DataTables (only pulled in by pages that @push('datatables-styles')) --}}
    @stack('datatables-styles')

    {{-- Toastr --}}
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">

    <style>
        /* =========================================================
           FINANCE APPLICATION DESIGN SYSTEM
           ========================================================= */

        :root {
            --finance-sidebar-width: 270px;
            --finance-sidebar-collapsed-width: 78px;
            --finance-header-height: 70px;

            --finance-bg: #f3f6fa;
            --finance-bg-secondary: #edf2f7;

            --finance-surface: #ffffff;
            --finance-surface-soft: #f8fafc;
            --finance-surface-elevated: #ffffff;

            --finance-text: #14213a;
            --finance-text-strong: #081b33;
            --finance-muted: #667085;
            --finance-muted-soft: #98a2b3;

            --finance-border: #e2e8f0;
            --finance-border-strong: #d4dce7;

            --finance-primary: #0875e1;
            --finance-primary-dark: #0057b7;
            --finance-primary-soft: rgba(8, 117, 225, .10);

            --finance-green: #00a86b;
            --finance-green-soft: rgba(0, 168, 107, .12);

            --finance-red: #dc3545;
            --finance-red-soft: rgba(220, 53, 69, .10);

            --finance-gold: #f5b700;
            --finance-gold-soft: rgba(245, 183, 0, .14);

            --finance-cyan: #11b8d4;
            --finance-purple: #7c5ce5;

            --finance-radius-xs: 7px;
            --finance-radius-sm: 10px;
            --finance-radius-md: 14px;
            --finance-radius-lg: 18px;
            --finance-radius-xl: 24px;

            --finance-shadow-xs:
                0 1px 2px rgba(15, 23, 42, .03);

            --finance-shadow-sm:
                0 4px 16px rgba(15, 23, 42, .055);

            --finance-shadow-md:
                0 12px 34px rgba(15, 23, 42, .09);

            --finance-shadow-lg:
                0 24px 60px rgba(15, 23, 42, .14);

            --finance-transition:
                .22s cubic-bezier(.4, 0, .2, 1);
        }

        html[data-finance-theme="dark"] {
            --finance-bg: #061727;
            --finance-bg-secondary: #081d30;

            --finance-surface: #0b2237;
            --finance-surface-soft: #0d2942;
            --finance-surface-elevated: #102d47;

            --finance-text: #e9f3fb;
            --finance-text-strong: #ffffff;
            --finance-muted: #9ab0c3;
            --finance-muted-soft: #71889c;

            --finance-border: rgba(163, 190, 214, .14);
            --finance-border-strong: rgba(163, 190, 214, .22);

            --finance-primary: #2b91f4;
            --finance-primary-dark: #0875e1;
            --finance-primary-soft: rgba(43, 145, 244, .14);

            --finance-green: #20cf8b;
            --finance-green-soft: rgba(32, 207, 139, .13);

            --finance-red: #ff6b78;
            --finance-red-soft: rgba(255, 107, 120, .13);

            --finance-gold: #ffc83d;
            --finance-gold-soft: rgba(255, 200, 61, .15);

            --finance-shadow-xs:
                0 1px 2px rgba(0, 0, 0, .15);

            --finance-shadow-sm:
                0 8px 24px rgba(0, 0, 0, .16);

            --finance-shadow-md:
                0 16px 42px rgba(0, 0, 0, .22);

            --finance-shadow-lg:
                0 28px 70px rgba(0, 0, 0, .30);
        }

        /* =========================================================
           BASE
           ========================================================= */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
        }

        body.finance-body {
            min-height: 100vh;
            margin: 0;
            color: var(--finance-text);
            background:
                radial-gradient(
                    circle at 0% 0%,
                    rgba(8, 117, 225, .08),
                    transparent 28rem
                ),
                radial-gradient(
                    circle at 100% 0%,
                    rgba(0, 168, 107, .07),
                    transparent 30rem
                ),
                var(--finance-bg);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            transition:
                background-color var(--finance-transition),
                color var(--finance-transition);
        }

        html[data-finance-theme="dark"] body.finance-body {
            background:
                radial-gradient(
                    circle at 4% 0%,
                    rgba(8, 117, 225, .12),
                    transparent 34rem
                ),
                radial-gradient(
                    circle at 100% 0%,
                    rgba(0, 168, 107, .08),
                    transparent 32rem
                ),
                linear-gradient(
                    145deg,
                    var(--finance-bg),
                    var(--finance-bg-secondary)
                );
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        ::selection {
            color: #ffffff;
            background: var(--finance-primary);
        }

        /* =========================================================
           ACCESSIBILITY
           ========================================================= */

        .finance-app a:focus-visible,
        .finance-app button:focus-visible,
        .finance-app input:focus-visible,
        .finance-app select:focus-visible,
        .finance-app textarea:focus-visible {
            outline: 3px solid rgba(245, 183, 0, .72);
            outline-offset: 2px;
        }

        .finance-sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        /* =========================================================
           APPLICATION SHELL
           ========================================================= */

        .main-wrapper.finance-app {
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .finance-page-wrapper {
            min-height: 100vh;
            margin-left: var(--finance-sidebar-width);
            padding-top: var(--finance-header-height);
            transition:
                margin-left var(--finance-transition),
                background-color var(--finance-transition);
        }

        .finance-content {
            width: 100%;
            padding: 24px 26px 34px;
        }

        .finance-page-shell {
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
        }

        body.finance-sidebar-collapsed .finance-page-wrapper,
        html.finance-sidebar-collapsed-preload .finance-page-wrapper {
            margin-left: var(--finance-sidebar-collapsed-width);
        }

        /* =========================================================
           EXISTING HEADER SUPPORT
           ========================================================= */

        .finance-app > .header {
            position: fixed !important;
            top: 0 !important;
            left: var(--finance-sidebar-width) !important;
            right: 0 !important;
            width: auto !important;
            min-height: var(--finance-header-height);
            z-index: 1035;
            transition:
                left var(--finance-transition),
                background-color var(--finance-transition),
                border-color var(--finance-transition);
        }

        body.finance-sidebar-collapsed .finance-app > .header,
        html.finance-sidebar-collapsed-preload .finance-app > .header {
            left: var(--finance-sidebar-collapsed-width) !important;
        }

        html[data-finance-theme="dark"] .finance-app > .header {
            color: var(--finance-text);
            background: rgba(6, 23, 39, .92) !important;
            border-bottom-color: var(--finance-border) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .13);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        html[data-finance-theme="dark"] .finance-app > .header a,
        html[data-finance-theme="dark"] .finance-app > .header button,
        html[data-finance-theme="dark"] .finance-app > .header .user-name,
        html[data-finance-theme="dark"] .finance-app > .header .nav-link {
            color: var(--finance-text);
        }

        /* =========================================================
           PAGE HEADERS
           ========================================================= */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            margin-bottom: 20px;
            color: var(--finance-text);
            background: var(--finance-surface);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-md);
            box-shadow: var(--finance-shadow-sm);
            transition:
                background-color var(--finance-transition),
                border-color var(--finance-transition),
                color var(--finance-transition);
        }

        .page-title,
        .page-header h1,
        .page-header h2,
        .page-header h3,
        .page-header h4,
        .page-header h5 {
            margin: 0;
            color: var(--finance-text-strong);
            font-weight: 800;
            letter-spacing: -.025em;
        }

        .page-header .breadcrumb {
            margin: 4px 0 0;
        }

        .page-header .breadcrumb-item,
        .page-header .breadcrumb-item a,
        .page-header .text-muted {
            color: var(--finance-muted) !important;
        }

        /* =========================================================
           CARDS
           ========================================================= */

        .card {
            color: var(--finance-text);
            background: var(--finance-surface);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-md);
            box-shadow: var(--finance-shadow-sm);
            overflow: hidden;
            transition:
                background-color var(--finance-transition),
                border-color var(--finance-transition),
                box-shadow var(--finance-transition),
                transform var(--finance-transition);
        }

        .card.finance-hover-card:hover {
            border-color: var(--finance-border-strong);
            box-shadow: var(--finance-shadow-md);
            transform: translateY(-2px);
        }

        .card-header {
            min-height: 60px;
            padding: 15px 18px;
            color: var(--finance-text);
            background:
                linear-gradient(
                    180deg,
                    var(--finance-surface-soft),
                    var(--finance-surface)
                );
            border-bottom: 1px solid var(--finance-border);
        }

        .card-body {
            padding: 18px;
        }

        .card-footer {
            padding: 14px 18px;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border-top: 1px solid var(--finance-border);
        }

        .card-title {
            margin-bottom: 0;
            color: var(--finance-text-strong);
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .card .text-muted,
        .text-muted {
            color: var(--finance-muted) !important;
        }

        .stat-card,
        .metric-card,
        .dashboard-card {
            height: 100%;
            padding: 18px;
            color: var(--finance-text);
            background: var(--finance-surface);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-md);
            box-shadow: var(--finance-shadow-sm);
            transition:
                transform var(--finance-transition),
                box-shadow var(--finance-transition),
                border-color var(--finance-transition),
                background-color var(--finance-transition);
        }

        .stat-card:hover,
        .metric-card:hover {
            transform: translateY(-2px);
            border-color: var(--finance-border-strong);
            box-shadow: var(--finance-shadow-md);
        }

        /* =========================================================
           BUTTONS
           ========================================================= */

        .btn {
            min-height: 40px;
            border-radius: var(--finance-radius-sm);
            font-weight: 700;
            box-shadow: none !important;
            transition:
                transform .15s ease,
                background-color .15s ease,
                border-color .15s ease,
                color .15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            border-color: transparent;
            background:
                linear-gradient(
                    135deg,
                    var(--finance-primary),
                    var(--finance-primary-dark)
                );
        }

        .btn-primary:hover,
        .btn-primary:focus {
            border-color: transparent;
            background:
                linear-gradient(
                    135deg,
                    #1686f1,
                    var(--finance-primary-dark)
                );
        }

        .btn-success {
            border-color: transparent;
            background:
                linear-gradient(
                    135deg,
                    var(--finance-green),
                    #008556
                );
        }

        .btn-outline-primary {
            color: var(--finance-primary);
            border-color: rgba(8, 117, 225, .38);
        }

        .btn-outline-primary:hover {
            color: #ffffff;
            background: var(--finance-primary);
            border-color: var(--finance-primary);
        }

        html[data-finance-theme="dark"] .btn-light {
            color: var(--finance-text);
            background: var(--finance-surface-soft);
            border-color: var(--finance-border);
        }

        /* =========================================================
           FORMS
           ========================================================= */

        .form-label {
            margin-bottom: 6px;
            color: var(--finance-text);
            font-size: 12.5px;
            font-weight: 700;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 42px;
            color: var(--finance-text);
            background-color: var(--finance-surface);
            border-color: var(--finance-border);
            border-radius: var(--finance-radius-sm);
            transition:
                border-color .15s ease,
                box-shadow .15s ease,
                background-color var(--finance-transition),
                color var(--finance-transition);
        }

        .form-control::placeholder {
            color: var(--finance-muted-soft);
        }

        .form-control:focus,
        .form-select:focus {
            color: var(--finance-text);
            background-color: var(--finance-surface);
            border-color: rgba(8, 117, 225, .58);
            box-shadow: 0 0 0 .22rem rgba(8, 117, 225, .11);
        }

        .form-control:disabled,
        .form-select:disabled {
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
        }

        html[data-finance-theme="dark"] .form-select {
            color-scheme: dark;
        }

        /* =========================================================
           TABLES
           ========================================================= */

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--finance-text);
            --bs-table-border-color: var(--finance-border);
            --bs-table-hover-bg: var(--finance-primary-soft);
            --bs-table-hover-color: var(--finance-text);
            margin-bottom: 0;
            color: var(--finance-text);
        }

        .table > :not(caption) > * > * {
            padding: 12px 14px;
            color: var(--finance-text);
            background-color: transparent;
            border-bottom-color: var(--finance-border);
            vertical-align: middle;
        }

        .table thead th {
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .055em;
        }

        .table-responsive {
            border-radius: var(--finance-radius-sm);
        }

        /* =========================================================
           DATATABLES
           ========================================================= */

        .dataTables_wrapper {
            width: 100%;
            color: var(--finance-text);
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--finance-muted) !important;
            font-size: 12.5px;
        }

        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 14px;
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 14px;
        }

        .dataTables_filter input,
        .dataTables_length select {
            min-height: 38px;
            margin-left: 6px;
            padding: 7px 11px;
            color: var(--finance-text);
            background: var(--finance-surface);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-sm);
            outline: none;
        }

        .dataTables_filter input:focus,
        .dataTables_length select:focus {
            border-color: rgba(8, 117, 225, .58);
            box-shadow: 0 0 0 .2rem rgba(8, 117, 225, .09);
        }

        table.dataTable {
            width: 100% !important;
            color: var(--finance-text);
            background: transparent;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        table.dataTable.no-footer {
            border-bottom-color: var(--finance-border);
        }

        table.dataTable thead th,
        table.dataTable thead td {
            padding: 12px 14px;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border-bottom: 1px solid var(--finance-border) !important;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            padding: 12px 14px;
            color: var(--finance-text);
            background: transparent;
            border-bottom: 1px solid var(--finance-border);
            font-size: 13px;
            vertical-align: middle;
        }

        table.dataTable tbody tr {
            background: transparent;
            transition: background-color .14s ease;
        }

        table.dataTable tbody tr:hover {
            background: var(--finance-primary-soft);
        }

        table.dataTable tbody tr.odd,
        table.dataTable tbody tr.even {
            background: transparent;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            min-width: 36px;
            min-height: 36px;
            margin: 0 2px;
            padding: 7px 10px !important;
            color: var(--finance-muted) !important;
            background: transparent !important;
            border: 1px solid transparent !important;
            border-radius: 9px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: var(--finance-primary) !important;
            background: var(--finance-primary-soft) !important;
            border-color: var(--finance-border) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #ffffff !important;
            background: var(--finance-primary) !important;
            border-color: var(--finance-primary) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: var(--finance-muted-soft) !important;
            opacity: .5;
        }

        .dt-buttons {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 14px;
        }

        .dt-buttons .dt-button,
        button.dt-button {
            min-height: 38px;
            margin: 0 !important;
            padding: 7px 13px !important;
            color: var(--finance-primary) !important;
            background: var(--finance-primary-soft) !important;
            border: 1px solid transparent !important;
            border-radius: var(--finance-radius-sm) !important;
            box-shadow: none !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            transition: all .15s ease !important;
        }

        .dt-buttons .dt-button:hover,
        button.dt-button:hover {
            color: #ffffff !important;
            background: var(--finance-primary) !important;
            border-color: var(--finance-primary) !important;
        }

        .dataTables_processing {
            top: 50% !important;
            left: 50% !important;
            width: auto !important;
            min-width: 180px;
            margin: 0 !important;
            padding: 14px 18px !important;
            color: var(--finance-text) !important;
            background: var(--finance-surface-elevated) !important;
            border: 1px solid var(--finance-border) !important;
            border-radius: var(--finance-radius-md) !important;
            box-shadow: var(--finance-shadow-md) !important;
            transform: translate(-50%, -50%);
        }

        /* =========================================================
           MODALS / DROPDOWNS / OFFCANVAS
           ========================================================= */

        .modal-content,
        .dropdown-menu,
        .offcanvas,
        .popover {
            color: var(--finance-text);
            background: var(--finance-surface-elevated);
            border-color: var(--finance-border);
        }

        .modal-content {
            border-radius: var(--finance-radius-lg);
            box-shadow: var(--finance-shadow-lg);
        }

        .modal-header,
        .modal-footer {
            border-color: var(--finance-border);
        }

        .dropdown-menu {
            padding: 7px;
            border-radius: var(--finance-radius-md);
            box-shadow: var(--finance-shadow-md);
        }

        .dropdown-item {
            color: var(--finance-text);
            border-radius: 8px;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: var(--finance-primary);
            background: var(--finance-primary-soft);
        }

        html[data-finance-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(180%);
        }

        /* =========================================================
           NAV TABS / PILLS
           ========================================================= */

        .nav-tabs {
            border-bottom-color: var(--finance-border);
        }

        .nav-tabs .nav-link {
            color: var(--finance-muted);
            border-color: transparent;
            border-radius: var(--finance-radius-sm) var(--finance-radius-sm) 0 0;
            font-weight: 700;
        }

        .nav-tabs .nav-link:hover {
            color: var(--finance-primary);
            border-color: transparent;
            background: var(--finance-primary-soft);
        }

        .nav-tabs .nav-link.active {
            color: var(--finance-primary);
            background: var(--finance-surface);
            border-color:
                var(--finance-border)
                var(--finance-border)
                var(--finance-surface);
        }

        /* =========================================================
           BADGES
           ========================================================= */

        .finance-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1;
        }

        .finance-badge-success {
            color: var(--finance-green);
            background: var(--finance-green-soft);
        }

        .finance-badge-warning {
            color: #ad7d00;
            background: var(--finance-gold-soft);
        }

        .finance-badge-danger {
            color: var(--finance-red);
            background: var(--finance-red-soft);
        }

        .finance-badge-info {
            color: var(--finance-primary);
            background: var(--finance-primary-soft);
        }

        /* =========================================================
           GLOBAL LOADER
           ========================================================= */

        #global-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: grid;
            place-items: center;
            color: var(--finance-text);
            background: var(--finance-bg);
            transition:
                opacity .24s ease,
                visibility .24s ease;
        }

        #global-loader.finance-loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .finance-loader-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 13px;
        }

        .finance-loader-ring {
            position: relative;
            width: 44px;
            height: 44px;
        }

        .finance-loader-ring::before,
        .finance-loader-ring::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
        }

        .finance-loader-ring::before {
            border: 3px solid var(--finance-primary-soft);
        }

        .finance-loader-ring::after {
            border: 3px solid transparent;
            border-top-color: var(--finance-primary);
            animation: financeSpin .7s linear infinite;
        }

        .finance-loader-label {
            color: var(--finance-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        @keyframes financeSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* =========================================================
           MOBILE BACKDROP
           ========================================================= */

        .finance-sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            display: block;
            opacity: 0;
            visibility: hidden;
            background: rgba(1, 12, 24, .62);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            transition:
                opacity var(--finance-transition),
                visibility var(--finance-transition);
        }

        body.finance-sidebar-open .finance-sidebar-backdrop {
            opacity: 1;
            visibility: visible;
        }

        /* =========================================================
           SCROLLBARS
           ========================================================= */

        body::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        body::-webkit-scrollbar-track {
            background: var(--finance-bg);
        }

        body::-webkit-scrollbar-thumb {
            background: var(--finance-border-strong);
            border: 3px solid var(--finance-bg);
            border-radius: 999px;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */

        @media (max-width: 1199.98px) and (min-width: 992px) {
            :root {
                --finance-sidebar-width: 252px;
            }

            .finance-content {
                padding: 20px 20px 30px;
            }
        }

        @media (max-width: 991.98px) {
            .finance-page-wrapper,
            body.finance-sidebar-collapsed .finance-page-wrapper,
            html.finance-sidebar-collapsed-preload .finance-page-wrapper {
                margin-left: 0;
            }

            .finance-content {
                padding: 18px 16px 28px;
            }

            .finance-app > .header,
            body.finance-sidebar-collapsed .finance-app > .header,
            html.finance-sidebar-collapsed-preload .finance-app > .header {
                left: 0 !important;
                width: 100% !important;
            }

            body.finance-sidebar-open {
                overflow: hidden;
            }
        }

        @media (max-width: 767.98px) {
            :root {
                --finance-header-height: 64px;
            }

            .finance-content {
                padding: 14px 12px 24px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 15px;
            }

            .card-header,
            .card-body,
            .card-footer {
                padding-left: 14px;
                padding-right: 14px;
            }

            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length {
                float: none;
                text-align: left;
            }

            .dataTables_filter input {
                width: calc(100% - 70px);
                max-width: none;
            }
        }

        @media (max-width: 575.98px) {
            .finance-content {
                padding: 12px 10px 22px;
            }

            .card,
            .page-header {
                border-radius: 12px;
            }

            .dt-buttons {
                width: 100%;
            }

            .dt-buttons .dt-button {
                flex: 1 1 auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>

    {{-- Compatibility with existing child pages --}}
    @yield('extracss')
    @stack('styles')
</head>

<body class="finance-body">

    {{-- Global loader --}}
    <div id="global-loader" role="status" aria-label="Loading page">
        <div class="finance-loader-box">
            <div class="finance-loader-ring" aria-hidden="true"></div>
            <span class="finance-loader-label">Loading Finance</span>
        </div>
    </div>

    <div class="main-wrapper finance-app">

        {{-- Existing application header --}}
        @include('layouts.headerv4')

        {{-- New finance sidebar --}}
        @include('layouts.finance.side')

        {{-- Mobile sidebar backdrop --}}
        <div
            class="finance-sidebar-backdrop"
            data-finance-sidebar-close
            aria-hidden="true"
        ></div>

        <main
            class="page-wrapper finance-page-wrapper"
            id="main-content"
            tabindex="-1"
        >
            <div class="content content-fluid finance-content">
                <div class="finance-page-shell">
                    @yield('content')
                </div>
            </div>
        </main>

    </div>

    {{-- =========================================================
         SCRIPTS
         ========================================================= --}}

    {{-- jQuery --}}
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>

    {{-- Bootstrap 5 --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Utilities --}}
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>

    {{-- DataTables + moment (only pulled in by pages that @push('datatables-scripts') —
         pdfmake/vfs_fonts alone are ~2MB and only 2 of ~23 Finance pages use DataTables at all) --}}
    @stack('datatables-scripts')

    {{-- Toastr --}}
    <script src="{{ asset('js/toastr.min.js') }}"></script>

    <script>
        if (window.toastr) {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                preventDuplicates: true,
                timeOut: 5000,
                extendedTimeOut: 1500,
                positionClass: 'toast-top-right',
                escapeHtml: false
            };
        }
    </script>

    {{-- Existing template JavaScript --}}
    <script src="{{ asset('assets/js/script.js') }}"></script>

    {{-- Finance application controller --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            'use strict';

            const html = document.documentElement;
            const body = document.body;
            const sidebar = document.getElementById('financeSidebar');
            const loader = document.getElementById('global-loader');

            const desktopBreakpoint = 992;

            /* =====================================================
               GLOBAL LOADER
               ===================================================== */

            function hideFinanceLoader() {
                if (!loader) {
                    return;
                }

                loader.classList.add('finance-loader-hidden');

                window.setTimeout(function () {
                    loader.style.display = 'none';
                }, 300);
            }

            window.requestAnimationFrame(function () {
                window.setTimeout(hideFinanceLoader, 120);
            });

            window.addEventListener('load', hideFinanceLoader);

            window.setTimeout(hideFinanceLoader, 3500);

            /* =====================================================
               FEATHER ICONS
               ===================================================== */

            if (window.feather) {
                window.feather.replace();
            }

            /* =====================================================
               SIDEBAR COLLAPSE
               ===================================================== */

            const collapseButtons = document.querySelectorAll(
                '[data-finance-sidebar-collapse]'
            );

            function isSidebarCollapsed() {
                return body.classList.contains(
                    'finance-sidebar-collapsed'
                );
            }

            function updateCollapseControls(collapsed) {
                collapseButtons.forEach(function (button) {
                    button.setAttribute(
                        'aria-expanded',
                        collapsed ? 'false' : 'true'
                    );

                    button.setAttribute(
                        'aria-label',
                        collapsed
                            ? 'Expand finance sidebar'
                            : 'Collapse finance sidebar'
                    );

                    const icon = button.querySelector(
                        '[data-finance-collapse-icon]'
                    );

                    const label = button.querySelector(
                        '[data-finance-collapse-label]'
                    );

                    if (icon) {
                        icon.className = collapsed
                            ? 'ti ti-chevrons-right'
                            : 'ti ti-chevrons-left';
                    }

                    if (label) {
                        label.textContent = collapsed
                            ? 'Expand sidebar'
                            : 'Collapse sidebar';
                    }
                });
            }

            function setSidebarCollapsed(collapsed, persist) {
                if (window.innerWidth < desktopBreakpoint) {
                    collapsed = false;
                }

                body.classList.toggle(
                    'finance-sidebar-collapsed',
                    collapsed
                );

                html.classList.remove(
                    'finance-sidebar-collapsed-preload'
                );

                if (persist !== false) {
                    try {
                        localStorage.setItem(
                            'finance-sidebar-collapsed',
                            collapsed ? 'true' : 'false'
                        );
                    } catch (error) {
                        console.warn(
                            '[Finance] Sidebar preference could not be saved.',
                            error
                        );
                    }
                }

                updateCollapseControls(collapsed);

                window.setTimeout(function () {
                    window.dispatchEvent(new Event('resize'));
                }, 240);
            }

            let savedCollapsed = false;

            try {
                savedCollapsed =
                    localStorage.getItem(
                        'finance-sidebar-collapsed'
                    ) === 'true';
            } catch (error) {
                savedCollapsed = false;
            }

            setSidebarCollapsed(
                savedCollapsed && window.innerWidth >= desktopBreakpoint,
                false
            );

            collapseButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setSidebarCollapsed(
                        !isSidebarCollapsed(),
                        true
                    );
                });
            });

            /* =====================================================
               MOBILE SIDEBAR
               ===================================================== */

            function openMobileSidebar() {
                body.classList.add('finance-sidebar-open');

                document.querySelectorAll(
                    '[data-finance-sidebar-toggle]'
                ).forEach(function (button) {
                    button.setAttribute('aria-expanded', 'true');
                });

                if (sidebar) {
                    sidebar.setAttribute('aria-hidden', 'false');
                }
            }

            function closeMobileSidebar() {
                body.classList.remove('finance-sidebar-open');

                document.querySelectorAll(
                    '[data-finance-sidebar-toggle]'
                ).forEach(function (button) {
                    button.setAttribute('aria-expanded', 'false');
                });

                if (sidebar && window.innerWidth < desktopBreakpoint) {
                    sidebar.setAttribute('aria-hidden', 'true');
                }
            }

            document.querySelectorAll(
                '[data-finance-sidebar-toggle]'
            ).forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();

                    if (
                        body.classList.contains(
                            'finance-sidebar-open'
                        )
                    ) {
                        closeMobileSidebar();
                    } else {
                        openMobileSidebar();
                    }
                });
            });

            document.querySelectorAll(
                '[data-finance-sidebar-close]'
            ).forEach(function (element) {
                element.addEventListener('click', function () {
                    closeMobileSidebar();
                });
            });

            /* =====================================================
               SIDEBAR SUBMENUS
               ===================================================== */

            if (sidebar) {
                sidebar.querySelectorAll(
                    '[data-finance-submenu-toggle]'
                ).forEach(function (toggle) {
                    toggle.addEventListener('click', function () {
                        if (
                            isSidebarCollapsed() &&
                            window.innerWidth >= desktopBreakpoint
                        ) {
                            setSidebarCollapsed(false, true);
                        }

                        const submenuId = toggle.getAttribute(
                            'aria-controls'
                        );

                        const submenu = document.getElementById(
                            submenuId
                        );

                        if (!submenu) {
                            return;
                        }

                        const isOpen = submenu.classList.contains('show');

                        sidebar.querySelectorAll(
                            '.finance-nav__submenu.show'
                        ).forEach(function (openMenu) {
                            if (openMenu === submenu) {
                                return;
                            }

                            openMenu.classList.remove('show');

                            const otherToggle = sidebar.querySelector(
                                '[aria-controls="' +
                                openMenu.id +
                                '"]'
                            );

                            if (otherToggle) {
                                otherToggle.classList.remove('is-open');
                                otherToggle.setAttribute(
                                    'aria-expanded',
                                    'false'
                                );
                            }
                        });

                        submenu.classList.toggle('show', !isOpen);
                        toggle.classList.toggle('is-open', !isOpen);
                        toggle.setAttribute(
                            'aria-expanded',
                            !isOpen ? 'true' : 'false'
                        );
                    });
                });
            }

            /* =====================================================
               SIDEBAR SEARCH
               ===================================================== */

            const sidebarSearch = document.querySelector(
                '[data-finance-sidebar-search]'
            );

            const noSearchResults = document.querySelector(
                '[data-finance-search-empty]'
            );

            function clearSidebarSearch() {
                if (!sidebarSearch) {
                    return;
                }

                sidebarSearch.value = '';
                sidebarSearch.dispatchEvent(new Event('input'));
            }

            if (sidebarSearch && sidebar) {
                sidebarSearch.addEventListener('focus', function () {
                    if (
                        isSidebarCollapsed() &&
                        window.innerWidth >= desktopBreakpoint
                    ) {
                        setSidebarCollapsed(false, true);
                    }
                });

                sidebarSearch.addEventListener('input', function () {
                    const query = sidebarSearch.value
                        .trim()
                        .toLowerCase();

                    let visibleItems = 0;

                    sidebar.querySelectorAll(
                        '.finance-nav__section'
                    ).forEach(function (section) {
                        let sectionVisibleItems = 0;

                        section.querySelectorAll(
                            '.finance-nav__item'
                        ).forEach(function (item) {
                            const searchContent = (
                                item.dataset.search || ''
                            ).toLowerCase();

                            const visible =
                                !query ||
                                searchContent.includes(query);

                            item.hidden = !visible;

                            if (visible) {
                                visibleItems++;
                                sectionVisibleItems++;
                            }

                            const submenu = item.querySelector(
                                '.finance-nav__submenu'
                            );

                            const toggle = item.querySelector(
                                '[data-finance-submenu-toggle]'
                            );

                            if (submenu && toggle) {
                                if (query && visible) {
                                    submenu.classList.add('show');
                                    toggle.classList.add('is-open');
                                    toggle.setAttribute(
                                        'aria-expanded',
                                        'true'
                                    );
                                } else if (!query) {
                                    const active =
                                        item.classList.contains('active');

                                    submenu.classList.toggle(
                                        'show',
                                        active
                                    );

                                    toggle.classList.toggle(
                                        'is-open',
                                        active
                                    );

                                    toggle.setAttribute(
                                        'aria-expanded',
                                        active ? 'true' : 'false'
                                    );
                                }
                            }
                        });

                        section.hidden = sectionVisibleItems === 0;
                    });

                    if (noSearchResults) {
                        noSearchResults.hidden =
                            !query || visibleItems > 0;
                    }
                });
            }

            /* Ctrl/Cmd + K focuses the sidebar search */
            document.addEventListener('keydown', function (event) {
                if (
                    (event.ctrlKey || event.metaKey) &&
                    event.key.toLowerCase() === 'k'
                ) {
                    event.preventDefault();

                    if (
                        window.innerWidth >= desktopBreakpoint &&
                        isSidebarCollapsed()
                    ) {
                        setSidebarCollapsed(false, true);
                    }

                    if (window.innerWidth < desktopBreakpoint) {
                        openMobileSidebar();
                    }

                    window.setTimeout(function () {
                        sidebarSearch?.focus();
                    }, 180);
                }

                if (event.key === 'Escape') {
                    if (
                        body.classList.contains(
                            'finance-sidebar-open'
                        )
                    ) {
                        closeMobileSidebar();
                        return;
                    }

                    if (
                        sidebarSearch &&
                        sidebarSearch.value.length > 0
                    ) {
                        clearSidebarSearch();
                        sidebarSearch.blur();
                    }
                }
            });

            /* =====================================================
               MOBILE LINK HANDLING
               ===================================================== */

            if (sidebar) {
                sidebar.querySelectorAll(
                    'a.finance-nav__link, .finance-nav__submenu a'
                ).forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (window.innerWidth < desktopBreakpoint) {
                            closeMobileSidebar();
                        }
                    });
                });
            }

            /* =====================================================
               RESPONSIVE STATE
               ===================================================== */

            function handleViewportChange() {
                if (window.innerWidth >= desktopBreakpoint) {
                    body.classList.remove('finance-sidebar-open');

                    if (sidebar) {
                        sidebar.setAttribute('aria-hidden', 'false');
                    }

                    let collapsedPreference = false;

                    try {
                        collapsedPreference =
                            localStorage.getItem(
                                'finance-sidebar-collapsed'
                            ) === 'true';
                    } catch (error) {
                        collapsedPreference = false;
                    }

                    setSidebarCollapsed(
                        collapsedPreference,
                        false
                    );
                } else {
                    body.classList.remove(
                        'finance-sidebar-collapsed'
                    );

                    updateCollapseControls(false);

                    if (
                        sidebar &&
                        !body.classList.contains(
                            'finance-sidebar-open'
                        )
                    ) {
                        sidebar.setAttribute('aria-hidden', 'true');
                    }
                }
            }

            let resizeTimer = null;

            window.addEventListener('resize', function () {
                window.clearTimeout(resizeTimer);

                resizeTimer = window.setTimeout(
                    handleViewportChange,
                    120
                );
            });

            handleViewportChange();

            /* =====================================================
               ACTIVE ITEM VISIBILITY
               ===================================================== */

            const activeNavigationItem = sidebar?.querySelector(
                '.finance-nav__item.active'
            );

            if (activeNavigationItem) {
                window.setTimeout(function () {
                    activeNavigationItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 250);
            }
        });
    </script>

    {{-- Existing child-page compatibility --}}
    @yield('extrajs')
    @yield('scripts')
    @stack('scripts')

</body>
</html>
