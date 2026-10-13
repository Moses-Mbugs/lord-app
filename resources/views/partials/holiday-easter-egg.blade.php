{{-- =========================================================
     HOLIDAY EASTER EGG
     Include just before </body>. Only renders while a holiday from App\Support\Holidays is active
     (HOLIDAY_EGGS_ALL=true activates all anywhere; locally ?holiday=<key> previews one). Typing a holiday's
     secret word anywhere outside a form field sets off an 8-second themed celebration.
========================================================== --}}
@php
    $holidayThemes = \App\Support\Holidays::active(request());
@endphp

@if ($holidayThemes)
    <style>
        .holiday-layer {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            pointer-events: none;
            overflow: hidden;
            box-shadow: inset 0 0 140px var(--holiday-glow);
            animation: holidayGlow 8s ease forwards;
        }

        .holiday-flyer {
            position: absolute;
            left: -70px;
            line-height: 1;
            animation: holidayFly var(--dur, 4s) var(--delay, 0s) ease-in-out forwards;
            opacity: 0;
        }

        .holiday-riser {
            position: absolute;
            bottom: -100px;
            right: 12%;
            font-size: 66px;
            line-height: 1;
            filter: drop-shadow(0 0 18px rgba(255, 255, 255, .6));
            animation: holidayRise 6s .6s ease-in-out forwards;
        }

        .holiday-toast {
            position: fixed;
            left: 50%;
            bottom: 28px;
            z-index: 2147483001;
            max-width: calc(100vw - 32px);
            padding: 11px 20px;
            border-radius: 999px;
            background: var(--holiday-dark);
            color: var(--holiday-accent);
            font: 700 14px/1.3 system-ui, -apple-system, 'Segoe UI', sans-serif;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
            transform: translateX(-50%);
            animation: holidayToast .4s ease both;
        }

        /* Loans layout hero banner picks up the holiday colours. */
        .loan-hero.is-holiday {
            --hero-base: linear-gradient(118deg, var(--holiday-g1) 0%, var(--holiday-g2) 46%, var(--holiday-g3) 100%);
            box-shadow: 0 12px 40px var(--holiday-glow);
        }

        .loan-hero.is-holiday .hero-eyebrow {
            color: var(--holiday-accent);
        }

        @keyframes holidayGlow {
            0% { opacity: 0; }
            10%, 85% { opacity: 1; }
            100% { opacity: 0; }
        }

        @keyframes holidayFly {
            0% { transform: translate(0, 0); opacity: 0; }
            10% { opacity: 1; }
            25% { transform: translate(28vw, -40px); }
            50% { transform: translate(55vw, 30px); }
            75% { transform: translate(82vw, -30px); }
            100% { transform: translate(112vw, 10px); opacity: 0; }
        }

        @keyframes holidayRise {
            0% { transform: translateY(0); opacity: 0; }
            15% { opacity: .95; }
            50% { transform: translate(-40px, -55vh); }
            85% { opacity: .9; }
            100% { transform: translate(20px, -115vh); opacity: 0; }
        }

        @keyframes holidayToast {
            from { opacity: 0; transform: translate(-50%, 10px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .holiday-flyer,
            .holiday-riser {
                display: none;
            }

            .holiday-layer {
                animation: none;
            }
        }
    </style>

    <script>
        (function() {
            var themes = @json(array_values($holidayThemes));
            var maxLength = Math.max.apply(null, themes.map(function(t) { return t.word.length; }));
            var buffer = '';
            var active = false;

            if (themes.length === 1) {
                console.log('%c' + themes[0].suffix + ' Psst… happy ' + themes[0].name + '! Try typing "' +
                    themes[0].word + '" on this page.', 'color:' + themes[0].gradient[2] + ';font-weight:bold;');
            } else {
                console.log('%c🎉 All holiday easter eggs are active. Words: ' +
                    themes.map(function(t) { return t.word + ' ' + t.suffix; }).join(', '), 'font-weight:bold;');
            }

            document.addEventListener('keydown', function(event) {
                var target = event.target;
                var tag = (target.tagName || '').toLowerCase();

                // Never hijack typing in forms.
                if (tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable) {
                    return;
                }

                if (!event.key || event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                buffer = (buffer + event.key.toLowerCase()).slice(-maxLength);

                for (var t = 0; t < themes.length; t++) {
                    var word = themes[t].word.toLowerCase();

                    if (buffer.slice(-word.length) === word) {
                        buffer = '';
                        celebrate(themes[t]);
                        break;
                    }
                }
            });

            function celebrate(theme) {
                if (active) return;
                active = true;

                var root = document.documentElement;
                root.style.setProperty('--holiday-g1', theme.gradient[0]);
                root.style.setProperty('--holiday-g2', theme.gradient[1]);
                root.style.setProperty('--holiday-g3', theme.gradient[2]);
                root.style.setProperty('--holiday-dark', theme.gradient[0]);
                root.style.setProperty('--holiday-glow', theme.glow);
                root.style.setProperty('--holiday-accent', theme.accent);

                var heroes = document.querySelectorAll('.loan-hero');
                var titles = document.querySelectorAll('.hero-title');
                var original = [];

                heroes.forEach(function(hero) {
                    hero.classList.add('is-holiday');
                });

                titles.forEach(function(title, i) {
                    var text = title.textContent.trim();
                    original[i] = title.textContent;

                    if (theme.twist) {
                        text = text.replace(theme.twist[0], theme.twist[1]);
                    }

                    title.textContent = text + ' ' + theme.suffix;
                });

                var layer = document.createElement('div');
                layer.className = 'holiday-layer';
                layer.setAttribute('aria-hidden', 'true');

                for (var n = 0; n < 10; n++) {
                    var flyer = document.createElement('span');
                    flyer.className = 'holiday-flyer';
                    flyer.textContent = theme.flyers[n % theme.flyers.length];
                    flyer.style.top = (8 + Math.random() * 62) + 'vh';
                    flyer.style.fontSize = (20 + Math.random() * 18) + 'px';
                    flyer.style.setProperty('--delay', (Math.random() * 2.5).toFixed(2) + 's');
                    flyer.style.setProperty('--dur', (3 + Math.random() * 2.5).toFixed(2) + 's');
                    layer.appendChild(flyer);
                }

                var riser = document.createElement('span');
                riser.className = 'holiday-riser';
                riser.textContent = theme.riser;
                layer.appendChild(riser);

                var toast = document.createElement('div');
                toast.className = 'holiday-toast';
                toast.setAttribute('role', 'status');
                toast.textContent = theme.suffix + ' ' + theme.message;

                document.body.appendChild(layer);
                document.body.appendChild(toast);

                setTimeout(function() {
                    heroes.forEach(function(hero) {
                        hero.classList.remove('is-holiday');
                    });

                    titles.forEach(function(title, i) {
                        title.textContent = original[i];
                    });

                    layer.remove();
                    toast.remove();
                    active = false;
                }, 8000);
            }
        })();
    </script>
@endif
