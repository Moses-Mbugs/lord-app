<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finance Dashboard | EcoBank</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue: #0082BB;
            --dark-blue: #005B82;
            --green: #BED600;
            --dark-green: #669438;
            --gray: #464646;
            --light-gray: #EDEDED;
            --mid-gray: #979797;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        /* ── SPLIT LAYOUT ─────────────────────── */
        .wrapper {
            display: flex;
            height: 100vh;
        }

        /* ── LEFT PANEL ───────────────────────── */
        .left {
            flex: 0 0 58%;
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, #003a54 0%, var(--dark-blue) 30%, var(--blue) 65%, #4a7a28 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px;
        }

        /* dot-grid overlay */
        .left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: 0;
        }

        /* floating glass blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(2px);
            pointer-events: none;
        }
        .blob-1 { width: 520px; height: 520px; top: -180px; right: -160px; }
        .blob-2 { width: 340px; height: 340px; bottom: -100px; left: -100px; }
        .blob-3 { width: 180px; height: 180px; top: 45%; left: 62%; transform: translate(-50%,-50%); background: rgba(190,214,0,0.08); }
        .blob-4 { width: 100px; height: 100px; top: 18%; left: 12%; background: rgba(190,214,0,0.06); }

        .left-inner {
            position: relative;
            z-index: 1;
        }

        /* wordmark */
        .wordmark {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 64px;
        }
        .wordmark-icon {
            width: 46px; height: 46px;
            background: var(--green);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .wordmark-icon i { color: var(--dark-blue); font-size: 1.2rem; }
        .wordmark-text {
            color: white;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 1.5px;
        }
        .wordmark-text em { color: var(--green); font-style: normal; }

        .left h1 {
            color: white;
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 18px;
        }
        .left h1 .accent { color: var(--green); }

        .left .tagline {
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
            line-height: 1.75;
            margin-bottom: 52px;
        }

        /* feature list */
        .features { display: flex; flex-direction: column; gap: 22px; }
        .feat {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .feat-icon {
            width: 42px; height: 42px; flex-shrink: 0;
            background: rgba(190,214,0,0.15);
            border: 1px solid rgba(190,214,0,0.35);
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
        }
        .feat-icon i { color: var(--green); font-size: 0.95rem; }
        .feat-body strong {
            display: block;
            color: rgba(255,255,255,0.95);
            font-size: 0.9rem;
            font-weight: 600;
        }
        .feat-body span {
            color: rgba(255,255,255,0.55);
            font-size: 0.78rem;
        }

        .left-foot {
            position: absolute;
            bottom: 28px; left: 64px;
            color: rgba(255,255,255,0.35);
            font-size: 0.72rem;
        }

        /* ── CANVAS + BLOB ANIMATIONS ─────────── */
        #leftCanvas {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        @keyframes bfloat {
            0%, 100% { transform: scale(1)    translateY(0px);   opacity: 1;   }
            50%       { transform: scale(1.07) translateY(-20px); opacity: 0.65; }
        }
        .blob-1 { animation: bfloat 16s ease-in-out infinite;       }
        .blob-2 { animation: bfloat 13s ease-in-out infinite 3s;    }
        .blob-3 { animation: bfloat 19s ease-in-out infinite 1.5s;  }
        .blob-4 { animation: bfloat 11s ease-in-out infinite 5s;    }

        /* ── RIGHT PANEL ──────────────────────── */
        .right {
            flex: 1;
            background: #eef2f7;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            position: relative;
            overflow: hidden;
        }
        .right::before {
            content: '';
            position: absolute;
            width: 420px; height: 420px;
            top: -120px; right: -120px;
            background: radial-gradient(circle, rgba(0,130,187,0.07) 0%, transparent 70%);
            pointer-events: none;
        }
        .right::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            bottom: -80px; left: -60px;
            background: radial-gradient(circle, rgba(190,214,0,0.07) 0%, transparent 70%);
            pointer-events: none;
        }

        /* glass card */
        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 430px;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 26px;
            padding: 50px 46px;
            box-shadow:
                0 30px 60px rgba(0, 91, 130, 0.1),
                0 8px 20px rgba(0,0,0,0.05),
                inset 0 1px 0 rgba(255,255,255,0.6);
        }

        /* colour dots logo */
        .card-dots {
            display: flex;
            gap: 7px;
            margin-bottom: 34px;
        }
        .dot {
            width: 11px; height: 11px;
            border-radius: 50%;
        }
        .dot-b { background: var(--blue); }
        .dot-g { background: var(--green); }
        .dot-d { background: var(--dark-green); }

        .card h2 {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 5px;
        }
        .card .sub {
            font-size: 0.85rem;
            color: var(--mid-gray);
            margin-bottom: 34px;
        }

        /* alerts */
        .alert {
            border-radius: 11px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 0.83rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert i { margin-top: 1px; flex-shrink: 0; }
        .alert-err { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
        .alert-ok  { background: #f0fff4; border: 1px solid #c6f6d5; color: #276749; }
        .alert-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

        /* form */
        .fgroup { margin-bottom: 22px; }
        .fgroup label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap .ico {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--mid-gray);
            font-size: 0.85rem;
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border: 1.5px solid var(--light-gray);
            border-radius: 11px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.88rem;
            color: var(--gray);
            background: rgba(255,255,255,0.7);
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .input-wrap input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0,130,187,0.1);
        }
        .input-wrap input::placeholder { color: #b0b8c1; }
        .toggle-pw {
            position: absolute;
            right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer;
            color: var(--mid-gray);
            font-size: 0.85rem;
            padding: 3px;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--blue); }

        .row-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.8rem;
            color: var(--gray);
            cursor: pointer;
            user-select: none;
        }
        .remember-label input[type="checkbox"] {
            accent-color: var(--blue);
            width: 15px; height: 15px;
        }

        /* submit */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--blue), var(--dark-blue));
            color: white;
            border: none;
            border-radius: 11px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.93rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 9px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, var(--dark-blue), #003452);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0,91,130,0.28);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 17px; height: 17px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .btn-submit.loading .spinner { display: block; }
        .btn-submit.loading .btn-label { display: none; }

        .card-foot {
            text-align: center;
            color: var(--mid-gray);
            font-size: 0.73rem;
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid rgba(0,0,0,0.06);
        }

        /* ── RESPONSIVE ───────────────────────── */
        @media (max-width: 900px) {
            .left { display: none; }
            .right {
                background: linear-gradient(145deg, #003a54 0%, var(--dark-blue) 35%, var(--blue) 70%, #4a7a28 100%);
            }
            .card { background: rgba(255,255,255,0.93); }
        }
        @media (max-width: 480px) {
            .card { padding: 38px 28px; }
        }
    </style>
</head>
<body>

<div class="wrapper">

    {{-- LEFT: Branding panel --}}
    <div class="left">
        <canvas id="leftCanvas"></canvas>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>

        <div class="left-inner">
            <div class="wordmark">
                <img src="{{ asset('assets/img/Ecobank_Logo.png') }}"
                     alt="EcoBank"
                     style="height:38px; filter:brightness(0) invert(1); opacity:0.92;">
            </div>

            <h1>
                Welcome to<br>
                <span class="accent">Finance</span><br>
                Dashboard
            </h1>

            <p class="tagline">
                A unified analytics platform powering<br>
                data-driven decisions across the group.
            </p>

            <div class="features">
                <div class="feat">
                    <div class="feat-icon"><i class="fas fa-users"></i></div>
                    <div class="feat-body">
                        <strong>Customer Profitability</strong>
                        <span>Segment and individual-level revenue analytics</span>
                    </div>
                </div>
                <div class="feat">
                    <div class="feat-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="feat-body">
                        <strong>Revenue Intelligence</strong>
                        <span>Multi-dimensional breakdown by RM and product</span>
                    </div>
                </div>
                <div class="feat">
                    <div class="feat-icon"><i class="fas fa-shield-halved"></i></div>
                    <div class="feat-body">
                        <strong>Secure &amp; Role-Based</strong>
                        <span>Enterprise-grade authentication and access control</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="left-foot">
            &copy; {{ date('Y') }} Ecobank Group &mdash; Finance Intelligence Platform
        </div>
    </div>

    {{-- RIGHT: Login form --}}
    <div class="right">
        <div class="card">

            <div class="card-dots">
                <div class="dot dot-b"></div>
                <div class="dot dot-g"></div>
                <div class="dot dot-d"></div>
            </div>

            <h2>Sign In</h2>
            <p class="sub">Access your Finance Dashboard</p>

            @if(session('session_timeout'))
                <div class="alert alert-warn">
                    <i class="fas fa-clock"></i>
                    Your session expired. Please sign in again.
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-ok">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-err">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                <div class="fgroup">
                    <label for="email">Username / Email</label>
                    <div class="input-wrap">
                        <i class="fas fa-user ico"></i>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="e.g. john.doe or john@ecobank.com"
                            autocomplete="username"
                            autofocus
                        >
                    </div>
                </div>

                <div class="fgroup">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock ico"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pw" id="togglePw" aria-label="Toggle password">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="row-footer">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="btn-label">
                        <i class="fas fa-arrow-right-to-bracket"></i>&nbsp; Sign In
                    </span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="card-foot">
                EcoBank Finance Intelligence Platform &middot; {{ date('Y') }}
            </div>
        </div>
    </div>

</div>

<script>
    // ── Reactive particle background ──────────────────────────────────────
    (function particles(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const parent = canvas.parentElement;
        let pts = [], mouse = { x: null, y: null }, raf;

        function resize() {
            canvas.width  = parent.offsetWidth;
            canvas.height = parent.offsetHeight;
        }

        function spawn() {
            pts = [];
            const n = Math.min(90, Math.floor(canvas.width * canvas.height / 9000));
            for (let i = 0; i < n; i++) {
                pts.push({
                    x:  Math.random() * canvas.width,
                    y:  Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.55,
                    vy: (Math.random() - 0.5) * 0.55,
                    r:  Math.random() * 1.6 + 0.6,
                    a:  Math.random() * 0.35 + 0.1
                });
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (const p of pts) {
                if (mouse.x !== null) {
                    const dx = p.x - mouse.x, dy = p.y - mouse.y;
                    const d  = Math.hypot(dx, dy);
                    if (d < 130 && d > 0) {
                        const f = (130 - d) / 130 * 0.28;
                        p.vx += dx / d * f;
                        p.vy += dy / d * f;
                    }
                }
                const spd = Math.hypot(p.vx, p.vy);
                if (spd > 2) { p.vx = p.vx / spd * 2; p.vy = p.vy / spd * 2; }
                p.vx *= 0.994; p.vy *= 0.994;
                p.x  += p.vx;  p.y  += p.vy;
                if (p.x < 0) p.x = canvas.width;
                if (p.x > canvas.width)  p.x = 0;
                if (p.y < 0) p.y = canvas.height;
                if (p.y > canvas.height) p.y = 0;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255,255,255,${p.a})`;
                ctx.fill();
            }

            for (let i = 0; i < pts.length; i++) {
                for (let j = i + 1; j < pts.length; j++) {
                    const dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y;
                    const d  = Math.hypot(dx, dy);
                    if (d < 115) {
                        ctx.beginPath();
                        ctx.moveTo(pts[i].x, pts[i].y);
                        ctx.lineTo(pts[j].x, pts[j].y);
                        ctx.strokeStyle = `rgba(255,255,255,${(1 - d / 115) * 0.18})`;
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }
            raf = requestAnimationFrame(draw);
        }

        parent.addEventListener('mousemove', e => {
            const r = canvas.getBoundingClientRect();
            mouse.x = e.clientX - r.left;
            mouse.y = e.clientY - r.top;
        });
        parent.addEventListener('mouseleave', () => { mouse.x = null; mouse.y = null; });

        window.addEventListener('resize', () => {
            cancelAnimationFrame(raf);
            resize(); spawn(); draw();
        });

        resize(); spawn(); draw();
    })('leftCanvas');

    // Password visibility toggle
    document.getElementById('togglePw').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eyeIcon');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !show);
        icon.classList.toggle('fa-eye-slash', show);
    });

    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });
</script>

</body>
</html>
