{{--
    Recipient picker partial — used in both the upload form and the send-only form.
    Variables expected:
      $configTo   – array of emails from config('reports.loans.to')
      $configCc   – array of emails from config('reports.loans.cc')
      $oldTo      – array, old('to', [])
      $oldCc      – array, old('cc', [])
      $oldToExtra – string, old('to_extra', '')
      $oldCcExtra – string, old('cc_extra', '')
--}}

<style>
    .rp-section { margin-bottom: 1rem; }
    .rp-label   { font-size: .72rem; font-weight: 600; text-transform: uppercase;
                  letter-spacing: .07em; color: var(--slate); margin-bottom: .6rem; display: block; }
    .rp-list    { display: flex; flex-direction: column; gap: .35rem; }
    .rp-item    { display: flex; align-items: center; gap: .55rem;
                  padding: .45rem .7rem; border-radius: 8px;
                  background: #f8fafc; border: 1.5px solid var(--border);
                  cursor: pointer; transition: background .15s, border-color .15s; }
    .rp-item:hover               { background: #f1f5f9; }
    .rp-item input[type=checkbox]{ accent-color: var(--teal); width: 15px; height: 15px; flex-shrink: 0; cursor: pointer; }
    .rp-item label               { font-size: .83rem; color: var(--navy); cursor: pointer;
                                   font-family: 'DM Mono', monospace; margin: 0; }
    .rp-item.checked             { background: #f0fdfa; border-color: #5eead4; }
    .rp-empty    { font-size: .8rem; color: var(--slate); font-style: italic; }
    .rp-extra    { margin-top: .6rem; }
    .rp-extra input { border: 1.5px solid var(--border); border-radius: 8px;
                      padding: .45rem .8rem; font-size: .83rem; font-family: 'DM Mono', monospace;
                      color: var(--navy); background: #fafbfd; width: 100%;
                      transition: border-color .15s; }
    .rp-extra input:focus { outline: none; border-color: var(--teal);
                            box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
    .rp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
    @media(max-width:640px) { .rp-grid { grid-template-columns: 1fr; } }
</style>

<div class="rp-grid" style="margin-top:.25rem;">

    {{-- TO --}}
    <div class="rp-section">
        <span class="rp-label">To</span>

        @if (!empty($configTo))
            <div class="rp-list">
                @foreach ($configTo as $email)
                    @php $checked = empty($oldTo) || in_array($email, (array)$oldTo); @endphp
                    <div class="rp-item {{ $checked ? 'checked' : '' }}" onclick="rpToggle(this)">
                        <input type="checkbox" name="to[]" value="{{ $email }}"
                               id="to_{{ $loop->index }}"
                               {{ $checked ? 'checked' : '' }}>
                        <label for="to_{{ $loop->index }}">{{ $email }}</label>
                    </div>
                @endforeach
            </div>
        @else
            <p class="rp-empty">No default recipients configured. Add them to <code>config/reports.php → loans.to</code>.</p>
        @endif

        <div class="rp-extra">
            <input type="text" name="to_extra" value="{{ $oldToExtra }}"
                   placeholder="Add more: one@ecobank.com, two@ecobank.com">
        </div>
    </div>

    {{-- CC --}}
    <div class="rp-section">
        <span class="rp-label">CC</span>

        @if (!empty($configCc))
            <div class="rp-list">
                @foreach ($configCc as $email)
                    @php $checked = empty($oldCc) || in_array($email, (array)$oldCc); @endphp
                    <div class="rp-item {{ $checked ? 'checked' : '' }}" onclick="rpToggle(this)">
                        <input type="checkbox" name="cc[]" value="{{ $email }}"
                               id="cc_{{ $loop->index }}"
                               {{ $checked ? 'checked' : '' }}>
                        <label for="cc_{{ $loop->index }}">{{ $email }}</label>
                    </div>
                @endforeach
            </div>
        @else
            <p class="rp-empty">No default CC configured.</p>
        @endif

        <div class="rp-extra">
            <input type="text" name="cc_extra" value="{{ $oldCcExtra }}"
                   placeholder="Add more: cc@ecobank.com">
        </div>
    </div>

</div>

<script>
    function rpToggle(item) {
        const cb = item.querySelector('input[type=checkbox]');
        if (!cb) return;
        cb.checked = !cb.checked;
        item.classList.toggle('checked', cb.checked);
    }
</script>
