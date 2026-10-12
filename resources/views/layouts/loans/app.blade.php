<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>@yield('title', 'Loan Book') | Ecobank</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Kablammo&family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Shell and component styles mirror the Compliance layout (finance_projects/compliance/layouts/app). --}}
    <style>
        :root {
            /* Ecobank-inspired palette */
            --blue: #0082BB;
            --blue-hover: #006f9f;
            --dark-blue: #005B82;
            --deep-blue: #003B5C;
            --navy: #002C46;

            --green: #BED600;
            --green-dark: #6F941F;
            --green-soft: #F3F8D8;

            --cyan: #00A9CE;
            --teal: #00A68A;

            --yellow: #F8C84A;
            --orange: #E99A28;
            --red: #D94F4F;

            --gray: #3D4652;
            --gray-2: #667085;
            --gray-3: #98A2B3;
            --gray-4: #D0D5DD;

            --light-gray: #EDF1F5;
            --lighter-gray: #F7F9FC;

            --bg: #F3F6FA;
            --card: #FFFFFF;
            --border: #E3EAF1;

            /* Status */
            --success: #27945A;
            --warning: #E6A11B;
            --danger: #D64545;

            /* Layout */
            --sidebar-width: 272px;
            --topbar-height: 78px;

            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(17, 39, 61, .05);
            --shadow-md: 0 8px 30px rgba(20, 50, 78, .08);
            --shadow-lg: 0 16px 45px rgba(10, 50, 80, .12);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(0, 130, 187, .045), transparent 30%),
                var(--bg);
            color: var(--gray);
            min-height: 100vh;
            line-height: 1.5;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* =========================================================
           APPLICATION SHELL
        ========================================================= */

        .shell {
            display: flex;
            min-height: 100vh;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--deep-blue) 0%, #003951 48%, #002F45 100%);
            color: #fff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 8px 0 30px rgba(0, 38, 58, .10);
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform .25s ease;
        }

        .sidebar::before {
            content: "";
            position: absolute;
            top: -130px;
            right: -130px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(0, 130, 187, .16);
            pointer-events: none;
        }

        .sidebar::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            left: -100px;
            bottom: 100px;
            background: rgba(190, 214, 0, .06);
            pointer-events: none;
        }

        .brand {
            min-height: 86px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 18px 19px;
            border-bottom: 1px solid rgba(255, 255, 255, .10);
            position: relative;
            z-index: 2;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--green), #d6eb2a);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(190, 214, 0, .20);
        }

        .brand-icon i {
            color: var(--deep-blue);
            font-size: 1.05rem;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-text strong {
            display: block;
            font-size: .95rem;
            font-weight: 800;
            letter-spacing: -.2px;
            white-space: nowrap;
        }

        .brand-text span {
            display: block;
            margin-top: 1px;
            font-size: .63rem;
            font-weight: 600;
            opacity: .58;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .nav-wrapper {
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 13px 0 18px;
        }

        .nav-section {
            padding: 7px 13px 2px;
        }

        .nav-label {
            font-size: .61rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255, 255, 255, .40);
            padding: 7px 12px;
            margin-bottom: 3px;
        }

        .nav-link {
            min-height: 43px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: .78rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .76);
            margin-bottom: 3px;
            position: relative;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: .88rem;
            color: rgba(255, 255, 255, .65);
            transition: color .2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, .075);
            color: #fff;
            transform: translateX(2px);
        }

        .nav-link:hover i {
            color: var(--green);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(190, 214, 0, .18), rgba(0, 130, 187, .09));
            color: #fff;
            border: 1px solid rgba(190, 214, 0, .12);
        }

        .nav-link.active::before {
            content: "";
            position: absolute;
            left: -13px;
            width: 4px;
            height: 24px;
            background: var(--green);
            border-radius: 0 4px 4px 0;
        }

        .nav-link.active i {
            color: var(--green);
        }

        .sidebar-foot {
            position: relative;
            z-index: 2;
            margin-top: auto;
            padding: 17px 18px;
            border-top: 1px solid rgba(255, 255, 255, .10);
        }

        .sidebar-home-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .62);
            font-size: .68rem;
            font-weight: 600;
            transition: .2s ease;
        }

        .sidebar-home-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .07);
        }

        /* =========================================================
           MAIN AREA + TOPBAR
        ========================================================= */

        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-width: 0;
            min-height: 100vh;
        }

        .topbar {
            min-height: var(--topbar-height);
            background: rgba(255, 255, 255, .95);
            border-bottom: 1px solid var(--border);
            padding: 14px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(20, 50, 80, .03);
            backdrop-filter: blur(10px);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .mobile-menu-btn {
            width: 38px;
            height: 38px;
            display: none;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #fff;
            color: var(--dark-blue);
            cursor: pointer;
        }

        .page-heading {
            min-width: 0;
        }

        .topbar h1 {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--deep-blue);
            letter-spacing: -.25px;
        }

        .topbar p {
            margin-top: 2px;
            font-size: .71rem;
            color: var(--gray-3);
            font-weight: 500;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border: 1px solid #DDE9D1;
            background: #F7FAEE;
            border-radius: 9px;
            font-size: .66rem;
            font-weight: 700;
            color: var(--green-dark);
        }

        .system-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #72A933;
            box-shadow: 0 0 0 4px rgba(114, 169, 51, .11);
        }

        .topbar-date {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .67rem;
            font-weight: 600;
            color: var(--gray-2);
            padding: 7px 10px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #fff;
        }

        .topbar-date i {
            color: var(--blue);
        }

        .content {
            width: 100%;
            max-width: 1640px;
            margin: 0 auto;
            padding: 26px 30px 42px;
        }

        /* =========================================================
           HERO
        ========================================================= */

        .loan-hero {
            --hero-base: linear-gradient(118deg, #003B5C 0%, #005B82 46%, #0082BB 100%);
            position: relative;
            overflow: hidden;
            min-height: 154px;
            /* Layer 1 is a soft light sweep that travels across; layer 2 is the brand gradient. */
            background:
                linear-gradient(105deg, transparent 40%, rgba(255, 255, 255, .10) 50%, transparent 60%) no-repeat,
                var(--hero-base);
            background-size: 250% 100%, 100% 100%;
            background-position: 150% 0, 0 0;
            animation: heroSweep 7s ease-in-out infinite;
            border-radius: 18px;
            padding: 25px 28px;
            margin-bottom: 20px;
            color: #fff;
            box-shadow: 0 12px 32px rgba(0, 91, 130, .16);
            transition: box-shadow .6s ease;
        }

        .loan-hero::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            right: -90px;
            top: -145px;
            background: rgba(255, 255, 255, .08);
            animation: heroDrift 14s ease-in-out infinite;
        }

        .loan-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            right: 110px;
            bottom: -145px;
            background: rgba(190, 214, 0, .12);
            animation: heroPulse 9s ease-in-out infinite;
        }

        .loan-hero .hero-copy {
            animation: heroIn .7s cubic-bezier(.2, .7, .2, 1) both;
        }

        .loan-hero .hero-panel {
            animation: heroIn .7s .15s cubic-bezier(.2, .7, .2, 1) both;
        }

        @keyframes heroSweep {
            0%, 35% { background-position: 150% 0, 0 0; }
            75%, 100% { background-position: -50% 0, 0 0; }
        }

        @keyframes heroDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-28px, 18px) scale(1.08); }
        }

        @keyframes heroPulse {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
            50% { transform: translate(24px, -10px) scale(1.15); opacity: .65; }
        }

        @keyframes heroIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .loan-hero,
            .loan-hero::before,
            .loan-hero::after,
            .loan-hero .hero-copy,
            .loan-hero .hero-panel {
                animation: none !important;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .hero-copy {
            max-width: 680px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 9px;
            font-size: .64rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--green);
        }

        /* Display font for the hero title only. */
        .hero-title {
            font-family: 'Kablammo', 'Montserrat', sans-serif;
            font-size: 2rem;
            font-weight: 400;
            line-height: 1.2;
            letter-spacing: .5px;
            margin-bottom: 7px;
        }

        .hero-description {
            max-width: 620px;
            font-size: .73rem;
            color: rgba(255, 255, 255, .72);
            line-height: 1.65;
        }

        .hero-panel {
            min-width: 210px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .13);
            backdrop-filter: blur(10px);
        }

        .hero-panel-label {
            font-size: .57rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .55);
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .hero-panel-value {
            margin-top: 3px;
            font-size: .9rem;
            font-weight: 800;
            word-break: break-all;
        }

        .hero-panel-meta {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            font-size: .61rem;
            color: rgba(255, 255, 255, .66);
        }

        .hero-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-hero {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #fff;
        }

        .btn-hero:hover {
            background: rgba(255, 255, 255, .22);
        }

        .hero-panel select {
            background: rgba(255, 255, 255, .95);
        }

        /* =========================================================
           KPI TILES
        ========================================================= */

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .kpi {
            --kpi-color: #005B82;
            --kpi-soft: rgba(0, 91, 130, .09);
            position: relative;
            overflow: hidden;
            min-height: 127px;
            min-width: 0;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .kpi:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .kpi::after {
            content: "";
            position: absolute;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            right: -25px;
            bottom: -25px;
            background: var(--kpi-soft);
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 13px;
        }

        .kpi-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--kpi-soft);
            color: var(--kpi-color);
            font-size: .84rem;
        }

        .kpi-mini-label {
            font-size: .55rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--gray-3);
            text-align: right;
        }

        .kpi-value {
            font-size: 1.35rem;
            line-height: 1.1;
            font-weight: 800;
            color: var(--deep-blue);
            letter-spacing: -.5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kpi-label {
            margin-top: 5px;
            font-size: .66rem;
            font-weight: 600;
            color: var(--gray-2);
        }

        .kpi-blue { --kpi-color: var(--blue); --kpi-soft: rgba(0, 130, 187, .10); }
        .kpi-green { --kpi-color: var(--success); --kpi-soft: rgba(39, 148, 90, .10); }
        .kpi-amber { --kpi-color: #C7860C; --kpi-soft: rgba(230, 161, 27, .12); }
        .kpi-red { --kpi-color: var(--danger); --kpi-soft: rgba(214, 69, 69, .10); }
        .kpi-purple { --kpi-color: #8D4FA6; --kpi-soft: rgba(141, 79, 166, .10); }

        .text-danger { color: var(--danger) !important; }
        .text-success { color: var(--success) !important; }
        .text-muted { color: var(--gray-3) !important; }

        /* =========================================================
           DASHBOARD CARDS
        ========================================================= */

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .dashboard-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            min-width: 0;
        }

        .grid-2 > .dashboard-card {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
        }

        .dashboard-card.flush {
            padding: 0;
            overflow: hidden;
        }

        .dashboard-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .flush > .dashboard-card-header {
            margin: 0;
            padding: 19px 21px;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }

        .dashboard-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-card-title-icon {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(0, 130, 187, .08);
            color: var(--blue);
            font-size: .8rem;
            font-weight: 800;
        }

        .dashboard-card-title-icon.is-done {
            background: rgba(39, 148, 90, .12);
            color: var(--success);
        }

        .dashboard-card-title-icon.is-ready {
            background: var(--blue);
            color: #fff;
        }

        .dashboard-card h3 {
            color: var(--deep-blue);
            font-size: .84rem;
            font-weight: 800;
        }

        .dashboard-card-subtitle {
            margin-top: 2px;
            color: var(--gray-3);
            font-size: .64rem;
        }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-foot {
            margin-top: auto;
            padding-top: 16px;
        }

        .flush .card-foot {
            padding: 12px 21px;
            border-top: 1px solid var(--border);
        }

        /* =========================================================
           PROGRESS STEPS
        ========================================================= */

        .steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 18px;
            min-width: 0;
        }

        .step + .step {
            border-left: 1px solid var(--border);
        }

        .step-dot {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--lighter-gray);
            border: 1px solid var(--gray-4);
            color: var(--gray-3);
            font-size: .72rem;
            font-weight: 800;
        }

        .step.is-done .step-dot {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }

        .step.is-current .step-dot {
            background: var(--blue);
            border-color: var(--blue);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(0, 130, 187, .12);
        }

        .step strong {
            display: block;
            font-size: .74rem;
            font-weight: 800;
            color: var(--deep-blue);
        }

        .step span {
            display: block;
            font-size: .64rem;
            color: var(--gray-3);
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: .76rem;
        }

        table.data th {
            text-align: left;
            padding: 11px 12px;
            background: #F7F9FC;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .55px;
            color: var(--gray-3);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        table.data td {
            padding: 11px 12px;
            border-bottom: 1px solid #EDF1F5;
            vertical-align: middle;
            white-space: nowrap;
        }

        table.data td.wrap {
            white-space: normal;
            min-width: 220px;
        }

        table.data .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        table.data .strong {
            font-weight: 700;
            color: var(--deep-blue);
        }

        table.data tbody tr {
            transition: background .15s ease;
        }

        table.data tbody tr:hover {
            background: #FAFCFE;
        }

        table.data tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           BADGES / TAGS
        ========================================================= */

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: .61rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge::before {
            content: "";
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-success { background: rgba(39, 148, 90, .11); color: var(--success); }
        .badge-warning { background: rgba(230, 161, 27, .13); color: #A46E07; }
        .badge-danger { background: rgba(214, 69, 69, .11); color: var(--danger); }
        .badge-info { background: rgba(0, 130, 187, .11); color: var(--blue); }
        .badge-muted { background: var(--light-gray); color: var(--gray-2); }

        .loan-hero .badge {
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .tag {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 3px 8px;
            border-radius: 6px;
            background: var(--lighter-gray);
            border: 1px solid var(--border);
            color: var(--gray-2);
            font-size: .62rem;
            font-weight: 600;
        }

        .staged-file {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin-top: 14px;
            padding: 9px 12px;
            border-radius: 9px;
            background: var(--lighter-gray);
            border: 1px solid var(--border);
            font-size: .68rem;
            color: var(--gray-2);
        }

        .staged-file strong {
            overflow: hidden;
            color: var(--deep-blue);
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .btn {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-family: 'Montserrat', sans-serif;
            font-size: .72rem;
            font-weight: 700;
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: .5;
            transform: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), var(--dark-blue));
            color: #fff;
            box-shadow: 0 5px 14px rgba(0, 130, 187, .18);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--blue-hover), var(--deep-blue));
        }

        .btn-outline {
            background: #fff;
            border: 1px solid var(--border);
            color: var(--gray);
        }

        .btn-outline:hover {
            background: #F7FAFC;
        }

        .btn-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 8px;
            background: rgba(0, 130, 187, .07);
            color: var(--blue);
            font-size: .64rem;
            font-weight: 800;
            white-space: nowrap;
            transition: .18s ease;
        }

        .btn-link:hover {
            background: var(--blue);
            color: #fff;
        }

        .btn-sm {
            min-height: 30px;
            padding: 5px 11px;
            font-size: .68rem;
        }

        .btn-lg {
            min-height: 42px;
            padding: 10px 22px;
            font-size: .78rem;
        }

        /* =========================================================
           FORMS
        ========================================================= */

        input[type=text],
        input[type=number],
        input[type=date],
        select,
        textarea {
            font-family: 'Montserrat', sans-serif;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: .77rem;
            color: var(--gray);
            width: 100%;
            background: #fff;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgba(0, 130, 187, .65);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, .08);
        }

        label.field-label {
            font-size: .64rem;
            font-weight: 700;
            color: var(--gray-2);
            text-transform: uppercase;
            letter-spacing: .4px;
            display: block;
            margin-bottom: 6px;
        }

        .req {
            color: var(--danger);
        }

        .help {
            margin-top: 7px;
            font-size: .64rem;
            color: var(--gray-3);
            line-height: 1.5;
        }

        /* =========================================================
           ALERTS
        ========================================================= */

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 11px 15px;
            border-radius: 9px;
            font-size: .76rem;
            margin-bottom: 16px;
            border: 1px solid transparent;
        }

        .alert i {
            margin-top: 3px;
        }

        .alert ul {
            margin: 4px 0 0 16px;
        }

        .alert-success {
            background: rgba(102, 148, 56, .09);
            color: var(--green-dark);
            border-color: rgba(102, 148, 56, .14);
        }

        .alert-error {
            background: rgba(192, 57, 43, .08);
            color: var(--danger);
            border-color: rgba(192, 57, 43, .13);
        }

        .alert-warning {
            background: rgba(230, 161, 27, .10);
            color: #8A5C05;
            border-color: rgba(230, 161, 27, .18);
        }

        /* =========================================================
           EMPTY STATES
        ========================================================= */

        .empty {
            padding: 35px 20px;
            text-align: center;
            color: var(--gray-3);
            font-size: .76rem;
            background: #FAFBFC;
            border: 1px dashed var(--border);
            border-radius: 10px;
        }

        .empty i {
            display: block;
            font-size: 1.5rem;
            color: var(--blue);
            margin-bottom: 10px;
        }

        .flush .empty {
            margin: 20px;
        }

        /* =========================================================
           TABS
        ========================================================= */

        .tabs {
            display: flex;
            gap: 4px;
            padding: 0 14px;
            border-bottom: 1px solid var(--border);
        }

        .tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 10px 13px;
            margin-bottom: -1px;
            border: none;
            border-bottom: 2px solid transparent;
            background: none;
            font-size: .74rem;
            font-weight: 700;
            color: var(--gray-3);
            cursor: pointer;
        }

        .tab:hover {
            color: var(--deep-blue);
        }

        .tab.is-active {
            color: var(--deep-blue);
            border-bottom-color: var(--blue);
        }

        /* =========================================================
           PAGINATION (Laravel bootstrap-4 markup, styled here)
        ========================================================= */

        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            list-style: none;
        }

        .page-link {
            min-width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            color: var(--gray-2);
            font-size: .7rem;
            font-weight: 700;
        }

        a.page-link:hover {
            color: var(--blue);
            background: rgba(0, 130, 187, .07);
        }

        .page-item.active .page-link {
            background: var(--blue);
            border-color: var(--blue);
            color: #fff;
        }

        .page-item.disabled .page-link {
            opacity: .45;
        }

        /* =========================================================
           MOBILE BACKDROP
        ========================================================= */

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 31, 48, .48);
            z-index: 900;
            backdrop-filter: blur(2px);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .sidebar-backdrop.active {
                display: block;
            }

            .main {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: inline-flex;
            }

            .content {
                padding: 22px 20px 35px;
            }

            .topbar {
                padding: 14px 20px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .hero-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-panel {
                width: 100%;
            }

            .steps {
                grid-template-columns: 1fr;
            }

            .step + .step {
                border-left: none;
                border-top: 1px solid var(--border);
            }
        }

        @media (max-width: 700px) {
            .topbar-right {
                display: none;
            }

            .content {
                padding: 18px 14px 28px;
            }

            .dashboard-card {
                padding: 17px;
                border-radius: 12px;
            }

            .topbar {
                min-height: 68px;
            }

            .topbar h1 {
                font-size: .98rem;
            }

            .topbar p {
                font-size: .65rem;
            }

            .loan-hero {
                padding: 21px 19px;
            }

            .hero-title {
                font-size: 1.55rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <div class="shell">

        {{-- =========================================================
             SIDEBAR
        ========================================================== --}}
        <aside class="sidebar" id="loanSidebar">

            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-book"></i>
                </div>

                <div class="brand-text">
                    <strong>Loan Book</strong>
                    <span>Automation Console</span>
                </div>
            </div>

            <div class="nav-wrapper">

                <div class="nav-section">
                    <div class="nav-label">Loan Book</div>

                    <a href="{{ route('loans.loan-book.index') }}"
                        class="nav-link {{ request()->routeIs('loans.loan-book.index') ? 'active' : '' }}">
                        <i class="fas fa-file-arrow-up"></i>
                        <span>Generate Loan Book</span>
                    </a>

                    <a href="{{ route('loans.loan-book.index') }}#recent-runs"
                        class="nav-link {{ request()->routeIs('loans.loan-book.show') ? 'active' : '' }}">
                        <i class="fas fa-layer-group"></i>
                        <span>Loan Book Runs</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-label">Portfolio</div>

                    <a href="{{ route('loans.loan-utilization.index') }}"
                        class="nav-link {{ request()->routeIs('loans.loan-utilization.*') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i>
                        <span>Loan Utilization</span>
                    </a>
                </div>

            </div>

            <div class="sidebar-foot">
                <a href="{{ route('home') }}" class="sidebar-home-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Ecobank Systems</span>
                </a>
            </div>

        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleLoanSidebar()"></div>

        {{-- =========================================================
             MAIN CONTENT
        ========================================================== --}}
        <div class="main">

            <header class="topbar">

                <div class="topbar-left">
                    <button type="button" class="mobile-menu-btn" onclick="toggleLoanSidebar()"
                        aria-label="Open navigation">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="page-heading">
                        <h1>@yield('title', 'Loan Book')</h1>
                        <p>@yield('subtitle', 'Loan Book generation & portfolio monitoring')</p>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="system-status">
                        <span class="system-status-dot"></span>
                        {{ auth()->user()->name ?? 'Loan Book' }}
                    </div>

                    <div class="topbar-date">
                        <i class="far fa-calendar"></i>
                        {{ now()->format('d M Y') }}
                    </div>
                </div>

            </header>

            <main class="content">

                @if (session('success'))
                    <div class="alert alert-success" role="status">
                        <i class="fas fa-circle-check"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-circle-exclamation"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-circle-exclamation"></i>
                        <div>
                            <strong>Please correct the following:</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')

            </main>

        </div>
    </div>

    <script>
        function toggleLoanSidebar() {
            document.getElementById('loanSidebar').classList.toggle('mobile-open');
            document.getElementById('sidebarBackdrop').classList.toggle('active');
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                document.getElementById('loanSidebar').classList.remove('mobile-open');
                document.getElementById('sidebarBackdrop').classList.remove('active');
            }
        });

        // Simple tabs: <button class="tab" data-tab="x"> toggles <div data-tab-pane="x">.
        document.querySelectorAll('[data-tabs]').forEach(function(group) {
            var tabs = group.querySelectorAll('[data-tab]');
            var panes = group.querySelectorAll('[data-tab-pane]');

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var target = tab.getAttribute('data-tab');

                    tabs.forEach(function(t) {
                        var active = t === tab;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    panes.forEach(function(pane) {
                        pane.hidden = pane.getAttribute('data-tab-pane') !== target;
                    });
                });
            });
        });
    </script>

    {{-- =========================================================
         HALLOWEEN EASTER EGG — type "boo" anywhere (outside a form field).
         Only shipped 24 Oct – 2 Nov, locally, or with ?boo=1 for testing.
    ========================================================== --}}
    @php
        $today = now();
        $spookySeason = ($today->month === 10 && $today->day >= 24)
            || ($today->month === 11 && $today->day <= 2)
            || app()->environment('local')
            || request()->boolean('boo');
    @endphp

    @if ($spookySeason)
        <style>
            .loan-hero.is-spooky {
                --hero-base: linear-gradient(118deg, #1A0B2E 0%, #4B1D6B 46%, #E8651A 100%);
                box-shadow: 0 12px 40px rgba(232, 101, 26, .35);
            }

            .loan-hero.is-spooky::after {
                background: rgba(255, 140, 0, .25);
            }

            .loan-hero.is-spooky .hero-eyebrow {
                color: #FFB347;
            }

            .spooky-layer {
                position: fixed;
                inset: 0;
                z-index: 2000;
                pointer-events: none;
                overflow: hidden;
            }

            .spooky-bat {
                position: absolute;
                left: -60px;
                font-size: 28px;
                animation: batFly var(--dur, 4s) var(--delay, 0s) ease-in-out forwards;
            }

            .spooky-ghost {
                position: absolute;
                bottom: -90px;
                right: 12%;
                font-size: 64px;
                filter: drop-shadow(0 0 18px rgba(255, 255, 255, .7));
                animation: ghostRise 6s .6s ease-in-out forwards;
            }

            .spooky-toast {
                position: fixed;
                left: 50%;
                bottom: 28px;
                z-index: 2001;
                padding: 10px 18px;
                border-radius: 999px;
                background: #1A0B2E;
                color: #FFB347;
                font-size: .78rem;
                font-weight: 800;
                box-shadow: 0 10px 30px rgba(26, 11, 46, .35);
                transform: translate(-50%, 0);
                animation: heroIn .4s ease both;
            }

            @keyframes batFly {
                0% { transform: translate(0, 0) scaleX(1); opacity: 0; }
                10% { opacity: 1; }
                25% { transform: translate(28vw, -40px) scaleX(1); }
                50% { transform: translate(55vw, 30px) scaleX(1); }
                75% { transform: translate(82vw, -30px) scaleX(1); }
                100% { transform: translate(112vw, 10px) scaleX(1); opacity: 0; }
            }

            @keyframes ghostRise {
                0% { transform: translateY(0); opacity: 0; }
                15% { opacity: .95; }
                50% { transform: translate(-40px, -55vh); }
                85% { opacity: .9; }
                100% { transform: translate(20px, -110vh); opacity: 0; }
            }

            @media (prefers-reduced-motion: reduce) {
                .spooky-bat,
                .spooky-ghost {
                    display: none;
                }
            }
        </style>

        <script>
            (function() {
                var buffer = '';
                var active = false;

                console.log('%c🎃 Psst… it\'s spooky season. Try typing "boo" on this page.',
                    'color:#E8651A;font-weight:bold;');

                document.addEventListener('keydown', function(event) {
                    var target = event.target;
                    var tag = (target.tagName || '').toLowerCase();

                    // Never hijack typing in forms.
                    if (tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable) {
                        return;
                    }

                    if (!event.key || event.key.length !== 1) {
                        return;
                    }

                    buffer = (buffer + event.key.toLowerCase()).slice(-3);

                    if (buffer === 'boo') {
                        buffer = '';
                        haunt();
                    }
                });

                function haunt() {
                    if (active) return;
                    active = true;

                    var heroes = document.querySelectorAll('.loan-hero');
                    var titles = document.querySelectorAll('.hero-title');
                    var original = [];

                    heroes.forEach(function(hero) {
                        hero.classList.add('is-spooky');
                    });

                    titles.forEach(function(title, i) {
                        original[i] = title.textContent;
                        title.textContent = title.textContent.trim().replace(/Book/, 'Boo-k') + ' 🎃';
                    });

                    var layer = document.createElement('div');
                    layer.className = 'spooky-layer';
                    layer.setAttribute('aria-hidden', 'true');

                    for (var b = 0; b < 9; b++) {
                        var bat = document.createElement('span');
                        bat.className = 'spooky-bat';
                        bat.textContent = '🦇';
                        bat.style.top = (8 + Math.random() * 60) + 'vh';
                        bat.style.setProperty('--delay', (Math.random() * 2.5).toFixed(2) + 's');
                        bat.style.setProperty('--dur', (3 + Math.random() * 2.5).toFixed(2) + 's');
                        bat.style.fontSize = (20 + Math.random() * 18) + 'px';
                        layer.appendChild(bat);
                    }

                    var ghost = document.createElement('span');
                    ghost.className = 'spooky-ghost';
                    ghost.textContent = '👻';
                    layer.appendChild(ghost);

                    var toast = document.createElement('div');
                    toast.className = 'spooky-toast';
                    toast.setAttribute('role', 'status');
                    toast.textContent = '🎃 Happy Halloween from the Loan Book team!';

                    document.body.appendChild(layer);
                    document.body.appendChild(toast);

                    setTimeout(function() {
                        heroes.forEach(function(hero) {
                            hero.classList.remove('is-spooky');
                        });

                        titles.forEach(function(title, i) {
                            title.textContent = original[i];
                        });

                        layer.remove();
                        toast.remove();
                        active = false;
                    }, 8000);
                }
            })();
        </script>
    @endif

    @stack('scripts')

</body>

</html>
