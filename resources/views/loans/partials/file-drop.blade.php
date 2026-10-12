{{-- Drag & drop file input in the Finance style.
     Expects: $id, $name; optional: $accept, $required, $hint. --}}
<label class="loan-drop" for="{{ $id }}" data-loan-drop>
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept ?? '.xls,.xlsx,.csv' }}"
        @if ($required ?? true) required @endif>

    <span class="loan-drop__icon" aria-hidden="true">
        <i class="ti ti-cloud-upload" data-loan-drop-icon></i>
    </span>

    <span class="loan-drop__copy">
        <span class="loan-drop__name" data-loan-drop-name>Drag &amp; drop or <u>browse</u></span>
        <span class="loan-drop__hint" data-loan-drop-hint>{{ $hint ?? 'Excel (.xls, .xlsx) or CSV' }}</span>
    </span>
</label>

@once
    @push('styles')
        <style>
            .loan-drop {
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 0;
                padding: 14px 16px;
                background: var(--finance-surface-soft);
                border: 2px dashed var(--finance-border-strong);
                border-radius: var(--finance-radius-sm);
                cursor: pointer;
                transition: border-color .15s ease, background-color .15s ease;
            }

            .loan-drop:hover,
            .loan-drop.is-dragover {
                background: var(--finance-primary-soft);
                border-color: rgba(8, 117, 225, .45);
            }

            .loan-drop:focus-within {
                border-color: var(--finance-primary);
            }

            .loan-drop.has-file {
                background: var(--finance-green-soft);
                border-style: solid;
                border-color: rgba(0, 168, 107, .35);
            }

            .loan-drop input[type=file] {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                opacity: 0;
                cursor: pointer;
            }

            .loan-drop__icon {
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                display: grid;
                place-items: center;
                color: var(--finance-primary);
                background: var(--finance-surface);
                border: 1px solid var(--finance-border);
                border-radius: var(--finance-radius-xs);
                font-size: 18px;
            }

            .loan-drop.has-file .loan-drop__icon {
                color: var(--finance-green);
            }

            .loan-drop__copy {
                min-width: 0;
                display: flex;
                flex-direction: column;
                line-height: 1.35;
            }

            .loan-drop__name {
                overflow: hidden;
                color: var(--finance-text-strong);
                font-size: 13px;
                font-weight: 700;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .loan-drop__hint {
                color: var(--finance-muted);
                font-size: 11.5px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('[data-loan-drop]').forEach(function(zone) {
                var input = zone.querySelector('input[type=file]');
                var name = zone.querySelector('[data-loan-drop-name]');
                var hint = zone.querySelector('[data-loan-drop-hint]');
                var icon = zone.querySelector('[data-loan-drop-icon]');
                var emptyName = name.innerHTML;
                var emptyHint = hint.textContent;

                input.addEventListener('change', function() {
                    var file = input.files && input.files[0];

                    zone.classList.toggle('has-file', !!file);
                    icon.className = file ? 'ti ti-file-spreadsheet' : 'ti ti-cloud-upload';

                    if (file) {
                        name.textContent = file.name;
                        hint.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    } else {
                        name.innerHTML = emptyName;
                        hint.textContent = emptyHint;
                    }
                });

                ['dragenter', 'dragover'].forEach(function(evt) {
                    zone.addEventListener(evt, function() {
                        zone.classList.add('is-dragover');
                    });
                });

                ['dragleave', 'drop'].forEach(function(evt) {
                    zone.addEventListener(evt, function() {
                        zone.classList.remove('is-dragover');
                    });
                });
            });
        </script>
    @endpush
@endonce
