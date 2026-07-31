@extends('layouts.finance.template')

@section('title', 'Loan Book Pipeline')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --navy: #0f1e38;
            --navy2: #1a3254;
            --teal: #0d9488;
            --amber: #d97706;
            --red: #dc2626;
            --green: #16a34a;
            --slate: #64748b;
            --border: #e2e8f0;
            --bg: #f6f8fb;
            --white: #ffffff;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(15,30,56,.07), 0 4px 16px rgba(15,30,56,.06);
        }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); }

        .pipe-wrap { max-width: 820px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-header h1 { font-size: 1.4rem; font-weight: 600; color: var(--navy); margin: 0 0 .25rem; }
        .page-header p  { font-size: .875rem; color: var(--slate); margin: 0; }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); }
        .card-title { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--slate); margin: 0 0 1.25rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:580px) { .form-row { grid-template-columns: 1fr; } }

        .field { display: flex; flex-direction: column; gap: .3rem; }
        .field label { font-size: .825rem; font-weight: 500; color: var(--navy); }
        .hint { font-weight: 400; color: #94a3b8; font-size: .73rem; display: block; margin-top: .1rem; line-height: 1.4; }

        .field input[type=text],
        .field input[type=date],
        .field input[type=file] {
            border: 1.5px solid var(--border); border-radius: 8px; padding: .55rem .8rem;
            font-size: .875rem; font-family: inherit; color: var(--navy); background: #fafbfd;
            transition: border-color .15s, box-shadow .15s; width: 100%;
        }
        .field input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); background: #fff; }
        .field input[type=file] { padding: .4rem .8rem; cursor: pointer; }

        .toggle-row { display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem; background: #f8fafc; border-radius: 8px; border: 1.5px solid var(--border); cursor: pointer; user-select: none; transition: background .15s; }
        .toggle-row:hover { background: #f1f5f9; }
        .sw-wrap { position: relative; width: 40px; height: 23px; flex-shrink: 0; }
        .sw-wrap input { opacity: 0; width: 0; height: 0; position: absolute; }
        .sw-track { position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px; transition: background .2s; pointer-events: none; }
        .sw-thumb { position: absolute; top: 3px; left: 3px; width: 17px; height: 17px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); pointer-events: none; }
        .sw-wrap input:checked ~ .sw-track { background: var(--teal); }
        .sw-wrap input:checked ~ .sw-thumb { transform: translateX(17px); }
        .toggle-text strong { font-size: .875rem; color: var(--navy); font-weight: 500; display: block; }
        .toggle-text span { font-size: .775rem; color: var(--slate); }

        .accordion-btn { display: flex; align-items: center; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; }
        .acc-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--slate); }
        .acc-chevron { width: 18px; height: 18px; color: var(--slate); transition: transform .25s; flex-shrink: 0; }
        .acc-chevron.open { transform: rotate(180deg); }
        .accordion-body { display: none; margin-top: 1.25rem; }
        .accordion-body.open { display: block; }
        .email-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:580px) { .email-grid { grid-template-columns: 1fr; } }

        .run-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: .25rem; }
        .btn-run { display: inline-flex; align-items: center; gap: .6rem; padding: .75rem 2rem; background: var(--navy); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: .9rem; font-family: inherit; cursor: pointer; transition: background .15s, transform .1s; letter-spacing: -.1px; }
        .btn-run:hover:not(:disabled) { background: var(--navy2); transform: translateY(-1px); }
        .btn-run:disabled { background: #94a3b8; cursor: not-allowed; }
        .run-note { font-size: .8rem; color: var(--slate); }

        .result-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); }
        .result-card.ok { background: #f0fdf4; border: 1px solid #86efac; }
        .result-card.fail { background: #fef2f2; border: 1px solid #fca5a5; }
        .result-head { display: flex; align-items: center; gap: .8rem; margin-bottom: .5rem; }
        .result-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
        .result-icon.ok { background: #dcfce7; color: var(--green); }
        .result-icon.fail { background: #fee2e2; color: var(--red); }
        .result-title { font-size: .95rem; font-weight: 600; color: var(--navy); }
        .result-meta { font-size: .8rem; color: var(--slate); margin-top: .15rem; }

        .alert-err { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: .8rem 1rem; margin-bottom: 1.25rem; color: var(--red); font-size: .85rem; }
        .alert-err ul { margin: .4rem 0 0 1rem; padding: 0; }

        .divider { border: none; border-top: 1px solid var(--border); margin: 1rem 0; }

        .log-wrap { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; box-shadow: var(--shadow); margin-top: 1.5rem; }
        .log-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
        .log-head h3 { font-size: .875rem; font-weight: 600; color: var(--navy); margin: 0; }
        .log-head span { font-size: .72rem; color: var(--slate); }
        .log-tail { background: #1e293b; color: #64748b; font-family: 'DM Mono', monospace; font-size: .72rem; line-height: 1.55; padding: .75rem 1rem; border-radius: 8px; max-height: 220px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }

        .filename-preview { font-family: 'DM Mono', monospace; font-size: .78rem; color: var(--teal); background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: .3rem .6rem; display: none; margin-top: .4rem; }
        .filename-preview.show { display: block; }

        /* ── Modals (upload / send) ── */
        .modal-content { border-radius: var(--radius); border: 1px solid var(--border); font-family: 'DM Sans', sans-serif; }
        .modal-header, .modal-footer { border-color: var(--border); }
        .modal-title { font-size: 1.05rem; font-weight: 600; color: var(--navy); }
        .btn-close:focus { box-shadow: none; }
        #sendLoanEmailModal .modal-body { max-height: 65vh; overflow-y: auto; }
        .btn-run:disabled { background: #94a3b8; cursor: not-allowed; }
    </style>

@endpush

@section('content')

    <div class="pipe-wrap">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-money-bill-trend-up" style="color:#0d9488;margin-right:.4rem;"></i>Loan Book Pipeline</h1>
                <p>Upload the daily Loan Book Excel file to import data and send the movement report.</p>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="alert-err">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success banner --}}
        @if (session('success'))
            <div class="result-card ok">
                <div class="result-head">
                    <div class="result-icon ok"><i class="fa-solid fa-check"></i></div>
                    <div>
                        <div class="result-title">{{ session('success') }}</div>
                        @if (session('importedDate'))
                            <div class="result-meta">Date: <strong>{{ session('importedDate') }}</strong></div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════ ACTIONS ═══════════ --}}
        <div class="run-row" style="margin-bottom:1.5rem;">
            <button type="button" class="btn-run" data-bs-toggle="modal" data-bs-target="#uploadLoanModal">
                <i class="fa-solid fa-upload"></i>
                <span>Upload Loan Book</span>
            </button>
            <button type="button" class="btn-run" style="background:var(--teal);" data-bs-toggle="modal" data-bs-target="#sendLoanEmailModal">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Send Report Only</span>
            </button>
            <span class="run-note">Upload to import a new date, or send the report for data already loaded.</span>
        </div>

        {{-- Log --}}
        @if (!empty($logLines))
            <div class="log-wrap">
                <div class="log-head">
                    <h3>Activity Log</h3>
                    <span>Last 200 entries</span>
                </div>
                <div class="log-tail" id="logTail">{{ implode("\n", $logLines) }}</div>
            </div>
        @endif

    </div>

    {{-- ═══════════ UPLOAD MODAL ═══════════ --}}
    <div class="modal fade" id="uploadLoanModal" tabindex="-1" aria-labelledby="uploadLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.loans.pipeline.upload') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <input type="hidden" name="form_name" value="upload">

                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadLoanModalLabel">Upload Loan Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="field">
                                <label>
                                    Loan Book Excel (.xlsx) <span style="color:#ef4444">*</span>
                                    <span class="hint">Must contain a sheet named "Loan Book"</span>
                                </label>
                                <input type="file" name="loan_file" id="loan_file" accept=".xlsx,.xls" required
                                       onchange="handleFileChange(this)">
                                <div class="filename-preview" id="filePreview"></div>
                            </div>

                            <div class="field">
                                <label>
                                    Report date
                                    <span class="hint">Auto-detected from filename. Override if needed.</span>
                                </label>
                                <input type="date" name="loan_date" id="loan_date" value="{{ old('loan_date', $defaultDate) }}">
                            </div>
                        </div>

                        <div style="margin-top:1rem;">
                            <label class="toggle-row" for="send_email">
                                <div class="sw-wrap">
                                    <input type="hidden" name="send_email" value="0">
                                    <input type="checkbox" id="send_email" name="send_email" value="1"
                                        {{ old('send_email') ? 'checked' : '' }}
                                        onchange="toggleEmailSection(this)">
                                    <div class="sw-track"></div>
                                    <div class="sw-thumb"></div>
                                </div>
                                <div class="toggle-text">
                                    <strong>Send movement email after import</strong>
                                    <span>Builds LCY &amp; FCY loan book movement and emails the report immediately</span>
                                </div>
                            </label>
                        </div>

                        {{-- Email section (shown when toggle is on) --}}
                        <div id="emailSection" style="display:none;margin-top:1rem;">
                            <hr class="divider" style="margin:0 0 1rem;">

                            <div class="field" style="margin-bottom:1rem;">
                                <label>
                                    Compare against (start date)
                                    <span class="hint">Leave blank to use the previous day automatically</span>
                                </label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}">
                            </div>

                            @include('finance.loans._recipient_picker', [
                                'configTo'  => $configTo,
                                'configCc'  => $configCc,
                                'oldTo'     => old('to', []),
                                'oldCc'     => old('cc', []),
                                'oldToExtra'=> old('to_extra', ''),
                                'oldCcExtra'=> old('cc_extra', ''),
                            ])
                        </div>
                    </div>

                    <div class="modal-footer">
                        <span class="run-note" style="margin-right:auto;">Large files may take up to a minute to process.</span>
                        <button type="submit" class="btn-run" id="uploadBtn">
                            <i class="fa-solid fa-upload"></i>
                            <span>Import File</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════ SEND EMAIL MODAL (no re-import) ═══════════ --}}
    <div class="modal fade" id="sendLoanEmailModal" tabindex="-1" aria-labelledby="sendLoanEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.loans.pipeline.send') }}" id="sendForm">
                    @csrf
                    <input type="hidden" name="form_name" value="send">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="sendLoanEmailModalLabel">Send Report Only</h5>
                            <div class="hint" style="margin-top:.15rem;">Use this if the data is already loaded and you just need to send the email for a date range.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row" style="margin-bottom:1rem;">
                            <div class="field">
                                <label>Start date <span style="color:#ef4444">*</span></label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="field">
                                <label>End date <span style="color:#ef4444">*</span></label>
                                <input type="date" name="end_date" value="{{ old('end_date', $defaultDate) }}" required>
                            </div>
                        </div>
                        @include('finance.loans._recipient_picker', [
                            'configTo'  => $configTo,
                            'configCc'  => $configCc,
                            'oldTo'     => old('to', []),
                            'oldCc'     => old('cc', []),
                            'oldToExtra'=> old('to_extra', ''),
                            'oldCcExtra'=> old('cc_extra', ''),
                        ])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-run" style="background:var(--teal);" id="sendBtn">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Send Email</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleFileChange(input) {
            const preview = document.getElementById('filePreview');
            const dateInput = document.getElementById('loan_date');

            if (!input.files || !input.files[0]) {
                preview.classList.remove('show');
                return;
            }

            const filename = input.files[0].name;
            preview.textContent = filename;
            preview.classList.add('show');

            // Try to extract YYYYMMDD from filename
            const match = filename.match(/(\d{4})(\d{2})(\d{2})/);
            if (match) {
                const [, y, m, d] = match;
                const dateStr = `${y}-${m}-${d}`;
                // Only auto-fill if the date looks valid (month 01-12, day 01-31)
                if (+m >= 1 && +m <= 12 && +d >= 1 && +d <= 31) {
                    dateInput.value = dateStr;
                }
            }
        }

        function toggleEmailSection(checkbox) {
            document.getElementById('emailSection').style.display = checkbox.checked ? 'block' : 'none';
        }

        function toggleAcc(btn) {
            const body = btn.nextElementSibling;
            const chevron = btn.querySelector('.acc-chevron');
            const open = body.classList.toggle('open');
            chevron.classList.toggle('open', open);
        }

        // Disable the submit button on click so a double-click (or an impatient
        // second click while the slow synchronous import/email request is still
        // in flight) can't fire a second POST — this was the cause of the
        // loan movement email being sent twice.
        function guardSubmit(formId, btnId, busyLabel) {
            const form = document.getElementById(formId);
            const btn = document.getElementById(btnId);
            if (!form || !btn) return;
            form.addEventListener('submit', function () {
                btn.disabled = true;
                const label = btn.querySelector('span');
                if (label) label.textContent = busyLabel;
            });
        }

        // Auto-scroll log to bottom
        document.addEventListener('DOMContentLoaded', function () {
            const log = document.getElementById('logTail');
            if (log) log.scrollTop = log.scrollHeight;

            // Show email section if checkbox was checked (after validation error redirect)
            const cb = document.getElementById('send_email');
            if (cb && cb.checked) {
                document.getElementById('emailSection').style.display = 'block';
            }

            guardSubmit('uploadForm', 'uploadBtn', 'Uploading…');
            guardSubmit('sendForm', 'sendBtn', 'Sending…');

            // Re-open whichever modal failed validation, so the errors are visible
            @if ($errors->any())
                const reopenModalId = {{ old('form_name') === 'send' ? "'sendLoanEmailModal'" : "'uploadLoanModal'" }};
                const reopenModalEl = document.getElementById(reopenModalId);
                if (reopenModalEl) {
                    new bootstrap.Modal(reopenModalEl).show();
                }
            @endif
        });
    </script>
@endpush
