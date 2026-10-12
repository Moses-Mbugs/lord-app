{{-- Shared styles for the Loan Book generator + detail pages. Everything is scoped under .lb
     so other pages using layouts.loans.template are not affected. --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        .lb {
            --lb-ink: #0F2A43;
            --lb-ink-soft: #3D5468;
            --lb-muted: #71849A;
            --lb-line: #E1E9F0;
            --lb-line-strong: #CBD8E3;
            --lb-surface: #FFFFFF;
            --lb-surface-alt: #F6F9FB;
            --lb-blue: #0082BB;
            --lb-blue-dark: #005B82;
            --lb-blue-tint: #EAF5FB;
            --lb-lime: #BED600;
            --lb-green: #168A45;
            --lb-green-tint: #E8F5ED;
            --lb-amber: #B7791F;
            --lb-amber-tint: #FBF3E4;
            --lb-red: #C0392B;
            --lb-red-tint: #FBECEA;
            --lb-radius: 14px;
            --lb-shadow: 0 1px 2px rgba(15, 42, 67, .04), 0 6px 20px rgba(15, 42, 67, .05);

            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
            color: var(--lb-ink);
            font-size: 14px;
            max-width: 1320px;
        }

        .lb h1, .lb h2, .lb h3, .lb h4 {
            font-family: inherit;
            color: var(--lb-ink);
            margin: 0;
            letter-spacing: -.01em;
        }

        .lb .num {
            font-variant-numeric: tabular-nums;
        }

        .lb .mono {
            font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
            font-size: 12.5px;
        }

        /* ---------- Page header ---------- */
        .lb-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .lb-eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--lb-blue);
            margin-bottom: 6px;
        }

        .lb-header h1 {
            font-size: 26px;
            font-weight: 800;
        }

        .lb-header p {
            margin: 6px 0 0;
            color: var(--lb-muted);
            max-width: 680px;
            line-height: 1.55;
        }

        .lb-header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ---------- Buttons ---------- */
        .lb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.2;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
            white-space: nowrap;
        }

        .lb-btn-primary {
            background: var(--lb-blue);
            color: #fff;
        }

        .lb-btn-primary:hover {
            background: var(--lb-blue-dark);
            color: #fff;
        }

        .lb-btn-primary:focus-visible,
        .lb-btn-ghost:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 130, 187, .25);
        }

        .lb-btn-primary:disabled {
            background: #B9C8D4;
            cursor: not-allowed;
        }

        .lb-btn-ghost {
            background: var(--lb-surface);
            color: var(--lb-ink);
            border-color: var(--lb-line-strong);
        }

        .lb-btn-ghost:hover {
            background: var(--lb-surface-alt);
            color: var(--lb-ink);
        }

        .lb-btn-sm {
            padding: 7px 12px;
            font-size: 13px;
        }

        .lb-btn-lg {
            padding: 13px 22px;
            font-size: 15px;
        }

        /* ---------- Alerts ---------- */
        .lb-alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-radius: 12px;
            padding: 13px 16px;
            margin-bottom: 18px;
            border: 1px solid;
            line-height: 1.5;
        }

        .lb-alert ul {
            margin: 6px 0 0;
            padding-left: 18px;
        }

        .lb-alert-icon {
            font-weight: 800;
            flex-shrink: 0;
        }

        .lb-alert-success {
            background: var(--lb-green-tint);
            border-color: #BFE2CC;
            color: #0F5E30;
        }

        .lb-alert-danger {
            background: var(--lb-red-tint);
            border-color: #F0C6C0;
            color: #8E2A20;
        }

        .lb-alert-warning {
            background: var(--lb-amber-tint);
            border-color: #EBD3A8;
            color: #7A5014;
        }

        /* ---------- Cards / panels ---------- */
        .lb-panel {
            background: var(--lb-surface);
            border: 1px solid var(--lb-line);
            border-radius: var(--lb-radius);
            box-shadow: var(--lb-shadow);
            margin-bottom: 20px;
        }

        .lb-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--lb-line);
            flex-wrap: wrap;
        }

        .lb-panel-head h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .lb-panel-head p {
            margin: 3px 0 0;
            color: var(--lb-muted);
            font-size: 13px;
        }

        .lb-panel-body {
            padding: 20px;
        }

        /* ---------- Stats ---------- */
        .lb-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .lb-stat {
            background: var(--lb-surface);
            border: 1px solid var(--lb-line);
            border-radius: var(--lb-radius);
            padding: 16px 18px;
            box-shadow: var(--lb-shadow);
            min-width: 0;
        }

        .lb-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--lb-muted);
            margin-bottom: 6px;
        }

        .lb-stat-value {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lb-stat-value.is-small {
            font-size: 16px;
            font-weight: 700;
        }

        .lb-stat-note {
            font-size: 12px;
            color: var(--lb-muted);
            margin-top: 4px;
        }

        .lb-stat.is-accent {
            background: linear-gradient(135deg, var(--lb-blue-dark), var(--lb-blue));
            border-color: transparent;
            color: #fff;
        }

        .lb-stat.is-accent .lb-stat-label,
        .lb-stat.is-accent .lb-stat-note {
            color: rgba(255, 255, 255, .78);
        }

        .lb-text-danger { color: var(--lb-red); }
        .lb-text-success { color: var(--lb-green); }
        .lb-text-muted { color: var(--lb-muted); }

        /* ---------- Chips / badges ---------- */
        .lb-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .lb-chip::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .lb-chip-success { background: var(--lb-green-tint); color: var(--lb-green); }
        .lb-chip-pending { background: var(--lb-surface-alt); color: var(--lb-muted); border: 1px solid var(--lb-line); }
        .lb-chip-warning { background: var(--lb-amber-tint); color: var(--lb-amber); }
        .lb-chip-danger { background: var(--lb-red-tint); color: var(--lb-red); }
        .lb-chip-info { background: var(--lb-blue-tint); color: var(--lb-blue-dark); }

        .lb-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            background: var(--lb-surface-alt);
            border: 1px solid var(--lb-line);
            color: var(--lb-ink-soft);
            font-size: 12px;
            font-weight: 600;
            margin: 0 4px 4px 0;
        }

        /* ---------- Progress tracker ---------- */
        .lb-progress {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            background: var(--lb-surface);
            border: 1px solid var(--lb-line);
            border-radius: var(--lb-radius);
            box-shadow: var(--lb-shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .lb-progress-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            min-width: 0;
        }

        .lb-progress-step + .lb-progress-step {
            border-left: 1px solid var(--lb-line);
        }

        .lb-progress-step strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
        }

        .lb-progress-step span {
            display: block;
            font-size: 12px;
            color: var(--lb-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lb-step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
            background: var(--lb-surface-alt);
            color: var(--lb-muted);
            border: 1px solid var(--lb-line-strong);
        }

        .is-done .lb-step-dot,
        .lb-step-dot.is-done {
            background: var(--lb-green);
            border-color: var(--lb-green);
            color: #fff;
        }

        .lb-step-dot.is-ready {
            background: var(--lb-blue);
            border-color: var(--lb-blue);
            color: #fff;
        }

        .is-current .lb-step-dot {
            background: var(--lb-blue);
            border-color: var(--lb-blue);
            color: #fff;
            box-shadow: 0 0 0 4px var(--lb-blue-tint);
        }

        /* ---------- Upload steps ---------- */
        .lb-steps {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .lb-steps > .lb-panel {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
        }

        .lb-steps .lb-panel-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .lb-step-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lb-field-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--lb-ink);
        }

        .lb-field-label .req {
            color: var(--lb-red);
        }

        .lb-help {
            font-size: 12.5px;
            color: var(--lb-muted);
            line-height: 1.5;
            margin-top: 8px;
        }

        .lb-current-file {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--lb-surface-alt);
            border: 1px solid var(--lb-line);
            font-size: 13px;
            color: var(--lb-ink-soft);
            min-width: 0;
        }

        .lb-current-file strong {
            color: var(--lb-ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lb-form-foot {
            margin-top: auto;
            padding-top: 18px;
        }

        /* ---------- Drop zone file input ---------- */
        .lb-drop {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1.5px dashed var(--lb-line-strong);
            border-radius: 12px;
            background: var(--lb-surface-alt);
            transition: border-color .15s ease, background .15s ease;
            cursor: pointer;
            min-width: 0;
        }

        .lb-drop:hover,
        .lb-drop.is-dragover {
            border-color: var(--lb-blue);
            background: var(--lb-blue-tint);
        }

        .lb-drop:focus-within {
            border-color: var(--lb-blue);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, .18);
        }

        .lb-drop.has-file {
            border-style: solid;
            border-color: #9FD3B4;
            background: var(--lb-green-tint);
        }

        .lb-drop input[type=file] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .lb-drop-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--lb-surface);
            border: 1px solid var(--lb-line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--lb-blue);
            flex-shrink: 0;
        }

        .has-file .lb-drop-icon {
            color: var(--lb-green);
        }

        .lb-drop-text {
            min-width: 0;
            line-height: 1.35;
        }

        .lb-drop-text strong {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lb-drop-text span {
            font-size: 12px;
            color: var(--lb-muted);
        }

        /* ---------- Generate panel ---------- */
        .lb-supp-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .lb-generate-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--lb-line);
        }

        .lb-generate-bar .lb-date {
            width: 220px;
        }

        .lb-input {
            width: 100%;
            border: 1px solid var(--lb-line-strong);
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            color: var(--lb-ink);
            background: var(--lb-surface);
        }

        .lb-input:focus {
            outline: none;
            border-color: var(--lb-blue);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, .18);
        }

        .lb-generate-cta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .lb-generate-cta small {
            color: var(--lb-muted);
            font-size: 12.5px;
            max-width: 320px;
            text-align: right;
            line-height: 1.45;
        }

        /* ---------- Tables ---------- */
        .lb-table-wrap {
            overflow-x: auto;
        }

        .lb-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .lb-table th {
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: var(--lb-muted);
            background: var(--lb-surface-alt);
            padding: 11px 16px;
            border-bottom: 1px solid var(--lb-line);
            white-space: nowrap;
        }

        .lb-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--lb-line);
            white-space: nowrap;
            color: var(--lb-ink);
            vertical-align: middle;
        }

        .lb-table tbody tr:last-child td {
            border-bottom: none;
        }

        .lb-table tbody tr:hover td {
            background: #FAFCFD;
        }

        .lb-table .text-right {
            text-align: right;
        }

        .lb-table td.wrap {
            white-space: normal;
            min-width: 220px;
        }

        .lb-empty {
            text-align: center;
            padding: 40px 16px !important;
            color: var(--lb-muted);
        }

        .lb-empty strong {
            display: block;
            color: var(--lb-ink);
            margin-bottom: 4px;
        }

        .lb-table-foot {
            padding: 12px 16px;
            border-top: 1px solid var(--lb-line);
        }

        .lb-table-foot:empty {
            display: none;
        }

        .lb .pagination {
            margin: 0;
            flex-wrap: wrap;
        }

        .lb .page-link {
            border-radius: 8px;
            margin: 0 2px;
            color: var(--lb-ink);
            border-color: var(--lb-line);
            font-weight: 600;
            font-size: 13px;
        }

        .lb .page-item.active .page-link {
            background: var(--lb-blue);
            border-color: var(--lb-blue);
            color: #fff;
        }

        /* ---------- Source tiles (detail page) ---------- */
        .lb-sources {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .lb-source {
            border: 1px solid var(--lb-line);
            border-radius: 12px;
            padding: 14px;
            background: var(--lb-surface-alt);
            min-width: 0;
        }

        .lb-source-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--lb-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .lb-source-file {
            font-size: 13px;
            font-weight: 600;
            margin: 6px 0 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lb-source-counts {
            display: flex;
            gap: 16px;
        }

        .lb-source-counts div {
            font-size: 11.5px;
            color: var(--lb-muted);
        }

        .lb-source-counts b {
            display: block;
            font-size: 16px;
            color: var(--lb-ink);
            font-variant-numeric: tabular-nums;
        }

        /* ---------- Tabs ---------- */
        .lb-tabs {
            display: flex;
            gap: 4px;
            padding: 0 12px;
            border-bottom: 1px solid var(--lb-line);
        }

        .lb-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 10px 12px;
            margin-bottom: -1px;
            border: none;
            border-bottom: 2px solid transparent;
            background: none;
            font: inherit;
            font-weight: 700;
            color: var(--lb-muted);
            cursor: pointer;
        }

        .lb-tab:hover {
            color: var(--lb-ink);
        }

        .lb-tab.is-active {
            color: var(--lb-blue-dark);
            border-bottom-color: var(--lb-blue);
        }

        .lb-tab-count {
            font-size: 11.5px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--lb-surface-alt);
            border: 1px solid var(--lb-line);
            color: var(--lb-ink-soft);
            font-variant-numeric: tabular-nums;
        }

        .lb-tab-pane[hidden] {
            display: none;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 1200px) {
            .lb-sources {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 992px) {
            .lb-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .lb-steps,
            .lb-supp-grid {
                grid-template-columns: 1fr;
            }

            .lb-sources {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .lb-progress {
                grid-template-columns: 1fr;
            }

            .lb-progress-step + .lb-progress-step {
                border-left: none;
                border-top: 1px solid var(--lb-line);
            }

            .lb-stats,
            .lb-sources {
                grid-template-columns: 1fr;
            }

            .lb-generate-bar .lb-date,
            .lb-generate-cta,
            .lb-generate-cta .lb-btn {
                width: 100%;
            }

            .lb-generate-cta small {
                text-align: left;
                max-width: none;
            }
        }
    </style>
@endpush
