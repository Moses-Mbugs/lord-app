{{-- File input styled as a drop zone. Expects: $name, $id; optional: $accept, $required, $hint. --}}
<label class="lb-drop" for="{{ $id }}" data-lb-drop>
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept ?? '.xls,.xlsx,.csv' }}"
        @if ($required ?? true) required @endif>

    <span class="lb-drop-icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
    </span>

    <span class="lb-drop-text">
        <strong data-lb-drop-name>Choose a file or drag it here</strong>
        <span>{{ $hint ?? 'Excel (.xls, .xlsx) or CSV' }}</span>
    </span>
</label>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-lb-drop]').forEach(function(zone) {
                var input = zone.querySelector('input[type=file]');
                var label = zone.querySelector('[data-lb-drop-name]');
                var emptyText = label.textContent;

                input.addEventListener('change', function() {
                    var file = input.files && input.files[0];
                    label.textContent = file ? file.name : emptyText;
                    zone.classList.toggle('has-file', !!file);
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
