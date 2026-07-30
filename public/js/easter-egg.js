/**
 * 🥚 Easter Egg — "Moses was here"
 * ─────────────────────────────────────────────────────────────
 * HOW TO TRIGGER (three ways):
 *   1. Type  m-o-s-e-s  anywhere on the page (no input focused)
 *   2. Click the page-header icon  5 times  in a row
 *   3. Konami code: ↑ ↑ ↓ ↓ ← → ← → B A
 * ─────────────────────────────────────────────────────────────
 * Drop this file at the bottom of your layout, or @push it:
 *   @push('scripts')
 *       <script src="{{ asset('js/easter-egg.js') }}"></script>
 *   @endpush
 * ─────────────────────────────────────────────────────────────
 */

(function () {
    "use strict";

    // ── Console signature (always visible on DevTools open) ───────────────────
    const art = [
        "%c",
        "███╗   ███╗ ██████╗ ███████╗███████╗███████╗",
        "████╗ ████║██╔═══██╗██╔════╝██╔════╝██╔════╝",
        "██╔████╔██║██║   ██║███████╗█████╗  ███████╗",
        "██║╚██╔╝██║██║   ██║╚════██║██╔══╝  ╚════██║",
        "██║ ╚═╝ ██║╚██████╔╝███████║███████╗███████║",
        "╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚══════╝╚══════╝",
        "",
        "  👋  Moses was here.",
        "  🛠️  System built by Moses.",
        "  📅  " + new Date().getFullYear(),
        "",
    ].join("\n");

    console.log(
        art,
        "color:#0082BB; font-family:monospace; font-size:11px; line-height:1.4",
    );

    // ── Shared trigger function ────────────────────────────────────────────────
    let triggered = false;

    function launch() {
        if (triggered) return; // only show once per session
        triggered = true;
        showOverlay();
    }

    // ── Overlay ────────────────────────────────────────────────────────────────
    function showOverlay() {
        // Inject keyframe + styles once
        if (!document.getElementById("egg-style")) {
            const s = document.createElement("style");
            s.id = "egg-style";
            s.textContent = `
                #egg-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(0,0,0,.55);
                    backdrop-filter: blur(6px);
                    animation: eggFadeIn .35s ease-out;
                }
                @keyframes eggFadeIn {
                    from { opacity:0; }
                    to   { opacity:1; }
                }
                #egg-box {
                    background: #fff;
                    border-radius: 20px;
                    padding: 48px 52px 40px;
                    text-align: center;
                    max-width: 420px;
                    width: 90%;
                    box-shadow: 0 32px 80px rgba(0,0,0,.25);
                    animation: eggPop .4s cubic-bezier(.34,1.56,.64,1);
                    font-family: 'DM Sans', sans-serif;
                    position: relative;
                }
                @keyframes eggPop {
                    from { opacity:0; transform:scale(.7) translateY(30px); }
                    to   { opacity:1; transform:scale(1) translateY(0); }
                }
                #egg-emoji {
                    font-size: 3.8rem;
                    display: block;
                    margin-bottom: 16px;
                    animation: eggWave 1.6s ease-in-out infinite;
                    transform-origin: 70% 80%;
                }
                @keyframes eggWave {
                    0%,100% { transform: rotate(0deg);   }
                    15%     { transform: rotate(14deg);  }
                    30%     { transform: rotate(-10deg); }
                    45%     { transform: rotate(10deg);  }
                    60%     { transform: rotate(-6deg);  }
                    75%     { transform: rotate(4deg);   }
                }
                #egg-title {
                    font-size: 1.55rem;
                    font-weight: 800;
                    color: #005B82;
                    margin: 0 0 8px;
                    letter-spacing: -.3px;
                }
                #egg-sub {
                    font-size: .95rem;
                    color: #979797;
                    margin: 0 0 28px;
                    line-height: 1.55;
                }
                #egg-sub strong { color: #0082BB; }
                #egg-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: linear-gradient(135deg,#0082BB,#005B82);
                    color: #fff;
                    font-size: .8rem;
                    font-weight: 700;
                    letter-spacing: .4px;
                    padding: 6px 18px;
                    border-radius: 30px;
                    margin-bottom: 28px;
                    text-transform: uppercase;
                }
                #egg-close {
                    background: #f0f8ff;
                    color: #0082BB;
                    border: 1.5px solid #0082BB;
                    border-radius: 10px;
                    padding: 10px 32px;
                    font-size: .9rem;
                    font-weight: 700;
                    font-family: 'DM Sans', sans-serif;
                    cursor: pointer;
                    transition: background .2s, transform .15s;
                    display: block;
                    width: 100%;
                }
                #egg-close:hover {
                    background: #0082BB;
                    color: #fff;
                    transform: translateY(-1px);
                }
                #egg-hint {
                    margin-top: 16px;
                    font-size: .72rem;
                    color: #ccc;
                }
            `;
            document.head.appendChild(s);
        }

        const overlay = document.createElement("div");
        overlay.id = "egg-overlay";
        overlay.setAttribute("role", "dialog");
        overlay.setAttribute("aria-modal", "true");
        overlay.setAttribute("aria-label", "Easter egg");
        overlay.innerHTML = `
            <div id="egg-box">
                <span id="egg-emoji">👋</span>
                <h2 id="egg-title">Moses was here.</h2>
                <p id="egg-sub">
                    This system was crafted with care by <strong>Moses</strong>.<br>
                    Every pixel, every query — built to last.
                </p>
                <div id="egg-badge">
                    <span>🛠️</span> System built by Moses
                </div>
                <button id="egg-close">Got it, thanks Moses!</button>
                <p id="egg-hint">Press Esc or click outside to close</p>
            </div>
        `;

        document.body.appendChild(overlay);

        // Close handlers
        function close() {
            overlay.style.animation = "eggFadeIn .25s ease-in reverse forwards";
            setTimeout(() => overlay.remove(), 260);
            triggered = false; // allow re-triggering after close
        }

        document.getElementById("egg-close").addEventListener("click", close);
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) close();
        });
        document.addEventListener("keydown", function onEsc(e) {
            if (e.key === "Escape") {
                close();
                document.removeEventListener("keydown", onEsc);
            }
        });
    }

    // ── Trigger 1: Type "moses" anywhere (not in an input/textarea) ───────────
    let keyBuffer = "";
    const SECRET = "moses";

    document.addEventListener("keydown", function (e) {
        // Ignore when user is typing in a form field
        const tag = document.activeElement?.tagName?.toLowerCase();
        if (tag === "input" || tag === "textarea" || tag === "select") return;

        keyBuffer += e.key.toLowerCase();
        if (keyBuffer.length > SECRET.length) {
            keyBuffer = keyBuffer.slice(-SECRET.length);
        }
        if (keyBuffer === SECRET) {
            keyBuffer = "";
            launch();
        }
    });

    // ── Trigger 2: Click the header icon 5 times ──────────────────────────────
    let iconClicks = 0;
    let iconClickTimer = null;

    document.addEventListener("click", function (e) {
        // Target the pg-header-icon (or anything inside it)
        if (e.target.closest(".pg-header-icon")) {
            iconClicks++;
            clearTimeout(iconClickTimer);

            if (iconClicks >= 5) {
                iconClicks = 0;
                launch();
            } else {
                // Reset if user stops clicking for 2 seconds
                iconClickTimer = setTimeout(() => {
                    iconClicks = 0;
                }, 2000);
            }
        }
    });

    // ── Trigger 3: Konami code ─────────────────────────────────────────────────
    const KONAMI = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // ↑↑↓↓←→←→BA
    let konamiIdx = 0;

    document.addEventListener("keydown", function (e) {
        if (e.keyCode === KONAMI[konamiIdx]) {
            konamiIdx++;
            if (konamiIdx === KONAMI.length) {
                konamiIdx = 0;
                launch();
            }
        } else {
            konamiIdx = 0;
        }
    });
})();
