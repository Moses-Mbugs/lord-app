@extends('layouts.finance.template')

@section('title', 'Loan Movements Dashboard')

@push('styles')
    <style>
        :root {
            --eco-blue: #0077A8;
            --eco-blue-dark: #004F71;
            --eco-blue-soft: #E8F4F9;
            --eco-green: #0F766E;
            --eco-green-soft: #E7F6F2;
            --eco-red: #B42318;
            --eco-red-soft: #FDECEC;
            --eco-lime: #BED600;
            --eco-lime-text: #536000;
            --eco-amber: #B86E00;
            --eco-text: #243746;
            --eco-heading: #163046;
            --eco-muted: #5F6F82;
            --eco-border: #D7E1EA;
            --eco-bg: #F3F6F9;
            --eco-panel: #FFFFFF;
            --eco-shadow: 0 2px 10px rgba(16, 24, 40, 0.06);
            --eco-shadow-raised: 0 12px 28px rgba(16, 24, 40, 0.12);
            --eco-focus: 0 0 0 4px rgba(0, 119, 168, 0.22);
            --eco-radius: 16px;
        }

        .finance-home,
        .finance-home * {
            box-sizing: border-box;
        }

        .finance-home {
            min-height: 100vh;
            background: linear-gradient(180deg, #EEF3F7 0%, #F8FAFC 100%);
            color: var(--eco-text);
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            zoom: 0.75;
        }

        .sr-only {
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

        [hidden] {
            display: none !important;
        }

        button,
        input {
            font: inherit;
        }

        button,
        a,
        input {
            -webkit-tap-highlight-color: transparent;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: none;
            box-shadow: var(--eco-focus);
        }

        /* Banner */
        .dash-banner {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 24px;
            padding: 24px clamp(16px, 2vw, 32px);
            background:
                radial-gradient(circle at 90% 0%, rgba(190, 214, 0, 0.18), transparent 30%),
                linear-gradient(135deg, #004861 0%, #005F86 46%, #0087B8 100%);
        }

        .dash-banner::after {
            content: '';
            position: absolute;
            inset: auto -10% -70% 42%;
            z-index: -1;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            transform: rotate(-8deg);
        }

        .banner-copy,
        .banner-meta {
            position: relative;
            z-index: 2;
        }

        .banner-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .banner-eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--eco-lime);
            box-shadow: 0 0 0 4px rgba(190, 214, 0, 0.15);
        }

        .banner-title {
            margin: 0 0 7px;
            color: #FFFFFF;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.08;
        }

        .banner-sub {
            max-width: 760px;
            margin: 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: 14px;
            line-height: 1.6;
        }

        .banner-meta {
            min-width: 230px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
        }

        .banner-meta-label {
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .banner-meta-value {
            color: #FFFFFF;
            font-size: 20px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .banner-meta-note {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 6px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--eco-lime);
        }

        .banner-particles {
            position: absolute;
            inset: 0;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .banner-particle {
            position: absolute;
            display: block;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.24);
            animation: floatParticle linear infinite;
        }

        .banner-particle.alt {
            background: rgba(190, 214, 0, 0.26);
        }

        @keyframes floatParticle {
            0% {
                transform: translate3d(0, 20px, 0) scale(0.8);
                opacity: 0;
            }

            20% {
                opacity: 0.7;
            }

            100% {
                transform: translate3d(0, -120px, 0) scale(1.15);
                opacity: 0;
            }
        }

        /* Page shell */
        .finance-shell {
            width: 100%;
            padding: 18px clamp(12px, 1.6vw, 26px) 28px;
        }

        .summary-grid,
        .segment-grid,
        .mix-grid,
        .insight-grid {
            display: grid;
            gap: 12px;
        }

        .summary-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-bottom: 14px;
        }

        .summary-card,
        .segment-card,
        .mix-card,
        .panel-card,
        .insight-strip {
            border: 1px solid var(--eco-border);
            border-radius: var(--eco-radius);
            background: var(--eco-panel);
            box-shadow: var(--eco-shadow);
        }

        .summary-card {
            min-width: 0;
            padding: 16px;
            border-left: 4px solid var(--accent, var(--eco-blue));
        }

        .summary-label,
        .segment-chip,
        .mix-title,
        .insight-label {
            color: var(--eco-muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .summary-value {
            margin: 8px 0 10px;
            color: var(--eco-blue-dark);
            font-size: clamp(22px, 2vw, 28px);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .summary-value.is-up {
            color: var(--eco-green);
        }

        .summary-value.is-down {
            color: var(--eco-red);
        }

        .summary-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .summary-range,
        .panel-subtitle,
        .segment-meta,
        .mix-subtitle,
        .toolbar-note {
            color: var(--eco-muted);
            font-size: 12px;
        }

        .summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 28px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .summary-badge.up {
            color: #065F46;
            background: var(--eco-green-soft);
        }

        .summary-badge.down {
            color: #991B1B;
            background: var(--eco-red-soft);
        }

        .summary-badge.flat {
            color: var(--eco-blue-dark);
            background: var(--eco-blue-soft);
        }

        /* Insight strip */
        .insight-strip {
            margin-bottom: 14px;
            padding: 14px 16px;
        }

        .insight-strip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .insight-strip-title {
            margin: 0;
            color: var(--eco-heading);
            font-size: 14px;
            font-weight: 800;
        }

        .insight-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .insight-item {
            min-width: 0;
            padding: 10px 12px;
            border-radius: 12px;
            background: #F7FAFC;
            border: 1px solid #E6EDF3;
        }

        .insight-value {
            margin-top: 4px;
            color: var(--eco-heading);
            font-size: 14px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }

        .insight-value.is-up {
            color: var(--eco-green);
        }

        .insight-value.is-down {
            color: var(--eco-red);
        }

        /* Sticky toolbar */
        .dashboard-toolbar {
            position: sticky;
            top: 8px;
            z-index: 20;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
            padding: 8px;
            border: 1px solid var(--eco-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 8px 22px rgba(16, 24, 40, 0.08);
            backdrop-filter: blur(10px);
        }

        .tab-strip,
        .period-control,
        .mini-segmented {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            border-radius: 12px;
            background: #EFF4F7;
            border: 1px solid #E0E8EF;
        }

        .tab-btn,
        .period-btn,
        .mini-segmented-btn,
        .panel-action,
        .branch-limit-btn {
            min-height: 40px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--eco-muted);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        }

        .tab-btn,
        .period-btn {
            padding: 8px 14px;
            white-space: nowrap;
        }

        .tab-btn[aria-selected='true'],
        .period-btn[aria-pressed='true'],
        .mini-segmented-btn[aria-pressed='true'] {
            color: #FFFFFF;
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            box-shadow: 0 5px 12px rgba(0, 119, 168, 0.20);
        }

        .toolbar-note {
            min-width: 0;
            margin: 0;
            line-height: 1.45;
        }

        .toolbar-period-wrap {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .toolbar-period-label {
            color: var(--eco-muted);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .tab-pane {
            display: block;
        }

        /* Segment cards */
        .segment-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .segment-card {
            display: block;
            min-height: 100%;
            padding: 16px;
            color: inherit;
            text-decoration: none;
            border-left: 4px solid var(--seg-accent, var(--eco-blue));
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .segment-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--eco-shadow-raised);
        }

        .segment-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .segment-title {
            margin: 3px 0 0;
            color: var(--eco-heading);
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
        }

        .segment-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--eco-blue-soft);
            color: var(--seg-accent, var(--eco-blue));
            font-size: 18px;
            font-weight: 800;
        }

        .segment-balance {
            margin-bottom: 5px;
            color: var(--eco-blue-dark);
            font-size: clamp(20px, 2vw, 25px);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .segment-delta {
            margin-top: 7px;
        }

        .segment-delta-inner {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 28px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .delta-up {
            color: #065F46;
            background: var(--eco-green-soft);
        }

        .delta-down {
            color: #991B1B;
            background: var(--eco-red-soft);
        }

        .delta-flat {
            color: var(--eco-muted);
            background: #EEF3F6;
        }

        /* Panels */
        .panel-card {
            margin-bottom: 14px;
            padding: 18px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .panel-heading-group {
            min-width: 0;
        }

        .panel-title,
        .mix-headline {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: 16px;
            font-weight: 800;
            line-height: 1.3;
        }

        .panel-subtitle,
        .mix-subtitle {
            margin: 5px 0 0;
            line-height: 1.5;
        }

        .panel-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            flex-wrap: wrap;
        }

        .panel-action,
        .branch-limit-btn {
            min-height: 38px;
            padding: 7px 11px;
            border: 1px solid var(--eco-border);
            background: #FFFFFF;
            color: var(--eco-blue-dark);
        }

        .panel-action:hover,
        .branch-limit-btn:hover {
            background: var(--eco-blue-soft);
            border-color: #B7D9E8;
        }

        /* Charts */
        .chart-shell {
            position: relative;
            width: 100%;
            height: 320px;
            min-height: 240px;
        }

        .chart-shell.tall {
            height: 370px;
        }

        .chart-shell.medium {
            height: 270px;
        }

        .chart-shell canvas {
            width: 100% !important;
            height: 100% !important;
            transition: opacity 0.15s ease;
        }

        .chart-shell.updating canvas {
            opacity: 0.35;
        }

        .chart-empty {
            position: absolute;
            inset: 12px;
            display: grid;
            place-items: center;
            padding: 20px;
            border: 1px dashed var(--eco-border);
            border-radius: 12px;
            background: #FAFCFD;
            color: var(--eco-muted);
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .chart-shell.skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 4;
            border-radius: 12px;
            background: linear-gradient(90deg, #EEF2F6 25%, #DDE6EF 50%, #EEF2F6 75%);
            background-size: 200% 100%;
            animation: shimmer 1.3s ease-in-out infinite;
        }

        /* Composition */
        .mix-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .mix-card {
            min-width: 0;
            padding: 15px;
        }

        .mix-card .chart-shell {
            height: 225px;
            min-height: 200px;
            margin-top: 10px;
        }

        .mix-breakdown {
            display: grid;
            gap: 7px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #E9EFF4;
        }

        .mix-breakdown-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            color: var(--eco-text);
            font-size: 12px;
        }

        .mix-breakdown-label {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 7px;
        }

        .mix-dot {
            width: 9px;
            height: 9px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--dot, var(--eco-blue));
        }

        .mix-breakdown-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mix-breakdown-value {
            color: var(--eco-heading);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* Top movers */
        .movers-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .movers-subhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .movers-subtitle {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: 13px;
            font-weight: 800;
        }

        @media (max-width: 980px) {
            .movers-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Data tables */
        .data-table-wrap {
            margin-top: 14px;
            overflow-x: auto;
            border: 1px solid var(--eco-border);
            border-radius: 12px;
            background: #FFFFFF;
        }

        .data-table {
            width: 100%;
            min-width: 660px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .data-table caption {
            padding: 12px 14px;
            color: var(--eco-heading);
            font-weight: 800;
            text-align: left;
            background: #F7FAFC;
            border-bottom: 1px solid var(--eco-border);
        }

        .data-table th,
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #E9EFF4;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .data-table th:first-child,
        .data-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            text-align: left;
            background: #FFFFFF;
        }

        .data-table thead th {
            color: var(--eco-heading);
            font-weight: 800;
            background: #F7FAFC;
        }

        .data-table thead th:first-child {
            z-index: 2;
            background: #F7FAFC;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .data-table tbody tr:hover td,
        .data-table tbody tr:hover th {
            background: #F8FBFD;
        }

        .data-table tbody tr:hover td:first-child,
        .data-table tbody tr:hover th:first-child {
            background: #F8FBFD;
        }

        .cell-positive {
            color: var(--eco-green);
            font-weight: 700;
        }

        .cell-negative {
            color: var(--eco-red);
            font-weight: 700;
        }

        /* Empty page state */
        .empty-state-wrap {
            padding: 24px;
        }

        .empty-state {
            max-width: 760px;
            margin: 0 auto;
            padding: 44px 24px;
            border: 1px dashed var(--eco-border);
            border-radius: 18px;
            background: #FFFFFF;
            color: var(--eco-muted);
            text-align: center;
            box-shadow: var(--eco-shadow);
        }

        .empty-state-icon {
            display: inline-grid;
            place-items: center;
            width: 52px;
            height: 52px;
            margin-bottom: 14px;
            border-radius: 999px;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            font-size: 24px;
            font-weight: 800;
        }

        .empty-state h1 {
            margin: 0 0 8px;
            color: var(--eco-blue-dark);
            font-size: 22px;
        }

        .empty-state p {
            margin: 0;
            line-height: 1.6;
        }

        .debug-box {
            margin-top: 16px;
            padding: 12px;
            border: 1px solid var(--eco-border);
            border-radius: 12px;
            background: #F7FAFC;
            text-align: left;
        }

        .debug-box strong {
            color: var(--eco-heading);
        }

        .debug-box code {
            display: block;
            margin-top: 8px;
            padding: 8px;
            overflow-x: auto;
            border-radius: 8px;
            background: #EEF3F6;
            color: var(--eco-blue-dark);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            white-space: nowrap;
        }

        /* Responsive */
        @media (max-width: 1280px) {
            .segment-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .mix-grid {
                grid-template-columns: 1fr;
            }

            .mix-card {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(240px, 0.8fr);
                column-gap: 18px;
                align-items: center;
            }

            .mix-card>.mix-title,
            .mix-card>.mix-headline,
            .mix-card>.mix-subtitle {
                grid-column: 1 / -1;
            }

            .mix-card .chart-shell {
                grid-column: 1;
            }

            .mix-card .mix-breakdown {
                grid-column: 2;
                margin-top: 0;
                padding-top: 0;
                border-top: 0;
            }
        }

        @media (max-width: 980px) {
            .dashboard-toolbar {
                grid-template-columns: 1fr auto;
            }

            .toolbar-note {
                grid-column: 1 / -1;
                grid-row: 2;
                padding: 0 4px 3px;
            }

            .insight-grid {
                grid-template-columns: 1fr;
            }

            .branch-toolbar {
                grid-template-columns: 1fr auto;
            }

            .branch-search {
                grid-column: 1 / -1;
                grid-row: 2;
            }
        }

        @media (max-width: 720px) {
            .dash-banner {
                grid-template-columns: 1fr;
                padding: 20px 16px;
            }

            .banner-meta {
                min-width: 0;
                width: 100%;
            }

            .finance-shell {
                padding: 12px;
            }

            .summary-grid,
            .segment-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-toolbar {
                position: static;
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .tab-strip,
            .period-control {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }

            .period-control {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .tab-btn,
            .period-btn {
                width: 100%;
                padding-inline: 8px;
            }

            .toolbar-period-wrap {
                display: block;
            }

            .toolbar-period-label {
                display: block;
                margin: 0 0 6px 2px;
            }

            .toolbar-note {
                grid-column: auto;
                grid-row: auto;
            }

            .panel-header {
                display: block;
            }

            .panel-actions {
                justify-content: flex-start;
                margin-top: 10px;
            }

            .chart-shell,
            .chart-shell.tall {
                height: 300px;
            }

            .chart-shell.medium {
                height: 280px;
            }

            .mix-card {
                display: block;
            }

            .mix-card .chart-shell {
                height: 230px;
            }

            .mix-card .mix-breakdown {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #E9EFF4;
            }

            .mini-segmented {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }
        }

        @media (max-width: 420px) {

            .panel-card,
            .summary-card,
            .segment-card,
            .mix-card,
            .insight-strip {
                border-radius: 13px;
            }

            .panel-card {
                padding: 14px;
            }

            .banner-title {
                font-size: 25px;
            }

            .banner-sub {
                font-size: 13px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .finance-home *,
            .finance-home *::before,
            .finance-home *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .banner-particles {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finance-home" @if (!empty($asOfDate)) data-loan-dashboard-ready="true" @endif>
        @if (empty($asOfDate))
            <div class="empty-state-wrap">
                <section class="empty-state" aria-labelledby="emptyLoanTitle">
                    <div class="empty-state-icon" aria-hidden="true">!</div>
                    <h1 id="emptyLoanTitle">Loan data is not available</h1>
                    <p>No loan book snapshot data was found. Confirm that the daily Loan Book file has been imported
                        via the Loan Book Pipeline, then refresh this page.</p>

                    @env('local')
                        <div class="debug-box">
                            <strong>Developer diagnostics</strong>
                            <code>SELECT MAX(as_at_date) FROM loan_listings;</code>
                        </div>
                    @endenv
                </section>
            </div>
        @else
            @php
                $asOfCarbon = \Carbon\Carbon::parse($asOfDate);
                $asOfFileDate = $asOfCarbon->format('Y-m-d');
                $formatCompactKes = static function ($value) {
                    if ($value === null || !is_numeric($value)) {
                        return '—';
                    }

                    $number = (float) $value;
                    $absolute = abs($number);
                    $sign = $number < 0 ? '−' : '';

                    if ($absolute >= 1_000_000_000) {
                        $formatted = number_format($absolute / 1_000_000_000, 1);
                        $suffix = 'B';
                    } elseif ($absolute >= 1_000_000) {
                        $formatted = number_format($absolute / 1_000_000, 1);
                        $suffix = 'M';
                    } elseif ($absolute >= 1_000) {
                        $formatted = number_format($absolute / 1_000, 1);
                        $suffix = 'K';
                    } else {
                        $formatted = number_format($absolute, 0);
                        $suffix = '';
                    }

                    if ($suffix !== '') {
                        $formatted = rtrim(rtrim($formatted, '0'), '.');
                    }

                    return $sign . 'KES ' . $formatted . $suffix;
                };
            @endphp

            <header class="dash-banner">
                <div class="banner-particles" id="dashboardBannerParticles" aria-hidden="true"></div>

                <div class="banner-copy">
                    <div class="banner-eyebrow">
                        <span class="banner-eyebrow-dot" aria-hidden="true"></span>
                        Loan analytics
                    </div>
                    <h1 class="banner-title">Loan Movements Dashboard</h1>
                    <p class="banner-sub">Review the performing loan book, movement trends, and asset quality across
                        business segments.</p>
                </div>

                <div class="banner-meta" aria-label="Data date and status">
                    <div class="banner-meta-label">As at date</div>
                    <div class="banner-meta-value">{{ $asOfCarbon->format('d M Y') }}</div>
                    <div class="banner-meta-note">
                        <span class="status-dot" aria-hidden="true"></span>
                        Latest available closing date
                    </div>
                </div>
            </header>

            <main class="finance-shell">
                <section class="summary-grid" aria-label="Loan summary">
                    @foreach ($summaryCards ?? [] as $card)
                        @php
                            $isPlaceholder = !empty($card['is_placeholder']);
                            $hasBadge = !empty($card['badge']);
                            $changePct = $card['change_pct'] ?? null;
                            $direction = $card['direction'] ?? 'flat';
                            $isFlat =
                                $isPlaceholder || is_null($changePct) || !in_array($direction, ['up', 'down'], true);
                            $valueClass = $isFlat ? 'is-flat' : ($direction === 'up' ? 'is-up' : 'is-down');
                            $badgeClass = $isFlat ? 'flat' : ($direction === 'up' ? 'up' : 'down');
                            $arrow = $direction === 'up' ? '▲' : ($direction === 'down' ? '▼' : '');
                        @endphp

                        <article class="summary-card" style="--accent: {{ $card['accent'] ?? '#0077A8' }}">
                            <div class="summary-label">{{ $card['label'] ?? 'Summary' }}</div>
                            <div class="summary-value {{ $valueClass }}">
                                {{ !$isFlat ? $arrow . ' ' : '' }}{{ $card['value'] ?? '—' }}</div>
                            <div class="summary-foot">
                                <div class="summary-range">{{ $card['range'] ?? '' }}</div>

                                @if ($isPlaceholder)
                                    <span class="summary-badge flat">Pending</span>
                                @elseif ($hasBadge)
                                    <span class="summary-badge flat">{{ $card['badge'] }}</span>
                                @elseif ($isFlat)
                                    <span class="summary-badge flat">Balance</span>
                                @else
                                    <span class="summary-badge {{ $badgeClass }}">
                                        {{ $arrow }} {{ number_format(abs((float) $changePct), 1) }}%
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="insight-strip" aria-labelledby="insightStripTitle">
                    <div class="insight-strip-header">
                        <h2 class="insight-strip-title" id="insightStripTitle">Key insights</h2>
                        <span class="summary-range" id="insightPeriodLabel">Daily view</span>
                    </div>
                    <div class="insight-grid" aria-live="polite">
                        <article class="insight-item">
                            <div class="insight-label">Latest overall movement</div>
                            <div class="insight-value" id="insightOverall">Calculating…</div>
                        </article>
                        <article class="insight-item">
                            <div class="insight-label">Largest segment movement</div>
                            <div class="insight-value" id="insightSegment">Calculating…</div>
                        </article>
                        <article class="insight-item">
                            <div class="insight-label">Largest single-CIF movement</div>
                            <div class="insight-value" id="insightTopMover">Calculating…</div>
                        </article>
                    </div>
                </section>

                <div class="dashboard-toolbar" aria-label="Dashboard controls">
                    <div class="tab-strip" role="tablist" aria-label="Loan dashboard views">
                        <button type="button" class="tab-btn" id="loanBookTab" role="tab" aria-selected="true"
                            aria-controls="tab-loanbook" tabindex="0" data-tab="loanbook">
                            Loan Book
                        </button>
                        <button type="button" class="tab-btn" id="movementTab" role="tab" aria-selected="false"
                            aria-controls="tab-movement" tabindex="-1" data-tab="movement">
                            Movements
                        </button>
                    </div>

                    <p class="toolbar-note" id="periodDefinition">Each point uses the latest available business-day close.
                    </p>

                    <div class="toolbar-period-wrap">
                        <span class="toolbar-period-label">Trend granularity</span>
                        <div class="period-control" role="group" aria-label="Chart granularity">
                            <button type="button" class="period-btn" data-period="daily" aria-pressed="true">Daily</button>
                            <button type="button" class="period-btn" data-period="weekly"
                                aria-pressed="false">Weekly</button>
                            <button type="button" class="period-btn" data-period="monthly"
                                aria-pressed="false">Monthly</button>
                        </div>
                    </div>
                </div>

                @php
                    $segmentCards = [
                        ['label' => 'Corporate', 'color' => '#005B82'],
                        ['label' => 'Commercial', 'color' => '#008FC7'],
                        ['label' => 'Consumer', 'color' => '#10B981'],
                    ];
                    $segmentPie = $chartPayload['segmentPie'] ?? ['labels' => [], 'data' => [], 'colors' => []];
                    $segmentPieMap = collect($segmentPie['labels'] ?? [])->mapWithKeys(
                        fn($label, $index) => [
                            $label => [
                                'value' => $segmentPie['data'][$index] ?? 0,
                                'color' => $segmentPie['colors'][$index] ?? '#0077A8',
                            ],
                        ],
                    );
                    $pieTotal = collect($segmentPie['data'] ?? [])
                        ->filter(fn($value) => is_numeric($value))
                        ->sum();
                @endphp

                <section class="tab-pane" id="tab-loanbook" role="tabpanel" aria-labelledby="loanBookTab">

                    <div class="segment-grid" aria-label="Performing loan book by business segment">
                        @foreach ($segmentCards as $segmentCard)
                            @php
                                $hasValue = $segmentPieMap->has($segmentCard['label']);
                                $meta = $segmentPieMap[$segmentCard['label']] ?? [
                                    'value' => null,
                                    'color' => $segmentCard['color'],
                                ];
                                $value =
                                    $hasValue && is_numeric($meta['value'] ?? null) ? (float) $meta['value'] : null;
                                $percentage =
                                    $pieTotal > 0 && $value !== null ? round(($value / $pieTotal) * 100, 1) : null;
                            @endphp

                            <div class="segment-card" style="--seg-accent: {{ $meta['color'] }}"
                                data-segment-label="{{ $segmentCard['label'] }}"
                                aria-label="{{ $segmentCard['label'] }} loan book">
                                <div class="segment-card-top">
                                    <div>
                                        <div class="segment-chip">{{ $segmentCard['label'] }}</div>
                                        <h2 class="segment-title">{{ $segmentCard['label'] }} loans</h2>
                                    </div>
                                </div>

                                <div class="segment-balance">{{ $formatCompactKes($value) }}</div>

                                <div class="segment-delta">
                                    <span class="segment-delta-inner delta-flat js-seg-delta" aria-live="polite">Movement
                                        unavailable</span>
                                </div>

                                <div class="segment-meta" style="margin-top: 7px">
                                    {{ $percentage === null ? 'Share unavailable' : number_format($percentage, 1) . '% of performing loan book' }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <article class="panel-card" aria-labelledby="loanOverallTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="loanOverallTitle">Performing loan book by segment</h2>
                                <p class="panel-subtitle" id="loanOverallSub">Daily closing balances split by business
                                    segment.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="loanOverallTableWrap"
                                    aria-controls="loanOverallTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="loanOverall">Export
                                    CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell tall skeleton">
                            <canvas id="loanOverallChart" role="img"
                                aria-label="Stacked bar chart of performing loan book by segment"></canvas>
                            <div class="chart-empty" id="loanOverallEmpty" hidden>No loan balance data is available for
                                this period.</div>
                        </div>

                        <div class="data-table-wrap" id="loanOverallTableWrap" hidden>
                            <table class="data-table" id="loanOverallTable">
                                <caption>Performing loan book by segment</caption>
                            </table>
                        </div>
                    </article>

                    <article class="panel-card" aria-labelledby="mixOverviewTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="mixOverviewTitle">Contribution and mix overview</h2>
                                <p class="panel-subtitle">Current-date mix; this section is not affected by trend
                                    granularity.</p>
                            </div>
                        </div>

                        <div class="mix-grid">
                            <section class="mix-card" aria-labelledby="segmentMixTitle">
                                <div class="mix-title">Business segments</div>
                                <h3 class="mix-headline" id="segmentMixTitle">Contribution mix</h3>
                                <p class="mix-subtitle">Share of the current performing loan book by segment.</p>
                                <div class="chart-shell skeleton">
                                    <canvas id="segmentMixChart" role="img"
                                        aria-label="Horizontal bar chart of segment contribution percentages"></canvas>
                                    <div class="chart-empty" id="segmentMixEmpty" hidden>No segment mix data is available.
                                    </div>
                                </div>
                                <div class="mix-breakdown" id="segmentMixBreakdown" aria-label="Segment mix values">
                                </div>
                            </section>

                            <section class="mix-card" aria-labelledby="currencyMixTitle">
                                <div class="mix-title">Currency</div>
                                <h3 class="mix-headline" id="currencyMixTitle">Currency mix</h3>
                                <p class="mix-subtitle">Local-currency versus foreign-currency loan balances.</p>
                                <div class="chart-shell skeleton">
                                    <canvas id="currencyMixChart" role="img"
                                        aria-label="Horizontal bar chart of currency mix percentages"></canvas>
                                    <div class="chart-empty" id="currencyMixEmpty" hidden>No currency mix data is
                                        available.</div>
                                </div>
                                <div class="mix-breakdown" id="currencyMixBreakdown" aria-label="Currency mix values">
                                </div>
                            </section>

                            <section class="mix-card" aria-labelledby="statusMixTitle">
                                <div class="mix-title">Asset quality</div>
                                <h3 class="mix-headline" id="statusMixTitle">Status mix</h3>
                                <p class="mix-subtitle">Performing, watch, and non-performing shares of the total loan
                                    book.</p>
                                <div class="chart-shell skeleton">
                                    <canvas id="statusMixChart" role="img"
                                        aria-label="Horizontal bar chart of loan status mix percentages"></canvas>
                                    <div class="chart-empty" id="statusMixEmpty" hidden>No status mix data is
                                        available.</div>
                                </div>
                                <div class="mix-breakdown" id="statusMixBreakdown" aria-label="Status mix values">
                                </div>
                            </section>
                        </div>
                    </article>
                </section>

                <section class="tab-pane" id="tab-movement" role="tabpanel" aria-labelledby="movementTab" hidden>

                    <article class="panel-card" aria-labelledby="overallMovementTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="overallMovementTitle">Overall movement trend</h2>
                                <p class="panel-subtitle" id="overallSub">Net movement between consecutive business-day
                                    closing points.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="overallTableWrap"
                                    aria-controls="overallTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="overall">Export CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell tall skeleton">
                            <canvas id="overallChart" role="img"
                                aria-label="Line chart of total loan book movement"></canvas>
                            <div class="chart-empty" id="overallEmpty" hidden>No overall movement data is available for
                                this period.</div>
                        </div>

                        <div class="data-table-wrap" id="overallTableWrap" hidden>
                            <table class="data-table" id="overallTable">
                                <caption>Overall movement trend</caption>
                            </table>
                        </div>
                    </article>

                    <article class="panel-card" aria-labelledby="segmentMovementTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="segmentMovementTitle">Segment movement trend</h2>
                                <p class="panel-subtitle">Compare Corporate, Commercial, and Consumer segments over the
                                    selected period.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="segmentTableWrap"
                                    aria-controls="segmentTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="segment">Export CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell tall skeleton">
                            <canvas id="segmentChart" role="img"
                                aria-label="Line chart of movement by business segment"></canvas>
                            <div class="chart-empty" id="segmentEmpty" hidden>No segment movement data is available for
                                this period.</div>
                        </div>

                        <div class="data-table-wrap" id="segmentTableWrap" hidden>
                            <table class="data-table" id="segmentTable">
                                <caption>Segment movement trend</caption>
                            </table>
                        </div>
                    </article>

                    <article class="panel-card" aria-labelledby="mtdYtdTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="mtdYtdTitle">MTD versus YTD by segment</h2>
                                <p class="panel-subtitle">Fixed month-to-date and year-to-date comparison; this chart is
                                    not affected by trend granularity.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="mtdYtdTableWrap"
                                    aria-controls="mtdYtdTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="mtdYtd">Export CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell medium skeleton">
                            <canvas id="mtdYtdChart" role="img"
                                aria-label="Grouped horizontal bar chart comparing month-to-date and year-to-date movement by segment"></canvas>
                            <div class="chart-empty" id="mtdYtdEmpty" hidden>No MTD or YTD data is available.</div>
                        </div>

                        <div class="data-table-wrap" id="mtdYtdTableWrap" hidden>
                            <table class="data-table" id="mtdYtdTable">
                                <caption>MTD versus YTD by segment</caption>
                            </table>
                        </div>
                    </article>

                    <article class="panel-card" aria-labelledby="topMoversTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="topMoversTitle">Top mover accounts</h2>
                                <p class="panel-subtitle">Individual CIFs with the largest balance change over the
                                    comparison window.</p>
                            </div>
                        </div>

                        <div class="movers-grid">
                            <div>
                                <div class="movers-subhead">
                                    <h3 class="movers-subtitle">Top gainers</h3>
                                    <button type="button" class="panel-action" data-export-key="topGainers">Export
                                        CSV</button>
                                </div>
                                <div class="data-table-wrap">
                                    <table class="data-table">
                                        <caption>Top gainers</caption>
                                        <thead>
                                            <tr>
                                                <th scope="col">Customer</th>
                                                <th scope="col">Branch</th>
                                                <th scope="col">Segment</th>
                                                <th scope="col">Start balance</th>
                                                <th scope="col">End balance</th>
                                                <th scope="col">Movement</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (($topMovers['gainers'] ?? []) as $row)
                                                <tr>
                                                    <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                                    <td>{{ $row['branch'] ?? '—' }}</td>
                                                    <td>{{ $row['business_segment'] ?? '—' }}</td>
                                                    <td>{{ $formatCompactKes($row['start_balance'] ?? null) }}</td>
                                                    <td>{{ $formatCompactKes($row['end_balance'] ?? null) }}</td>
                                                    <td class="cell-positive">
                                                        {{ $formatCompactKes($row['movement'] ?? null) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" style="text-align:center">No gainers for this
                                                        period.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <div class="movers-subhead">
                                    <h3 class="movers-subtitle">Top losers</h3>
                                    <button type="button" class="panel-action" data-export-key="topLosers">Export
                                        CSV</button>
                                </div>
                                <div class="data-table-wrap">
                                    <table class="data-table">
                                        <caption>Top losers</caption>
                                        <thead>
                                            <tr>
                                                <th scope="col">Customer</th>
                                                <th scope="col">Branch</th>
                                                <th scope="col">Segment</th>
                                                <th scope="col">Start balance</th>
                                                <th scope="col">End balance</th>
                                                <th scope="col">Movement</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (($topMovers['losers'] ?? []) as $row)
                                                <tr>
                                                    <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                                    <td>{{ $row['branch'] ?? '—' }}</td>
                                                    <td>{{ $row['business_segment'] ?? '—' }}</td>
                                                    <td>{{ $formatCompactKes($row['start_balance'] ?? null) }}</td>
                                                    <td>{{ $formatCompactKes($row['end_balance'] ?? null) }}</td>
                                                    <td class="cell-negative">
                                                        {{ $formatCompactKes($row['movement'] ?? null) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" style="text-align:center">No losers for this
                                                        period.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </main>
        @endif
    </div>
@endsection

@if (!empty($asOfDate))
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js">
        </script>
        <script src="{{ asset('js/easter-egg.js') }}"></script>
        <script>
            (() => {
                'use strict';

                const chartPayload = @json($chartPayload ?? []);
                const mtdYtdPayload = @json($mtdYtdPayload ?? []);
                const topMovers = @json($topMovers ?? ['gainers' => [], 'losers' => []]);
                const asOfDateForFile = @json($asOfFileDate);

                const PERIOD_COPY = {
                    daily: {
                        label: 'Daily view',
                        definition: 'Each point uses the latest available business-day close and compares it with the previous available business day.',
                        loanBook: 'Daily closing balances split by business segment, with the EOY baseline identified where supplied.',
                        overall: 'Net movement between consecutive available business-day closing points.'
                    },
                    weekly: {
                        label: 'Weekly view',
                        definition: 'Each weekly point resolves to the latest available closing date on or before Friday.',
                        loanBook: 'Weekly closing balances split by business segment, with the EOY baseline identified where supplied.',
                        overall: 'Net movement between consecutive weekly closing points.'
                    },
                    monthly: {
                        label: 'Monthly view',
                        definition: 'Each monthly point uses the last available closing record in the calendar month.',
                        loanBook: 'Month-end balances split by business segment, with the EOY baseline identified where supplied.',
                        overall: 'Net movement between consecutive month-end closing points.'
                    }
                };

                const state = {
                    period: 'daily',
                    tab: 'loanbook'
                };

                const charts = {
                    loanOverall: null,
                    overall: null,
                    segment: null,
                    mtdYtd: null,
                    segmentMix: null,
                    currencyMix: null,
                    statusMix: null
                };

                const exportData = {};
                const exactNumberFormatter = new Intl.NumberFormat('en-KE', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });

                const tip = {
                    backgroundColor: 'rgba(255,255,255,0.98)',
                    borderWidth: 1,
                    borderColor: 'rgba(0,119,168,0.22)',
                    titleColor: '#004F71',
                    bodyColor: '#243746',
                    padding: 11,
                    cornerRadius: 9,
                    displayColors: true
                };

                function safeArray(value) {
                    return Array.isArray(value) ? value : [];
                }

                function asNumber(value) {
                    if (value === null || value === undefined || value === '') {
                        return null;
                    }

                    const number = Number(value);
                    return Number.isFinite(number) ? number : null;
                }

                function hasNumericData(values) {
                    return safeArray(values).some(value => asNumber(value) !== null);
                }

                function fKes(value, decimals = 1) {
                    const number = asNumber(value);
                    if (number === null) return '—';

                    const absolute = Math.abs(number);
                    const sign = number < 0 ? '−' : '';
                    let divisor = 1;
                    let suffix = '';

                    if (absolute >= 1e9) {
                        divisor = 1e9;
                        suffix = 'B';
                    } else if (absolute >= 1e6) {
                        divisor = 1e6;
                        suffix = 'M';
                    } else if (absolute >= 1e3) {
                        divisor = 1e3;
                        suffix = 'K';
                    }

                    let formatted = (absolute / divisor).toFixed(suffix ? decimals : 0);
                    formatted = formatted
                        .replace(/(\.\d*?[1-9])0+$/, '$1')
                        .replace(/\.0+$/, '');

                    return `${sign}KES ${formatted}${suffix}`;
                }

                function fKesExact(value) {
                    const number = asNumber(value);
                    if (number === null) return '—';
                    const sign = number < 0 ? '−' : '';
                    return `${sign}KES ${exactNumberFormatter.format(Math.abs(number))}`;
                }

                function fAxis(value) {
                    const number = asNumber(value);
                    if (number === null) return '—';
                    const absolute = Math.abs(number);

                    if (absolute >= 1e9) return `${(number / 1e9).toFixed(1)}B`;
                    if (absolute >= 1e6) return `${(number / 1e6).toFixed(1)}M`;
                    if (absolute >= 1e3) return `${(number / 1e3).toFixed(1)}K`;
                    return Number(number).toFixed(0);
                }

                function fPercent(value) {
                    const number = asNumber(value);
                    return number === null ? '—' : `${number.toFixed(1)}%`;
                }

                function movementClass(value) {
                    const number = asNumber(value);
                    if (number === null || number === 0) return '';
                    return number > 0 ? 'cell-positive' : 'cell-negative';
                }

                function setValueTone(element, value) {
                    if (!element) return;
                    const number = asNumber(value);
                    element.classList.remove('is-up', 'is-down');
                    if (number > 0) element.classList.add('is-up');
                    if (number < 0) element.classList.add('is-down');
                }

                function removeSkeleton(canvasId) {
                    const canvas = document.getElementById(canvasId);
                    const shell = canvas?.closest('.chart-shell');
                    shell?.classList.remove('skeleton');
                }

                function withFade(canvasId, callback) {
                    const canvas = document.getElementById(canvasId);
                    const shell = canvas?.closest('.chart-shell');
                    shell?.classList.add('updating');
                    callback();
                    window.setTimeout(() => shell?.classList.remove('updating'), 170);
                }

                function setChartAvailability(canvasId, emptyId, hasData, message = '') {
                    const canvas = document.getElementById(canvasId);
                    const empty = document.getElementById(emptyId);

                    if (canvas) canvas.hidden = !hasData;
                    if (empty) {
                        empty.hidden = hasData;
                        if (message) empty.textContent = message;
                    }

                    removeSkeleton(canvasId);
                }

                function getStoredValue(key) {
                    try {
                        return window.localStorage.getItem(key);
                    } catch (error) {
                        return null;
                    }
                }

                function storeValue(key, value) {
                    try {
                        window.localStorage.setItem(key, value);
                    } catch (error) {
                        // Storage is optional; the dashboard still works without it.
                    }
                }

                function seedParticles(id, count = 14) {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    const host = document.getElementById(id);
                    if (!host) return;
                    host.innerHTML = '';

                    for (let index = 0; index < count; index += 1) {
                        const particle = document.createElement('span');
                        particle.className = `banner-particle${index % 4 === 0 ? ' alt' : ''}`;
                        particle.style.left = `${Math.random() * 100}%`;
                        particle.style.bottom = `${-20 - Math.random() * 36}px`;
                        particle.style.animationDuration = `${8 + Math.random() * 6}s`;
                        particle.style.animationDelay = `${Math.random() * 5}s`;
                        particle.style.opacity = String(0.15 + Math.random() * 0.45);
                        const size = 4 + Math.random() * 5;
                        particle.style.width = `${size}px`;
                        particle.style.height = `${size}px`;
                        host.appendChild(particle);
                    }
                }

                function zeroGridColor(context) {
                    return Number(context.tick?.value) === 0 ?
                        'rgba(36,55,70,0.46)' :
                        'rgba(36,55,70,0.08)';
                }

                function zeroGridWidth(context) {
                    return Number(context.tick?.value) === 0 ? 2 : 1;
                }

                function buildTable(tableId, caption, headers, rows, cellClasses = []) {
                    const table = document.getElementById(tableId);
                    if (!table) return;

                    table.replaceChildren();

                    const captionElement = document.createElement('caption');
                    captionElement.textContent = caption;
                    table.appendChild(captionElement);

                    const thead = document.createElement('thead');
                    const headRow = document.createElement('tr');

                    headers.forEach(header => {
                        const th = document.createElement('th');
                        th.scope = 'col';
                        th.textContent = String(header);
                        headRow.appendChild(th);
                    });

                    thead.appendChild(headRow);
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');
                    rows.forEach((row, rowIndex) => {
                        const tr = document.createElement('tr');
                        row.forEach((value, columnIndex) => {
                            const cell = columnIndex === 0 ?
                                document.createElement('th') :
                                document.createElement('td');

                            if (columnIndex === 0) cell.scope = 'row';
                            cell.textContent = value === null || value === undefined ? '—' : String(value);

                            const className = cellClasses[rowIndex]?.[columnIndex];
                            if (className) cell.classList.add(className);
                            tr.appendChild(cell);
                        });
                        tbody.appendChild(tr);
                    });

                    table.appendChild(tbody);
                }

                function csvEscape(value) {
                    const stringValue = value === null || value === undefined ? '' : String(value);
                    return /[",\n]/.test(stringValue) ?
                        `"${stringValue.replace(/"/g, '""')}"` :
                        stringValue;
                }

                function downloadCsv(key) {
                    const payload = exportData[key];
                    if (!payload || !payload.rows?.length) return;

                    const lines = [payload.headers, ...payload.rows]
                        .map(row => row.map(csvEscape).join(','));
                    const blob = new Blob([`﻿${lines.join('\n')}`], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = payload.filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                }

                function renderMixBreakdown(containerId, records) {
                    const container = document.getElementById(containerId);
                    if (!container) return;
                    container.replaceChildren();

                    records.forEach(record => {
                        const row = document.createElement('div');
                        row.className = 'mix-breakdown-row';

                        const label = document.createElement('div');
                        label.className = 'mix-breakdown-label';

                        const dot = document.createElement('span');
                        dot.className = 'mix-dot';
                        dot.style.setProperty('--dot', record.color);
                        dot.setAttribute('aria-hidden', 'true');

                        const name = document.createElement('span');
                        name.className = 'mix-breakdown-name';
                        name.textContent = record.label;

                        const value = document.createElement('div');
                        value.className = 'mix-breakdown-value';
                        value.textContent = `${fKes(record.value)} · ${fPercent(record.percentage)}`;

                        label.append(dot, name);
                        row.append(label, value);
                        container.appendChild(row);
                    });
                }

                function normaliseMixPayload(payload) {
                    const labels = safeArray(payload?.labels);
                    const data = safeArray(payload?.data);
                    const colors = safeArray(payload?.colors);
                    const records = labels.map((label, index) => ({
                        label: String(label),
                        value: asNumber(data[index]) ?? 0,
                        color: colors[index] || '#0077A8'
                    })).filter(record => record.value >= 0);

                    const total = records.reduce((sum, record) => sum + record.value, 0);
                    return records
                        .map(record => ({
                            ...record,
                            percentage: total > 0 ? (record.value / total) * 100 : 0
                        }))
                        .sort((first, second) => second.value - first.value);
                }

                function buildMixChart(canvasId, emptyId, breakdownId, payload) {
                    const records = normaliseMixPayload(payload);
                    const hasData = records.some(record => record.value > 0);
                    setChartAvailability(canvasId, emptyId, hasData);
                    renderMixBreakdown(breakdownId, hasData ? records : []);

                    const canvas = document.getElementById(canvasId);
                    if (!canvas || !hasData) return null;

                    return new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: records.map(record => record.label),
                            datasets: [{
                                label: 'Share',
                                data: records.map(record => record.percentage),
                                rawValues: records.map(record => record.value),
                                backgroundColor: records.map(record => record.color),
                                borderColor: records.map(record => record.color),
                                borderWidth: 1,
                                borderRadius: 7,
                                borderSkipped: false,
                                maxBarThickness: 28
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            maintainAspectRatio: false,
                            responsive: true,
                            layout: {
                                padding: {
                                    right: 38
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: Object.assign({}, tip, {
                                    callbacks: {
                                        label(context) {
                                            const rawValue = context.dataset.rawValues?.[context
                                                .dataIndex] ?? 0;
                                            return ` ${fKes(rawValue)} (${fPercent(context.parsed.x)})`;
                                        }
                                    }
                                }),
                                datalabels: {
                                    display: true,
                                    anchor: 'end',
                                    align: 'right',
                                    clamp: true,
                                    color: '#163046',
                                    font: {
                                        size: 11,
                                        weight: '800'
                                    },
                                    formatter: value => fPercent(value)
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: {
                                        color: 'rgba(36,55,70,0.07)'
                                    },
                                    ticks: {
                                        callback: value => `${value}%`,
                                        maxTicksLimit: 6
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#243746',
                                        font: {
                                            size: 11,
                                            weight: '700'
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                function isTopOfStack(context) {
                    const dataIndex = context.dataIndex;
                    const datasets = context.chart.data.datasets;
                    const currentValue = asNumber(context.dataset.data[dataIndex]) ?? 0;
                    if (currentValue === 0) return false;

                    for (let index = context.datasetIndex + 1; index < datasets.length; index += 1) {
                        const laterValue = asNumber(datasets[index]?.data?.[dataIndex]) ?? 0;
                        if (laterValue !== 0) return false;
                    }

                    return true;
                }

                function buildLoanOverallChart() {
                    const canvas = document.getElementById('loanOverallChart');
                    return new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: [],
                            datasets: []
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            layout: {
                                padding: {
                                    top: 18
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 14,
                                        boxWidth: 8,
                                        color: '#243746',
                                        font: {
                                            size: 11,
                                            weight: '700'
                                        }
                                    }
                                },
                                tooltip: Object.assign({}, tip, {
                                    callbacks: {
                                        label: context =>
                                            ` ${context.dataset.label}: ${fKes(context.parsed.y)}`,
                                        footer(items) {
                                            const total = items.reduce((sum, item) => sum + (asNumber(item
                                                .parsed.y) ?? 0), 0);
                                            return `Total: ${fKes(total)}`;
                                        }
                                    }
                                }),
                                datalabels: {
                                    display: isTopOfStack,
                                    anchor: 'end',
                                    align: 'end',
                                    offset: 2,
                                    clamp: true,
                                    color: '#163046',
                                    font: {
                                        size: 10,
                                        weight: '800'
                                    },
                                    formatter(value, context) {
                                        const total = context.chart.data.datasets.reduce((sum, dataset) => {
                                            return sum + (asNumber(dataset.data[context.dataIndex]) ?? 0);
                                        }, 0);
                                        return total === 0 ? '' : fAxis(total);
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 14,
                                        font: {
                                            size: 11,
                                            weight: '600'
                                        }
                                    }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(36,55,70,0.08)'
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        callback: fAxis
                                    }
                                }
                            }
                        }
                    });
                }

                function buildOverallChart() {
                    const canvas = document.getElementById('overallChart');
                    const context = canvas.getContext('2d');
                    const gradient = context.createLinearGradient(0, 0, 0, 360);
                    gradient.addColorStop(0, 'rgba(0,119,168,0.22)');
                    gradient.addColorStop(1, 'rgba(0,119,168,0.01)');

                    return new Chart(context, {
                        type: 'line',
                        data: {
                            labels: [],
                            datasets: [{
                                label: 'Net movement',
                                data: [],
                                borderColor: '#0077A8',
                                backgroundColor: gradient,
                                fill: true,
                                borderWidth: 2.5,
                                tension: 0.32,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBorderWidth: 2,
                                pointBorderColor: '#FFFFFF',
                                pointBackgroundColor(context) {
                                    const value = asNumber(context.raw) ?? 0;
                                    return value >= 0 ? '#0F766E' : '#B42318';
                                }
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: Object.assign({}, tip, {
                                    callbacks: {
                                        label: context => ` Movement: ${fKes(context.parsed.y)}`
                                    }
                                }),
                                datalabels: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(36,55,70,0.06)'
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        maxRotation: 35,
                                        autoSkip: true,
                                        maxTicksLimit: 14
                                    }
                                },
                                y: {
                                    grid: {
                                        color: zeroGridColor,
                                        lineWidth: zeroGridWidth
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        callback: fAxis
                                    }
                                }
                            }
                        }
                    });
                }

                function buildSegmentChart() {
                    const canvas = document.getElementById('segmentChart');
                    return new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: [],
                            datasets: []
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 14,
                                        boxWidth: 8,
                                        color: '#243746',
                                        font: {
                                            size: 11,
                                            weight: '700'
                                        }
                                    }
                                },
                                tooltip: Object.assign({}, tip, {
                                    callbacks: {
                                        label: context =>
                                            ` ${context.dataset.label}: ${fKes(context.parsed.y)}`
                                    }
                                }),
                                datalabels: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(36,55,70,0.06)'
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        maxRotation: 35,
                                        autoSkip: true,
                                        maxTicksLimit: 14
                                    }
                                },
                                y: {
                                    grid: {
                                        color: zeroGridColor,
                                        lineWidth: zeroGridWidth
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        callback: fAxis
                                    }
                                }
                            }
                        }
                    });
                }

                function buildMtdYtdChart() {
                    const canvas = document.getElementById('mtdYtdChart');
                    const labels = safeArray(mtdYtdPayload?.labels);
                    const mtd = safeArray(mtdYtdPayload?.mtd);
                    const ytd = safeArray(mtdYtdPayload?.ytd);
                    const hasData = hasNumericData(mtd) || hasNumericData(ytd);

                    setChartAvailability('mtdYtdChart', 'mtdYtdEmpty', hasData);

                    const tableRows = labels.map((label, index) => [
                        String(label),
                        fKesExact(mtd[index]),
                        fKesExact(ytd[index])
                    ]);
                    const cellClasses = labels.map((label, index) => [
                        '',
                        movementClass(mtd[index]),
                        movementClass(ytd[index])
                    ]);
                    buildTable('mtdYtdTable', 'MTD versus YTD by segment', ['Segment', 'MTD movement', 'YTD movement'],
                        tableRows, cellClasses);
                    exportData.mtdYtd = {
                        filename: `loan-mtd-ytd-${asOfDateForFile}.csv`,
                        headers: ['Segment', 'MTD movement', 'YTD movement'],
                        rows: labels.map((label, index) => [label, mtd[index] ?? '', ytd[index] ?? ''])
                    };

                    if (!hasData) return null;

                    return new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'MTD movement',
                                data: mtd,
                                backgroundColor: 'rgba(0,119,168,0.78)',
                                borderColor: '#0077A8',
                                borderWidth: 1,
                                borderRadius: 6,
                                borderSkipped: false,
                                maxBarThickness: 24
                            }, {
                                label: 'YTD movement',
                                data: ytd,
                                backgroundColor: 'rgba(15,118,110,0.76)',
                                borderColor: '#0F766E',
                                borderWidth: 1,
                                borderRadius: 6,
                                borderSkipped: false,
                                maxBarThickness: 24
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            maintainAspectRatio: false,
                            responsive: true,
                            layout: {
                                padding: {
                                    left: 24,
                                    right: 44
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 14,
                                        boxWidth: 8,
                                        color: '#243746',
                                        font: {
                                            size: 11,
                                            weight: '700'
                                        }
                                    }
                                },
                                tooltip: Object.assign({}, tip, {
                                    callbacks: {
                                        label: context =>
                                            ` ${context.dataset.label}: ${fKes(context.parsed.x)}`
                                    }
                                }),
                                datalabels: {
                                    display(context) {
                                        return asNumber(context.dataset.data[context.dataIndex]) !== null;
                                    },
                                    anchor: 'end',
                                    align(context) {
                                        return Number(context.dataset.data[context.dataIndex]) >= 0 ? 'right' :
                                            'left';
                                    },
                                    clamp: true,
                                    color(context) {
                                        return Number(context.dataset.data[context.dataIndex]) >= 0 ? '#0F766E' :
                                            '#B42318';
                                    },
                                    font: {
                                        size: 10,
                                        weight: '800'
                                    },
                                    formatter: fAxis
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: zeroGridColor,
                                        lineWidth: zeroGridWidth
                                    },
                                    ticks: {
                                        color: '#5F6F82',
                                        callback: fAxis
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#243746',
                                        font: {
                                            weight: '700'
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                function updateLoanOverallChart(period) {
                    const payload = chartPayload?.overallBreakdown?.[period] || {};
                    const labels = safeArray(payload.labels);
                    const datasets = safeArray(payload.datasets);
                    const periods = safeArray(payload.periods);
                    const hasData = datasets.some(dataset => hasNumericData(dataset?.data));

                    setChartAvailability('loanOverallChart', 'loanOverallEmpty', hasData);
                    document.getElementById('loanOverallSub').textContent = PERIOD_COPY[period].loanBook;

                    const tableHeaders = ['Period', ...datasets.map(dataset => dataset.label || 'Segment'), 'Total'];
                    const rawRows = labels.map((label, labelIndex) => {
                        const values = datasets.map(dataset => asNumber(dataset?.data?.[labelIndex]));
                        const total = values.some(value => value !== null) ?
                            values.reduce((sum, value) => sum + (value ?? 0), 0) :
                            null;
                        const isBaseline = periods[labelIndex]?.is_baseline === true;
                        return [isBaseline ? `${label} (EOY baseline)` : label, ...values, total];
                    });
                    const displayRows = rawRows.map(row => [row[0], ...row.slice(1).map(fKesExact)]);
                    buildTable('loanOverallTable', 'Performing loan book by segment', tableHeaders, displayRows);
                    exportData.loanOverall = {
                        filename: `loan-book-by-segment-${period}-${asOfDateForFile}.csv`,
                        headers: tableHeaders,
                        rows: rawRows
                    };

                    if (!charts.loanOverall || !hasData) return;

                    const barConfig = {
                        daily: {
                            maxBarThickness: 28,
                            categoryPercentage: 0.72,
                            barPercentage: 0.84,
                            maxTicksLimit: 14
                        },
                        weekly: {
                            maxBarThickness: 44,
                            categoryPercentage: 0.86,
                            barPercentage: 0.92,
                            maxTicksLimit: 13
                        },
                        monthly: {
                            maxBarThickness: 56,
                            categoryPercentage: 0.92,
                            barPercentage: 0.95,
                            maxTicksLimit: 13
                        }
                    } [period];

                    withFade('loanOverallChart', () => {
                        charts.loanOverall.data.labels = labels;
                        charts.loanOverall.data.datasets = datasets.map(dataset => ({
                            label: dataset.label,
                            data: safeArray(dataset.data),
                            backgroundColor: dataset.color || '#0077A8',
                            borderColor: safeArray(dataset.data).map((value, index) => {
                                return periods[index]?.is_baseline === true ? '#B86E00' : (
                                    dataset.color || '#0077A8');
                            }),
                            borderWidth: safeArray(dataset.data).map((value, index) => {
                                return periods[index]?.is_baseline === true ? 2 : 0;
                            }),
                            borderRadius: 5,
                            borderSkipped: false,
                            maxBarThickness: barConfig.maxBarThickness,
                            categoryPercentage: barConfig.categoryPercentage,
                            barPercentage: barConfig.barPercentage
                        }));
                        charts.loanOverall.options.scales.x.ticks.maxTicksLimit = barConfig.maxTicksLimit;
                        charts.loanOverall.options.scales.x.ticks.autoSkip = period === 'daily';
                        charts.loanOverall.update();
                    });
                }

                function updateOverallChart(period) {
                    const payload = chartPayload?.overall?.[period] || {};
                    const labels = safeArray(payload.labels);
                    const data = safeArray(payload.data);
                    const hasData = hasNumericData(data);

                    setChartAvailability('overallChart', 'overallEmpty', hasData);
                    document.getElementById('overallSub').textContent = PERIOD_COPY[period].overall;

                    const rawRows = labels.map((label, index) => [label, data[index] ?? null]);
                    const displayRows = rawRows.map(row => [row[0], fKesExact(row[1])]);
                    const cellClasses = rawRows.map(row => ['', movementClass(row[1])]);
                    buildTable('overallTable', 'Overall movement trend', ['Period', 'Movement'], displayRows, cellClasses);
                    exportData.overall = {
                        filename: `loan-overall-movement-${period}-${asOfDateForFile}.csv`,
                        headers: ['Period', 'Movement'],
                        rows: rawRows
                    };

                    if (!charts.overall || !hasData) return;

                    withFade('overallChart', () => {
                        charts.overall.data.labels = labels;
                        charts.overall.data.datasets[0].data = data;
                        charts.overall.update();
                    });
                }

                function updateSegmentChart(period) {
                    const payload = chartPayload?.segments?.[period] || {};
                    const labels = safeArray(payload.labels);
                    const datasets = safeArray(payload.datasets);
                    const hasData = datasets.some(dataset => hasNumericData(dataset?.data));

                    setChartAvailability('segmentChart', 'segmentEmpty', hasData);

                    const headers = ['Period', ...datasets.map(dataset => dataset.label || 'Segment')];
                    const rawRows = labels.map((label, labelIndex) => [
                        label,
                        ...datasets.map(dataset => dataset?.data?.[labelIndex] ?? null)
                    ]);
                    const displayRows = rawRows.map(row => [row[0], ...row.slice(1).map(fKesExact)]);
                    const cellClasses = rawRows.map(row => ['', ...row.slice(1).map(movementClass)]);
                    buildTable('segmentTable', 'Segment movement trend', headers, displayRows, cellClasses);
                    exportData.segment = {
                        filename: `loan-segment-movement-${period}-${asOfDateForFile}.csv`,
                        headers,
                        rows: rawRows
                    };

                    if (!charts.segment || !hasData) return;

                    withFade('segmentChart', () => {
                        charts.segment.data.labels = labels;
                        charts.segment.data.datasets = datasets.map(dataset => ({
                            label: dataset.label,
                            data: safeArray(dataset.data),
                            borderColor: dataset.color || '#0077A8',
                            backgroundColor: `${dataset.color || '#0077A8'}1F`,
                            pointBackgroundColor: dataset.color || '#0077A8',
                            pointBorderColor: '#FFFFFF',
                            pointBorderWidth: 1.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            borderWidth: 2.2,
                            tension: 0.32,
                            fill: false,
                            spanGaps: true
                        }));
                        charts.segment.update();
                    });
                }

                function populateSegmentDeltas() {
                    const datasets = safeArray(chartPayload?.segments?.daily?.datasets);
                    const cards = Array.from(document.querySelectorAll('[data-segment-label]'));

                    cards.forEach(card => {
                        const badge = card.querySelector('.js-seg-delta');
                        const dataset = datasets.find(item => String(item.label) === card.dataset.segmentLabel);
                        const values = safeArray(dataset?.data);
                        const latest = values.length ? asNumber(values[values.length - 1]) : null;

                        badge.className = 'segment-delta-inner js-seg-delta';

                        if (latest === null) {
                            badge.classList.add('delta-flat');
                            badge.textContent = 'Movement unavailable';
                            return;
                        }

                        if (latest > 0) {
                            badge.classList.add('delta-up');
                            badge.textContent = `▲ ${fKes(Math.abs(latest))} daily movement`;
                        } else if (latest < 0) {
                            badge.classList.add('delta-down');
                            badge.textContent = `▼ ${fKes(Math.abs(latest))} daily movement`;
                        } else {
                            badge.classList.add('delta-flat');
                            badge.textContent = 'No daily movement';
                        }
                    });
                }

                function seedTopMoversExport() {
                    const headers = ['Customer', 'CIF', 'Branch', 'Segment', 'Start balance', 'End balance', 'Movement'];
                    const toRows = list => safeArray(list).map(row => [
                        row.name ?? '',
                        row.cif ?? '',
                        row.branch ?? '',
                        row.business_segment ?? '',
                        asNumber(row.start_balance) ?? '',
                        asNumber(row.end_balance) ?? '',
                        asNumber(row.movement) ?? ''
                    ]);

                    exportData.topGainers = {
                        filename: `loan-top-gainers-${asOfDateForFile}.csv`,
                        headers,
                        rows: toRows(topMovers?.gainers)
                    };
                    exportData.topLosers = {
                        filename: `loan-top-losers-${asOfDateForFile}.csv`,
                        headers,
                        rows: toRows(topMovers?.losers)
                    };
                }

                function updateTopMoverInsight() {
                    const element = document.getElementById('insightTopMover');
                    if (!element) return;

                    const gainer = safeArray(topMovers?.gainers)[0] || null;
                    const loser = safeArray(topMovers?.losers)[0] || null;
                    const gainerMovement = gainer ? asNumber(gainer.movement) : null;
                    const loserMovement = loser ? asNumber(loser.movement) : null;
                    const gainerAbs = gainerMovement === null ? -1 : Math.abs(gainerMovement);
                    const loserAbs = loserMovement === null ? -1 : Math.abs(loserMovement);

                    const leader = gainerAbs >= loserAbs ? gainer : loser;
                    const movement = leader === gainer ? gainerMovement : loserMovement;

                    if (!leader || movement === null) {
                        element.textContent = 'Data unavailable';
                        setValueTone(element, null);
                        return;
                    }

                    element.textContent = `${leader.name || leader.cif || 'Unknown'} · ${fKes(movement)}`;
                    setValueTone(element, movement);
                }

                function updateInsights(period) {
                    const periodLabel = document.getElementById('insightPeriodLabel');
                    periodLabel.textContent = PERIOD_COPY[period].label;

                    const overallElement = document.getElementById('insightOverall');
                    const overallPayload = chartPayload?.overall?.[period] || {};
                    const overallData = safeArray(overallPayload.data);
                    const overallLabels = safeArray(overallPayload.labels);
                    const overallValue = overallData.length ? asNumber(overallData[overallData.length - 1]) : null;
                    const overallLabel = overallLabels.length ? overallLabels[overallLabels.length - 1] : 'Latest point';
                    overallElement.textContent = overallValue === null ?
                        'Data unavailable' :
                        `${fKes(overallValue)} · ${overallLabel}`;
                    setValueTone(overallElement, overallValue);

                    const segmentElement = document.getElementById('insightSegment');
                    const segmentDatasets = safeArray(chartPayload?.segments?.[period]?.datasets);
                    const segmentRecords = segmentDatasets.map(dataset => {
                        const values = safeArray(dataset?.data);
                        return {
                            label: dataset?.label || 'Segment',
                            value: values.length ? asNumber(values[values.length - 1]) : null
                        };
                    }).filter(record => record.value !== null);
                    segmentRecords.sort((first, second) => Math.abs(second.value) - Math.abs(first.value));
                    const segmentLeader = segmentRecords[0];
                    segmentElement.textContent = segmentLeader ?
                        `${segmentLeader.label} · ${fKes(segmentLeader.value)}` :
                        'Data unavailable';
                    setValueTone(segmentElement, segmentLeader?.value ?? null);
                }

                function setPeriod(period, persist = true) {
                    if (!['daily', 'weekly', 'monthly'].includes(period)) return;
                    state.period = period;

                    document.querySelectorAll('[data-period]').forEach(button => {
                        button.setAttribute('aria-pressed', String(button.dataset.period === period));
                    });
                    document.getElementById('periodDefinition').textContent = PERIOD_COPY[period].definition;

                    updateLoanOverallChart(period);
                    updateOverallChart(period);
                    updateSegmentChart(period);
                    updateInsights(period);

                    if (persist) storeValue('loanDashboardPeriod', period);
                }

                function resizeCharts() {
                    Object.values(charts).forEach(chart => chart?.resize());
                }

                function activateTab(tabName, focusTab = false, persist = true) {
                    if (!['loanbook', 'movement'].includes(tabName)) return;
                    state.tab = tabName;

                    document.querySelectorAll('[role="tab"][data-tab]').forEach(tab => {
                        const active = tab.dataset.tab === tabName;
                        tab.setAttribute('aria-selected', String(active));
                        tab.tabIndex = active ? 0 : -1;
                        if (focusTab && active) tab.focus();
                    });

                    document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                        panel.hidden = panel.id !== `tab-${tabName}`;
                    });

                    if (persist) storeValue('loanDashboardTab', tabName);
                    window.requestAnimationFrame(resizeCharts);
                }

                function bindTabs() {
                    const tabs = Array.from(document.querySelectorAll('[role="tab"][data-tab]'));

                    tabs.forEach((tab, index) => {
                        tab.addEventListener('click', () => activateTab(tab.dataset.tab, false));
                        tab.addEventListener('keydown', event => {
                            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                            event.preventDefault();

                            let nextIndex = index;
                            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
                            if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs
                                .length;
                            if (event.key === 'Home') nextIndex = 0;
                            if (event.key === 'End') nextIndex = tabs.length - 1;

                            activateTab(tabs[nextIndex].dataset.tab, true);
                        });
                    });
                }

                function bindTableToggles() {
                    document.querySelectorAll('[data-toggle-table]').forEach(button => {
                        button.addEventListener('click', () => {
                            const target = document.getElementById(button.dataset.toggleTable);
                            if (!target) return;
                            const opening = target.hidden;
                            target.hidden = !opening;
                            button.setAttribute('aria-expanded', String(opening));
                            button.textContent = opening ? 'Hide data' : 'View data';
                        });
                    });
                }

                function bindExports() {
                    document.querySelectorAll('[data-export-key]').forEach(button => {
                        button.addEventListener('click', () => downloadCsv(button.dataset.exportKey));
                    });
                }

                function handleChartLibraryFailure() {
                    document.querySelectorAll('.chart-shell').forEach(shell => {
                        shell.classList.remove('skeleton');
                    });
                    document.querySelectorAll('.chart-empty').forEach(empty => {
                        empty.hidden = false;
                        empty.textContent =
                            'Charts could not be loaded. Refresh the page or check the Chart.js connection.';
                    });
                    document.querySelectorAll('canvas').forEach(canvas => {
                        canvas.hidden = true;
                    });
                }

                document.addEventListener('DOMContentLoaded', () => {
                    const root = document.querySelector('[data-loan-dashboard-ready="true"]');
                    if (!root) return;

                    seedParticles('dashboardBannerParticles', 14);
                    bindTabs();
                    bindTableToggles();
                    bindExports();
                    seedTopMoversExport();
                    updateTopMoverInsight();

                    if (typeof Chart === 'undefined') {
                        handleChartLibraryFailure();
                        return;
                    }

                    Chart.defaults.color = '#5F6F82';
                    Chart.defaults.font.family = 'Montserrat, Segoe UI, Arial, sans-serif';
                    Chart.defaults.font.size = 11;

                    if (typeof ChartDataLabels !== 'undefined') {
                        Chart.register(ChartDataLabels);
                    }

                    charts.loanOverall = buildLoanOverallChart();
                    charts.overall = buildOverallChart();
                    charts.segment = buildSegmentChart();
                    charts.mtdYtd = buildMtdYtdChart();
                    charts.segmentMix = buildMixChart('segmentMixChart', 'segmentMixEmpty', 'segmentMixBreakdown',
                        chartPayload?.segmentPie);
                    charts.currencyMix = buildMixChart('currencyMixChart', 'currencyMixEmpty',
                        'currencyMixBreakdown', chartPayload?.currencyMixPie);
                    charts.statusMix = buildMixChart('statusMixChart', 'statusMixEmpty', 'statusMixBreakdown',
                        chartPayload?.statusPie);

                    populateSegmentDeltas();

                    const savedPeriod = getStoredValue('loanDashboardPeriod');
                    const initialPeriod = ['daily', 'weekly', 'monthly'].includes(savedPeriod) ? savedPeriod :
                        'daily';
                    setPeriod(initialPeriod, false);

                    const savedTab = getStoredValue('loanDashboardTab');
                    const initialTab = ['loanbook', 'movement'].includes(savedTab) ? savedTab : 'loanbook';
                    activateTab(initialTab, false, false);

                    document.querySelectorAll('[data-period]').forEach(button => {
                        button.addEventListener('click', () => setPeriod(button.dataset.period));
                    });

                    let resizeTimer = null;
                    window.addEventListener('resize', () => {
                        window.clearTimeout(resizeTimer);
                        resizeTimer = window.setTimeout(resizeCharts, 120);
                    });
                });
            })();
        </script>
    @endpush
@endif
