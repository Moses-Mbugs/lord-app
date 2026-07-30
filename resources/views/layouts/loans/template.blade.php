<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Loan Book Generator')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Bootstrap --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    {{-- Font --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-blue-dark: #005B82;
            --eco-blue-soft: #DFF3FB;
            --eco-teal: #0D7C8C;
            --eco-lime: #BED600;
            --eco-lime-soft: #F3F9CB;

            --loan-bg: #EAF1F6;
            --loan-bg-deep: #DDE8F0;
            --loan-card: #FFFFFF;
            --loan-card-soft: #F7FAFC;
            --loan-border: #D7E4ED;

            --loan-text: #17324D;
            --loan-muted: #6B7C8F;

            --loan-success: #168A45;
            --loan-danger: #C0392B;
            --loan-warning: #B7791F;
            --loan-info: #2563EB;

            --shadow-soft: 0 16px 35px rgba(23, 50, 77, 0.08);
            --shadow-blue: 0 18px 45px rgba(0, 91, 130, 0.24);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(190, 214, 0, 0.14), transparent 32%),
                radial-gradient(circle at top right, rgba(0, 130, 187, 0.16), transparent 34%),
                linear-gradient(135deg, var(--loan-bg), var(--loan-bg-deep));
            color: var(--loan-text);
            font-family: 'Inter', sans-serif;
        }

        a {
            text-decoration: none;
        }

        a:hover {
            text-decoration: none;
        }

        .loan-shell {
            min-height: 100vh;
            display: flex;
        }

        .loan-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #053D59 0%, #005B82 52%, #0078A8 100%);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 22px 18px;
            overflow-y: auto;
            box-shadow: 14px 0 35px rgba(5, 61, 89, 0.18);
            z-index: 20;
        }

        .loan-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 8px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 18px;
        }

        .loan-brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--eco-lime), #E2F336);
            color: #053D59;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 17px;
            box-shadow: 0 12px 25px rgba(190, 214, 0, 0.28);
        }

        .loan-brand-title {
            line-height: 1.15;
        }

        .loan-brand-title strong {
            display: block;
            font-family: 'Montserrat', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .loan-brand-title span {
            display: block;
            font-size: 12px;
            opacity: .78;
            margin-top: 3px;
        }

        .loan-nav-section-title {
            margin: 22px 8px 9px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: rgba(255, 255, 255, 0.62);
            font-weight: 800;
        }

        .loan-nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px 13px;
            border-radius: 14px;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 700;
            margin-bottom: 7px;
            transition: all .2s ease;
        }

        .loan-nav-link:hover,
        .loan-nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            transform: translateX(3px);
        }

        .loan-nav-icon {
            width: 31px;
            height: 31px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.13);
            font-size: 15px;
        }

        .loan-sidebar-footer {
            margin-top: 28px;
            padding: 15px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
        }

        .loan-sidebar-footer .small-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            opacity: .78;
            margin-bottom: 5px;
        }

        .loan-sidebar-footer p {
            margin: 0;
            font-size: 12px;
            line-height: 1.5;
            opacity: .82;
        }

        .loan-main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }

        .loan-topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            height: 74px;
            background: rgba(234, 241, 246, 0.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(215, 228, 237, 0.75);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .loan-topbar-left h2 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: var(--eco-blue-dark);
        }

        .loan-topbar-left span {
            display: block;
            color: var(--loan-muted);
            font-size: 12px;
            margin-top: 3px;
            font-weight: 600;
        }

        .loan-topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .loan-user-pill {
            background: #fff;
            border: 1px solid var(--loan-border);
            border-radius: 999px;
            padding: 8px 13px;
            font-size: 13px;
            font-weight: 800;
            color: var(--loan-text);
            box-shadow: 0 8px 18px rgba(23, 50, 77, 0.06);
        }

        .loan-content {
            padding: 26px 28px 42px;
        }

        .loan-page-hero {
            background:
                linear-gradient(135deg, rgba(0, 91, 130, 0.97), rgba(0, 130, 187, 0.94)),
                radial-gradient(circle at right, rgba(190, 214, 0, 0.45), transparent 30%);
            color: #fff;
            border-radius: 26px;
            padding: 28px;
            box-shadow: var(--shadow-blue);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .loan-page-hero::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background: rgba(190, 214, 0, 0.18);
            right: -75px;
            top: -85px;
        }

        .loan-page-hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            margin: 0 0 8px;
            font-size: 28px;
            position: relative;
            z-index: 1;
        }

        .loan-page-hero p {
            margin: 0;
            max-width: 900px;
            opacity: .91;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .loan-hero-actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .loan-card {
            background: var(--loan-card);
            border: 1px solid var(--loan-border);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            padding: 22px;
            margin-bottom: 22px;
        }

        .loan-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 17px;
        }

        .loan-card h3 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 900;
            color: var(--eco-blue-dark);
        }

        .loan-card-subtitle {
            color: var(--loan-muted);
            font-size: 13px;
            margin-top: 4px;
            line-height: 1.5;
        }

        .form-label {
            font-weight: 800;
            color: var(--loan-text);
            font-size: 13px;
            margin-bottom: 7px;
        }

        .form-control {
            border-radius: 13px;
            border: 1px solid var(--loan-border);
            padding: 11px 13px;
            min-height: 45px;
            box-shadow: none;
            font-size: 14px;
            color: var(--loan-text);
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--eco-blue);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, .12);
        }

        .loan-help-text {
            margin-top: 6px;
            color: var(--loan-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .btn-loan-primary {
            background: linear-gradient(135deg, var(--eco-blue), var(--eco-blue-dark));
            color: #fff;
            border: none;
            border-radius: 13px;
            font-weight: 900;
            padding: 11px 18px;
            box-shadow: 0 12px 24px rgba(0, 130, 187, .22);
            transition: all .2s ease;
        }

        .btn-loan-primary:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 15px 28px rgba(0, 91, 130, .28);
        }

        .btn-loan-secondary {
            background: #fff;
            color: var(--eco-blue-dark);
            border: 1px solid var(--loan-border);
            border-radius: 13px;
            font-weight: 900;
            padding: 10px 16px;
            box-shadow: 0 8px 18px rgba(23, 50, 77, 0.06);
        }

        .btn-loan-secondary:hover {
            color: var(--eco-blue-dark);
            background: var(--eco-blue-soft);
        }

        .btn-loan-hero {
            background: rgba(255, 255, 255, .16);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .36);
            border-radius: 13px;
            font-weight: 900;
            padding: 10px 16px;
        }

        .btn-loan-hero:hover {
            background: rgba(255, 255, 255, .24);
            color: #fff;
        }

        .loan-alert {
            border-radius: 16px;
            border: none;
            padding: 15px 17px;
            font-weight: 600;
            box-shadow: var(--shadow-soft);
        }

        .loan-alert ul {
            margin-bottom: 0;
        }

        .loan-table-wrap {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--loan-border);
        }

        .loan-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            margin-bottom: 0;
            background: #fff;
        }

        .loan-table thead th {
            background: #F3F8FB;
            color: var(--eco-blue-dark);
            font-weight: 900;
            border-bottom: 1px solid var(--loan-border);
            padding: 13px 12px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .loan-table tbody td {
            padding: 12px;
            border-bottom: 1px solid var(--loan-border);
            white-space: nowrap;
            color: var(--loan-text);
        }

        .loan-table tbody tr:hover td {
            background: #FAFDFF;
        }

        .loan-table tbody tr:last-child td {
            border-bottom: none;
        }

        .loan-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .loan-badge-success {
            background: rgba(22, 138, 69, .12);
            color: var(--loan-success);
        }

        .loan-badge-danger {
            background: rgba(192, 57, 43, .12);
            color: var(--loan-danger);
        }

        .loan-badge-warning {
            background: rgba(183, 121, 31, .14);
            color: var(--loan-warning);
        }

        .loan-badge-info {
            background: rgba(37, 99, 235, .12);
            color: var(--loan-info);
        }

        .loan-metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .loan-metric-card {
            background: #fff;
            border: 1px solid var(--loan-border);
            border-radius: 20px;
            padding: 18px;
            box-shadow: var(--shadow-soft);
            position: relative;
            overflow: hidden;
        }

        .loan-metric-card::after {
            content: "";
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            right: -25px;
            top: -25px;
            background: rgba(0, 130, 187, .09);
        }

        .loan-metric-label {
            color: var(--loan-muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 8px;
        }

        .loan-metric-value {
            font-size: 23px;
            font-weight: 900;
            color: var(--eco-blue-dark);
            line-height: 1.15;
        }

        .loan-muted {
            color: var(--loan-muted);
        }

        .loan-empty {
            text-align: center;
            color: var(--loan-muted);
            padding: 28px !important;
            font-weight: 700;
        }

        .pagination {
            margin-top: 16px;
        }

        .page-link {
            border-radius: 10px;
            margin: 0 2px;
            color: var(--eco-blue-dark);
            border-color: var(--loan-border);
            font-weight: 700;
        }

        .page-item.active .page-link {
            background: var(--eco-blue);
            border-color: var(--eco-blue);
        }

        .loan-mobile-toggle {
            display: none;
            background: #fff;
            border: 1px solid var(--loan-border);
            color: var(--eco-blue-dark);
            border-radius: 12px;
            padding: 8px 11px;
            font-weight: 900;
        }

        @media (max-width: 1100px) {
            .loan-sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            .loan-sidebar.open {
                transform: translateX(0);
            }

            .loan-main {
                margin-left: 0;
                width: 100%;
            }

            .loan-mobile-toggle {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .loan-metric-grid {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }
        }

        @media (max-width: 700px) {
            .loan-topbar {
                padding: 0 16px;
            }

            .loan-topbar-left h2 {
                font-size: 16px;
            }

            .loan-user-pill {
                display: none;
            }

            .loan-content {
                padding: 18px 14px 32px;
            }

            .loan-page-hero {
                padding: 22px;
                border-radius: 22px;
            }

            .loan-page-hero h1 {
                font-size: 23px;
            }

            .loan-metric-grid {
                grid-template-columns: 1fr;
            }

            .loan-card {
                padding: 18px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <div class="loan-shell">
        <aside class="loan-sidebar" id="loanSidebar">
            <div class="loan-brand">
                <div class="loan-brand-mark">LB</div>
                <div class="loan-brand-title">
                    <strong>Loan Book</strong>
                    <span>Automation Console</span>
                </div>
            </div>

            <div class="loan-nav-section-title">Workspace</div>

            <a href="{{ route('loans.loan-book.index') }}"
                class="loan-nav-link {{ request()->routeIs('loans.loan-book.index') ? 'active' : '' }}">
                <span class="loan-nav-icon">📘</span>
                <span>Generate Loan Book</span>
            </a>

            <a href="{{ route('loans.loan-book.index') }}"
                class="loan-nav-link {{ request()->routeIs('loans.loan-book.show') ? 'active' : '' }}">
                <span class="loan-nav-icon">📊</span>
                <span>Loan Book Runs</span>
            </a>

            <div class="loan-nav-section-title">Controls</div>

            <div class="loan-nav-link" style="cursor: default;">
                <span class="loan-nav-icon">✓</span>
                <span>Column Validation</span>
            </div>

            <div class="loan-nav-link" style="cursor: default;">
                <span class="loan-nav-icon">↔</span>
                <span>PMS Netting</span>
            </div>

            <div class="loan-nav-link" style="cursor: default;">
                <span class="loan-nav-icon">⚠</span>
                <span>Exceptions Report</span>
            </div>

            <div class="loan-sidebar-footer">
                <div class="small-title">Phase 1 Logic</div>
                <p>
                    PMS balances are netted by Related Account and Customer ID.
                    Only negative net outstanding balances are stored as Loan Book entries.
                </p>
            </div>
        </aside>

        <main class="loan-main">
            <header class="loan-topbar">
                <div class="loan-topbar-left">
                    <button class="loan-mobile-toggle" type="button" onclick="toggleLoanSidebar()">
                        ☰ Menu
                    </button>

                    <h2>@yield('page_title', 'Loan Book Generator')</h2>
                    <span>@yield('page_subtitle', 'PMS Proofing + Loan Details automation')</span>
                </div>

                <div class="loan-topbar-right">
                    <div class="loan-user-pill">
                        {{ auth()->user()->name ?? 'User' }}
                    </div>
                </div>
            </header>

            <section class="loan-content">
                @yield('content')
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleLoanSidebar() {
            var sidebar = document.getElementById('loanSidebar');
            sidebar.classList.toggle('open');
        }

        document.addEventListener('click', function(event) {
            var sidebar = document.getElementById('loanSidebar');
            var toggle = document.querySelector('.loan-mobile-toggle');

            if (!sidebar || !toggle) {
                return;
            }

            var clickedInsideSidebar = sidebar.contains(event.target);
            var clickedToggle = toggle.contains(event.target);

            if (!clickedInsideSidebar && !clickedToggle && window.innerWidth <= 1100) {
                sidebar.classList.remove('open');
            }
        });
    </script>

    @stack('scripts')

</body>

</html>
