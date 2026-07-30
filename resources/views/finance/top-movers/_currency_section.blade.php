@php
    $ccy = $ccy ?? 'lcy';
    $ccyLabel = $ccyLabel ?? 'Local Currency (LCY)';
    $ccyNote = $ccyNote ?? 'Amounts in LCY equivalent';
    $showCurrencyCol = $showCurrencyCol ?? false;
    $showSegmentCol = $showSegmentCol ?? false;
    $sectionAccent = $sectionAccent ?? null;
@endphp

<div class="currency-section{{ $sectionAccent ? ' currency-section-accent-' . $sectionAccent : '' }}">
    <div class="currency-section-title">
        <h5>{{ $ccyLabel }}</h5>
        <span class="currency-section-badge">{{ strtoupper(str_replace('loans_', '', $ccy)) }}</span>
    </div>

    <div class="movers-card" role="region" aria-label="{{ $ccyLabel }} Top Movers results">
        <div class="tab-nav" role="tablist" aria-label="{{ $ccyLabel }} mover direction" id="tab-nav-root-{{ $ccy }}">
            <button class="tab-btn gain-tab" id="tab_btn_{{ $ccy }}_gain" role="tab" aria-selected="true"
                aria-controls="panel_{{ $ccy }}_gain" tabindex="0">
                <i class="fas fa-arrow-up" aria-hidden="true"></i>
                Top Gainers
                <span class="tab-badge gain-badge" id="{{ $ccy }}_gain_count" aria-label="Gainers count">—</span>
            </button>

            <button class="tab-btn loss-tab" id="tab_btn_{{ $ccy }}_loss" role="tab" aria-selected="false"
                aria-controls="panel_{{ $ccy }}_loss" tabindex="-1">
                <i class="fas fa-arrow-down" aria-hidden="true"></i>
                Top Losers
                <span class="tab-badge loss-badge" id="{{ $ccy }}_loss_count" aria-label="Losers count">—</span>
            </button>
        </div>

        <div class="tab-panel active" id="panel_{{ $ccy }}_gain" role="tabpanel" aria-labelledby="tab_btn_{{ $ccy }}_gain">
            <div class="tab-topbar">
                <div>
                    <div class="tab-topbar-info" id="{{ $ccy }}_gain_info" aria-live="polite">—</div>
                    <span class="amount-note">
                        <i class="fas fa-coins" aria-hidden="true"></i>
                        {{ $ccyNote }}
                    </span>
                </div>

                <button class="btn-export" id="export_{{ $ccy }}_gain" aria-label="Export {{ $ccyLabel }} gainers as CSV">
                    <i class="fas fa-download export-icon" aria-hidden="true"></i>
                    <span class="export-spinner" aria-hidden="true"></span>
                    Export CSV
                </button>
            </div>

            <div id="skeleton_{{ $ccy }}_gain" class="skeleton-table" aria-hidden="true">
                @for ($i = 0; $i < 8; $i++)
                    <div class="skeleton-row">
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:24%"></div>
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:8%"></div>
                    </div>
                @endfor
            </div>

            <div class="empty-state" id="empty_{{ $ccy }}_gain">
                <div class="empty-icon">
                    <i class="fas fa-search-dollar" aria-hidden="true"></i>
                </div>
                <h5>No gainers found</h5>
                <div class="empty-hints" id="empty_{{ $ccy }}_gain_hints"></div>
                <p id="empty_{{ $ccy }}_gain_msg">No records matched the current filters.</p>
                <button class="btn-ghost-eco" onclick="document.getElementById('btn_reset').click()">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                    Reset Filters
                </button>
            </div>

            <div class="table-live-wrapper" aria-live="polite" aria-atomic="false">
                <div class="table-responsive desktop-table">
                    <table id="table_{{ $ccy }}_gain" class="table table-hover table-bordered w-100 mb-0">
                        <thead>
                            <tr>
                                <th>CIF</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                @if ($showCurrencyCol)
                                    <th>Currency</th>
                                @endif
                                @if ($showSegmentCol)
                                    <th>Segment</th>
                                @endif
                                <th class="text-end">Previous Balance</th>
                                <th class="text-end">Current Balance</th>
                                <th class="text-end">Day Movement</th>
                                <th class="text-end">% Change</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="mobile-cards" id="mobile_{{ $ccy }}_gain_cards"></div>
            </div>
        </div>

        <div class="tab-panel" id="panel_{{ $ccy }}_loss" role="tabpanel" aria-labelledby="tab_btn_{{ $ccy }}_loss">
            <div class="tab-topbar">
                <div>
                    <div class="tab-topbar-info" id="{{ $ccy }}_loss_info" aria-live="polite">—</div>
                    <span class="amount-note">
                        <i class="fas fa-coins" aria-hidden="true"></i>
                        {{ $ccyNote }}
                    </span>
                </div>

                <button class="btn-export" id="export_{{ $ccy }}_loss" aria-label="Export {{ $ccyLabel }} losers as CSV">
                    <i class="fas fa-download export-icon" aria-hidden="true"></i>
                    <span class="export-spinner" aria-hidden="true"></span>
                    Export CSV
                </button>
            </div>

            <div id="skeleton_{{ $ccy }}_loss" class="skeleton-table" aria-hidden="true">
                @for ($i = 0; $i < 8; $i++)
                    <div class="skeleton-row">
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:24%"></div>
                        <div class="skeleton-cell" style="width:10%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:16%"></div>
                        <div class="skeleton-cell" style="width:8%"></div>
                    </div>
                @endfor
            </div>

            <div class="empty-state" id="empty_{{ $ccy }}_loss">
                <div class="empty-icon">
                    <i class="fas fa-search-dollar" aria-hidden="true"></i>
                </div>
                <h5>No losers found</h5>
                <div class="empty-hints" id="empty_{{ $ccy }}_loss_hints"></div>
                <p id="empty_{{ $ccy }}_loss_msg">No records matched the current filters.</p>
                <button class="btn-ghost-eco" onclick="document.getElementById('btn_reset').click()">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                    Reset Filters
                </button>
            </div>

            <div class="table-live-wrapper" aria-live="polite" aria-atomic="false">
                <div class="table-responsive desktop-table">
                    <table id="table_{{ $ccy }}_loss" class="table table-hover table-bordered w-100 mb-0">
                        <thead>
                            <tr>
                                <th>CIF</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                @if ($showCurrencyCol)
                                    <th>Currency</th>
                                @endif
                                @if ($showSegmentCol)
                                    <th>Segment</th>
                                @endif
                                <th class="text-end">Previous Balance</th>
                                <th class="text-end">Current Balance</th>
                                <th class="text-end">Day Movement</th>
                                <th class="text-end">% Change</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="mobile-cards" id="mobile_{{ $ccy }}_loss_cards"></div>
            </div>
        </div>
    </div>
</div>
