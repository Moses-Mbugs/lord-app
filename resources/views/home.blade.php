<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Home | Ecobank Finance Intelligence Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">

    @php
        $visibleModules = collect($modules ?? [])
            ->where('visible', true)
            ->values();

        $moduleCount = $visibleModules->count();

        $fullName = trim($user->name ?? 'User');
        $firstName = explode(' ', $fullName)[0] ?: 'User';

        $nameParts = preg_split('/\s+/', $fullName);

        $initials = collect($nameParts)
            ->filter()
            ->take(2)
            ->map(fn($name) => strtoupper(substr($name, 0, 1)))
            ->implode('');

        $initials = $initials ?: 'U';

        $currentHour = now()->hour;

        $greeting = match (true) {
            $currentHour < 12 => 'Good morning',
            $currentHour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        /*
         * Automatically select a professional icon based on the
         * module name and description.
         */
        $getModuleIcon = function (array $module): string {
            $searchText = strtolower(trim(($module['name'] ?? '') . ' ' . ($module['description'] ?? '')));

            return match (true) {
                str_contains($searchText, 'dashboard') => 'fa-solid fa-chart-pie',

                str_contains($searchText, 'analytics') || str_contains($searchText, 'intelligence')
                    => 'fa-solid fa-chart-line',

                str_contains($searchText, 'reconciliation') || str_contains($searchText, 'recon')
                    => 'fa-solid fa-scale-balanced',

                str_contains($searchText, 'payment') => 'fa-solid fa-money-check-dollar',

                str_contains($searchText, 'invoice') => 'fa-solid fa-file-invoice-dollar',

                str_contains($searchText, 'procurement') => 'fa-solid fa-cart-shopping',

                str_contains($searchText, 'vendor') => 'fa-solid fa-handshake',

                str_contains($searchText, 'budget') => 'fa-solid fa-wallet',

                str_contains($searchText, 'risk') => 'fa-solid fa-shield-halved',

                str_contains($searchText, 'issue') || str_contains($searchText, 'incident')
                    => 'fa-solid fa-triangle-exclamation',

                str_contains($searchText, 'tax') => 'fa-solid fa-receipt',

                str_contains($searchText, 'contract') ||
                    str_contains($searchText, 'legal') ||
                    str_contains($searchText, 'agreement')
                    => 'fa-solid fa-file-signature',

                str_contains($searchText, 'report') => 'fa-solid fa-chart-column',

                str_contains($searchText, 'statement') || str_contains($searchText, 'bank')
                    => 'fa-solid fa-building-columns',

                str_contains($searchText, 'account') ||
                    str_contains($searchText, 'ledger') ||
                    str_contains($searchText, 'gl ')
                    => 'fa-solid fa-book-open',

                str_contains($searchText, 'customer') || str_contains($searchText, 'client') => 'fa-solid fa-users',

                str_contains($searchText, 'notification') || str_contains($searchText, 'alert') => 'fa-solid fa-bell',

                str_contains($searchText, 'document') || str_contains($searchText, 'file') => 'fa-solid fa-folder-open',

                str_contains($searchText, 'approval') || str_contains($searchText, 'workflow')
                    => 'fa-solid fa-list-check',

                str_contains($searchText, 'operation') => 'fa-solid fa-gears',

                str_contains($searchText, 'admin') ||
                    str_contains($searchText, 'user') ||
                    str_contains($searchText, 'role')
                    => 'fa-solid fa-users-gear',

                str_contains($searchText, 'security') || str_contains($searchText, 'access')
                    => 'fa-solid fa-user-shield',

                default => 'fa-solid fa-layer-group',
            };
        };
    @endphp

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --font-family: 'Montserrat', sans-serif;

            --brand-blue: #0082bb;
            --brand-blue-dark: #005b82;
            --brand-blue-deep: #073e5b;
            --brand-blue-soft: #e8f6fc;

            --brand-green: #bed600;
            --brand-green-dark: #73942f;
            --brand-green-soft: #f4f8dc;

            --page-bg: #eef4f8;
            --page-bg-secondary: #e5eef4;

            --surface: rgba(255, 255, 255, 0.92);
            --surface-solid: #ffffff;
            --surface-muted: #f5f8fa;
            --surface-hover: #f0f7fa;

            --text-primary: #173247;
            --text-secondary: #637989;
            --text-muted: #8b9eab;

            --border: rgba(55, 91, 113, 0.13);
            --border-strong: rgba(55, 91, 113, 0.22);

            --shadow-xs: 0 2px 8px rgba(18, 52, 73, 0.05);
            --shadow-sm: 0 12px 32px rgba(18, 52, 73, 0.08);
            --shadow-md: 0 22px 55px rgba(18, 52, 73, 0.13);
            --shadow-lg: 0 32px 80px rgba(18, 52, 73, 0.18);

            --radius-sm: 12px;
            --radius-md: 18px;
            --radius-lg: 26px;
            --radius-xl: 34px;

            --transition: 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
            font-family: var(--font-family);
            color: var(--text-primary);
            background:
                radial-gradient(circle at 0% 0%,
                    rgba(0, 130, 187, 0.15),
                    transparent 31%),
                radial-gradient(circle at 100% 10%,
                    rgba(190, 214, 0, 0.12),
                    transparent 25%),
                linear-gradient(145deg,
                    var(--page-bg),
                    var(--page-bg-secondary));
        }

        body::before,
        body::after {
            position: fixed;
            z-index: -1;
            border-radius: 50%;
            content: '';
            pointer-events: none;
        }

        body::before {
            top: 16%;
            left: -190px;
            width: 340px;
            height: 340px;
            background: rgba(0, 130, 187, 0.1);
        }

        body::after {
            right: -170px;
            bottom: 8%;
            width: 340px;
            height: 340px;
            background: rgba(190, 214, 0, 0.1);
        }

        button,
        input {
            font: inherit;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 3px solid rgba(0, 130, 187, 0.28);
            outline-offset: 3px;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */

        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(45, 83, 107, 0.11);
            background: rgba(255, 255, 255, 0.87);
            box-shadow: var(--shadow-xs);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
        }

        .topbar-inner {
            width: min(1280px, calc(100% - 48px));
            min-height: 78px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
        }

        .brand {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            color: inherit;
            text-decoration: none;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(0, 130, 187, 0.12);
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 9px 25px rgba(0, 91, 130, 0.14);
        }

        .brand-logo img {
            display: block;
            width: 40px;
            max-height: 34px;
            object-fit: contain;
        }

        .brand-copy {
            min-width: 0;
        }

        .brand-title {
            display: block;
            overflow: hidden;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.2px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .brand-subtitle {
            display: block;
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.15px;
            text-transform: uppercase;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-panel {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 6px 13px 6px 7px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow-xs);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 13px;
            color: #ffffff;
            background:
                linear-gradient(135deg,
                    var(--brand-blue),
                    var(--brand-blue-dark));
            box-shadow: 0 8px 20px rgba(0, 91, 130, 0.2);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .user-details {
            min-width: 0;
            line-height: 1.25;
        }

        .user-name {
            display: block;
            max-width: 180px;
            overflow: hidden;
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-role {
            display: block;
            margin-top: 3px;
            color: var(--text-muted);
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .logout-form {
            display: flex;
        }

        .logout-button {
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border: 1px solid rgba(0, 130, 187, 0.18);
            border-radius: 14px;
            color: var(--brand-blue-dark);
            background: var(--brand-blue-soft);
            cursor: pointer;
            font-size: 11px;
            font-weight: 800;
            transition:
                transform var(--transition),
                color var(--transition),
                background var(--transition),
                border-color var(--transition);
        }

        .logout-button:hover {
            color: #ffffff;
            border-color: var(--brand-blue);
            background: var(--brand-blue);
            transform: translateY(-2px);
        }

        .logout-button i {
            font-size: 14px;
        }

        /* Main content */

        .main-content {
            width: min(1280px, calc(100% - 48px));
            margin: 0 auto;
            padding: 38px 0 48px;
            flex: 1;
        }

        /* Hero */

        .hero {
            position: relative;
            isolation: isolate;
            min-height: 330px;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(290px, 0.55fr);
            align-items: stretch;
            gap: 24px;
            padding: 42px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-xl);
            color: #ffffff;
            background:
                linear-gradient(125deg,
                    #043c59 0%,
                    #006e9f 50%,
                    #0082bb 100%);
            box-shadow: var(--shadow-lg);
        }

        .hero::before {
            position: absolute;
            z-index: -1;
            top: -140px;
            right: -80px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            content: '';
            background:
                radial-gradient(circle,
                    rgba(190, 214, 0, 0.28),
                    transparent 68%);
        }

        .hero::after {
            position: absolute;
            z-index: -1;
            bottom: -160px;
            left: 28%;
            width: 430px;
            height: 300px;
            border-radius: 50%;
            content: '';
            background: rgba(255, 255, 255, 0.07);
            transform: rotate(-12deg);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .eyebrow {
            width: max-content;
            max-width: 100%;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 18px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            color: rgba(255, 255, 255, 0.88);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.85px;
            text-transform: uppercase;
        }

        .eyebrow i {
            color: var(--brand-green);
            font-size: 10px;
        }

        .hero h1 {
            max-width: 750px;
            color: #ffffff;
            font-size: clamp(32px, 4.3vw, 54px);
            font-weight: 800;
            line-height: 1.07;
            letter-spacing: -2.1px;
        }

        .hero-name {
            color: var(--brand-green);
        }

        .hero-description {
            max-width: 680px;
            margin-top: 17px;
            color: rgba(255, 255, 255, 0.74);
            font-size: 14px;
            font-weight: 500;
            line-height: 1.8;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 26px;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 13px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 13px;
            color: rgba(255, 255, 255, 0.87);
            background: rgba(255, 255, 255, 0.08);
            font-size: 10px;
            font-weight: 700;
        }

        .meta-pill i {
            width: 15px;
            color: var(--brand-green);
            font-size: 14px;
            text-align: center;
        }

        .hero-overview {
            position: relative;
            z-index: 2;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 26px;
            background:
                linear-gradient(145deg,
                    rgba(255, 255, 255, 0.16),
                    rgba(255, 255, 255, 0.08));
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                0 18px 45px rgba(0, 43, 64, 0.18);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .overview-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .overview-number {
            margin-top: 13px;
            color: #ffffff;
            font-size: 62px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -4px;
        }

        .overview-number span {
            margin-left: 4px;
            color: rgba(255, 255, 255, 0.54);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .overview-description {
            margin-top: 12px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 11px;
            font-weight: 500;
            line-height: 1.65;
        }

        .overview-divider {
            height: 1px;
            margin: 22px 0;
            background:
                linear-gradient(to right,
                    rgba(255, 255, 255, 0.22),
                    transparent);
        }

        .overview-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .status-content {
            min-width: 0;
        }

        .status-content strong {
            display: block;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
        }

        .status-content span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.57);
            font-size: 9px;
            font-weight: 600;
        }

        .security-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 15px;
            color: var(--brand-green);
            background: rgba(190, 214, 0, 0.13);
            font-size: 19px;
        }

        /* Module section */

        .modules-section {
            margin-top: 39px;
        }

        .section-toolbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 21px;
        }

        .section-title-group {
            min-width: 0;
        }

        .section-kicker {
            display: block;
            margin-bottom: 8px;
            color: var(--brand-blue);
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .section-title {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .section-description {
            margin-top: 7px;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
            line-height: 1.6;
        }

        .module-search {
            position: relative;
            width: min(360px, 100%);
            flex: 0 0 auto;
        }

        .module-search>i {
            position: absolute;
            top: 50%;
            left: 17px;
            color: var(--text-muted);
            pointer-events: none;
            font-size: 14px;
            transform: translateY(-50%);
        }

        .module-search input {
            width: 100%;
            height: 48px;
            padding: 0 48px 0 46px;
            border: 1px solid var(--border);
            border-radius: 16px;
            outline: none;
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow-xs);
            font-size: 11px;
            font-weight: 600;
            transition:
                border-color var(--transition),
                box-shadow var(--transition),
                background var(--transition);
        }

        .module-search input::placeholder {
            color: var(--text-muted);
        }

        .module-search input:focus {
            border-color: rgba(0, 130, 187, 0.38);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, 0.08);
        }

        .search-shortcut {
            position: absolute;
            top: 50%;
            right: 11px;
            min-width: 28px;
            height: 26px;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-muted);
            background: var(--surface-muted);
            font-size: 9px;
            font-weight: 800;
            transform: translateY(-50%);
        }

        /* Module cards */

        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 19px;
        }

        .module-card {
            --card-accent: #0082bb;
            --card-accent-dark: #005b82;
            --card-soft: rgba(0, 130, 187, 0.1);

            position: relative;
            isolation: isolate;
            min-height: 255px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 25px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            color: inherit;
            background: var(--surface);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            transition:
                transform var(--transition),
                box-shadow var(--transition),
                border-color var(--transition),
                background var(--transition);
        }

        .module-card:nth-child(6n + 1) {
            --card-accent: #0082bb;
            --card-accent-dark: #005b82;
            --card-soft: rgba(0, 130, 187, 0.1);
        }

        .module-card:nth-child(6n + 2) {
            --card-accent: #739b2e;
            --card-accent-dark: #52731e;
            --card-soft: rgba(115, 155, 46, 0.11);
        }

        .module-card:nth-child(6n + 3) {
            --card-accent: #7659d6;
            --card-accent-dark: #5638b6;
            --card-soft: rgba(118, 89, 214, 0.1);
        }

        .module-card:nth-child(6n + 4) {
            --card-accent: #e3892f;
            --card-accent-dark: #b96513;
            --card-soft: rgba(227, 137, 47, 0.11);
        }

        .module-card:nth-child(6n + 5) {
            --card-accent: #cf528d;
            --card-accent-dark: #a8326a;
            --card-soft: rgba(207, 82, 141, 0.1);
        }

        .module-card:nth-child(6n + 6) {
            --card-accent: #1d9fae;
            --card-accent-dark: #107583;
            --card-soft: rgba(29, 159, 174, 0.1);
        }

        .module-card::before {
            position: absolute;
            z-index: -1;
            top: -90px;
            right: -92px;
            width: 205px;
            height: 205px;
            border-radius: 50%;
            content: '';
            background: var(--card-soft);
            transition:
                transform 380ms cubic-bezier(0.2, 0.8, 0.2, 1),
                opacity var(--transition);
        }

        .module-card::after {
            position: absolute;
            z-index: -1;
            right: 0;
            bottom: 0;
            left: 0;
            height: 4px;
            content: '';
            opacity: 0;
            background:
                linear-gradient(90deg,
                    var(--card-accent),
                    var(--card-accent-dark));
            transform: scaleX(0.45);
            transform-origin: center;
            transition:
                opacity var(--transition),
                transform var(--transition);
        }

        .module-card:hover {
            border-color: rgba(0, 130, 187, 0.2);
            background: var(--surface-solid);
            box-shadow: var(--shadow-md);
            transform: translateY(-7px);
        }

        .module-card:hover::before {
            opacity: 0.95;
            transform: scale(1.18);
        }

        .module-card:hover::after {
            opacity: 1;
            transform: scaleX(1);
        }

        .module-card[hidden] {
            display: none;
        }

        .module-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        .module-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 18px;
            color: #ffffff;
            background:
                linear-gradient(135deg,
                    var(--card-accent),
                    var(--card-accent-dark));
            box-shadow: 0 13px 26px rgba(0, 91, 130, 0.18);
            transition:
                transform var(--transition),
                box-shadow var(--transition);
        }

        .module-icon i {
            font-size: 23px;
        }

        .module-card:hover .module-icon {
            box-shadow: 0 17px 32px rgba(0, 91, 130, 0.22);
            transform: rotate(-4deg) scale(1.05);
        }

        .module-index {
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.9px;
        }

        .module-card-body {
            margin-top: 23px;
        }

        .module-card h3 {
            color: var(--text-primary);
            font-size: 17px;
            font-weight: 800;
            line-height: 1.35;
            letter-spacing: -0.35px;
        }

        .module-card p {
            display: -webkit-box;
            overflow: hidden;
            margin-top: 10px;
            color: var(--text-secondary);
            font-size: 11.5px;
            font-weight: 500;
            line-height: 1.72;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .module-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: auto;
            padding-top: 24px;
        }

        .module-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--text-muted);
            font-size: 9.5px;
            font-weight: 700;
        }

        .module-status i {
            color: var(--brand-green-dark);
            font-size: 7px;
        }

        .module-open {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--card-accent);
            font-size: 10.5px;
            font-weight: 800;
            transition: gap var(--transition);
        }

        .module-open i {
            font-size: 12px;
            transition: transform var(--transition);
        }

        .module-card:hover .module-open {
            gap: 12px;
        }

        .module-card:hover .module-open i {
            transform: translateX(2px);
        }

        /* Empty states */

        .empty-state,
        .search-empty-state {
            display: grid;
            place-items: center;
            padding: 55px 24px;
            border: 1px dashed var(--border-strong);
            border-radius: var(--radius-lg);
            color: var(--text-secondary);
            background: var(--surface);
            text-align: center;
        }

        .empty-state {
            grid-column: 1 / -1;
        }

        .search-empty-state {
            display: none;
            margin-top: 18px;
        }

        .search-empty-state.is-visible {
            display: grid;
        }

        .empty-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin-bottom: 17px;
            border-radius: 18px;
            color: var(--brand-blue);
            background: var(--brand-blue-soft);
            font-size: 23px;
        }

        .empty-state h3,
        .search-empty-state h3 {
            color: var(--text-primary);
            font-size: 15px;
            font-weight: 800;
        }

        .empty-state p,
        .search-empty-state p {
            max-width: 450px;
            margin-top: 8px;
            font-size: 11px;
            line-height: 1.7;
        }

        /* Footer */

        .footer {
            width: min(1280px, calc(100% - 48px));
            margin: 0 auto;
            padding: 18px 0 28px;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--text-muted);
            font-size: 9.5px;
            font-weight: 700;
        }

        .footer-brand i {
            color: var(--brand-blue);
            font-size: 9px;
        }

        .footer-meta {
            color: var(--text-muted);
            font-size: 9px;
            font-weight: 600;
        }

        /* Responsive */

        @media (max-width: 1050px) {
            .hero {
                grid-template-columns: minmax(0, 1fr) 280px;
                padding: 35px;
            }

            .module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {

            .topbar-inner,
            .main-content,
            .footer {
                width: min(100% - 30px, 1280px);
            }

            .topbar-inner {
                min-height: 70px;
            }

            .brand-logo {
                width: 45px;
                height: 45px;
            }

            .brand-logo img {
                width: 35px;
            }

            .brand-subtitle,
            .user-details,
            .logout-button span {
                display: none;
            }

            .user-panel {
                padding: 4px;
                border-radius: 15px;
            }

            .logout-button {
                width: 42px;
                padding: 0;
            }

            .hero {
                min-height: auto;
                grid-template-columns: 1fr;
                padding: 32px;
            }

            .hero-overview {
                display: grid;
                grid-template-columns: 1fr auto;
                align-items: center;
                gap: 20px;
            }

            .overview-divider {
                display: none;
            }

            .overview-status {
                justify-content: flex-end;
            }

            .status-content {
                text-align: right;
            }

            .section-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .module-search {
                width: 100%;
            }
        }

        @media (max-width: 620px) {

            .topbar-inner,
            .main-content,
            .footer {
                width: min(100% - 22px, 1280px);
            }

            .topbar-actions {
                gap: 7px;
            }

            .brand {
                gap: 9px;
            }

            .brand-title {
                max-width: 145px;
                font-size: 11.5px;
            }

            .user-panel {
                display: none;
            }

            .main-content {
                padding-top: 22px;
            }

            .hero {
                padding: 27px 22px;
                border-radius: 26px;
            }

            .hero h1 {
                font-size: 34px;
                letter-spacing: -1.4px;
            }

            .hero-description {
                font-size: 12px;
            }

            .hero-meta {
                align-items: stretch;
                flex-direction: column;
            }

            .meta-pill {
                width: 100%;
            }

            .hero-overview {
                display: block;
                padding: 21px;
            }

            .overview-number {
                font-size: 52px;
            }

            .overview-divider {
                display: block;
            }

            .overview-status {
                justify-content: space-between;
            }

            .status-content {
                text-align: left;
            }

            .modules-section {
                margin-top: 31px;
            }

            .section-title {
                font-size: 21px;
            }

            .module-grid {
                grid-template-columns: 1fr;
            }

            .module-card {
                min-height: 235px;
            }

            .footer-inner {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 390px) {
            .brand-title {
                max-width: 115px;
            }

            .hero h1 {
                font-size: 30px;
            }

            .hero {
                padding-inline: 19px;
            }

            .module-card {
                padding: 22px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="app-shell">

        <header class="topbar">
            <div class="topbar-inner">
                <a href="{{ url('/') }}" class="brand" aria-label="Finance platform home">
                    <span class="brand-logo">
                        <img src="{{ asset('assets/img/Ecobank_Logo.png') }}" alt="Ecobank">
                    </span>

                    <span class="brand-copy">
                        <span class="brand-title">
                            Finance Intelligence Platform
                        </span>

                        <span class="brand-subtitle">
                            Ecobank Finance Workspace
                        </span>
                    </span>
                </a>

                <div class="topbar-actions">
                    <div class="user-panel">
                        <div class="user-avatar" aria-hidden="true">
                            {{ $initials }}
                        </div>

                        <div class="user-details">
                            <span class="user-name">
                                {{ $fullName }}
                            </span>

                            <span class="user-role">
                                Authorized user
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                        @csrf

                        <button type="submit" class="logout-button" aria-label="Sign out">
                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>

                            <span>Sign out</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="main-content">

            <section class="hero" aria-labelledby="welcomeHeading">
                <div class="hero-content">
                    <div class="eyebrow">
                        <i class="fa-solid fa-circle" aria-hidden="true"></i>

                        Finance operations workspace
                    </div>

                    <h1 id="welcomeHeading">
                        {{ $greeting }},
                        <span class="hero-name">{{ $firstName }}</span>.
                    </h1>

                    <p class="hero-description">
                        Access your authorized finance modules, monitor critical
                        operations and move work forward from one intelligent
                        workspace.
                    </p>

                    <div class="hero-meta">
                        <span class="meta-pill">
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>

                            {{ now()->format('l, d F Y') }}
                        </span>

                        <span class="meta-pill">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>

                            Secure authenticated session
                        </span>
                    </div>
                </div>

                <aside class="hero-overview" aria-label="Workspace overview">
                    <div>
                        <span class="overview-label">
                            Available modules
                        </span>

                        <div class="overview-number">
                            {{ str_pad($moduleCount, 2, '0', STR_PAD_LEFT) }}

                            <span>modules</span>
                        </div>

                        <p class="overview-description">
                            Modules are displayed according to your assigned
                            access permissions.
                        </p>
                    </div>

                    <div class="overview-divider"></div>

                    <div class="overview-status">
                        <div class="status-content">
                            <strong>Protected workspace</strong>
                            <span>Role-based access enabled</span>
                        </div>

                        <div class="security-icon" aria-hidden="true">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="modules-section" aria-labelledby="modulesHeading">
                <div class="section-toolbar">
                    <div class="section-title-group">
                        <span class="section-kicker">
                            Your workspace
                        </span>

                        <h2 class="section-title" id="modulesHeading">
                            Finance modules
                        </h2>

                        <p class="section-description">
                            Select a module to continue with your assigned tasks.
                        </p>
                    </div>

                    @if ($moduleCount > 0)
                        <div class="module-search">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                            <input type="search" id="moduleSearch" placeholder="Search your modules..."
                                aria-label="Search modules" autocomplete="off">

                            <span class="search-shortcut" aria-hidden="true">
                                /
                            </span>
                        </div>
                    @endif
                </div>

                <div class="module-grid" id="moduleGrid">
                    @forelse ($visibleModules as $module)
                        @php
                            $searchableText = strtolower(
                                trim(($module['name'] ?? '') . ' ' . ($module['description'] ?? '')),
                            );

                            $moduleIcon = $getModuleIcon($module);
                        @endphp

                        <a href="{{ route($module['route']) }}" class="module-card" data-module-card
                            data-module-search="{{ $searchableText }}" aria-label="Open {{ $module['name'] }}">
                            <div class="module-card-header">
                                <div class="module-icon" aria-hidden="true">
                                    <i class="{{ $moduleIcon }}"></i>
                                </div>

                                <span class="module-index">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            <div class="module-card-body">
                                <h3>{{ $module['name'] }}</h3>

                                <p>
                                    {{ $module['description'] }}
                                </p>
                            </div>

                            <div class="module-card-footer">
                                <span class="module-status">
                                    <i class="fa-solid fa-circle" aria-hidden="true"></i>

                                    Available
                                </span>

                                <span class="module-open">
                                    Open module

                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            </div>

                            <h3>No modules assigned</h3>

                            <p>
                                You currently do not have access to any finance
                                modules. Contact the system administrator if you
                                believe this is incorrect.
                            </p>
                        </div>
                    @endforelse
                </div>

                <div class="search-empty-state" id="searchEmptyState" aria-live="polite">
                    <div class="empty-icon">
                        <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i>
                    </div>

                    <h3>No matching modules</h3>

                    <p>
                        Try using another module name or keyword.
                    </p>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-inner">
                <div class="footer-brand">
                    <i class="fa-solid fa-circle" aria-hidden="true"></i>

                    Ecobank Finance Intelligence Platform
                </div>

                <div class="footer-meta">
                    &copy; {{ date('Y') }} Ecobank. Authorized access only.
                </div>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('moduleSearch');

            const moduleCards = Array.from(
                document.querySelectorAll('[data-module-card]')
            );

            const searchEmptyState = document.getElementById(
                'searchEmptyState'
            );

            function filterModules() {
                if (!searchInput) {
                    return;
                }

                const query = searchInput.value
                    .trim()
                    .toLowerCase();

                let visibleCount = 0;

                moduleCards.forEach(function(card) {
                    const searchableText =
                        card.dataset.moduleSearch?.toLowerCase() || '';

                    const matches =
                        query === '' ||
                        searchableText.includes(query);

                    card.hidden = !matches;

                    if (matches) {
                        visibleCount++;
                    }
                });

                searchEmptyState?.classList.toggle(
                    'is-visible',
                    query !== '' && visibleCount === 0
                );
            }

            searchInput?.addEventListener('input', filterModules);

            document.addEventListener('keydown', function(event) {
                const activeElement = document.activeElement;

                const userIsTyping =
                    activeElement instanceof HTMLInputElement ||
                    activeElement instanceof HTMLTextAreaElement ||
                    activeElement?.isContentEditable;

                if (
                    event.key === '/' &&
                    !userIsTyping &&
                    searchInput
                ) {
                    event.preventDefault();
                    searchInput.focus();
                }

                if (
                    event.key === 'Escape' &&
                    document.activeElement === searchInput
                ) {
                    searchInput.value = '';
                    filterModules();
                    searchInput.blur();
                }
            });
        });
    </script>
</body>

</html>
