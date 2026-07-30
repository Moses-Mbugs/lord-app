<div class="modal fade" id="uploadBalancesModal" tabindex="-1" aria-labelledby="uploadBalancesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-white">
                <div>
                    <h5 class="modal-title mb-0" id="uploadBalancesModalLabel">Upload Customer Balances</h5>
                    <div class="text-muted small">Upload the Flexcube TXT file. The system will import into DB (no file is stored).</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('finance.balances.upload') }}" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label">Balances TXT File</label>
                        <input type="file" name="balances_file" class="form-control" required accept=".txt,text/plain">
                        <small class="text-muted">Upload “BALANCES PER CUSTOMER...” tab-delimited TXT</small>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Balance Date (optional)</label>
                            <input type="date" name="balance_date" class="form-control" value="{{ request('balance_date') }}">
                            <small class="text-muted">Leave blank to auto-detect from filename (e.g. 12.01.2026).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Auto build movers?</label>
                            <select name="build_movers" class="form-select">
                                <option value="0" selected>No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="currency_type" class="form-select">
                                <option value="LCY" {{ request('currency_type','LCY') === 'LCY' ? 'selected' : '' }}>LCY</option>
                                <option value="FCY" {{ request('currency_type') === 'FCY' ? 'selected' : '' }}>FCY</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Limit</label>
                            <input type="number" name="limit" class="form-control" value="{{ request('limit',20) }}" min="1" max="200">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-upload me-1"></i> Upload & Process
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
