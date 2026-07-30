@extends('layouts.finance.template')

@section('title', 'Daily Balances Report')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --navy: #0f1e38;
            --navy2: #1a3254;
            --teal: #0d9488;
            --teal-lt: #ccfbf1;
            --amber: #d97706;
            --red: #dc2626;
            --green: #16a34a;
            --slate: #64748b;
            --border: #e2e8f0;
            --bg: #f6f8fb;
            --white: #ffffff;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(15, 30, 56, .07), 0 4px 16px rgba(15, 30, 56, .06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
        }

        /* ── Layout ── */
        .pipe-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 2rem 1.25rem 4rem;
        }

        /* ── Header ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .page-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--navy);
            margin: 0 0 .25rem;
            letter-spacing: -.3px;
        }

        .page-header p {
            font-size: .875rem;
            color: var(--slate);
            margin: 0;
        }

        .sched-badge {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: var(--navy);
            color: #94a3b8;
            font-size: .75rem;
            border-radius: 8px;
            padding: .55rem 1rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sched-badge strong {
            color: #e2e8f0;
        }

        .sched-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--teal);
            flex-shrink: 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 2px rgba(13, 148, 136, .35)
            }

            50% {
                box-shadow: 0 0 0 6px rgba(13, 148, 136, .0)
            }
        }

        /* ── Cards ── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow);
        }

        .card-title {
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--slate);
            margin: 0 0 1.25rem;
        }

        /* ── Form ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media(max-width:580px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .field label {
            font-size: .825rem;
            font-weight: 500;
            color: var(--navy);
        }

        .hint {
            font-weight: 400;
            color: #94a3b8;
            font-size: .73rem;
            display: block;
            margin-top: .1rem;
            line-height: 1.4;
        }

        .field input[type=text],
        .field input[type=date],
        .field input[type=number] {
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: .55rem .8rem;
            font-size: .875rem;
            font-family: inherit;
            color: var(--navy);
            background: #fafbfd;
            transition: border-color .15s, box-shadow .15s;
            width: 100%;
        }

        .field input:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, .12);
            background: #fff;
        }

        .path-row {
            display: flex;
            gap: .5rem;
        }

        .path-row input {
            flex: 1;
            font-family: 'DM Mono', monospace;
            font-size: .78rem;
        }

        .btn-ghost {
            padding: .5rem .85rem;
            font-size: .78rem;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            color: var(--slate);
            font-family: inherit;
            transition: background .15s;
            white-space: nowrap;
        }

        .btn-ghost:hover {
            background: #edf0f5;
        }

        /* ── Toggle ── */
        .toggle-row {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .85rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            cursor: pointer;
            user-select: none;
            transition: background .15s;
        }

        .toggle-row:hover {
            background: #f1f5f9;
        }

        .sw-wrap {
            position: relative;
            width: 40px;
            height: 23px;
            flex-shrink: 0;
        }

        .sw-wrap input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .sw-track {
            position: absolute;
            inset: 0;
            background: #cbd5e1;
            border-radius: 999px;
            transition: background .2s;
            pointer-events: none;
        }

        .sw-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 17px;
            height: 17px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            pointer-events: none;
        }

        .sw-wrap input:checked~.sw-track {
            background: var(--teal);
        }

        .sw-wrap input:checked~.sw-thumb {
            transform: translateX(17px);
        }

        .toggle-text strong {
            font-size: .875rem;
            color: var(--navy);
            font-weight: 500;
            display: block;
        }

        .toggle-text span {
            font-size: .775rem;
            color: var(--slate);
        }

        /* ── Accordion ── */
        .accordion-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }

        .acc-label {
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--slate);
        }

        .acc-chevron {
            width: 18px;
            height: 18px;
            color: var(--slate);
            transition: transform .25s;
            flex-shrink: 0;
        }

        .acc-chevron.open {
            transform: rotate(180deg);
        }

        .accordion-body {
            display: none;
            margin-top: 1.25rem;
        }

        .accordion-body.open {
            display: block;
        }

        .adv-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media(max-width:580px) {
            .adv-grid {
                grid-template-columns: 1fr;
            }
        }

        .adv-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1rem 0;
        }

        .email-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media(max-width:580px) {
            .email-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Run button ── */
        .run-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: .25rem;
        }

        .btn-run {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .75rem 2rem;
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .9rem;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, transform .1s;
            letter-spacing: -.1px;
        }

        .btn-run:hover:not(:disabled) {
            background: var(--navy2);
            transform: translateY(-1px);
        }

        .btn-run:active {
            transform: translateY(0);
        }

        .btn-run:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        .run-note {
            font-size: .8rem;
            color: var(--slate);
        }

        /* ── Result ── */
        .result-card {
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow);
        }

        .result-card.ok {
            background: #f0fdf4;
            border: 1px solid #86efac;
        }

        .result-card.warn {
            background: #fffbeb;
            border: 1px solid #fcd34d;
        }

        .result-card.fail {
            background: #fef2f2;
            border: 1px solid #fca5a5;
        }

        .result-head {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: 1rem;
        }

        .result-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .result-icon.ok {
            background: #dcfce7;
            color: var(--green);
        }

        .result-icon.warn {
            background: #fef9c3;
            color: var(--amber);
        }

        .result-icon.fail {
            background: #fee2e2;
            color: var(--red);
        }

        .result-title {
            font-size: .95rem;
            font-weight: 600;
            color: var(--navy);
        }

        .result-meta {
            font-size: .8rem;
            color: var(--slate);
            margin-top: .15rem;
        }

        .terminal {
            background: #0f172a;
            color: #cbd5e1;
            font-family: 'DM Mono', monospace;
            font-size: .74rem;
            line-height: 1.7;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            max-height: 380px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ── Alerts ── */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: .8rem 1rem;
            margin-bottom: 1.25rem;
            color: var(--red);
            font-size: .85rem;
        }

        .alert-err ul {
            margin: .4rem 0 0 1rem;
            padding: 0;
        }

        /* ── Log ── */
        .log-wrap {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
            margin-top: 1.5rem;
        }

        .log-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem;
        }

        .log-head h3 {
            font-size: .875rem;
            font-weight: 600;
            color: var(--navy);
            margin: 0;
        }

        .log-head span {
            font-size: .72rem;
            color: var(--slate);
        }

        .log-tail {
            background: #1e293b;
            color: #64748b;
            font-family: 'DM Mono', monospace;
            font-size: .72rem;
            line-height: 1.55;
            padding: .75rem 1rem;
            border-radius: 8px;
            max-height: 220px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ════════════════════════════
                       LOADER OVERLAY
                    ════════════════════════════ */
        #loaderOverlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 30, 56, .6);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        #loaderOverlay.active {
            display: flex;
        }

        .loader-box {
            background: var(--white);
            border-radius: 18px;
            padding: 2rem 2.25rem;
            width: min(500px, 94vw);
            box-shadow: 0 32px 90px rgba(15, 30, 56, .3);
            animation: popIn .3s cubic-bezier(.175, .885, .32, 1.275);
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(.9)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .loader-top {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .loader-spinner {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 3px solid #e2e8f0;
            border-top-color: var(--teal);
            animation: lspin .75s linear infinite;
        }

        @keyframes lspin {
            to {
                transform: rotate(360deg);
            }
        }

        .loader-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--navy);
        }

        .loader-sub {
            font-size: .8rem;
            color: var(--slate);
            margin-top: .2rem;
            line-height: 1.4;
        }

        .progress-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 5px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--teal), #34d399);
            border-radius: 999px;
            transition: width .7s ease;
            width: 0%;
        }

        /* Steps */
        .steps-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .6rem .75rem;
            border-radius: 9px;
            transition: background .25s;
        }

        .step-item.s-done {
            background: #f0fdf4;
        }

        .step-item.s-active {
            background: var(--teal-lt);
        }

        .step-item.s-pending {
            background: transparent;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .73rem;
            font-weight: 600;
            transition: background .25s, color .25s;
        }

        .step-num.s-done {
            background: var(--green);
            color: #fff;
        }

        .step-num.s-active {
            background: var(--teal);
            color: #fff;
            animation: stepGlow 1.3s infinite;
        }

        .step-num.s-pending {
            background: #e2e8f0;
            color: #94a3b8;
        }

        @keyframes stepGlow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(13, 148, 136, .45)
            }

            60% {
                box-shadow: 0 0 0 6px rgba(13, 148, 136, 0)
            }
        }

        .step-label {
            font-size: .84rem;
            flex: 1;
            font-weight: 400;
        }

        .step-label.s-done {
            color: var(--navy);
        }

        .step-label.s-active {
            color: var(--navy);
            font-weight: 500;
        }

        .step-label.s-pending {
            color: #94a3b8;
        }

        .step-tag {
            font-size: .71rem;
            font-weight: 500;
            padding: .15rem .5rem;
            border-radius: 999px;
        }

        .step-tag.s-done {
            background: #dcfce7;
            color: var(--green);
        }

        .step-tag.s-active {
            background: var(--teal-lt);
            color: var(--teal);
        }

        .step-tag.s-pending {
            visibility: hidden;
        }

        .loader-foot {
            text-align: center;
            margin-top: 1.25rem;
            font-size: .775rem;
            color: #94a3b8;
        }
    </style>
