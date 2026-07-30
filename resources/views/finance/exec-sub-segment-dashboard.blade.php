@extends('layouts.finance.template')

@section('title', 'Sub-Segment Executive Dashboard')

@push('styles')
    <style>
        #subSegmentExecutivePage {
            --blue: #0082BB;
            --blue-2: #00A0D4;
            --dark-blue: #005B82;
            --green: #BED600;
            --green-dark: #6A9A16;
            --page-bg: #EEF3F8;
            --panel-bg: #FFFFFF;
            --text: #163247;
            --muted: #6F8091;
            --line: #DCE5EE;
            --line-2: #E8EEF5;
            --soft: #F7FAFD;
            --success: #0F9D6C;
            --danger: #D64545;
            --warning: #C98916;
            --shadow-sm: 0 6px 18px rgba(16, 34, 52, 0.06);
            --shadow-md: 0 14px 34px rgba(16, 34, 52, 0.10);
            --radius: 18px;
            --radius-sm: 12px;
        }

        #subSegmentExecutivePage *,
        #subSegmentExecutivePage *::before,
        #subSegmentExecutivePage *::after {
            box-sizing: border-box;
        }

        body:has(#subSegmentExecutivePage) {
            background: #EEF3F8;
        }

        #subSegmentExecutivePage {
            min-height: 100vh;
            background: var(--page-bg);
            color: var(--text);
        }

        #subSegmentExecutivePage .exec-dashboard {
            width: 100%;
            padding: 14px 18px 24px;
        }

        #subSegmentExecutivePage .hero-panel {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 24px 26px;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(190, 214, 0, .18), transparent 28%),
                linear-gradient(135deg, #005B82 0%, #0072A4 55%, #0099CF 100%);
            color: #fff;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        #subSegmentExecutivePage .hero-panel::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -70px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        #subSegmentExecutivePage .hero-copy {
            position: relative;
            z-index: 1;
            max-width: 900px;
        }

        #subSegmentExecutivePage .hero-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -.03em;
        }

        #subSegmentExecutivePage .hero-subtitle {
            margin: 8px 0 0;
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.82);
            max-width: 760px;
        }

        #subSegmentExecutivePage .hero-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        #subSegmentExecutivePage .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.12);
            white-space: nowrap;
        }

        #subSegmentExecutivePage .meta-chip.neutral {
            color: rgba(255, 255, 255, 0.88);
        }

        #subSegmentExecutivePage .hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1;
        }

        #subSegmentExecutivePage .facts-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        #subSegmentExecutivePage .fact-card {
            background: var(--panel-bg);
            border: 1px solid rgba(0, 91, 130, 0.08);
            border-radius: 18px;
            padding: 16px 16px 14px;
            box-shadow: var(--shadow-sm);
            min-height: 118px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        #subSegmentExecutivePage .fact-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        #subSegmentExecutivePage .fact-label {
            margin: 0;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
        }

        #subSegmentExecutivePage .fact-icon {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0, 130, 187, .10), rgba(190, 214, 0, .16));
            color: var(--dark-blue);
            flex-shrink: 0;
        }

        #subSegmentExecutivePage .fact-value {
            margin: 10px 0 0;
            font-size: 26px;
            line-height: 1.05;
            font-weight: 900;
            color: var(--dark-blue);
            letter-spacing: -.03em;
            word-break: break-word;
        }

        #subSegmentExecutivePage .fact-value.compact {
            font-size: 19px;
            line-height: 1.2;
        }

        #subSegmentExecutivePage .fact-value.up {
            color: var(--success);
        }

        #subSegmentExecutivePage .fact-value.down {
            color: var(--danger);
        }

        #subSegmentExecutivePage .fact-meta {
            margin-top: 8px;
            font-size: 11px;
            line-height: 1.5;
            color: var(--muted);
        }

        #subSegmentExecutivePage .filters-panel {
            position: sticky;
            top: 12px;
            z-index: 25;
            margin-top: 14px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(0, 91, 130, 0.08);
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
        }

        #subSegmentExecutivePage .filters-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        #subSegmentExecutivePage .filters-left {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1 1 auto;
        }

        #subSegmentExecutivePage .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        #subSegmentExecutivePage .filter-group label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .10em;
            color: var(--muted);
        }

        #subSegmentExecutivePage .filter-control,
        #subSegmentExecutivePage .search-input {
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            font-size: 12px;
            font-family: inherit;
            color: var(--text);
            transition: .2s ease;
        }

        #subSegmentExecutivePage .filter-control:focus,
        #subSegmentExecutivePage .search-input:focus {
            outline: none;
            border-color: rgba(0, 130, 187, .45);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, .08);
        }

        #subSegmentExecutivePage .filter-sep {
            width: 1px;
            height: 42px;
            background: var(--line-2);
            align-self: flex-end;
        }

        #subSegmentExecutivePage .filters-right {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        #subSegmentExecutivePage .segment-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        #subSegmentExecutivePage .segment-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
            white-space: nowrap;
        }

        #subSegmentExecutivePage .seg-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        #subSegmentExecutivePage .seg-pill {
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: .18s ease;
        }

        #subSegmentExecutivePage .seg-pill:hover {
            border-color: rgba(0, 130, 187, .35);
            color: var(--dark-blue);
            transform: translateY(-1px);
        }

        #subSegmentExecutivePage .seg-pill.active {
            background: var(--dark-blue);
            border-color: var(--dark-blue);
            color: #fff;
            box-shadow: 0 10px 20px rgba(0, 91, 130, .14);
        }

        #subSegmentExecutivePage .panel {
            background: var(--panel-bg);
            border: 1px solid rgba(0, 91, 130, 0.08);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        #subSegmentExecutivePage .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px 0;
        }

        #subSegmentExecutivePage .panel-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        #subSegmentExecutivePage .panel-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0, 130, 187, .12), rgba(190, 214, 0, .18));
            color: var(--dark-blue);
            flex-shrink: 0;
        }

        #subSegmentExecutivePage .panel-title {
            margin: 0;
            font-size: 16px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--dark-blue);
            letter-spacing: -.02em;
        }

        #subSegmentExecutivePage .panel-subtitle {
            margin: 4px 0 0;
            font-size: 12px;
            color: var(--muted);
        }

        #subSegmentExecutivePage .panel-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        #subSegmentExecutivePage .mini-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--soft);
            border: 1px solid var(--line-2);
            color: var(--dark-blue);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        #subSegmentExecutivePage .trend-panel {
            margin-top: 14px;
            padding-bottom: 12px;
        }

        #subSegmentExecutivePage .chart-shell {
            position: relative;
            width: 100%;
            padding: 14px 20px 4px;
        }

        #subSegmentExecutivePage .chart-shell.h360 {
            height: 360px;
        }

        #subSegmentExecutivePage .chart-shell.h320 {
            height: 320px;
        }

        #subSegmentExecutivePage .insights-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 14px;
        }

        #subSegmentExecutivePage .legend-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 20px 18px;
        }

        #subSegmentExecutivePage .legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            background: var(--soft);
            border: 1px solid var(--line-2);
            font-size: 11px;
        }

        #subSegmentExecutivePage .legend-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1 1 auto;
        }

        #subSegmentExecutivePage .legend-left span:last-child {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #subSegmentExecutivePage .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        #subSegmentExecutivePage .legend-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            color: var(--dark-blue);
            white-space: nowrap;
        }

        #subSegmentExecutivePage .leaderboard {
            padding: 8px 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #subSegmentExecutivePage .leader-row {
            padding: 12px 12px;
            border-radius: 14px;
            border: 1px solid var(--line-2);
            background: linear-gradient(180deg, #fff, #FBFDFF);
        }

        #subSegmentExecutivePage .leader-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #subSegmentExecutivePage .leader-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(0, 130, 187, .10);
            color: var(--dark-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 900;
            flex-shrink: 0;
        }

        #subSegmentExecutivePage .leader-main {
            min-width: 0;
            flex: 1 1 auto;
        }

        #subSegmentExecutivePage .leader-name {
            font-size: 12px;
            font-weight: 800;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #subSegmentExecutivePage .leader-meta {
            margin-top: 2px;
            font-size: 10px;
            color: var(--muted);
        }

        #subSegmentExecutivePage .leader-movement {
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        #subSegmentExecutivePage .leader-movement.up {
            color: var(--success);
        }

        #subSegmentExecutivePage .leader-movement.down {
            color: var(--danger);
        }

        #subSegmentExecutivePage .leader-track {
            margin-top: 10px;
            height: 8px;
            border-radius: 999px;
            background: #EAF1F7;
            overflow: hidden;
        }

        #subSegmentExecutivePage .leader-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--blue), var(--green));
        }

        #subSegmentExecutivePage .table-panel {
            margin-top: 14px;
            overflow: hidden;
        }

        #subSegmentExecutivePage .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding: 18px 20px 0;
        }

        #subSegmentExecutivePage .table-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-left: auto;
        }

        #subSegmentExecutivePage .table-meta {
            padding: 10px 20px 12px;
            font-size: 11px;
            color: var(--muted);
            border-bottom: 1px solid var(--line-2);
        }

        #subSegmentExecutivePage .excel-wrap {
            overflow: auto;
            max-height: 72vh;
        }

        #subSegmentExecutivePage .excel-table {
            width: 100%;
            min-width: 1180px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11px;
            white-space: nowrap;
        }

        #subSegmentExecutivePage .excel-table thead th {
            position: sticky;
            top: 0;
            z-index: 4;
            background: #0B577B;
            color: #fff;
            font-weight: 800;
            padding: 12px 12px;
            border-right: 1px solid rgba(255, 255, 255, .10);
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            text-align: right;
        }

        #subSegmentExecutivePage .excel-table thead th.left {
            text-align: left;
        }

        #subSegmentExecutivePage .excel-table thead th.sticky-col {
            left: 0;
            z-index: 5;
            box-shadow: 6px 0 10px -6px rgba(7, 30, 46, .35);
        }

        #subSegmentExecutivePage .excel-table tbody td {
            padding: 10px 12px;
            border-right: 1px solid var(--line-2);
            border-bottom: 1px solid var(--line-2);
            text-align: right;
            background: #fff;
            color: var(--text);
            transition: background .18s ease, color .18s ease;
        }

        #subSegmentExecutivePage .excel-table tbody td.sticky-col {
            position: sticky;
            left: 0;
            z-index: 2;
            text-align: left;
            min-width: 220px;
            background: var(--sub-bg-sticky, #fff);
            color: var(--sub-text, var(--text));
            font-weight: 800;
            box-shadow: inset 4px 0 0 var(--sub-accent, transparent), 6px 0 10px -6px rgba(7, 30, 46, .18);
        }

        #subSegmentExecutivePage .seg-row td {
            background: var(--seg-bg, linear-gradient(90deg, #0B577B, #0A77AB));
            color: var(--seg-text, #fff);
            font-weight: 800;
            cursor: pointer;
            border-right-color: rgba(255, 255, 255, .12);
            border-bottom-color: rgba(255, 255, 255, .12);
        }

        #subSegmentExecutivePage .seg-row td.sticky-col {
            background: var(--seg-bg, linear-gradient(90deg, #0B577B, #0A77AB));
            color: var(--seg-text, #fff);
            box-shadow: 6px 0 10px -6px rgba(7, 30, 46, .28);
        }

        #subSegmentExecutivePage .seg-row:hover td {
            filter: brightness(1.03);
        }

        #subSegmentExecutivePage .sub-row td {
            background: var(--sub-bg, #fff);
            color: var(--sub-text, var(--text));
            border-right-color: var(--sub-border, var(--line-2));
            border-bottom-color: var(--sub-border, var(--line-2));
        }

        #subSegmentExecutivePage .sub-row td.sticky-col {
            background: var(--sub-bg-sticky, var(--sub-bg, #fff));
            color: var(--sub-text, var(--text));
        }

        #subSegmentExecutivePage .sub-row:hover td {
            background: var(--sub-hover-bg, #F0F7FC);
        }

        #subSegmentExecutivePage .sub-row:hover td.sticky-col {
            background: var(--sub-hover-bg, #F0F7FC);
        }

        #subSegmentExecutivePage .grand-row td {
            background: #073E5A;
            color: #fff;
            font-weight: 900;
        }

        #subSegmentExecutivePage .grand-row td.sticky-col {
            background: #073E5A !important;
            color: #fff !important;
            box-shadow: 6px 0 10px -6px rgba(7, 30, 46, .35) !important;
        }

        #subSegmentExecutivePage .sub-row.hidden {
            display: none;
        }

        #subSegmentExecutivePage .collapse-icon {
            display: inline-block;
            width: 18px;
            margin-right: 6px;
            transition: transform .18s ease;
            transform-origin: center;
        }

        #subSegmentExecutivePage .seg-row.collapsed .collapse-icon {
            transform: rotate(-90deg);
        }

        #subSegmentExecutivePage .mov-up {
            color: var(--success);
            font-weight: 800;
        }

        #subSegmentExecutivePage .mov-down {
            color: var(--danger);
            font-weight: 800;
        }

        #subSegmentExecutivePage .mov-zero {
            color: var(--muted);
            font-weight: 700;
        }

        #subSegmentExecutivePage .pct-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 900;
            margin-left: 6px;
            vertical-align: middle;
        }

        #subSegmentExecutivePage .pct-badge.up {
            background: rgba(15, 157, 108, .10);
            color: #0B7A54;
        }

        #subSegmentExecutivePage .pct-badge.down {
            background: rgba(214, 69, 69, .10);
            color: #B83232;
        }

        #subSegmentExecutivePage .btn {
            min-height: 40px;
            border: 0;
            border-radius: 12px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .02em;
            cursor: pointer;
            transition: .18s ease;
            white-space: nowrap;
        }

        #subSegmentExecutivePage .btn svg {
            flex-shrink: 0;
        }

        #subSegmentExecutivePage .btn-primary {
            background: linear-gradient(135deg, var(--blue), var(--dark-blue));
            color: #fff;
            box-shadow: 0 10px 18px rgba(0, 130, 187, .18);
        }

        #subSegmentExecutivePage .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(0, 130, 187, .22);
        }

        #subSegmentExecutivePage .btn-secondary {
            background: #fff;
            color: var(--dark-blue);
            border: 1px solid var(--line);
        }

        #subSegmentExecutivePage .btn-secondary:hover {
            border-color: rgba(0, 130, 187, .35);
            color: var(--dark-blue);
            background: #F8FCFF;
        }

        #subSegmentExecutivePage .btn-ghost {
            background: var(--soft);
            color: var(--muted);
            border: 1px solid var(--line-2);
        }

        #subSegmentExecutivePage .btn-ghost:hover {
            color: var(--dark-blue);
            border-color: rgba(0, 130, 187, .25);
        }

        #subSegmentExecutivePage .empty-inline {
            padding: 36px 18px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        #subSegmentExecutivePage .empty-inline .shape {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, rgba(0, 130, 187, .10), rgba(190, 214, 0, .18));
        }

        #subSegmentExecutivePage .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(7, 52, 76, .42);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 14px;
        }

        #subSegmentExecutivePage .loading-overlay.active {
            display: flex;
        }

        #subSegmentExecutivePage .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, .26);
            border-top-color: var(--green);
            border-radius: 50%;
            animation: spin .85s linear infinite;
        }

        #subSegmentExecutivePage .loading-text {
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1380px) {
            #subSegmentExecutivePage .facts-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #subSegmentExecutivePage .insights-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            #subSegmentExecutivePage .exec-dashboard {
                padding: 12px;
            }

            #subSegmentExecutivePage .hero-panel {
                padding: 20px 18px;
                flex-direction: column;
            }

            #subSegmentExecutivePage .facts-grid {
                grid-template-columns: 1fr;
            }

            #subSegmentExecutivePage .chart-shell.h360,
            #subSegmentExecutivePage .chart-shell.h320 {
                height: 320px;
            }

            #subSegmentExecutivePage .table-toolbar,
            #subSegmentExecutivePage .filters-row {
                align-items: stretch;
            }

            #subSegmentExecutivePage .filters-right,
            #subSegmentExecutivePage .table-controls {
                width: 100%;
                margin-left: 0;
            }

            #subSegmentExecutivePage .filters-right .btn,
            #subSegmentExecutivePage .table-controls .btn,
            #subSegmentExecutivePage .table-controls .filter-control,
            #subSegmentExecutivePage .table-controls .search-input {
                flex: 1 1 auto;
            }
        }

        @media (max-width: 640px) {
            #subSegmentExecutivePage .facts-grid {
                grid-template-columns: 1fr;
            }

            #subSegmentExecutivePage .hero-title {
                font-size: 24px;
            }

            #subSegmentExecutivePage .hero-meta,
            #subSegmentExecutivePage .segment-row {
                gap: 8px;
            }

            #subSegmentExecutivePage .meta-chip,
            #subSegmentExecutivePage .seg-pill {
                width: 100%;
                justify-content: center;
            }
        }

        @media print {
            body:has(#subSegmentExecutivePage) {
                background: #fff !important;
            }

            #subSegmentExecutivePage .filters-panel,
            #subSegmentExecutivePage .hero-actions,
            #subSegmentExecutivePage .table-controls,
            #subSegmentExecutivePage .loading-overlay {
                display: none !important;
            }

            #subSegmentExecutivePage .exec-dashboard {
                padding: 0 !important;
            }

            #subSegmentExecutivePage .panel,
            #subSegmentExecutivePage .fact-card,
            #subSegmentExecutivePage .hero-panel {
                box-shadow: none !important;
                border: 1px solid #D8E0E8 !important;
            }

            #subSegmentExecutivePage .hero-panel {
                color: #163247 !important;
                background: #fff !important;
            }

            #subSegmentExecutivePage .hero-subtitle,
            #subSegmentExecutivePage .meta-chip {
                color: #4B6072 !important;
                background: #F6F9FC !important;
                border-color: #D8E0E8 !important;
            }
        }


        #subSegmentExecutivePage .decision-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        #subSegmentExecutivePage .decision-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 92px;
            padding: 15px 16px;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .42);
            box-shadow: var(--shadow-sm);
        }

        #subSegmentExecutivePage .decision-card::after {
            content: "";
            position: absolute;
            width: 110px;
            height: 110px;
            right: -42px;
            top: -52px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .18);
        }

        #subSegmentExecutivePage .decision-purple {
            background: linear-gradient(135deg, #ECE7FF, #FAF8FF);
            color: #5B3FC2;
        }

        #subSegmentExecutivePage .decision-green {
            background: linear-gradient(135deg, #DDF8ED, #F5FFFB);
            color: #08795A;
        }

        #subSegmentExecutivePage .decision-orange {
            background: linear-gradient(135deg, #FFF0D8, #FFF9F1);
            color: #A45B00;
        }

        #subSegmentExecutivePage .decision-icon {
            position: relative;
            z-index: 1;
            width: 44px;
            height: 44px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .72);
            box-shadow: 0 8px 18px rgba(15, 35, 55, .08);
            font-size: 20px;
            font-weight: 900;
            flex: 0 0 auto;
        }

        #subSegmentExecutivePage .decision-label {
            position: relative;
            z-index: 1;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .13em;
            text-transform: uppercase;
            opacity: .75;
        }

        #subSegmentExecutivePage .decision-value {
            position: relative;
            z-index: 1;
            margin-top: 3px;
            font-size: 18px;
            line-height: 1.1;
            font-weight: 900;
        }

        #subSegmentExecutivePage .decision-meta {
            position: relative;
            z-index: 1;
            margin-top: 4px;
            font-size: 10px;
            line-height: 1.35;
            opacity: .78;
        }

        #subSegmentExecutivePage .fact-card:nth-child(1) {
            border-top: 4px solid #0082BB;
        }

        #subSegmentExecutivePage .fact-card:nth-child(2) {
            border-top: 4px solid #BED600;
        }

        #subSegmentExecutivePage .fact-card:nth-child(3) {
            border-top: 4px solid #8B5CF6;
        }

        #subSegmentExecutivePage .fact-card:nth-child(4) {
            border-top: 4px solid #F59E0B;
        }

        #subSegmentExecutivePage .btn-glass {
            background: rgba(255, 255, 255, .11);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .22);
            backdrop-filter: blur(8px);
        }

        #subSegmentExecutivePage .btn-glass:hover {
            color: #fff;
            background: rgba(255, 255, 255, .2);
            transform: translateY(-1px);
        }

        #subSegmentExecutivePage .btn-light-hero {
            background: #fff;
            color: #005B82;
            box-shadow: 0 10px 22px rgba(0, 36, 58, .18);
        }

        #subSegmentExecutivePage .btn-light-hero:hover {
            background: #F4FBFF;
            color: #004C6D;
            transform: translateY(-1px);
        }

        #subSegmentExecutivePage .btn:focus-visible,
        #subSegmentExecutivePage .seg-pill:focus-visible,
        #subSegmentExecutivePage .filter-control:focus-visible,
        #subSegmentExecutivePage .search-input:focus-visible,
        #subSegmentExecutivePage .seg-row:focus-visible {
            outline: 3px solid rgba(190, 214, 0, .75);
            outline-offset: 2px;
        }

        #subSegmentExecutivePage .hero-panel {
            background:
                radial-gradient(circle at 60% 120%, rgba(190, 214, 0, .22), transparent 34%),
                linear-gradient(125deg, #054B75 0%, #0072A4 55%, #0099CF 100%);
        }

        #subSegmentExecutivePage .panel {
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        #subSegmentExecutivePage .panel:hover {
            border-color: rgba(0, 130, 187, .16);
            box-shadow: var(--shadow-md);
        }

        #subSegmentExecutivePage .toast-stack {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 10050;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(360px, calc(100vw - 28px));
            pointer-events: none;
        }

        #subSegmentExecutivePage .dash-toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 14px;
            border-radius: 14px;
            color: #fff;
            background: #153D55;
            box-shadow: 0 16px 38px rgba(0, 0, 0, .2);
            transform: translateY(12px);
            opacity: 0;
            animation: toastIn .22s ease forwards;
            pointer-events: auto;
        }

        #subSegmentExecutivePage .dash-toast.success {
            background: #08795A;
        }

        #subSegmentExecutivePage .dash-toast.error {
            background: #B83232;
        }

        #subSegmentExecutivePage .dash-toast.warning {
            background: #A45B00;
        }

        #subSegmentExecutivePage .dash-toast strong {
            display: block;
            font-size: 12px;
            margin-bottom: 2px;
        }

        #subSegmentExecutivePage .dash-toast span {
            display: block;
            font-size: 11px;
            line-height: 1.4;
            opacity: .9;
        }

        @keyframes toastIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        #subSegmentExecutivePage[data-theme="dark"] {
            --page-bg: #071521;
            --panel-bg: #0D2231;
            --text: #E8F2F8;
            --muted: #93A9B8;
            --line: #254052;
            --line-2: #1B3445;
            --soft: #102A3A;
            --shadow-sm: 0 8px 24px rgba(0, 0, 0, .20);
            --shadow-md: 0 16px 42px rgba(0, 0, 0, .32);
        }

        #subSegmentExecutivePage[data-theme="dark"] .filters-panel {
            background: rgba(13, 34, 49, .92);
        }

        #subSegmentExecutivePage[data-theme="dark"] .filter-control,#subSegmentExecutivePage[data-theme="dark"] .search-input,#subSegmentExecutivePage[data-theme="dark"] .seg-pill,#subSegmentExecutivePage[data-theme="dark"] .btn-secondary {
            background: #102A3A;
            color: #E8F2F8;
            border-color: #2A4658;
        }

        #subSegmentExecutivePage[data-theme="dark"] .fact-value,#subSegmentExecutivePage[data-theme="dark"] .panel-title,#subSegmentExecutivePage[data-theme="dark"] .mini-badge,#subSegmentExecutivePage[data-theme="dark"] .legend-right,#subSegmentExecutivePage[data-theme="dark"] .leader-name {
            color: #E8F2F8;
        }

        #subSegmentExecutivePage[data-theme="dark"] .leader-row,#subSegmentExecutivePage[data-theme="dark"] .legend-row {
            background: #102A3A;
        }

        #subSegmentExecutivePage[data-theme="dark"] .excel-table tbody td {
            background: #0D2231;
            color: #DCEAF2;
        }

        #subSegmentExecutivePage[data-theme="dark"] .decision-purple {
            background: linear-gradient(135deg, #32275B, #171F39);
            color: #D7CCFF;
        }

        #subSegmentExecutivePage[data-theme="dark"] .decision-green {
            background: linear-gradient(135deg, #0C4C3D, #102B2C);
            color: #A8F1D7;
        }

        #subSegmentExecutivePage[data-theme="dark"] .decision-orange {
            background: linear-gradient(135deg, #5B3811, #2B261F);
            color: #FFD69A;
        }


        #subSegmentExecutivePage .leader-button {
            width: 100%;
            font: inherit;
            color: inherit;
            text-align: left;
            cursor: pointer;
        }

        #subSegmentExecutivePage .leader-button:hover {
            transform: translateY(-1px);
            border-color: rgba(0, 130, 187, .24);
            box-shadow: 0 10px 20px rgba(16, 34, 52, .08);
        }

        #subSegmentExecutivePage .btn:disabled {
            opacity: .55;
            cursor: wait;
            transform: none !important;
            box-shadow: none !important;
        }

        #subSegmentExecutivePage .loading-overlay[aria-hidden="true"] {
            pointer-events: none;
        }

        @media (max-width: 900px) {
            #subSegmentExecutivePage .decision-strip {
                grid-template-columns: 1fr;
            }

            #subSegmentExecutivePage .hero-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            #subSegmentExecutivePage .hero-actions .btn {
                flex: 1 1 auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            #subSegmentExecutivePage *,
            #subSegmentExecutivePage *::before,
            #subSegmentExecutivePage *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
