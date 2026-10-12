{{-- Drag & drop file input for the loans layout.
     Expects: $id, $name; optional: $accept, $required, $hint. --}}
<label class="file-drop" for="{{ $id }}" data-file-drop>
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept ?? '.xls,.xlsx,.csv' }}"
        @if ($required ?? true) required @endif>

    <span class="file-drop__icon" aria-hidden="true">
        <i class="fas fa-cloud-arrow-up" data-file-drop-icon></i>
    </span>

    <span class="file-drop__copy">
        <span class="file-drop__name" data-file-drop-name>Drag &amp; drop or <u>browse</u></span>
        <span class="file-drop__hint" data-file-drop-hint>{{ $hint ?? 'Excel (.xls, .xlsx) or CSV' }}</span>
    </span>
</label>

@once
    @push('styles')
        <style>
            .file-drop {
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 15px;
                background: #FAFBFC;
                border: 1.5px dashed var(--gray-4);
                border-radius: 10px;
                cursor: pointer;
                transition: border-color .15s ease, background .15s ease;
            }

            .file-drop:hover,
            .file-drop.is-dragover {
                background: rgba(0, 130, 187, .05);
                border-color: var(--blue);
            }

            .file-drop:focus-within {
                border-color: var(--blue);
                box-shadow: 0 0 0 3px rgba(0, 130, 187, .08);
            }

            .file-drop.has-file {
                background: var(--green-soft);
                border-style: solid;
                border-color: rgba(111, 148, 31, .35);
            }

            .file-drop input[type=file] {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                opacity: 0;
                cursor: pointer;
            }

            .file-drop__icon {
                width: 38px;
                height: 38px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: rgba(0, 130, 187, .08);
                color: var(--blue);
            }

            .file-drop.has-file .file-drop__icon {
                background: rgba(111, 148, 31, .14);
                color: var(--green-dark);
            }

            .file-drop__copy {
                min-width: 0;
                display: flex;
                flex-direction: column;
                line-height: 1.4;
            }

            .file-drop__name {
                overflow: hidden;
                color: var(--deep-blue);
                font-size: .74rem;
                font-weight: 700;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .file-drop__hint {
                color: var(--gray-3);
                font-size: .62rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('[data-file-drop]').forEach(function(zone) {
                var input = zone.querySelector('input[type=file]');
                var name = zone.querySelector('[data-file-drop-name]');
                var hint = zone.querySelector('[data-file-drop-hint]');
                var icon = zone.querySelector('[data-file-drop-icon]');
                var emptyName = name.innerHTML;
                var emptyHint = hint.textContent;

                input.addEventListener('change', function() {
                    var file = input.files && input.files[0];

                    zone.classList.toggle('has-file', !!file);
                    icon.className = file ? 'fas fa-file-excel' : 'fas fa-cloud-arrow-up';

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