@endpush

@section('content')

    {{-- ════ LOADER OVERLAY ════ --}}
    <div id="loaderOverlay">
        <div class="loader-box">
            <div class="loader-top">
                <div class="loader-spinner"></div>
                <div>
                    <div class="loader-title" id="loaderTitle">Getting started…</div>
                    <div class="loader-sub" id="loaderSub">Please keep this tab open. This usually takes 1–3 minutes.</div>
                </div>
            </div>

            <div class="progress-track">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <ul class="steps-list" id="stepsList">
                @php
                    $stepLabels = [
                        'Loading balance data files',
                        'Finding biggest balance changes',
                        'Analysing customer segments',
                        'Reviewing branch performance',
                        'Building local currency report',
                        'Building foreign currency report',
                        'Sending balance summary by email',
                        'Sending branch report by email',
                    ];
                @endphp
                @foreach ($stepLabels as $i => $label)
                    <li class="step-item s-pending" data-step="{{ $i + 1 }}">
                        <div class="step-num s-pending">{{ $i + 1 }}</div>
                        <div class="step-label s-pending">{{ $label }}</div>
                        <span class="step-tag s-pending">done</span>
                    </li>
                @endforeach
            </ul>

            <div class="loader-foot">Reports will be emailed once all steps complete ✦</div>
        </div>
    </div>

    {{-- ════ PAGE ════ --}}
    <div class="pipe-wrap">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1>Daily Balances Report</h1>
                <p>Use this page if the automatic report didn't run — pick a date and click <strong>Run Report</strong>.</p>
            </div>
            <div class="sched-badge">
                <div class="sched-dot"></div>
                Auto-runs <strong>&nbsp;weekdays at 9:30 AM</strong>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="alert-err">
                <strong>Please fix the following before running:</strong>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Run result --}}
        {{--  @isset($runOutput)
            @php
                $cls = match ($runStatus) {
                    'success' => 'ok',
                    'error' => 'fail',
                    default => 'warn',
                };
                $icon = match ($runStatus) {
                    'success' => '✓',
                    'error' => '✗',
                    default => '⚠',
                };
                $title = match ($runStatus) {
                    'success' => 'Report completed — emails have been sent',
                    'error' => 'Something went wrong — check the log below',
                    default => 'Done, but with some warnings',
                };
            @endphp
            <div class="result-card {{ $cls }}">
                <div class="result-head">
                    <div class="result-icon {{ $cls }}">{{ $icon }}</div>
                    <div>
                        <div class="result-title">{{ $title }}</div>
                        <div class="result-meta">Date: <strong>{{ $ranFor }}</strong> &nbsp;·&nbsp; Took
                            {{ $runDuration }}s</div>
                    </div>
                </div>
                <div class="terminal" id="runOutput">{{ $runOutput }}</div>
            </div>
        @endisset  --}}


        {{-- Dispatched confirmation --}}
        @if (session('dispatched'))
            <div class="result-card ok">
                <div class="result-head">
                    <div class="result-icon ok">🚀</div>
                    <div>
                        <div class="result-title">Report is running in the background</div>
                        <div class="result-meta">
                            Started for <strong>{{ session('dispatchedFor') }}</strong> &nbsp;·&nbsp;
                            Emails will be sent automatically when done.
                        </div>
                    </div>
                </div>
                <div
                    style="background:#f0fdf4; border-radius:8px; padding:.75rem 1rem; font-size:.82rem; color:#166534; margin-top:.5rem;">
                    ⏳ &nbsp;The pipeline is still processing. Check the <strong>Activity Log</strong> below in a few minutes
                    to confirm it completed.
                </div>
            </div>
        @endif

        {{-- ── FORM ── --}}
        <form method="POST" action="{{ route('finance.balances.pipeline.run') }}" id="pipelineForm">
            @csrf

            {{-- Dates --}}
            <div class="card">
                <div class="card-title">Report Date</div>
                <div class="form-row">
                    <div class="field">
                        <label>
                            Run report for <span style="color:#ef4444">*</span>
                            <span class="hint">Pick the date whose balances you want to process</span>
                        </label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $defaultDate) }}"
                            required onchange="rebuildPath()">
                    </div>
                    <div class="field">
                        <label>
                            Compare against
                            <span class="hint">Leave blank to use the last available date automatically</span>
                        </label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}">
                    </div>
                </div>
            </div>

            {{-- Data location --}}
            <div class="card">
                <div class="card-title">Data Location</div>
                <div class="field">
                    <label>
                        Data folder path
                        <span class="hint">Updates automatically when you change the date above. Only edit this if the
                            files were saved somewhere different.</span>
                    </label>
                    <div class="path-row">
                        <input type="text" id="import_path" name="import_path"
                            value="{{ old('import_path', $defaultImportPath) }}" spellcheck="false">
                        <button type="button" class="btn-ghost" onclick="rebuildPath()">↺ Reset path</button>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    <label class="toggle-row" for="no_import">
                        <div class="sw-wrap">
                            <input type="hidden" name="no_import" value="0">
                            <input type="checkbox" id="no_import" name="no_import" value="1"
                                {{ old('no_import') ? 'checked' : '' }}>
                            <div class="sw-track"></div>
                            <div class="sw-thumb"></div>
                        </div>
                        <div class="toggle-text">
                            <strong>Data already loaded for this date</strong>
                            <span>Turn on to skip the file import step and go straight to building the reports</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Advanced --}}
            <div class="card">
                <button type="button" class="accordion-btn" onclick="toggleAcc(this)">
                    <span class="acc-label">Advanced Options</span>
                    <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>
                <div class="accordion-body">
                    <div class="adv-grid">
                        <div class="field">
                            <label>Top customers to show<span class="hint">How many movers appear in the
                                    report</span></label>
                            <input type="number" name="limit" value="{{ old('limit', 20) }}" min="1"
                                max="500">
                        </div>
                        <div class="field">
                            <label>Currency rows<span class="hint">Per currency bucket</span></label>
                            <input type="number" name="currency_limit" value="{{ old('currency_limit', 10) }}"
                                min="1" max="500">
                        </div>
                        <div class="field">
                            <label>Branch rows<span class="hint">Top branches to include</span></label>
                            <input type="number" name="branch_limit" value="{{ old('branch_limit', 10) }}"
                                min="1" max="500">
                        </div>
                    </div>
                    <hr class="adv-divider">
                    <p style="font-size:.775rem; color:var(--slate); margin:0 0 .9rem;">Override email recipients — leave
                        blank to use the system defaults</p>
                    <div class="email-grid">
                        <div class="field">
                            <label>Balance report — To<span class="hint">Comma-separated emails</span></label>
                            <input type="text" name="to" value="{{ old('to') }}"
                                placeholder="analyst@bank.co.ke">
                        </div>
                        <div class="field">
                            <label>Balance report — CC</label>
                            <input type="text" name="cc" value="{{ old('cc') }}"
                                placeholder="manager@bank.co.ke">
                        </div>
                        <div class="field">
                            <label>Branch report — To</label>
                            <input type="text" name="branch_to" value="{{ old('branch_to') }}"
                                placeholder="branches@bank.co.ke">
                        </div>
                        <div class="field">
                            <label>Branch report — CC</label>
                            <input type="text" name="branch_cc" value="{{ old('branch_cc') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="run-row">
                <button type="submit" class="btn-run" id="runBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="10 8 16 12 10 16 10 8" />
                    </svg>
                    <span id="runBtnLabel">Run Report</span>
                </button>
                <span class="run-note">Emails will be sent automatically once all steps are done.</span>
            </div>
        </form>

        {{-- Log --}}
        @if (!empty($logLines))
            <div class="log-wrap">
                <div class="log-head">
                    <h3>Recent Activity Log</h3>
                    <span>Last 200 entries</span>
                </div>
                <div class="log-tail" id="logTail">{{ implode("\n", $logLines) }}</div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        const BASE_DIR = @json(rtrim(config('reports.balances.base_dir', '/mnt/eke_dailyflexcubereports'), '/'));
        const COUNTRY = @json(config('reports.balances.country_folder', 'Kenya'));
        const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // ── Path auto-builder ──
        function rebuildPath() {
            const val = document.getElementById('end_date').value;
            if (!val) return;
            const [y, m, d] = val.split('-');
            document.getElementById('import_path').value = `${BASE_DIR}/${y}/${MONTHS[+m-1]}/${d}/${COUNTRY}`;
        }

        // ── Accordion ──
        function toggleAcc(btn) {
            const body = btn.nextElementSibling;
            const chevron = btn.querySelector('.acc-chevron');
            const open = body.classList.toggle('open');
            chevron.classList.toggle('open', open);
        }

        // ─────────────────────────────────────────
        // LOADER — simulates step progress visually
        // (we can't poll since it's synchronous)
        // ─────────────────────────────────────────
        const STEP_DURATIONS = [28000, 12000, 9000, 11000, 9000, 9000, 7000, 5000]; // ms per step (≈90s total)
        const STEP_TITLES = [
            'Loading balance data files…',
            'Finding biggest balance changes…',
            'Analysing customer segments…',
            'Reviewing branch performance…',
            'Building local currency report…',
            'Building foreign currency report…',
            'Sending balance summary by email…',
            'Sending branch report by email…',
        ];

        let currentStep = 0;
        let stepTimer = null;

        function startLoader() {
            document.getElementById('loaderOverlay').classList.add('active');
            advanceStep(0);
        }

        function advanceStep(idx) {
            if (idx >= 8) return;
            currentStep = idx;

            // Complete previous steps
            for (let i = 0; i < idx; i++) setStep(i, 'done');
            setStep(idx, 'active');

            // Progress bar: 0→90% across the 8 steps
            const pct = Math.round((idx / 8) * 90);
            document.getElementById('progressFill').style.width = pct + '%';

            // Header copy
            document.getElementById('loaderTitle').textContent = STEP_TITLES[idx];

            // Schedule next step
            stepTimer = setTimeout(() => advanceStep(idx + 1), STEP_DURATIONS[idx]);
        }

        function setStep(idx, state) {
            const item = document.querySelector(`[data-step="${idx + 1}"]`);
            if (!item) return;
            const num = item.querySelector('.step-num');
            const label = item.querySelector('.step-label');
            const tag = item.querySelector('.step-tag');

            // Reset classes
            item.className = `step-item s-${state}`;
            num.className = `step-num s-${state}`;
            label.className = `step-label s-${state}`;
            tag.className = `step-tag s-${state}`;

            if (state === 'done') {
                num.textContent = '✓';
                tag.textContent = 'Done';
            }
            if (state === 'active') {
                num.textContent = idx + 1;
                tag.textContent = 'Running…';
            }
            if (state === 'pending') {
                num.textContent = idx + 1;
                tag.textContent = '';
            }
        }

        // ── Form submit ──
        document.getElementById('pipelineForm').addEventListener('submit', function() {
            const btn = document.getElementById('runBtn');
            btn.disabled = true;
            document.getElementById('runBtnLabel').textContent = 'Starting…';
        });

        // ── Auto-scroll on page load ──
        document.addEventListener('DOMContentLoaded', () => {
            ['runOutput', 'logTail'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.scrollTop = el.scrollHeight;
            });
        });
    </script>
@endpush