@endpush

@section('content')
    <div id="subSegmentExecutivePage" data-theme="light">
        <div class="exec-dashboard">

            <div class="loading-overlay" id="loadingOverlay" role="status" aria-live="polite" aria-hidden="true">
                <div class="spinner"></div>
                <div class="loading-text" id="loadingText">Loading...</div>
            </div>

            <section class="hero-panel">
                <div class="hero-copy">
                    <h1 class="hero-title">Sub-Segment Dashboard</h1>
                    <p class="hero-subtitle">
                        Wide-screen view of deposit performance, movement, concentration, and sub-segment momentum in LCY
                        equivalent.
                    </p>

                    <div class="hero-meta">
                        <span class="meta-chip" id="heroPeriodChip">As at —</span>
                        <span class="meta-chip neutral" id="heroScopeChip">Scope: All segments</span>
                        <span class="meta-chip neutral" id="heroTrendChip">Loading insight…</span>
                    </div>
                </div>

                <div class="hero-actions">
                    <button class="btn btn-glass" id="btnTheme" type="button" aria-label="Switch dashboard theme"
                        title="Switch theme">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20.5 14.2A8.5 8.5 0 119.8 3.5 7 7 0 0020.5 14.2z" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span id="themeButtonText">Dark</span>
                    </button>
                    <button class="btn btn-glass" id="btnCsv" type="button">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3v11m0 0l4-4m-4 4l-4-4M5 16v2a2 2 0 002 2h10a2 2 0 002-2v-2" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        CSV
                    </button>
                    <button class="btn btn-light-hero" id="btnExport" type="button">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M7 8V4h10v4M7 17H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2h-2M7 14h10v7H7v-7z"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Print / PDF
                    </button>
                </div>
            </section>

            <section class="facts-grid">
                <article class="fact-card">
                    <div class="fact-top">
                        <div>
                            <p class="fact-label">Total deposits</p>
                            <div class="fact-value" id="factTotalBalance">—</div>
                        </div>
                        <span class="fact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 7h16M6 3h12l2 4H4l2-4zm-1 6h14v10a2 2 0 01-2 2H7a2 2 0 01-2-2V9z"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="fact-meta" id="factTotalBalanceMeta">Latest period</div>
                </article>

                <article class="fact-card">
                    <div class="fact-top">
                        <div>
                            <p class="fact-label">Net movement</p>
                            <div class="fact-value" id="factNetMovement">—</div>
                        </div>
                        <span class="fact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M5 15l5-5 4 4 5-7" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M14 7h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="fact-meta" id="factNetMovementMeta">Period-on-period change</div>
                </article>

                <article class="fact-card">
                    <div class="fact-top">
                        <div>
                            <p class="fact-label" id="factLeaderLabel">Leading segment</p>
                            <div class="fact-value compact" id="factLeaderValue">—</div>
                        </div>
                        <span class="fact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M8 20h8M12 4v16M5 8h14M7 4h10l-1 4H8L7 4z" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="fact-meta" id="factLeaderMeta">Top concentration</div>
                </article>

                <article class="fact-card">
                    <div class="fact-top">
                        <div>
                            <p class="fact-label">Growth breadth</p>
                            <div class="fact-value compact" id="factBreadth">—</div>
                        </div>
                        <span class="fact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 19h16M7 15l3-3 3 2 4-6" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M17 8h3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="fact-meta" id="factBreadthMeta">Positive vs negative movers</div>
                </article>
            </section>

            <section class="decision-strip" aria-label="Executive insights">
                <article class="decision-card decision-purple">
                    <div class="decision-icon">◎</div>
                    <div>
                        <div class="decision-label">Concentration</div>
                        <div class="decision-value" id="insightConcentration">—</div>
                        <div class="decision-meta" id="insightConcentrationMeta">Waiting for portfolio data</div>
                    </div>
                </article>
                <article class="decision-card decision-green">
                    <div class="decision-icon">↗</div>
                    <div>
                        <div class="decision-label">Momentum</div>
                        <div class="decision-value" id="insightMomentum">—</div>
                        <div class="decision-meta" id="insightMomentumMeta">Waiting for movement data</div>
                    </div>
                </article>
                <article class="decision-card decision-orange">
                    <div class="decision-icon">≈</div>
                    <div>
                        <div class="decision-label">Movement intensity</div>
                        <div class="decision-value" id="insightVolatility">—</div>
                        <div class="decision-meta" id="insightVolatilityMeta">Waiting for variance data</div>
                    </div>
                </article>
            </section>

            <section class="filters-panel">
                <div class="filters-row">
                    <div class="filters-left">
                        <div class="filter-group">
                            <label>From</label>
                            <input type="date" id="filterFrom" class="filter-control">
                        </div>

                        <div class="filter-group">
                            <label>To</label>
                            <input type="date" id="filterTo" class="filter-control">
                        </div>

                        <div class="filter-sep"></div>

                        <div class="filter-group">
                            <label>Quick range</label>
                            <select id="quickRange" class="filter-control">
                                <option value="">Custom / all dates</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="180">Last 6 months</option>
                                <option value="ytd">Year to date</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Show last</label>
                            <select id="filterN" class="filter-control">
                                <option value="5">5 periods</option>
                                <option value="8">8 periods</option>
                                <option value="10" selected>10 periods</option>
                                <option value="12">12 periods</option>
                                <option value="0">All periods</option>
                            </select>
                        </div>
                    </div>

                    <div class="filters-right">
                        <button class="btn btn-primary" id="btnApply">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12h16M12 4l8 8-8 8" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Apply
                        </button>

                        <button class="btn btn-secondary" id="btnReset">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M20 11a8 8 0 10-2.34 5.66M20 4v7h-7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>

                <div class="segment-row">
                    <span class="segment-label">Segment</span>
                    <div class="seg-pills" id="segPills">
                        <button class="seg-pill active" data-seg="">All</button>
                    </div>
                </div>
            </section>

            <section class="panel trend-panel">
                <div class="panel-header">
                    <div class="panel-title-wrap">
                        <span class="panel-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 18l5-6 4 3 7-9" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M17 6h3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="panel-title">Trend Overview</h3>
                            <p class="panel-subtitle" id="trendSubtitle">Balance and movement across selected periods</p>
                        </div>
                    </div>

                    <div class="panel-badges">
                        <span class="mini-badge" id="trendBalanceBadge">Balance —</span>
                        <span class="mini-badge" id="trendMovementBadge">Movement —</span>
                    </div>
                </div>

                <div class="chart-shell h360">
                    <canvas id="trendChart"></canvas>
                </div>
            </section>

            <section class="insights-grid">
                <section class="panel">
                    <div class="panel-header">
                        <div class="panel-title-wrap">
                            <span class="panel-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 3a9 9 0 109 9h-9V3zM13.5 3.13A9 9 0 0120.87 10H13.5V3.13z"
                                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="panel-title">Balance Mix</h3>
                                <p class="panel-subtitle" id="mixSubtitle">Latest-period concentration split</p>
                            </div>
                        </div>
                    </div>

                    <div class="chart-shell h320">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <div class="legend-list" id="doughnutLegend"></div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div class="panel-title-wrap">
                            <span class="panel-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 17l3-3 2 2 6-6M5 5h14v14H5V5z" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="panel-title" id="leaderboardTitle">Leading Segments</h3>
                                <p class="panel-subtitle" id="leaderboardSubtitle">Highest latest closing balances</p>
                            </div>
                        </div>
                    </div>

                    <div class="leaderboard" id="leaderboardBody"></div>
                </section>
            </section>

            <section class="panel table-panel">
                <div class="table-toolbar">
                    <div class="panel-title-wrap" style="padding-bottom: 12px;">
                        <span class="panel-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 7h16M4 12h16M4 17h16M8 4v16M16 4v16" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="panel-title">Sub-Segment Detail Table</h3>
                            <p class="panel-subtitle">Compact analytical view with sticky headers and search</p>
                        </div>
                    </div>

                    <div class="table-controls">
                        <input type="search" id="tableSearch" class="search-input" placeholder="Search sub-segment…"
                            autocomplete="off" aria-label="Search sub-segments">

                        <select id="metricFilter" class="filter-control">
                            <option value="both" selected>Balance + Movement</option>
                            <option value="end_balance">Closing Balance only</option>
                            <option value="movement">Movement only</option>
                        </select>

                        <button class="btn btn-ghost" id="btnExpandAll">Expand</button>
                        <button class="btn btn-ghost" id="btnCollapseAll">Collapse</button>
                    </div>
                </div>

                <div class="table-meta" id="detailMeta">Loading detail table…</div>

                <div class="excel-wrap">
                    <table class="excel-table" aria-describedby="detailMeta">
                        <thead id="tableHead"></thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        (() => {
            'use strict';

            const DATA_URL = @json(route('finance.exec-sub-segment.data'));
            const PAGE_ID = 'subSegmentExecutivePage';
            const SEG_COLORS = [
                '#0082BB', '#BED600', '#8B5CF6', '#F59E0B', '#0F9D6C',
                '#EF476F', '#06B6D4', '#F97316', '#EC4899', '#14B8A6'
            ];
            const SEGMENT_BASES = {
                commercial: [180, 132, 132],
                consumer: [79, 203, 162],
                corporate: [108, 182, 215],
                others: [169, 134, 247],
            };
            const FALLBACK_SEGMENT_BASE = [11, 87, 123];

            const qs = id => document.getElementById(id);
            const page = qs(PAGE_ID);
            if (!page) return;

            function storedTheme() {
                try {
                    return localStorage.getItem('execSubSegmentTheme') || 'light';
                } catch (_) {
                    return 'light';
                }
            }

            const state = {
                segment: '',
                from: '',
                to: '',
                n: 10,
                theme: storedTheme(),
            };

            let payload = null;
            let availableSegmentsCache = [];
            let trendInstance = null;
            let doughnutInstance = null;
            let collapsedGroups = new Set();
            let activeController = null;
            let requestSequence = 0;
            let tableSearchTimer = null;

            const TIP = {
                backgroundColor: 'rgba(255,255,255,0.98)',
                borderWidth: 1,
                borderColor: 'rgba(0,130,187,0.18)',
                titleColor: '#005B82',
                bodyColor: '#163247',
                padding: 11,
                cornerRadius: 10,
                displayColors: true,
            };

            const num = value => {
                const parsed = Number(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const escapeHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const escapeCsv = value => {
                const text = String(value ?? '');
                return /[",\n\r]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
            };

            const rgbString = rgb => `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]})`;
            const rgbaString = (rgb, alpha) => `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${alpha})`;
            // Sticky column sits above other columns while scrolling horizontally, so its background
            // must be fully opaque or the scrolled-under cell content bleeds through visually.
            const blendOverBase = (rgb, alpha, base) => rgbString([0, 1, 2].map(i =>
                Math.round(rgb[i] * alpha + base[i] * (1 - alpha))));
            const contrastText = rgb => (((rgb[0] * 299) + (rgb[1] * 587) + (rgb[2] * 114)) / 1000) >= 160 ? '#163247' :
                '#FFFFFF';
            const segmentBase = segment => SEGMENT_BASES[String(segment || '').trim().toLowerCase()] ||
                FALLBACK_SEGMENT_BASE;

            function segmentTheme(segment, index = 0, total = 1) {
                const base = segmentBase(segment);
                const grade = total > 1 ? index / Math.max(total - 1, 1) : 0;
                const tableBase = state.theme === 'dark' ? [13, 34, 49] : [255, 255, 255];
                return {
                    segBg: rgbString(base),
                    segText: contrastText(base),
                    subBg: rgbaString(base, 0.08 + grade * 0.08),
                    subStickyBg: blendOverBase(base, 0.16 + grade * 0.10, tableBase),
                    subHoverBg: blendOverBase(base, 0.22 + grade * 0.10, tableBase),
                    subText: state.theme === 'dark' ? '#E8F2F8' : '#163247',
                    subAccent: rgbString(base),
                    subBorder: rgbaString(base, 0.18 + grade * 0.06),
                };
            }

            const fKes = value => {
                const v = num(value);
                const a = Math.abs(v);
                const sign = v < 0 ? '-' : '';
                if (a >= 1e12) return `${sign}KES ${(a / 1e12).toFixed(2)}T`;
                if (a >= 1e9) return `${sign}KES ${(a / 1e9).toFixed(2)}B`;
                if (a >= 1e6) return `${sign}KES ${(a / 1e6).toFixed(2)}M`;
                if (a >= 1e3) return `${sign}KES ${(a / 1e3).toFixed(2)}K`;
                return `${sign}KES ${a.toFixed(2)}`;
            };

            const fAxis = value => {
                const v = num(value);
                const a = Math.abs(v);
                if (a >= 1e12) return `${(v / 1e12).toFixed(1)}T`;
                if (a >= 1e9) return `${(v / 1e9).toFixed(1)}B`;
                if (a >= 1e6) return `${(v / 1e6).toFixed(1)}M`;
                if (a >= 1e3) return `${(v / 1e3).toFixed(1)}K`;
                return v.toFixed(0);
            };

            const fPct = value => Number.isFinite(Number(value)) ? `${Math.abs(Number(value)).toFixed(1)}%` : '—';
            const movementClass = value => num(value) > 0 ? 'mov-up' : num(value) < 0 ? 'mov-down' : 'mov-zero';
            const getLastLabel = labels => Array.isArray(labels) && labels.length ? labels[labels.length - 1] : null;

            function toast(message, type = 'info', title = '') {
                const stack = qs('toastStack');
                if (!stack) return;
                const node = document.createElement('div');
                node.className = `dash-toast ${type}`;
                node.innerHTML =
                    `<div><strong>${escapeHtml(title || (type === 'error' ? 'Unable to complete request' : 'Dashboard'))}</strong><span>${escapeHtml(message)}</span></div>`;
                stack.appendChild(node);
                window.setTimeout(() => node.remove(), 4800);
            }

            function setLoading(on, text = 'Loading...') {
                const overlay = qs('loadingOverlay');
                qs('loadingText').textContent = text;
                overlay.classList.toggle('active', on);
                overlay.setAttribute('aria-hidden', on ? 'false' : 'true');
                ['btnApply', 'btnReset', 'btnCsv', 'btnExport'].forEach(id => {
                    const button = qs(id);
                    if (button) button.disabled = on;
                });
            }

            function applyTheme(theme) {
                state.theme = theme === 'dark' ? 'dark' : 'light';
                page.dataset.theme = state.theme;
                try {
                    localStorage.setItem('execSubSegmentTheme', state.theme);
                } catch (_) {
                    // Theme persistence is optional; the active theme still applies.
                }
                qs('themeButtonText').textContent = state.theme === 'dark' ? 'Light' : 'Dark';
                renderChartsOnly();
            }

            function destroyCharts() {
                [trendInstance, doughnutInstance].forEach(chart => chart?.destroy());
                trendInstance = doughnutInstance = null;
            }

            function chartTextColor() {
                return state.theme === 'dark' ? '#B9CCD8' : '#66798B';
            }

            function chartGridColor() {
                return state.theme === 'dark' ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)';
            }

            function safePeriodValue(obj, label, key) {
                return num(obj?.[label]?.[key]);
            }

            function subLookup(tree, segment, misCode, label) {
                const item = tree?.[segment]?.[misCode] || {};
                const period = item.periods?.[label] || {};
                return {
                    desc: item.desc || misCode,
                    balance: num(period.end_balance),
                    movement: num(period.movement),
                };
            }

            function pctFromBase(currentMovement, latestBalance) {
                const movement = num(currentMovement);
                const base = num(latestBalance) - movement;
                return base === 0 ? null : (movement / Math.abs(base)) * 100;
            }

            function normaliseSelectedSegment(p) {
                if (!state.segment) return;
                const exists = Boolean(p?.seg_totals?.[state.segment] || p?.tree?.[state.segment]);
                if (!exists) state.segment = '';
            }

            function getScopeData(p) {
                const labels = Array.isArray(p.period_labels) ? p.period_labels : [];
                const lastLabel = getLastLabel(labels);
                const tree = p.tree || {};
                const segTotals = p.seg_totals || {};
                const grandTotals = p.grand_totals || {};
                const activeSegment = state.segment && (segTotals[state.segment] || tree[state.segment]) ? state
                    .segment : '';

                if (activeSegment) {
                    const periodMap = segTotals[activeSegment] || {};
                    const subMap = tree[activeSegment] || {};
                    const moversItems = Object.entries(subMap).map(([mis, itemValue]) => {
                        const item = itemValue || {};
                        const periods = item.periods || {};
                        return {
                            name: `${item.mis_code || mis} · ${item.desc || mis}`,
                            shortName: item.mis_code || mis,
                            description: item.desc || mis,
                            segment: activeSegment,
                            balance: safePeriodValue(periods, lastLabel, 'end_balance'),
                            movement: safePeriodValue(periods, lastLabel, 'movement'),
                            periods,
                        };
                    });
                    const totalBalance = safePeriodValue(periodMap, lastLabel, 'end_balance');
                    const totalMovement = safePeriodValue(periodMap, lastLabel, 'movement');
                    const leader = [...moversItems].sort((a, b) => b.balance - a.balance)[0] || null;
                    return buildScope({
                        kind: 'segment',
                        scopeName: activeSegment,
                        currentLabel: lastLabel || '—',
                        trendLabels: labels,
                        trendBalance: labels.map(label => safePeriodValue(periodMap, label, 'end_balance')),
                        trendMovement: labels.map(label => safePeriodValue(periodMap, label, 'movement')),
                        latestBalance: totalBalance,
                        latestMovement: totalMovement,
                        moversItems,
                        leaderboardItems: [...moversItems].sort((a, b) => b.balance - a.balance),
                        mixItems: moversItems.filter(item => item.balance > 0).map(item => ({
                            name: item.shortName,
                            value: item.balance
                        })),
                        leader,
                    });
                }

                const segmentItems = Object.keys(segTotals)
                    .filter(segment => segment && segment !== 'UNMAPPED')
                    .map(segment => ({
                        name: segment,
                        segment,
                        balance: safePeriodValue(segTotals[segment], lastLabel, 'end_balance'),
                        movement: safePeriodValue(segTotals[segment], lastLabel, 'movement'),
                    }))
                    .sort((a, b) => b.balance - a.balance);

                const moversItems = (Array.isArray(p.sub_trend) ? p.sub_trend : [])
                    .filter(item => item?.segment && item.segment !== 'UNMAPPED')
                    .map(item => {
                        const movementData = Array.isArray(item.data) ? item.data : [];
                        const info = subLookup(tree, item.segment, item.mis_code, lastLabel);
                        return {
                            name: item.label || `${item.mis_code || ''} · ${info.desc}`,
                            shortName: item.mis_code || item.label || '',
                            description: info.desc,
                            segment: item.segment,
                            balance: info.balance,
                            movement: num(movementData[movementData.length - 1]),
                            periods: {},
                        };
                    });

                const totalBalance = safePeriodValue(grandTotals, lastLabel, 'end_balance');
                const totalMovement = safePeriodValue(grandTotals, lastLabel, 'movement');
                const kpi = p.kpi || {};
                return buildScope({
                    kind: 'all',
                    scopeName: 'All segments',
                    currentLabel: kpi.period_label ?? lastLabel ?? '—',
                    trendLabels: labels,
                    trendBalance: labels.map(label => safePeriodValue(grandTotals, label, 'end_balance')),
                    trendMovement: labels.map(label => safePeriodValue(grandTotals, label, 'movement')),
                    latestBalance: kpi.total_balance ?? totalBalance,
                    latestMovement: kpi.total_movement ?? totalMovement,
                    moversItems,
                    leaderboardItems: segmentItems,
                    mixItems: segmentItems.filter(item => item.balance > 0).map(item => ({
                        name: item.name,
                        value: item.balance
                    })),
                    leader: segmentItems[0] || null,
                });
            }

            function buildScope(scope) {
                const movers = Array.isArray(scope.moversItems) ? scope.moversItems : [];
                const totalAbsMovement = movers.reduce((sum, item) => sum + Math.abs(num(item.movement)), 0);
                const averageAbsMovement = movers.length ? totalAbsMovement / movers.length : 0;
                const maxAbsMovement = movers.reduce((max, item) => Math.max(max, Math.abs(num(item.movement))), 0);
                return {
                    ...scope,
                    latestBalance: num(scope.latestBalance),
                    latestMovement: num(scope.latestMovement),
                    positiveCount: movers.filter(item => num(item.movement) > 0).length,
                    negativeCount: movers.filter(item => num(item.movement) < 0).length,
                    flatCount: movers.filter(item => num(item.movement) === 0).length,
                    leaderShare: scope.leader && num(scope.latestBalance) !== 0 ? (num(scope.leader.balance) / Math.abs(
                        num(scope.latestBalance))) * 100 : 0,
                    totalAbsMovement,
                    averageAbsMovement,
                    intensityRatio: averageAbsMovement ? maxAbsMovement / averageAbsMovement : 0,
                };
            }

            function populateSegPills(segments) {
                const clean = [...new Set([...(availableSegmentsCache || []), ...(segments || [])])]
                    .filter(segment => segment && segment !== 'UNMAPPED')
                    .sort((a, b) => String(a).localeCompare(String(b)));
                availableSegmentsCache = clean;
                const container = qs('segPills');
                container.replaceChildren();

                const createPill = (label, value) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `seg-pill${state.segment === value ? ' active' : ''}`;
                    button.dataset.seg = value;
                    button.textContent = label;
                    button.setAttribute('aria-pressed', state.segment === value ? 'true' : 'false');
                    return button;
                };

                container.appendChild(createPill('All', ''));
                clean.forEach(segment => container.appendChild(createPill(segment, segment)));
            }

            function renderHero(scope) {
                qs('heroPeriodChip').textContent = `As at ${scope.currentLabel || '—'}`;
                qs('heroScopeChip').textContent = scope.kind === 'segment' ? `Focus: ${scope.scopeName}` :
                    'Scope: All segments';
                qs('heroTrendChip').textContent = scope.latestMovement >= 0 ?
                    `Net inflow ${fKes(scope.latestMovement)}` :
                    `Net outflow ${fKes(Math.abs(scope.latestMovement))}`;
                qs('trendSubtitle').textContent = scope.kind === 'segment' ?
                    `Movement and closing balance across periods · ${scope.scopeName}` :
                    'Movement and closing balance across selected periods';
                qs('mixSubtitle').textContent = scope.kind === 'segment' ?
                    `Latest-period sub-segment concentration inside ${scope.scopeName}` :
                    'Latest-period concentration split by segment';
                qs('leaderboardTitle').textContent = scope.kind === 'segment' ? 'Leading Sub-Segments' :
                    'Leading Segments';
                qs('leaderboardSubtitle').textContent = scope.kind === 'segment' ?
                    'Highest latest closing balances inside current segment' :
                    'Highest latest closing balances by segment';
                qs('trendBalanceBadge').textContent = `Balance ${fKes(scope.latestBalance)}`;
                qs('trendMovementBadge').textContent = `Movement ${fKes(scope.latestMovement)}`;
            }

            function renderFacts(scope) {
                const movementPct = pctFromBase(scope.latestMovement, scope.latestBalance);
                qs('factTotalBalance').textContent = fKes(scope.latestBalance);
                qs('factTotalBalanceMeta').textContent = `As at ${scope.currentLabel || '—'}`;

                const factNet = qs('factNetMovement');
                factNet.textContent = fKes(scope.latestMovement);
                factNet.className =
                    `fact-value ${scope.latestMovement > 0 ? 'up' : scope.latestMovement < 0 ? 'down' : ''}`;
                qs('factNetMovementMeta').textContent = movementPct === null ?
                    'No opening base is available for comparison' :
                    `${scope.latestMovement >= 0 ? 'Up' : 'Down'} ${fPct(movementPct)} from opening balance`;

                qs('factLeaderLabel').textContent = scope.kind === 'segment' ? 'Strongest sub-segment' :
                    'Leading segment';
                qs('factLeaderValue').textContent = scope.leader?.name || '—';
                qs('factLeaderMeta').textContent = scope.leader ?
                    `${fKes(scope.leader.balance)} · ${scope.leaderShare.toFixed(1)}% share` :
                    'No leader available';
                qs('factBreadth').textContent = `${scope.positiveCount} ↑ / ${scope.negativeCount} ↓`;
                qs('factBreadthMeta').textContent =
                    `${scope.flatCount} flat movers · ${scope.moversItems.length} observed`;

                const concentrationBand = scope.leaderShare >= 50 ? 'High concentration' : scope.leaderShare >= 30 ?
                    'Moderate concentration' : 'Diversified mix';
                qs('insightConcentration').textContent = `${scope.leaderShare.toFixed(1)}%`;
                qs('insightConcentrationMeta').textContent =
                    `${concentrationBand}${scope.leader ? ` · ${scope.leader.name}` : ''}`;

                const directionalTotal = scope.positiveCount + scope.negativeCount;
                const positiveShare = directionalTotal ? (scope.positiveCount / directionalTotal) * 100 : 0;
                qs('insightMomentum').textContent = directionalTotal ? `${positiveShare.toFixed(0)}% positive` :
                    'No movement';
                qs('insightMomentumMeta').textContent = scope.positiveCount >= scope.negativeCount ?
                    `${scope.positiveCount} gainers are leading portfolio breadth` :
                    `${scope.negativeCount} decliners require attention`;

                qs('insightVolatility').textContent = scope.intensityRatio ? `${scope.intensityRatio.toFixed(1)}×` :
                '—';
                qs('insightVolatilityMeta').textContent = scope.intensityRatio >= 3 ?
                    'Movement is concentrated in a small number of sub-segments' :
                    'Movement is relatively distributed across the portfolio';
            }

            function renderTrend(scope) {
                trendInstance?.destroy();
                trendInstance = null;
                if (!window.Chart || !scope.trendLabels.length) return;
                trendInstance = new Chart(qs('trendChart').getContext('2d'), {
                    data: {
                        labels: scope.trendLabels,
                        datasets: [{
                                type: 'bar',
                                label: 'Movement',
                                data: scope.trendMovement,
                                yAxisID: 'y1',
                                backgroundColor: scope.trendMovement.map(value => num(value) >= 0 ?
                                    'rgba(190,214,0,.78)' : 'rgba(239,71,111,.72)'),
                                borderColor: scope.trendMovement.map(value => num(value) >= 0 ? '#6A9A16' :
                                    '#C7355B'),
                                borderWidth: 1.2,
                                borderRadius: 7,
                                maxBarThickness: 34,
                            },
                            {
                                type: 'line',
                                label: 'Closing Balance',
                                data: scope.trendBalance,
                                yAxisID: 'y',
                                borderColor: '#0082BB',
                                backgroundColor: 'rgba(0,130,187,.13)',
                                fill: true,
                                tension: .3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#6552C8',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    color: chartTextColor(),
                                    font: {
                                        size: 11,
                                        weight: '700'
                                    }
                                }
                            },
                            tooltip: {
                                ...TIP,
                                callbacks: {
                                    label: context => ` ${context.dataset.label}: ${fKes(context.parsed.y)}`
                                }
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: chartGridColor()
                                },
                                ticks: {
                                    color: chartTextColor(),
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                position: 'left',
                                grid: {
                                    color: chartGridColor()
                                },
                                ticks: {
                                    color: chartTextColor(),
                                    callback: fAxis,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y1: {
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    color: chartTextColor(),
                                    callback: fAxis,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                        },
                    },
                });
            }

            function normaliseMixItems(items) {
                const sorted = [...items].sort((a, b) => b.value - a.value);
                if (sorted.length <= 7) return sorted;
                return [...sorted.slice(0, 6), {
                    name: 'Others',
                    value: sorted.slice(6).reduce((sum, item) => sum + num(item.value), 0)
                }];
            }

            function renderDoughnut(scope) {
                doughnutInstance?.destroy();
                doughnutInstance = null;
                const items = normaliseMixItems(scope.mixItems).filter(item => num(item.value) > 0);
                const total = items.reduce((sum, item) => sum + num(item.value), 0);
                const legend = qs('doughnutLegend');
                const canvas = qs('doughnutChart');
                if (!items.length || total <= 0 || !window.Chart) {
                    canvas.style.display = 'none';
                    legend.innerHTML =
                    '<div class="empty-inline"><div class="shape"></div>No mix data available.</div>';
                    return;
                }
                canvas.style.display = '';
                doughnutInstance = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: items.map(item => item.name),
                        datasets: [{
                            data: items.map(item => item.value),
                            backgroundColor: items.map((_, index) => SEG_COLORS[index % SEG_COLORS
                                .length]),
                            borderColor: state.theme === 'dark' ? '#0D2231' : '#F3F7FB',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '64%',
                        onClick: (_, elements) => {
                            if (scope.kind !== 'all' || !elements.length) return;
                            const segment = items[elements[0].index]?.name;
                            if (!segment || segment === 'Others') return;
                            state.segment = segment;
                            collapsedGroups.clear();
                            fetchData();
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...TIP,
                                callbacks: {
                                    label: context =>
                                        ` ${context.label}: ${fKes(context.parsed)} (${((context.parsed / total) * 100).toFixed(1)}%)`
                                }
                            },
                        },
                    },
                });
                legend.innerHTML = items.map((item, index) => `
                    <div class="legend-row">
                        <div class="legend-left"><span class="legend-dot" style="background:${SEG_COLORS[index % SEG_COLORS.length]}"></span><span>${escapeHtml(item.name)}</span></div>
                        <div class="legend-right"><span>${((item.value / total) * 100).toFixed(1)}%</span><span>${escapeHtml(fKes(item.value))}</span></div>
                    </div>`).join('');
            }

            function renderLeaderboard(scope) {
                const items = [...scope.leaderboardItems].slice(0, 6);
                const container = qs('leaderboardBody');
                if (!items.length) {
                    container.innerHTML =
                        '<div class="empty-inline"><div class="shape"></div>No ranking data available.</div>';
                    return;
                }
                const max = Math.max(...items.map(item => num(item.balance)), 1);
                container.innerHTML = items.map((item, index) => {
                    const width = Math.max(4, (num(item.balance) / max) * 100);
                    const share = scope.latestBalance ? (num(item.balance) / Math.abs(scope.latestBalance)) *
                        100 : 0;
                    const movement = num(item.movement);
                    const directionClass = movement > 0 ? 'up' : movement < 0 ? 'down' : '';
                    return `<button type="button" class="leader-row leader-button" data-focus="${escapeHtml(item.segment || item.name || '')}">
                        <div class="leader-top">
                            <div class="leader-rank">${index + 1}</div>
                            <div class="leader-main"><div class="leader-name">${escapeHtml(item.name)}</div><div class="leader-meta">${escapeHtml(fKes(item.balance))} · ${share.toFixed(1)}% share</div></div>
                            <div class="leader-movement ${directionClass}">${movement > 0 ? '+' : ''}${escapeHtml(fKes(movement))}</div>
                        </div>
                        <div class="leader-track"><div class="leader-fill" style="width:${Math.min(100, Math.max(0, width))}%"></div></div>
                    </button>`;
                }).join('');
            }

            function buildCells(metric, balanceValue, movementValue) {
                const balance = num(balanceValue);
                const movement = num(movementValue);
                if (metric === 'end_balance') return `<td>${escapeHtml(fKes(balance))}</td>`;
                if (metric === 'movement')
                return `<td class="${movementClass(movement)}">${escapeHtml(fKes(movement))}</td>`;
                const pct = pctFromBase(movement, balance);
                const badge = pct === null ? '' :
                    `<span class="pct-badge ${movement >= 0 ? 'up' : 'down'}">${movement >= 0 ? '▲' : '▼'}${escapeHtml(fPct(pct))}</span>`;
                return `<td>${escapeHtml(fKes(balance))}</td><td class="${movementClass(movement)}">${escapeHtml(fKes(movement))}${badge}</td>`;
            }

            function renderTable(p) {
                if (!p) return;
                const labels = Array.isArray(p.period_labels) ? p.period_labels : [];
                const tree = p.tree || {};
                const segTotals = p.seg_totals || {};
                const grandTotals = p.grand_totals || {};
                const metric = qs('metricFilter').value;
                const query = qs('tableSearch').value.trim().toLowerCase();
                const colSpan = metric === 'both' ? 2 : 1;

                let head = '<tr><th class="sticky-col left" rowspan="2">Segment / Sub-Segment</th>';
                labels.forEach(label => {
                    head += `<th colspan="${colSpan}" style="text-align:center">${escapeHtml(label)}</th>`;
                });
                head += '</tr><tr>';
                labels.forEach(() => {
                    head += metric === 'end_balance' ? '<th>Closing Bal</th>' : metric === 'movement' ?
                        '<th>Movement</th>' : '<th>Closing Bal</th><th>Movement</th>';
                });
                head += '</tr>';
                qs('tableHead').innerHTML = head;

                const segments = Object.keys(tree)
                    .filter(segment => segment && segment !== 'UNMAPPED')
                    .filter(segment => !state.segment || segment === state.segment)
                    .sort((a, b) => a.localeCompare(b));
                let body = '';
                let visibleRows = 0;
                let visibleSegments = 0;

                segments.forEach(segment => {
                    const subMap = tree[segment] || {};
                    const filteredKeys = Object.keys(subMap).sort().filter(mis => {
                        if (!query) return true;
                        const item = subMap[mis] || {};
                        return `${segment} ${mis} ${item.desc || ''} ${item.mis_code || ''}`
                            .toLowerCase().includes(query);
                    });
                    if (!filteredKeys.length) return;

                    visibleSegments++;
                    const collapsed = !query && collapsedGroups.has(segment);
                    const theme = segmentTheme(segment);
                    body +=
                        `<tr class="seg-row ${collapsed ? 'collapsed' : ''}" data-segment="${escapeHtml(segment)}" tabindex="0" role="button" aria-expanded="${collapsed ? 'false' : 'true'}" style="--seg-bg:${theme.segBg};--seg-text:${theme.segText}">
                        <td class="sticky-col"><span class="collapse-icon">▼</span>${escapeHtml(segment)}<span style="font-size:9px;opacity:.78;margin-left:8px">(${filteredKeys.length})</span></td>`;
                    labels.forEach(label => {
                        const totals = segTotals?.[segment]?.[label] || {};
                        body += buildCells(metric, totals.end_balance, totals.movement);
                    });
                    body += '</tr>';

                    filteredKeys.forEach((mis, index) => {
                        const item = subMap[mis] || {};
                        const rowTheme = segmentTheme(segment, index, filteredKeys.length);
                        visibleRows++;
                        body +=
                            `<tr class="sub-row ${collapsed ? 'hidden' : ''}" data-group="${escapeHtml(segment)}" style="--sub-bg:${rowTheme.subBg};--sub-bg-sticky:${rowTheme.subStickyBg};--sub-hover-bg:${rowTheme.subHoverBg};--sub-text:${rowTheme.subText};--sub-accent:${rowTheme.subAccent};--sub-border:${rowTheme.subBorder}">
                            <td class="sticky-col" style="padding-left:30px"><span style="color:var(--muted);font-size:10px;font-weight:800;margin-right:6px">${escapeHtml(item.mis_code || mis)}</span>${escapeHtml(item.desc || mis)}</td>`;
                        labels.forEach(label => {
                            const period = item.periods?.[label] || {};
                            body += buildCells(metric, period.end_balance, period.movement);
                        });
                        body += '</tr>';
                    });
                });

                if (!visibleRows) {
                    body =
                        `<tr><td colspan="${1 + labels.length * colSpan}"><div class="empty-inline"><div class="shape"></div>No table rows match the current selection.</div></td></tr>`;
                } else {
                    const focusedTotals = state.segment && segTotals[state.segment] ? segTotals[state.segment] :
                        grandTotals;
                    const totalLabel = state.segment ? `${state.segment} TOTAL` : 'GRAND TOTAL';
                    body += `<tr class="grand-row"><td class="sticky-col">${escapeHtml(totalLabel)}</td>`;
                    labels.forEach(label => {
                        const totals = focusedTotals?.[label] || {};
                        body += buildCells(metric, totals.end_balance, totals.movement);
                    });
                    body += '</tr>';
                }
                qs('tableBody').innerHTML = body;
                qs('detailMeta').textContent =
                    `${visibleRows} ${query ? 'matching ' : ''}sub-segments · ${visibleSegments} segment groups · ${labels.length} periods`;
            }

            function renderEmpty(message = 'No sub-segment data found for the selected period.') {
                destroyCharts();
                ['heroPeriodChip', 'heroScopeChip', 'heroTrendChip'].forEach((id, index) => qs(id).textContent =
                    index === 0 ? 'As at —' : index === 1 ? 'Scope: All segments' : 'No data available');
                ['factTotalBalance', 'factNetMovement', 'factLeaderValue', 'factBreadth', 'insightConcentration',
                    'insightMomentum', 'insightVolatility'
                ].forEach(id => qs(id).textContent = '—');
                ['factTotalBalanceMeta', 'factNetMovementMeta', 'factLeaderMeta', 'factBreadthMeta'].forEach(id => qs(
                    id).textContent = 'No data available');
                qs('insightConcentrationMeta').textContent = message;
                qs('insightMomentumMeta').textContent = message;
                qs('insightVolatilityMeta').textContent = message;
                qs('trendBalanceBadge').textContent = 'Balance —';
                qs('trendMovementBadge').textContent = 'Movement —';
                qs('leaderboardBody').innerHTML =
                    '<div class="empty-inline"><div class="shape"></div>No data found.</div>';
                qs('doughnutLegend').innerHTML =
                    '<div class="empty-inline"><div class="shape"></div>No data found.</div>';
                qs('tableHead').innerHTML = '';
                qs('tableBody').innerHTML =
                    `<tr><td colspan="20"><div class="empty-inline"><div class="shape"></div>${escapeHtml(message)}</div></td></tr>`;
                qs('detailMeta').textContent = message;
            }

            function renderChartsOnly() {
                if (!payload?.period_labels?.length || !window.Chart) return;
                const scope = getScopeData(payload);
                renderTrend(scope);
                renderDoughnut(scope);
            }

            function render() {
                if (!payload?.period_labels?.length) {
                    renderEmpty();
                    return;
                }
                normaliseSelectedSegment(payload);
                populateSegPills(payload.available_segments || Object.keys(payload.seg_totals || {}));
                const scope = getScopeData(payload);
                renderHero(scope);
                renderFacts(scope);
                renderTrend(scope);
                renderDoughnut(scope);
                renderLeaderboard(scope);
                renderTable(payload);
            }

            async function parseResponse(response) {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    if (response.status === 401 || response.status === 419) throw new Error(
                        'Your session expired. Refresh the page and sign in again.');
                    throw new Error(
                        `The server returned ${response.status} instead of dashboard data${text ? '.' : ''}`);
                }
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || `Request failed with status ${response.status}`);
                return data;
            }

            async function fetchData() {
                const sequence = ++requestSequence;
                activeController?.abort();
                activeController = new AbortController();
                const timeout = window.setTimeout(() => activeController?.abort(), 30000);
                setLoading(true, 'Fetching dashboard data...');
                try {
                    const params = new URLSearchParams({
                        n: String(state.n)
                    });
                    if (state.segment) params.set('segment', state.segment);
                    if (state.from) params.set('from', state.from);
                    if (state.to) params.set('to', state.to);
                    const response = await fetch(`${DATA_URL}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: activeController.signal,
                        credentials: 'same-origin',
                    });
                    const nextPayload = await parseResponse(response);
                    if (sequence !== requestSequence) return;
                    payload = nextPayload;
                    availableSegmentsCache = [...new Set([...availableSegmentsCache, ...(payload
                        .available_segments || []), ...Object.keys(payload.seg_totals || {})])];
                    render();
                } catch (error) {
                    if (error.name === 'AbortError' && sequence !== requestSequence) return;
                    const message = error.name === 'AbortError' ? 'The request took too long and was cancelled.' :
                        error.message;
                    renderEmpty(message);
                    qs('heroTrendChip').textContent = 'Unable to load dashboard';
                    toast(message, 'error');
                } finally {
                    window.clearTimeout(timeout);
                    if (sequence === requestSequence) setLoading(false);
                }
            }

            function toggleGroup(segment) {
                if (!segment) return;
                collapsedGroups.has(segment) ? collapsedGroups.delete(segment) : collapsedGroups.add(segment);
                renderTable(payload);
            }

            function setQuickRange(value) {
                if (!value) return;
                const today = new Date();
                const to = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                let from = new Date(to);
                if (value === 'ytd') from = new Date(today.getFullYear(), 0, 1);
                else from.setDate(from.getDate() - Math.max(0, Number(value) - 1));
                const iso = date =>
                    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                qs('filterFrom').value = iso(from);
                qs('filterTo').value = iso(to);
            }

            function exportCsv() {
                if (!payload?.period_labels?.length) {
                    toast('Load dashboard data before exporting.', 'warning');
                    return;
                }
                const labels = payload.period_labels;
                const headers = ['Segment', 'MIS Code', 'Description'];
                labels.forEach(label => headers.push(`${label} Closing Balance`, `${label} Movement`));
                const rows = [headers];
                Object.entries(payload.tree || {})
                    .filter(([segment]) => segment !== 'UNMAPPED' && (!state.segment || state.segment === segment))
                    .forEach(([segment, subMap]) => {
                        Object.entries(subMap || {}).forEach(([mis, item]) => {
                            const row = [segment, item.mis_code || mis, item.desc || mis];
                            labels.forEach(label => {
                                const period = item.periods?.[label] || {};
                                row.push(num(period.end_balance), num(period.movement));
                            });
                            rows.push(row);
                        });
                    });
                const csv = '\uFEFF' + rows.map(row => row.map(escapeCsv).join(',')).join('\r\n');
                const blob = new Blob([csv], {
                    type: 'text/csv;charset=utf-8'
                });
                const url = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = `sub-segment-dashboard-${new Date().toISOString().slice(0, 10)}.csv`;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                URL.revokeObjectURL(url);
                toast(`${rows.length - 1} rows exported successfully.`, 'success', 'CSV export');
            }

            qs('btnApply').addEventListener('click', () => {
                const from = qs('filterFrom').value;
                const to = qs('filterTo').value;
                if (from && to && to < from) {
                    toast('End date cannot be before start date.', 'warning', 'Invalid date range');
                    qs('filterTo').focus();
                    return;
                }
                state.from = from;
                state.to = to;
                state.n = Number.parseInt(qs('filterN').value || '10', 10);
                collapsedGroups.clear();
                fetchData();
            });

            qs('btnReset').addEventListener('click', () => {
                ['filterFrom', 'filterTo', 'tableSearch'].forEach(id => {
                    qs(id).value = '';
                });
                qs('quickRange').value = '';
                qs('filterN').value = '10';
                qs('metricFilter').value = 'both';
                Object.assign(state, {
                    segment: '',
                    from: '',
                    to: '',
                    n: 10
                });
                collapsedGroups.clear();
                fetchData();
            });

            qs('quickRange').addEventListener('change', event => setQuickRange(event.target.value));

            qs('segPills').addEventListener('click', event => {
                const pill = event.target.closest('.seg-pill');
                if (!pill) return;
                state.segment = pill.dataset.seg || '';
                collapsedGroups.clear();
                populateSegPills(availableSegmentsCache);
                fetchData();
            });

            qs('metricFilter').addEventListener('change', () => renderTable(payload));
            qs('tableSearch').addEventListener('input', () => {
                window.clearTimeout(tableSearchTimer);
                tableSearchTimer = window.setTimeout(() => renderTable(payload), 180);
            });
            qs('btnExpandAll').addEventListener('click', () => {
                collapsedGroups.clear();
                renderTable(payload);
            });
            qs('btnCollapseAll').addEventListener('click', () => {
                collapsedGroups = new Set(Object.keys(payload?.tree || {}).filter(segment => segment !==
                    'UNMAPPED' && (!state.segment || segment === state.segment)));
                renderTable(payload);
            });

            qs('tableBody').addEventListener('click', event => {
                const row = event.target.closest('.seg-row');
                if (row) toggleGroup(row.dataset.segment);
            });
            qs('tableBody').addEventListener('keydown', event => {
                const row = event.target.closest('.seg-row');
                if (row && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    toggleGroup(row.dataset.segment);
                }
            });

            qs('leaderboardBody').addEventListener('click', event => {
                const button = event.target.closest('.leader-button');
                if (!button || state.segment) return;
                const segment = button.dataset.focus;
                if (!segment || !availableSegmentsCache.includes(segment)) return;
                state.segment = segment;
                collapsedGroups.clear();
                fetchData();
            });

            qs('btnTheme').addEventListener('click', () => applyTheme(state.theme === 'dark' ? 'light' : 'dark'));
            qs('btnCsv').addEventListener('click', exportCsv);
            qs('btnExport').addEventListener('click', () => window.print());

            window.addEventListener('beforeunload', () => activeController?.abort());

            applyTheme(state.theme);
            if (!window.Chart) {
                renderEmpty('Chart.js could not be loaded. Check the network or host the library locally.');
                toast('Chart.js could not be loaded.', 'error');
                return;
            }
            fetchData();
        })();
    </script>
@endpush
