<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Home | EcoBank Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue: #0082BB;
            --dark-blue: #005B82;
            --green: #BED600;
            --dark-green: #669438;
            --gray: #464646;
            --light-gray: #EDEDED;
            --mid-gray: #979797;
            --bg: #EAF1F6;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--bg), #DDE8F0);
            color: var(--gray);
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, var(--dark-blue), var(--blue));
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0, 91, 130, 0.18);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            height: 30px;
            filter: brightness(0) invert(1);
        }

        .brand span {
            color: #fff;
            font-weight: 800;
            letter-spacing: .3px;
            font-size: 15px;
            opacity: .9;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-pill {
            color: #fff;
            font-weight: 700;
            font-size: 13.5px;
            background: rgba(255,255,255,0.14);
            padding: 8px 16px;
            border-radius: 999px;
        }

        .btn-logout {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.32);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.26);
        }

        .hero {
            padding: 48px 40px 20px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 30px;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 6px;
        }

        .hero h1 .accent {
            color: var(--dark-green);
        }

        .hero p {
            color: var(--mid-gray);
            font-size: 14.5px;
        }

        .module-grid {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 40px 60px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px;
        }

        .module-card {
            background: #fff;
            border-radius: 22px;
            padding: 30px;
            box-shadow: 0 16px 35px rgba(23, 50, 77, 0.08);
            border: 1px solid #D7E4ED;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 42px rgba(0, 91, 130, 0.16);
        }

        .module-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--blue), var(--dark-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .module-card h3 {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark-blue);
        }

        .module-card p {
            font-size: 13px;
            color: var(--mid-gray);
            line-height: 1.6;
        }

        .module-card .go {
            margin-top: auto;
            font-size: 13px;
            font-weight: 800;
            color: var(--blue);
        }

        .footer-note {
            text-align: center;
            color: var(--mid-gray);
            font-size: 12px;
            padding-bottom: 30px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand">
            <img src="{{ asset('assets/img/Ecobank_Logo.png') }}" alt="EcoBank">
            <span>Finance Intelligence Platform</span>
        </div>

        <div class="topbar-right">
            <span class="user-pill">{{ $user->name ?? 'User' }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Sign Out</button>
            </form>
        </div>
    </div>

    <div class="hero">
        <h1>Welcome back, <span class="accent">{{ explode(' ', $user->name ?? 'there')[0] }}</span></h1>
        <p>Choose a module to get started.</p>
    </div>

    <div class="module-grid">
        @foreach ($modules as $module)
            @if ($module['visible'])
                <a href="{{ route($module['route']) }}" class="module-card">
                    <div class="module-icon">{{ $module['icon'] }}</div>
                    <h3>{{ $module['name'] }}</h3>
                    <p>{{ $module['description'] }}</p>
                    <span class="go">Open &rarr;</span>
                </a>
            @endif
        @endforeach
    </div>

    <div class="footer-note">
        EcoBank Finance Intelligence Platform &middot; {{ date('Y') }}
    </div>

</body>
</html>
