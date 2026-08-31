@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Str;

    $user = Auth::user();

    $userName = $user?->name ?? 'Finance User';

    $userInitials = collect(preg_split('/\s+/', trim($userName)))
        ->filter()
        ->take(2)
        ->map(function ($word) {
            return strtoupper(mb_substr($word, 0, 1));
        })
        ->implode('');

    if ($userInitials === '') {
        $userInitials = 'FU';
    }

    $userRole = 'Finance User';

    $isFinanceAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('finance-admin');

    if ($user) {
        if (method_exists($user, 'getRoleNames')) {
            $resolvedRole = $user->getRoleNames()->first();

            if ($resolvedRole) {
                $userRole = $resolvedRole;
            }
        } else {
            $roleValue = data_get($user, 'role');

            if (is_string($roleValue) && trim($roleValue) !== '') {
                $userRole = $roleValue;
            } elseif (is_object($roleValue)) {
                $userRole = $roleValue->name ?? ($roleValue->title ?? 'Finance User');
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic badges
    |--------------------------------------------------------------------------
    |
    | Replace these values with real counts when needed.
    |
    */

    $loanCount = 0;
    $topMoversBadge = null;

    /*
    |--------------------------------------------------------------------------
    | Navigation configuration
    |--------------------------------------------------------------------------
    */

    $menuItems = [
        [
            'title' => 'Finance Home',
            'icon' => 'ti-home',
            'url' => '/finance/dashboard',
            'activeCondition' => request()->is('finance/dashboard') || request()->is('finance/home'),
            'badge' => null,
            'children' => [],
        ],
        [
            'title' => 'Top Movers',
            'icon' => 'ti-trophy',
            'url' => '/finance/top-movers',
            'activeCondition' => request()->is('finance/top-movers*'),
            'badge' => $topMoversBadge,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
        [
            'title' => 'Branch Dashboard',
            'icon' => 'ti-building-bank',
            'url' => '#',
            'activeCondition' => request()->is('finance/branch-dashboard*') || request()->is('finance/branch-movers*'),
            'badge' => null,
            'children' => [
                [
                    'title' => 'Branch Dashboard',
                    'icon' => 'ti-layout-dashboard',
                    'url' => '/finance/branch-dashboard',
                    'activeCondition' => request()->is('finance/branch-dashboard*'),
                ],
                [
                    'title' => 'Branch Movers',
                    'icon' => 'ti-trending-up',
                    'url' => '/finance/branch-movers',
                    'activeCondition' => request()->is('finance/branch-movers*'),
                ],
            ],
        ],
        [
            'title' => 'Sub-Segments',
            'icon' => 'ti-category-2',
            'url' => '/finance/exec-sub-segment',
            'activeCondition' => request()->is('finance/exec-sub-segment*'),
            'badge' => null,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
        [
            'title' => 'RM Dashboard',
            'icon' => 'ti-users',
            'url' => '#',
            'activeCondition' =>
                request()->is('finance/relationship-managers*') ||
                request()->is('finance/rm-workload*') ||
                request()->is('finance/rm-work-load*') ||
                request()->is('finance/rm-movers*') ||
                request()->is('finance/rm-targets*') ||
                request()->is('finance/rm-performance*'),
            'badge' => null,
            'children' => [
                [
                    'title' => 'RM Movers',
                    'icon' => 'ti-chart-line',
                    'url' => '/finance/rm-movers',
                    'activeCondition' => request()->is('finance/rm-movers*'),
                ],
                [
                    'title' => 'RM Work Load',
                    'icon' => 'ti-clipboard-list',
                    'url' => '/finance/rm-workload',
                    'activeCondition' =>
                        request()->is('finance/rm-workload*') || request()->is('finance/rm-work-load*'),
                ],
                [
                    'title' => 'RM Targets',
                    'icon' => 'ti-target',
                    'url' => '/finance/rm-targets',
                    'activeCondition' => request()->is('finance/rm-targets'),
                ],
                [
                    'title' => 'RM Performance',
                    'icon' => 'ti-chart-bar',
                    'url' => '/finance/rm-performance',
                    'activeCondition' => request()->is('finance/rm-performance*'),
                ],
                [
                    'title' => 'Manage RM Targets',
                    'icon' => 'ti-file-dollar',
                    'url' => '/finance/rm-targets/manage',
                    'activeCondition' => request()->is('finance/rm-targets/manage*'),
                    'requiresFinanceAdmin' => true,
                ],
                [
                    'title' => 'RM Management',
                    'icon' => 'ti-user-check',
                    'url' => '/finance/relationship-managers',
                    'activeCondition' => request()->is('finance/relationship-managers*'),
                    'requiresFinanceAdmin' => true,
                ],
            ],
        ],
        [
            'title' => 'Customer Trend',
            'icon' => 'ti-chart-area-line',
            'url' => '/finance/customer-trend',
            'activeCondition' => request()->is('finance/customer-trend*'),
            'badge' => null,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
        [
            'title' => 'Customer Profitability',
            'icon' => 'ti-report-money',
            'url' => '/finance/customer-profitability/upload',
            'activeCondition' => request()->is('finance/customer-profitability*'),
            'badge' => null,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
        [
            'title' => 'Loans',
            'icon' => 'ti-cash-banknote',
            'url' => '/finance/loans/pipeline',
            'activeCondition' => request()->is('finance/loans*'),
            'badge' => $loanCount > 0 ? $loanCount : null,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
        [
            'title' => 'Run Report',
            'icon' => 'ti-file-analytics',
            'url' => '/finance/balances/pipeline',
            'activeCondition' => request()->is('finance/balances/pipeline*'),
            'badge' => null,
            'children' => [],
            'requiresFinanceAdmin' => true,
        ],
    ];

    $menuItems = collect($menuItems)
        ->filter(fn ($item) => $isFinanceAdmin || empty($item['requiresFinanceAdmin']))
        ->map(function ($item) use ($isFinanceAdmin) {
            $item['children'] = collect($item['children'] ?? [])
                ->filter(fn ($child) => $isFinanceAdmin || empty($child['requiresFinanceAdmin']))
                ->values()
                ->all();

            return $item;
        })
        ->values()
        ->all();

    $sections = [
        'Overview' => ['Finance Home', 'Top Movers', 'Sub-Segments'],
        'Branches' => ['Branch Dashboard'],
        'Relationship Management' => ['RM Dashboard'],
        'Insights & Reports' => ['Customer Trend', 'Customer Profitability', 'Loans', 'Run Report'],
    ];

    $menuByTitle = collect($menuItems)->keyBy('title');
@endphp

<aside class="finance-sidebar" id="financeSidebar" aria-label="Finance navigation sidebar">
    <div class="finance-sidebar__inner">

        {{-- =====================================================
             BRAND
             ===================================================== --}}

        <div class="finance-sidebar__brand">
            <button type="button" class="finance-sidebar__mobile-close d-lg-none" data-finance-sidebar-close
                aria-label="Close finance sidebar">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>

        {{-- =====================================================
             USER
             ===================================================== --}}

        <div class="finance-sidebar__user">
            <div class="finance-user-avatar" aria-hidden="true" title="{{ $userName }}">
                {{ $userInitials }}
            </div>

            <div class="finance-user-details">
                <span class="finance-user-name">
                    {{ $userName }}
                </span>

                <span class="finance-user-role">
                    {{ $userRole }}
                </span>
            </div>

            <span class="finance-user-online" title="Online" aria-label="User is online"></span>
        </div>

        {{-- =====================================================
             SEARCH
             ===================================================== --}}

        <div class="finance-sidebar__search">
            <div class="finance-search-box">
                <i class="ti ti-search finance-search-box__icon" aria-hidden="true"></i>

                <input type="search" class="finance-search-box__input" placeholder="Search navigation..."
                    aria-label="Search finance navigation" autocomplete="off" data-finance-sidebar-search>

                <kbd class="finance-search-box__shortcut" aria-label="Keyboard shortcut Control K">
                    Ctrl K
                </kbd>
            </div>
        </div>

        {{-- =====================================================
             NAVIGATION
             ===================================================== --}}

        <nav class="finance-nav" id="financeNavigation" aria-label="Finance navigation">
            @foreach ($sections as $sectionLabel => $sectionTitles)
                <section class="finance-nav__section">
                    <div class="finance-nav__section-label">
                        <span>{{ $sectionLabel }}</span>
                    </div>

                    <ul class="finance-nav__list" role="list">
                        @foreach ($sectionTitles as $title)
                            @php
                                $item = $menuByTitle->get($title);
                            @endphp

                            @if (!$item)
                                @continue
                            @endif

                            @php
                                $hasChildren = !empty($item['children']);
                                $isActive = (bool) $item['activeCondition'];

                                $submenuId = 'finance-submenu-' . Str::slug($item['title']);

                                $searchTerms = collect($item['children'] ?? [])
                                    ->pluck('title')
                                    ->prepend($item['title'])
                                    ->implode(' ');
                            @endphp

                            <li class="finance-nav__item
                                    {{ $hasChildren ? 'has-submenu' : '' }}
                                    {{ $isActive ? 'active' : '' }}"
                                role="listitem" data-search="{{ Str::lower($searchTerms) }}">
                                @if ($hasChildren)
                                    <button type="button"
                                        class="finance-nav__link
                                            finance-nav__link--button
                                            {{ $isActive ? 'is-open' : '' }}"
                                        aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                                        aria-controls="{{ $submenuId }}" title="{{ $item['title'] }}"
                                        data-finance-submenu-toggle>
                                        <span class="finance-nav__icon" aria-hidden="true">
                                            <i class="ti {{ $item['icon'] }}"></i>
                                        </span>

                                        <span class="finance-nav__text">
                                            {{ $item['title'] }}
                                        </span>

                                        @if (!empty($item['badge']))
                                            <span class="finance-nav__badge">
                                                {{ $item['badge'] }}
                                            </span>
                                        @endif

                                        <i class="ti ti-chevron-down finance-nav__chevron" aria-hidden="true"></i>
                                    </button>

                                    <ul class="finance-nav__submenu
                                            {{ $isActive ? 'show' : '' }}"
                                        id="{{ $submenuId }}" role="list">
                                        @foreach ($item['children'] as $child)
                                            @php
                                                $childActive = (bool) ($child['activeCondition'] ?? false);
                                            @endphp

                                            <li class="{{ $childActive ? 'active' : '' }}" role="listitem">
                                                <a href="{{ url($child['url']) }}"
                                                    aria-current="{{ $childActive ? 'page' : 'false' }}">
                                                    <span class="finance-nav__submenu-icon" aria-hidden="true">
                                                        <i class="ti {{ $child['icon'] }}"></i>
                                                    </span>

                                                    <span>
                                                        {{ $child['title'] }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <a href="{{ url($item['url']) }}" class="finance-nav__link"
                                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                                        title="{{ $item['title'] }}">
                                        <span class="finance-nav__icon" aria-hidden="true">
                                            <i class="ti {{ $item['icon'] }}"></i>
                                        </span>

                                        <span class="finance-nav__text">
                                            {{ $item['title'] }}
                                        </span>

                                        @if (!empty($item['badge']))
                                            <span
                                                class="finance-nav__badge
                                                    {{ is_string($item['badge']) ? 'is-label' : '' }}">
                                                {{ $item['badge'] }}
                                            </span>
                                        @endif
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            <div class="finance-nav__empty" data-finance-search-empty hidden>
                <i class="ti ti-search-off" aria-hidden="true"></i>
                <strong>No menu found</strong>
                <span>Try a different search phrase.</span>
            </div>
        </nav>

        {{-- =====================================================
             FOOTER ACTIONS
             ===================================================== --}}

        <div class="finance-sidebar__footer">

            {{-- Desktop collapse button --}}
            <button type="button" class="finance-sidebar-collapse d-none d-lg-flex" data-finance-sidebar-collapse
                aria-expanded="true" aria-label="Collapse finance sidebar" title="Collapse or expand sidebar">
                <span class="finance-sidebar-collapse__icon">
                    <i class="ti ti-chevrons-left" data-finance-collapse-icon aria-hidden="true"></i>
                </span>

                <span data-finance-collapse-label>
                    Collapse sidebar
                </span>
            </button>

            <div class="finance-secure-status">
                <span class="finance-secure-status__icon">
                    <i class="ti ti-shield-check" aria-hidden="true"></i>
                </span>

                <span class="finance-secure-status__copy">
                    <strong>Secure session</strong>
                    <small>Protected finance workspace</small>
                </span>

                <span class="finance-secure-status__dot" aria-label="Secure connection active"></span>
            </div>
        </div>

    </div>
</aside>

<style>
    /* =========================================================
       FINANCE SIDEBAR
       ========================================================= */

    .finance-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 1050;
        width: var(--finance-sidebar-width, 270px);
        height: 100vh;
        height: 100dvh;
        color: #ffffff;
        background:
            radial-gradient(circle at 20% -8%,
                rgba(0, 168, 107, .22),
                transparent 18rem),
            radial-gradient(circle at 105% 22%,
                rgba(8, 117, 225, .19),
                transparent 20rem),
            linear-gradient(180deg,
                #041426 0%,
                #061d34 48%,
                #041525 100%);
        border-right: 1px solid rgba(255, 255, 255, .065);
        box-shadow:
            12px 0 40px rgba(2, 13, 25, .22);
        overflow: hidden;
        transition:
            width var(--finance-transition),
            transform var(--finance-transition),
            box-shadow var(--finance-transition);
    }

    .finance-sidebar::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        background:
            linear-gradient(90deg,
                rgba(255, 255, 255, .016),
                transparent 28%);
    }

    .finance-sidebar__inner {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    /* =========================================================
       BRAND
       ========================================================= */

    .finance-sidebar__brand {
        min-height: 54px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
        flex-shrink: 0;
    }

    @media (min-width: 992px) {
        .finance-sidebar__brand {
            display: none;
        }
    }

    .finance-brand {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 11px;
        color: #ffffff;
    }

    .finance-brand:hover {
        color: #ffffff;
    }

    .finance-brand__mark {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background:
            linear-gradient(135deg,
                #08b978 0%,
                #0875e1 100%);
        box-shadow:
            0 10px 24px rgba(0, 117, 225, .25);
    }

    .finance-brand__mark span {
        color: #ffffff;
        font-family: Georgia, serif;
        font-size: 21px;
        font-style: italic;
        font-weight: 700;
        line-height: 1;
    }

    .finance-brand__copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition:
            opacity .16s ease,
            width var(--finance-transition);
    }

    .finance-brand__copy strong {
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -.01em;
        white-space: nowrap;
    }

    .finance-brand__copy small {
        margin-top: 3px;
        color: rgba(255, 255, 255, .47);
        font-size: 9.5px;
        font-weight: 600;
        letter-spacing: .025em;
        white-space: nowrap;
    }

    .finance-sidebar__mobile-close {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: grid;
        place-items: center;
        padding: 0;
        color: rgba(255, 255, 255, .72);
        background: rgba(255, 255, 255, .07);
        border: 1px solid rgba(255, 255, 255, .09);
        border-radius: 10px;
        cursor: pointer;
    }

    .finance-sidebar__mobile-close:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, .12);
    }

    /* =========================================================
       USER
       ========================================================= */

    .finance-sidebar__user {
        min-height: 66px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, .06);
        flex-shrink: 0;
    }

    .finance-user-avatar {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: grid;
        place-items: center;
        color: #ffffff;
        background:
            linear-gradient(135deg,
                rgba(8, 117, 225, .96),
                rgba(20, 91, 174, .98));
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 50%;
        box-shadow:
            0 7px 18px rgba(0, 86, 171, .20);
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .03em;
    }

    .finance-user-details {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition:
            opacity .16s ease,
            width var(--finance-transition);
    }

    .finance-user-name {
        overflow: hidden;
        color: rgba(255, 255, 255, .94);
        font-size: 11.5px;
        font-weight: 750;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .finance-user-role {
        margin-top: 2px;
        overflow: hidden;
        color: rgba(255, 255, 255, .46);
        font-size: 9.5px;
        font-weight: 550;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .finance-user-online {
        width: 7px;
        height: 7px;
        flex: 0 0 7px;
        background: #22d792;
        border-radius: 50%;
        box-shadow:
            0 0 0 4px rgba(34, 215, 146, .10);
        animation: financeOnlinePulse 2.2s ease-in-out infinite;
    }

    @keyframes financeOnlinePulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .45;
        }
    }

    /* =========================================================
       SEARCH
       ========================================================= */

    .finance-sidebar__search {
        padding: 10px 11px 9px;
        flex-shrink: 0;
    }

    .finance-search-box {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 39px;
        padding: 0 9px;
        background: rgba(255, 255, 255, .055);
        border: 1px solid rgba(255, 255, 255, .09);
        border-radius: 10px;
        transition:
            background-color .15s ease,
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .finance-search-box:focus-within {
        background: rgba(255, 255, 255, .085);
        border-color: rgba(43, 145, 244, .45);
        box-shadow:
            0 0 0 3px rgba(43, 145, 244, .09);
    }

    .finance-search-box__icon {
        flex: 0 0 auto;
        color: rgba(255, 255, 255, .42);
        font-size: 14px;
    }

    .finance-search-box__input {
        min-width: 0;
        height: 37px;
        flex: 1;
        padding: 0 8px;
        color: rgba(255, 255, 255, .82);
        background: transparent;
        border: 0;
        outline: 0;
        font-size: 11px;
    }

    .finance-search-box__input::placeholder {
        color: rgba(255, 255, 255, .32);
    }

    .finance-search-box__input::-webkit-search-cancel-button {
        filter: invert(1);
        opacity: .5;
    }

    .finance-search-box__shortcut {
        flex: 0 0 auto;
        padding: 2px 5px;
        color: rgba(255, 255, 255, .35);
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 5px;
        font-family: inherit;
        font-size: 8px;
        font-weight: 700;
        line-height: 1.4;
    }

    /* =========================================================
       NAVIGATION
       ========================================================= */

    .finance-nav {
        min-height: 0;
        flex: 1;
        padding: 1px 9px 10px;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-color:
            rgba(255, 255, 255, .12) transparent;
        scrollbar-width: thin;
    }

    .finance-nav::-webkit-scrollbar {
        width: 4px;
    }

    .finance-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .finance-nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .12);
        border-radius: 999px;
    }

    .finance-nav__section {
        margin-bottom: 5px;
    }

    .finance-nav__section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 27px;
        margin: 4px 6px 2px;
        color: rgba(255, 255, 255, .34);
        font-size: 8.5px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        white-space: nowrap;
        transition:
            opacity .15s ease,
            height var(--finance-transition);
    }

    .finance-nav__section-label::after {
        content: "";
        height: 1px;
        flex: 1;
        background:
            linear-gradient(90deg,
                rgba(255, 255, 255, .08),
                transparent);
    }

    .finance-nav__list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .finance-nav__item {
        position: relative;
        margin: 2px 0;
    }

    .finance-nav__item[hidden],
    .finance-nav__section[hidden] {
        display: none !important;
    }

    .finance-nav__link {
        position: relative;
        width: 100%;
        min-height: 40px;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 5px 8px;
        color: rgba(255, 255, 255, .64);
        background: transparent;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: 11.5px;
        font-weight: 550;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
        overflow: hidden;
        transition:
            color .15s ease,
            background-color .15s ease,
            border-color .15s ease,
            transform .15s ease;
    }

    .finance-nav__link--button {
        appearance: none;
    }

    .finance-nav__link:hover {
        color: rgba(255, 255, 255, .94);
        background: rgba(255, 255, 255, .065);
        border-color: rgba(255, 255, 255, .045);
    }

    .finance-nav__item.active>.finance-nav__link {
        color: #ffffff;
        background:
            linear-gradient(90deg,
                rgba(8, 117, 225, .34),
                rgba(8, 117, 225, .16));
        border-color: rgba(43, 145, 244, .25);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .03);
    }

    .finance-nav__item.active>.finance-nav__link::before {
        content: "";
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: -1px;
        width: 3px;
        background:
            linear-gradient(180deg,
                #20cf8b,
                #2b91f4);
        border-radius: 0 4px 4px 0;
        box-shadow:
            0 0 14px rgba(43, 145, 244, .42);
    }

    .finance-nav__icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        display: grid;
        place-items: center;
        color: rgba(255, 255, 255, .56);
        background: rgba(255, 255, 255, .055);
        border: 1px solid rgba(255, 255, 255, .035);
        border-radius: 8px;
        transition:
            color .15s ease,
            background-color .15s ease,
            transform .15s ease;
    }

    .finance-nav__icon i {
        font-size: 13px;
    }

    .finance-nav__link:hover .finance-nav__icon {
        color: #ffffff;
        background: rgba(255, 255, 255, .10);
        transform: scale(1.03);
    }

    .finance-nav__item.active>.finance-nav__link .finance-nav__icon {
        color: #ffffff;
        background:
            linear-gradient(135deg,
                rgba(32, 207, 139, .88),
                rgba(43, 145, 244, .96));
        border-color: rgba(255, 255, 255, .12);
        box-shadow:
            0 7px 16px rgba(0, 100, 188, .18);
    }

    .finance-nav__text {
        min-width: 0;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        transition:
            opacity .14s ease,
            width var(--finance-transition);
    }

    .finance-nav__badge {
        min-width: 20px;
        padding: 3px 6px;
        flex: 0 0 auto;
        color: #ffd45f;
        background: rgba(245, 183, 0, .16);
        border: 1px solid rgba(245, 183, 0, .16);
        border-radius: 999px;
        font-size: 8.5px;
        font-weight: 800;
        line-height: 1;
        text-align: center;
    }

    .finance-nav__badge.is-label {
        color: #65eeb0;
        background: rgba(32, 207, 139, .14);
        border-color: rgba(32, 207, 139, .15);
    }

    .finance-nav__chevron {
        flex: 0 0 auto;
        color: rgba(255, 255, 255, .34);
        font-size: 10px;
        transition: transform .2s ease;
    }

    .finance-nav__link.is-open .finance-nav__chevron {
        transform: rotate(180deg);
    }

    /* =========================================================
       SUBMENU
       ========================================================= */

    .finance-nav__submenu {
        max-height: 0;
        margin: 1px 0 1px 22px;
        padding: 0 0 0 12px;
        border-left: 1px solid rgba(255, 255, 255, .095);
        list-style: none;
        overflow: hidden;
        opacity: 0;
        transition:
            max-height .25s ease,
            opacity .18s ease,
            padding-top .25s ease,
            padding-bottom .25s ease;
    }

    .finance-nav__submenu.show {
        max-height: 420px;
        padding-top: 3px;
        padding-bottom: 4px;
        opacity: 1;
    }

    .finance-nav__submenu li {
        position: relative;
        margin: 1px 0;
    }

    .finance-nav__submenu li a {
        min-height: 34px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 8px;
        color: rgba(255, 255, 255, .48);
        background: transparent;
        border-radius: 8px;
        font-size: 10.8px;
        font-weight: 520;
        transition:
            color .15s ease,
            background-color .15s ease;
    }

    .finance-nav__submenu li a:hover {
        color: rgba(255, 255, 255, .9);
        background: rgba(255, 255, 255, .055);
    }

    .finance-nav__submenu li.active a {
        color: #ffffff;
        background: rgba(43, 145, 244, .13);
    }

    .finance-nav__submenu li.active::before {
        content: "";
        position: absolute;
        top: 50%;
        left: -15px;
        width: 5px;
        height: 5px;
        background: #2b91f4;
        border-radius: 50%;
        box-shadow:
            0 0 8px rgba(43, 145, 244, .65);
        transform: translateY(-50%);
    }

    .finance-nav__submenu-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        display: grid;
        place-items: center;
        color: rgba(255, 255, 255, .34);
    }

    .finance-nav__submenu-icon i {
        font-size: 11px;
    }

    .finance-nav__submenu li.active .finance-nav__submenu-icon {
        color: #63aff8;
    }

    /* =========================================================
       EMPTY SEARCH
       ========================================================= */

    .finance-nav__empty {
        min-height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: rgba(255, 255, 255, .40);
        text-align: center;
    }

    .finance-nav__empty[hidden] {
        display: none !important;
    }

    .finance-nav__empty i {
        margin-bottom: 8px;
        font-size: 25px;
    }

    .finance-nav__empty strong {
        color: rgba(255, 255, 255, .65);
        font-size: 11px;
    }

    .finance-nav__empty span {
        margin-top: 3px;
        font-size: 9.5px;
    }

    /* =========================================================
       FOOTER
       ========================================================= */

    .finance-sidebar__footer {
        padding: 9px 10px 11px;
        border-top: 1px solid rgba(255, 255, 255, .07);
        flex-shrink: 0;
    }

    /* =========================================================
       COLLAPSE BUTTON
       ========================================================= */

    .finance-sidebar-collapse {
        width: 100%;
        min-height: 38px;
        align-items: center;
        gap: 9px;
        margin-bottom: 7px;
        padding: 6px 9px;
        color: rgba(255, 255, 255, .47);
        background: transparent;
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: 9px;
        cursor: pointer;
        font-size: 9.8px;
        font-weight: 650;
        text-align: left;
        transition:
            color .15s ease,
            background-color .15s ease;
    }

    .finance-sidebar-collapse:hover {
        color: rgba(255, 255, 255, .86);
        background: rgba(255, 255, 255, .06);
    }

    .finance-sidebar-collapse__icon {
        width: 22px;
        height: 22px;
        flex: 0 0 22px;
        display: grid;
        place-items: center;
        color: rgba(255, 255, 255, .50);
    }

    .finance-sidebar-collapse__icon i {
        font-size: 13px;
    }

    /* =========================================================
       SECURE STATUS
       ========================================================= */

    .finance-secure-status {
        min-height: 39px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 9px;
        background: rgba(32, 207, 139, .055);
        border: 1px solid rgba(32, 207, 139, .12);
        border-radius: 9px;
    }

    .finance-secure-status__icon {
        width: 24px;
        height: 24px;
        flex: 0 0 24px;
        display: grid;
        place-items: center;
        color: #3cdf9a;
        background: rgba(32, 207, 139, .08);
        border-radius: 7px;
    }

    .finance-secure-status__icon i {
        font-size: 12px;
    }

    .finance-secure-status__copy {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        transition:
            opacity .14s ease,
            width var(--finance-transition);
    }

    .finance-secure-status__copy strong {
        color: rgba(255, 255, 255, .57);
        font-size: 9.5px;
        font-weight: 700;
        line-height: 1.2;
    }

    .finance-secure-status__copy small {
        margin-top: 1px;
        color: rgba(255, 255, 255, .28);
        font-size: 7.8px;
        font-weight: 550;
        white-space: nowrap;
    }

    .finance-secure-status__dot {
        width: 6px;
        height: 6px;
        flex: 0 0 6px;
        background: #3cdf9a;
        border-radius: 50%;
        box-shadow:
            0 0 0 4px rgba(60, 223, 154, .08);
    }

    /* =========================================================
       COLLAPSED DESKTOP SIDEBAR
       ========================================================= */

    body.finance-sidebar-collapsed .finance-sidebar,
    html.finance-sidebar-collapsed-preload .finance-sidebar {
        width: var(--finance-sidebar-collapsed-width, 78px);
    }

    body.finance-sidebar-collapsed .finance-sidebar__brand,
    html.finance-sidebar-collapsed-preload .finance-sidebar__brand {
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }

    body.finance-sidebar-collapsed .finance-brand,
    html.finance-sidebar-collapsed-preload .finance-brand {
        justify-content: center;
    }

    body.finance-sidebar-collapsed .finance-brand__copy,
    body.finance-sidebar-collapsed .finance-user-details,
    body.finance-sidebar-collapsed .finance-user-online,
    body.finance-sidebar-collapsed .finance-search-box__input,
    body.finance-sidebar-collapsed .finance-search-box__shortcut,
    body.finance-sidebar-collapsed .finance-nav__section-label,
    body.finance-sidebar-collapsed .finance-nav__text,
    body.finance-sidebar-collapsed .finance-nav__badge,
    body.finance-sidebar-collapsed .finance-nav__chevron,
    body.finance-sidebar-collapsed .finance-theme-toggle__copy,
    body.finance-sidebar-collapsed .finance-theme-switch,
    body.finance-sidebar-collapsed .finance-sidebar-collapse span:not(.finance-sidebar-collapse__icon),
    body.finance-sidebar-collapsed .finance-secure-status__copy,
    body.finance-sidebar-collapsed .finance-secure-status__dot,
    body.finance-sidebar-collapsed .finance-sidebar-action span,
    html.finance-sidebar-collapsed-preload .finance-brand__copy,
    html.finance-sidebar-collapsed-preload .finance-user-details,
    html.finance-sidebar-collapsed-preload .finance-user-online,
    html.finance-sidebar-collapsed-preload .finance-search-box__input,
    html.finance-sidebar-collapsed-preload .finance-search-box__shortcut,
    html.finance-sidebar-collapsed-preload .finance-nav__section-label,
    html.finance-sidebar-collapsed-preload .finance-nav__text,
    html.finance-sidebar-collapsed-preload .finance-nav__badge,
    html.finance-sidebar-collapsed-preload .finance-nav__chevron,
    html.finance-sidebar-collapsed-preload .finance-theme-toggle__copy,
    html.finance-sidebar-collapsed-preload .finance-theme-switch,
    html.finance-sidebar-collapsed-preload .finance-sidebar-collapse span:not(.finance-sidebar-collapse__icon),
    html.finance-sidebar-collapsed-preload .finance-secure-status__copy,
    html.finance-sidebar-collapsed-preload .finance-secure-status__dot,
    html.finance-sidebar-collapsed-preload .finance-sidebar-action span {
        width: 0;
        max-width: 0;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
    }

    body.finance-sidebar-collapsed .finance-sidebar__user,
    html.finance-sidebar-collapsed-preload .finance-sidebar__user {
        justify-content: center;
        padding-left: 9px;
        padding-right: 9px;
    }

    body.finance-sidebar-collapsed .finance-sidebar__search,
    html.finance-sidebar-collapsed-preload .finance-sidebar__search {
        padding-left: 10px;
        padding-right: 10px;
    }

    body.finance-sidebar-collapsed .finance-search-box,
    html.finance-sidebar-collapsed-preload .finance-search-box {
        justify-content: center;
        padding: 0;
    }

    body.finance-sidebar-collapsed .finance-nav,
    html.finance-sidebar-collapsed-preload .finance-nav {
        padding-left: 9px;
        padding-right: 9px;
    }

    body.finance-sidebar-collapsed .finance-nav__section,
    html.finance-sidebar-collapsed-preload .finance-nav__section {
        margin-bottom: 8px;
    }

    body.finance-sidebar-collapsed .finance-nav__link,
    html.finance-sidebar-collapsed-preload .finance-nav__link {
        justify-content: center;
        gap: 0;
        padding-left: 6px;
        padding-right: 6px;
    }

    body.finance-sidebar-collapsed .finance-nav__item.active>.finance-nav__link::before,
    html.finance-sidebar-collapsed-preload .finance-nav__item.active>.finance-nav__link::before {
        top: 9px;
        bottom: 9px;
    }

    body.finance-sidebar-collapsed .finance-nav__submenu,
    html.finance-sidebar-collapsed-preload .finance-nav__submenu {
        display: none !important;
    }

    body.finance-sidebar-collapsed .finance-sidebar__footer-links,
    html.finance-sidebar-collapsed-preload .finance-sidebar__footer-links {
        grid-template-columns: 1fr;
    }

    body.finance-sidebar-collapsed .finance-sidebar-action,
    body.finance-sidebar-collapsed .finance-theme-toggle,
    body.finance-sidebar-collapsed .finance-sidebar-collapse,
    body.finance-sidebar-collapsed .finance-secure-status,
    html.finance-sidebar-collapsed-preload .finance-sidebar-action,
    html.finance-sidebar-collapsed-preload .finance-theme-toggle,
    html.finance-sidebar-collapsed-preload .finance-sidebar-collapse,
    html.finance-sidebar-collapsed-preload .finance-secure-status {
        justify-content: center;
        padding-left: 6px;
        padding-right: 6px;
    }

    body.finance-sidebar-collapsed .finance-theme-toggle__icon,
    html.finance-sidebar-collapsed-preload .finance-theme-toggle__icon {
        flex-basis: 27px;
    }

    body.finance-sidebar-collapsed .finance-secure-status__icon,
    html.finance-sidebar-collapsed-preload .finance-secure-status__icon {
        flex-basis: 24px;
    }

    /* =========================================================
       TABLET / MOBILE
       ========================================================= */

    @media (max-width: 991.98px) {
        .finance-sidebar {
            width: min(286px, 88vw);
            transform: translateX(-105%);
            box-shadow:
                20px 0 70px rgba(0, 0, 0, .34);
        }

        body.finance-sidebar-open .finance-sidebar {
            transform: translateX(0);
        }

        body.finance-sidebar-collapsed .finance-sidebar {
            width: min(286px, 88vw);
        }

        body.finance-sidebar-collapsed .finance-brand__copy,
        body.finance-sidebar-collapsed .finance-user-details,
        body.finance-sidebar-collapsed .finance-user-online,
        body.finance-sidebar-collapsed .finance-search-box__input,
        body.finance-sidebar-collapsed .finance-search-box__shortcut,
        body.finance-sidebar-collapsed .finance-nav__section-label,
        body.finance-sidebar-collapsed .finance-nav__text,
        body.finance-sidebar-collapsed .finance-nav__badge,
        body.finance-sidebar-collapsed .finance-nav__chevron,
        body.finance-sidebar-collapsed .finance-theme-toggle__copy,
        body.finance-sidebar-collapsed .finance-theme-switch,
        body.finance-sidebar-collapsed .finance-secure-status__copy,
        body.finance-sidebar-collapsed .finance-secure-status__dot,
        body.finance-sidebar-collapsed .finance-sidebar-action span {
            width: auto;
            max-width: none;
            opacity: 1;
            overflow: visible;
            pointer-events: auto;
        }

        body.finance-sidebar-collapsed .finance-sidebar__brand {
            justify-content: space-between;
            padding-left: 14px;
            padding-right: 14px;
        }

        body.finance-sidebar-collapsed .finance-brand {
            justify-content: flex-start;
        }

        body.finance-sidebar-collapsed .finance-sidebar__user {
            justify-content: flex-start;
            padding-left: 14px;
            padding-right: 14px;
        }

        body.finance-sidebar-collapsed .finance-search-box {
            justify-content: flex-start;
            padding-left: 9px;
            padding-right: 9px;
        }

        body.finance-sidebar-collapsed .finance-nav__link {
            justify-content: flex-start;
            gap: 9px;
            padding-left: 8px;
            padding-right: 8px;
        }

        body.finance-sidebar-collapsed .finance-nav__submenu {
            display: block;
        }

        body.finance-sidebar-collapsed .finance-sidebar__footer-links {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        body.finance-sidebar-collapsed .finance-sidebar-action,
        body.finance-sidebar-collapsed .finance-theme-toggle,
        body.finance-sidebar-collapsed .finance-secure-status {
            justify-content: flex-start;
            padding-left: 9px;
            padding-right: 9px;
        }
    }

    @media (max-height: 760px) and (min-width: 992px) {
        .finance-sidebar__brand {
            min-height: 62px;
            padding-top: 9px;
            padding-bottom: 9px;
        }

        .finance-sidebar__user {
            min-height: 56px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .finance-sidebar__search {
            padding-top: 7px;
            padding-bottom: 6px;
        }

        .finance-nav__section-label {
            min-height: 22px;
        }

        .finance-nav__link {
            min-height: 37px;
        }

        .finance-nav__submenu li a {
            min-height: 31px;
        }

        .finance-secure-status {
            display: none;
        }
    }

    @media (max-width: 380px) {
        .finance-sidebar {
            width: 92vw;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .finance-user-online {
            animation: none;
        }
    }
</style>
