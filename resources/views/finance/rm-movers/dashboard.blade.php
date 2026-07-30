@extends('layouts.finance.template')

@php
    $resolvedDefaultStart = $defaultStart ?? ($latestDbDate ?? now()->toDateString());
    $resolvedDefaultEnd = $defaultEnd ?? ($latestDbDate ?? now()->toDateString());
@endphp

@section('title', 'RM Movers Dashboard')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-dark-blue: #005B82;
            --eco-green: #BED600;
            --eco-dark-green: #669438;
            --eco-gray: #464646;
            --eco-light-gray: #EDEDED;
            --eco-mid-gray: #979797;
            --eco-bg: #F5F8FB;
            --eco-white: #ffffff;

            --success: #15803d;
            --success-soft: rgba(21, 128, 61, 0.10);
            --danger: #dc2626;
            --danger-soft: rgba(220, 38, 38, 0.10);
            --warning: #b7791f;
            --warning-soft: rgba(183, 121, 31, 0.12);

            --card-shadow: 0 8px 28px rgba(0, 0, 0, 0.06);
            --card-shadow-strong: 0 18px 50px rgba(0, 0, 0, 0.16);
            --border-soft: 1px solid rgba(0, 91, 130, 0.08);
            --radius-lg: 18px;
            --radius-md: 14px;
            --radius-sm: 9px;
        }

        body {
            background: var(--eco-bg);
        }

        .rm-dashboard {
            padding: 14px 18px 24px;
            position: relative;
        }

        /* =========================================================
               GLOBAL LOADER
            ========================================================= */
        .dashboard-loader {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2500;
            background: rgba(245, 248, 251, 0.76);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .dashboard-loader.active {
            display: flex;
        }

        .loader-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px 26px;
            box-shadow: var(--card-shadow-strong);
            border: var(--border-soft);
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 300px;
        }

        .loader-text-main {
            font-size: 0.9rem;
            font-weight: 900;
            color: var(--eco-dark-blue);
            margin-bottom: 2px;
        }

        .loader-text-sub {
            font-size: 0.75rem;
            color: var(--eco-mid-gray);
            font-weight: 600;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 91, 130, 0.16);
            border-top-color: var(--eco-dark-blue);
            border-radius: 50%;
            animation: spin .65s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .skeleton {
            position: relative;
            overflow: hidden;
            background: #edf3f8;
            border-radius: 8px;
        }

        .skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.62), transparent);
            animation: shimmer 1.15s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        .sk-line {
            height: 12px;
            width: 100%;
        }

        .sk-line.sm {
            height: 10px;
        }

        .sk-line.lg {
            height: 18px;
        }

        /* =========================================================
               ANIMATED HERO
            ========================================================= */
        .dashboard-hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-lg);
            padding: 22px 22px;
            margin-bottom: 14px;
            background:
                radial-gradient(circle at 88% 12%, rgba(190, 214, 0, 0.30), transparent 15rem),
                radial-gradient(circle at 8% 92%, rgba(190, 214, 0, 0.14), transparent 16rem),
                linear-gradient(135deg, var(--eco-dark-blue) 0%, var(--eco-blue) 58%, #0fa3d8 100%);
            box-shadow: var(--card-shadow);
            min-height: 132px;
            isolation: isolate;
        }

        .dashboard-hero::before {
            content: "";
            position: absolute;
            inset: -80px;
            background:
                linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.10), transparent);
            transform: rotate(-8deg);
            animation: heroSweep 7s ease-in-out infinite;
            z-index: 0;
        }

        .dashboard-hero::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--eco-blue), var(--eco-green), #0fa3d8);
            z-index: 1;
        }

        @keyframes heroSweep {
            0% {
                transform: translateX(-55%) rotate(-8deg);
            }

            50% {
                transform: translateX(35%) rotate(-8deg);
            }

            100% {
                transform: translateX(95%) rotate(-8deg);
            }
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .hero-particle {
            position: absolute;
            left: var(--x);
            top: var(--y);
            width: var(--s);
            height: var(--s);
            border-radius: 50%;
            background: rgba(255, 255, 255, var(--o));
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.42);
            animation: particleDrift var(--d) ease-in-out infinite;
            animation-delay: var(--delay);
            opacity: 0.8;
        }

        @keyframes particleDrift {

            0%,
            100% {
                transform: translate3d(0, 0, 0) scale(0.75);
                opacity: 0.35;
            }

            50% {
                transform: translate3d(var(--tx), var(--ty), 0) scale(1.08);
                opacity: 1;
            }
        }

        .float-tag {
            position: absolute;
            color: rgba(255, 255, 255, 0.88);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            font-family: 'DM Mono', monospace;
            font-size: 0.66rem;
            font-weight: 700;
            padding: 5px 9px;
            animation: floatTag 7s ease-in-out infinite;
        }

        .float-tag.t1 {
            right: 170px;
            top: 22px;
            animation-delay: 0s;
        }

        .float-tag.t2 {
            right: 38px;
            bottom: 28px;
            animation-delay: -2.8s;
        }

        .float-tag.t3 {
            right: 315px;
            top: 76px;
            animation-delay: -4.2s;
        }

        @keyframes floatTag {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-9px);
            }
        }

        .hero-bars {
            display: flex;
            align-items: end;
            gap: 5px;
            height: 40px;
            flex-shrink: 0;
            opacity: 0.5;
        }

        .hero-bars span {
            width: 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            animation: barPulse 1.8s ease-in-out infinite;
        }

        .hero-bars span:nth-child(1) {
            height: 18px;
            animation-delay: 0s;
        }

        .hero-bars span:nth-child(2) {
            height: 34px;
            animation-delay: .15s;
        }

        .hero-bars span:nth-child(3) {
            height: 24px;
            animation-delay: .3s;
        }

        .hero-bars span:nth-child(4) {
            height: 42px;
            animation-delay: .45s;
        }

        .hero-bars span:nth-child(5) {
            height: 28px;
            animation-delay: .6s;
        }

        @keyframes barPulse {

            0%,
            100% {
                transform: scaleY(0.72);
                opacity: 0.55;
            }

            50% {
                transform: scaleY(1);
                opacity: 1;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .hero-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }

        .hero-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .hero-title {
            margin: 0;
            color: #fff;
            font-size: 1.28rem;
            font-weight: 950;
            line-height: 1.15;
        }

        .hero-subtitle {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.82rem;
            font-weight: 600;
            max-width: 680px;
            line-height: 1.45;
        }

        .hero-metric-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 9px;
            background: rgba(190, 214, 0, 0.20);
            border: 1px solid rgba(190, 214, 0, 0.38);
            color: #eaffb0;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 999px;
            width: fit-content;
        }

        .hero-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .period-pill {
            color: rgba(255, 255, 255, 0.94);
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 0.78rem;
            font-weight: 800;
            border-radius: 999px;
            padding: 7px 12px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .hero-mini-note {
            color: rgba(255, 255, 255, 0.68);
            font-size: 0.7rem;
            font-weight: 700;
        }

        @media(max-width: 780px) {
            .hero-actions {
                align-items: flex-start;
            }

            .hero-bars,
            .float-tag {
                display: none;
            }
        }

        /* =========================================================
               BUTTONS / INPUTS
            ========================================================= */
        .btn-eco {
            background: var(--eco-dark-blue);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 8px 14px;
            font-size: 0.8rem;
            font-weight: 900;
            cursor: pointer;
            transition: all .18s ease;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            white-space: nowrap;
        }

        .btn-eco:hover {
            background: #004b6e;
            transform: translateY(-1px);
        }

        .btn-eco:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .btn-eco-green {
            background: var(--eco-dark-green);
        }

        .btn-eco-green:hover {
            background: #4d7028;
        }

        .btn-eco-light {
            background: #f2f7fb;
            color: var(--eco-dark-blue);
            border: 1px solid rgba(0, 91, 130, 0.12);
        }

        .btn-eco-light:hover {
            background: #e8f3fa;
        }

        .control-input,
        .control-select {
            border: 1px solid #d0dce8;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.82rem;
            color: var(--eco-gray);
            background: #fff;
            min-height: 34px;
            width: 100%;
        }

        .control-input:focus,
        .control-select:focus {
            outline: none;
            border-color: var(--eco-blue);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, 0.12);
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .control-label {
            font-size: 0.72rem;
            font-weight: 900;
            color: var(--eco-dark-blue);
            margin: 0;
            letter-spacing: 0.2px;
        }

        /* =========================================================
               CONTROLS
            ========================================================= */
        .controls-bar {
            background: #fff;
            border-radius: var(--radius-md);
            padding: 12px 14px;
            box-shadow: var(--card-shadow);
            border: var(--border-soft);
            margin-bottom: 14px;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto;
            align-items: end;
            gap: 10px;
        }

        .control-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media(max-width: 1400px) {
            .controls-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .control-actions {
                grid-column: 1 / -1;
            }
        }

        @media(max-width: 860px) {
            .controls-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media(max-width: 540px) {
            .controls-grid {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================================
               PANELS
            ========================================================= */
        .panel {
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--card-shadow);
            border: var(--border-soft);
            overflow: hidden;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: var(--border-soft);
            background: linear-gradient(90deg, rgba(0, 91, 130, 0.04), transparent);
        }

        .panel-title {
            font-size: 0.86rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .panel-subtitle {
            font-size: 0.7rem;
            color: var(--eco-mid-gray);
            font-weight: 700;
            margin-top: 2px;
        }

        .panel-body {
            padding: 14px;
        }

        .panel-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tiny-select,
        .tiny-input {
            font-size: 0.75rem;
            border: 1px solid #d0dce8;
            border-radius: 7px;
            padding: 5px 8px;
            background: #fff;
            color: var(--eco-gray);
        }

        .tiny-input {
            min-width: 180px;
        }

        /* =========================================================
               INFOGRAPHICS
            ========================================================= */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }

        .info-card {
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--card-shadow);
            border: var(--border-soft);
            overflow: hidden;
            min-height: 245px;
        }

        .info-card-header {
            padding: 12px 14px;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: linear-gradient(90deg, rgba(0, 91, 130, 0.04), transparent);
        }

        .info-card-title {
            margin: 0;
            color: var(--eco-dark-blue);
            font-size: 0.84rem;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .info-card-sub {
            margin-top: 2px;
            color: var(--eco-mid-gray);
            font-size: 0.68rem;
            font-weight: 700;
        }

        .info-card-body {
            padding: 12px 14px 14px;
        }

        .mini-metric-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-top: 10px;
        }

        .mini-metric {
            border-radius: 11px;
            background: #f8fbfd;
            border: 1px solid #edf2f7;
            padding: 8px;
            min-width: 0;
        }

        .mini-label {
            font-size: 0.62rem;
            color: var(--eco-mid-gray);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .35px;
            margin-bottom: 3px;
        }

        .mini-value {
            font-family: 'DM Mono', monospace;
            color: var(--eco-dark-blue);
            font-size: 0.9rem;
            font-weight: 950;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mini-value.good {
            color: var(--success);
        }

        .mini-value.bad {
            color: var(--danger);
        }

        .chart-box {
            position: relative;
            height: 155px;
        }

        .chart-box.tall {
            height: 214px;
        }

        .concentration-wrap {
            display: grid;
            gap: 12px;
        }

        .concentration-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .concentration-percent {
            font-family: 'DM Mono', monospace;
            font-size: 1.8rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
            line-height: 1;
        }

        .concentration-copy {
            color: var(--eco-gray);
            font-size: 0.78rem;
            line-height: 1.45;
            font-weight: 700;
        }

        .progress-shell {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #edf2f7;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--eco-blue), var(--eco-green));
            transition: width .45s ease;
        }

        .concentration-list {
            display: grid;
            gap: 7px;
        }

        .concentration-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 1px solid #f0f4f8;
            padding-bottom: 6px;
        }

        .concentration-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .concentration-name {
            font-family: 'DM Mono', monospace;
            font-size: 0.74rem;
            font-weight: 900;
            color: var(--eco-dark-blue);
        }

        .concentration-val {
            font-family: 'DM Mono', monospace;
            font-size: 0.74rem;
            font-weight: 950;
            white-space: nowrap;
        }

        @media(max-width: 1100px) {
            .analytics-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media(max-width: 620px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        @media(max-width: 980px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================================
               TABLES
            ========================================================= */
        .table-scroll {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 560px;
        }

        .rm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .rm-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--eco-dark-blue);
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }


        .rm-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f4f8;
            color: var(--eco-gray);
            vertical-align: middle;
        }

        .rm-table tr:hover td {
            background: #f0f7fb;
        }

        .rm-table .num {
            text-align: right;
            font-family: 'DM Mono', monospace;
            white-space: nowrap;
        }

        .rm-table .pos {
            color: var(--success);
            font-weight: 900;
        }

        .rm-table .neg {
            color: var(--danger);
            font-weight: 900;
        }

        .rm-table .flat {
            color: var(--eco-mid-gray);
            font-weight: 900;
        }

        .rm-person {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .rm-code-main {
            font-family: 'DM Mono', monospace;
            font-weight: 950;
            color: var(--eco-dark-blue);
            font-size: 0.78rem;
        }

        .rm-name {
            color: var(--eco-mid-gray);
            font-size: 0.7rem;
            font-weight: 700;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rm-name.pending {
            font-style: italic;
            opacity: 0.78;
        }

        .badge-rm {
            display: inline-block;
            background: rgba(0, 91, 130, 0.1);
            color: var(--eco-dark-blue);
            border-radius: 7px;
            padding: 3px 8px;
            font-size: 0.72rem;
            font-weight: 950;
            font-family: 'DM Mono', monospace;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.68rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .status-badge.gain {
            background: var(--success-soft);
            color: var(--success);
        }

        .status-badge.loss {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .status-badge.flat {
            background: rgba(151, 151, 151, 0.12);
            color: var(--eco-gray);
        }

        .row-link {
            cursor: pointer;
        }

        .row-link:hover td {
            background: #e8f3f9 !important;
        }

        .loading-row td {
            text-align: center;
            padding: 24px;
            color: var(--eco-mid-gray);
        }

        .empty-state {
            padding: 30px 18px;
            text-align: center;
            color: var(--eco-mid-gray);
        }

        .empty-state i {
            font-size: 1.45rem;
            color: rgba(0, 91, 130, 0.28);
            margin-bottom: 8px;
        }

        .empty-title {
            font-size: 0.9rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
            margin-bottom: 4px;
        }

        .empty-sub {
            font-size: 0.75rem;
            line-height: 1.5;
            font-weight: 600;
        }

        /* =========================================================
               MODALS
            ========================================================= */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.46);
            z-index: 1800;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 18px;
            width: min(940px, 96vw);
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.22);
            display: flex;
            flex-direction: column;
        }

        .modal-box.small {
            width: min(460px, 95vw);
        }

        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf2f7;
            background: linear-gradient(90deg, rgba(0, 91, 130, 0.05), transparent);
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
            margin: 0;
        }

        .modal-subtitle {
            margin-top: 3px;
            color: var(--eco-mid-gray);
            font-size: 0.74rem;
            font-weight: 700;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.15rem;
            cursor: pointer;
            color: var(--eco-mid-gray);
            line-height: 1;
            width: 32px;
            height: 32px;
            border-radius: 10px;
        }

        .modal-close:hover {
            background: #f2f4f7;
            color: var(--eco-dark-blue);
        }

        .modal-body {
            padding: 16px 20px 20px;
            overflow-y: auto;
        }

        .drilldown-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }

        .drill-card {
            border: 1px solid #e7eef5;
            background: #fbfdff;
            border-radius: 13px;
            padding: 11px 12px;
        }

        .drill-label {
            font-size: 0.64rem;
            font-weight: 950;
            color: var(--eco-mid-gray);
            letter-spacing: .45px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .drill-value {
            font-family: 'DM Mono', monospace;
            color: var(--eco-dark-blue);
            font-size: 0.92rem;
            font-weight: 950;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .drill-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 13px;
            background: #f7fafc;
            border: 1px solid #edf2f7;
        }

        .drill-toolbar-left,
        .drill-toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .drill-note {
            color: var(--eco-mid-gray);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .drill-table-wrap {
            max-height: 390px;
            overflow: auto;
            border-radius: 12px;
            border: 1px solid #edf2f7;
        }

        .drill-table-wrap .rm-table th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .modal-loader-box {
            padding: 18px 0 8px;
        }

        .modal-loader-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }

        .modal-loader-card {
            border: 1px solid #edf2f7;
            border-radius: 13px;
            padding: 12px;
        }

        .modal-loader-table {
            border: 1px solid #edf2f7;
            border-radius: 13px;
            padding: 12px;
            display: grid;
            gap: 9px;
        }

        @media(max-width: 760px) {
            .modal-overlay {
                padding: 0;
            }

            .modal-box {
                width: 100vw;
                height: 100vh;
                max-height: 100vh;
                border-radius: 0;
            }

            .drilldown-summary,
            .modal-loader-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 430px) {

            .drilldown-summary,
            .modal-loader-summary {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================================
               BUILD MODAL
            ========================================================= */
        .build-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .build-info {
            background: rgba(0, 91, 130, 0.05);
            border: 1px solid rgba(0, 91, 130, 0.10);
            color: var(--eco-dark-blue);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 0.76rem;
            line-height: 1.45;
            font-weight: 700;
        }

        .build-msg {
            font-size: 0.8rem;
            min-height: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .text-success {
            color: var(--success);
            font-weight: 950;
        }

        .text-danger {
            color: var(--danger);
            font-weight: 900;
        }

        .text-muted {
            color: var(--eco-mid-gray);
        }

        /* =========================================================
               TOP MOVERS CARDS
            ========================================================= */
        .top-mover-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 4px;
            border-bottom: 1px solid #f0f4f8;
            cursor: pointer;
            border-radius: 7px;
            transition: background .12s;
        }

        .top-mover-row:last-child {
            border-bottom: none;
        }

        .top-mover-row:hover {
            background: #f0f7fb;
        }

        .top-mover-rank {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.66rem;
            font-weight: 950;
            flex-shrink: 0;
        }

        .top-mover-rank.gain {
            background: var(--success-soft);
            color: var(--success);
        }

        .top-mover-rank.loss {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .top-mover-info {
            flex: 1;
            min-width: 0;
        }

        .top-mover-code {
            font-family: 'DM Mono', monospace;
            font-size: 0.76rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
        }

        .top-mover-name {
            font-size: 0.65rem;
            color: var(--eco-mid-gray);
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .top-mover-value {
            font-family: 'DM Mono', monospace;
            font-size: 0.76rem;
            font-weight: 950;
            white-space: nowrap;
        }

        /* =========================================================
               DRILLDOWN MOVER SECTION
            ========================================================= */
        .drill-mover-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        @media(max-width: 620px) {
            .drill-mover-section {
                grid-template-columns: 1fr;
            }
        }

        .drill-mover-panel {
            border: 1px solid #edf2f7;
            border-radius: 12px;
            overflow: hidden;
        }

        .drill-mover-header {
            padding: 8px 12px;
            font-size: 0.72rem;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .drill-mover-header.gain {
            background: var(--success-soft);
            color: var(--success);
        }

        .drill-mover-header.loss {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .drill-mover-rows {
            padding: 6px 12px 8px;
        }

        .drill-mover-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f5f8fa;
            gap: 8px;
        }

        .drill-mover-item:last-child {
            border-bottom: none;
        }

        .drill-mover-cif-wrap {
            min-width: 0;
            flex: 1;
        }

        .drill-mover-cif {
            font-family: 'DM Mono', monospace;
            font-weight: 950;
            color: var(--eco-dark-blue);
            font-size: 0.7rem;
        }

        .drill-mover-cname {
            color: var(--eco-mid-gray);
            font-weight: 700;
            font-size: 0.64rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .drill-mover-val {
            font-family: 'DM Mono', monospace;
            font-weight: 950;
            white-space: nowrap;
            font-size: 0.74rem;
        }

        /* =========================================================
               SINGLE RM PROFILE SECTION
            ========================================================= */
        .rm-period-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
        }

        .rm-period-card {
            border: 1px solid #e7eef5;
            border-radius: 13px;
            padding: 12px 14px;
            background: #fbfdff;
        }

        .rm-period-label {
            font-size: 0.62rem;
            font-weight: 950;
            color: var(--eco-mid-gray);
            letter-spacing: .45px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .rm-period-dates {
            font-size: 0.67rem;
            color: var(--eco-mid-gray);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .rm-period-movement {
            font-family: 'DM Mono', monospace;
            font-size: 1.08rem;
            font-weight: 950;
            margin-bottom: 3px;
        }

        .rm-period-meta {
            font-size: 0.68rem;
            color: var(--eco-mid-gray);
            font-weight: 700;
            line-height: 1.4;
        }

        .rm-period-na {
            font-size: 0.82rem;
            font-weight: 950;
            color: var(--eco-mid-gray);
            margin-top: 4px;
        }

        /* =========================================================
               TABLE SUMMARY FOOTER
            ========================================================= */
        .rm-table tfoot td {
            background: #eef5f9;
            border-top: 2px solid rgba(0, 91, 130, 0.15);
            padding: 9px 10px;
            font-size: 0.78rem;
            font-weight: 950;
            color: var(--eco-dark-blue);
            position: sticky;
            bottom: 0;
            z-index: 1;
        }

        .rm-table tfoot .sum-label {
            color: var(--eco-mid-gray);
            font-size: 0.7rem;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        /* =========================================================
               TABLE TABS
            ========================================================= */
        .tab-bar {
            display: flex;
            gap: 3px;
            background: #f0f4f8;
            border-radius: 10px;
            padding: 3px;
        }

        .tab-btn {
            border: none;
            background: transparent;
            border-radius: 7px;
            padding: 5px 13px;
            font-size: 0.72rem;
            font-weight: 900;
            cursor: pointer;
            color: var(--eco-mid-gray);
            transition: all .15s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .tab-btn:hover {
            background: rgba(0, 91, 130, 0.08);
            color: var(--eco-dark-blue);
        }

        .tab-btn.active {
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.10);
            color: var(--eco-dark-blue);
        }

        .tab-btn.active.tab-gain {
            color: var(--success);
        }

        .tab-btn.active.tab-loss {
            color: var(--danger);
        }
    </style>
@endpush

@section('content')

    <div class="dashboard-loader" id="dashboard-loader">
        <div class="loader-card">
            <span class="spinner"></span>
            <div>
                <div class="loader-text-main">Refreshing RM movers</div>
                <div class="loader-text-sub">Preparing movement analytics and RM summaries...</div>
            </div>
        </div>
    </div>

    <div class="rm-dashboard">

        {{-- Animated Hero --}}
        <div class="dashboard-hero">
            <div class="hero-content">
                <div class="hero-title-wrap">
                    <div class="hero-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>

                    <div>
                        <h1 class="hero-title">RM Movers Dashboard</h1>
                        <p class="hero-subtitle">
                            Investigate Relationship Manager balance movements, identify gainers and losers,
                            and drill down into the customers driving each RM movement.
                        </p>
                        <span class="hero-metric-tag">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                            Daily Deposit Movement
                        </span>
                    </div>
                </div>

                <div class="hero-bars" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="hero-actions">
                    <span class="period-pill">
                        <i class="fa-regular fa-calendar-days"></i>
                        <span id="hdr-period">—</span>
                    </span>

                    <span class="hero-mini-note" id="hero-refresh-note">
                        Last refreshed: —
                    </span>
                </div>
            </div>
        </div>

        {{-- Controls --}}
        <div class="controls-bar">
            <div class="controls-grid">
                <div class="control-group">
                    <label class="control-label" for="ctrl-start">Start Date</label>
                    <input type="date" id="ctrl-start" class="control-input" value="{{ $resolvedDefaultStart }}">
                </div>

                <div class="control-group">
                    <label class="control-label" for="ctrl-end">End Date</label>
                    <input type="date" id="ctrl-end" class="control-input" value="{{ $resolvedDefaultEnd }}">
                </div>

                <div class="control-group">
                    <label class="control-label" for="ctrl-segment">Business Segment</label>
                    <select id="ctrl-segment" class="control-select" onchange="onSegmentChange()">
                        <option value="">All Segments</option>
                    </select>
                </div>

                <div class="control-group">
                    <label class="control-label" for="ctrl-subsegment">Sub-segment</label>
                    <select id="ctrl-subsegment" class="control-select" onchange="onSubsegmentChange()">
                        <option value="">All Sub-segments</option>
                    </select>
                </div>

                <div class="control-group">
                    <label class="control-label" for="ctrl-rm">Relationship Manager</label>
                    <select id="ctrl-rm" class="control-select">
                        <option value="">All RMs</option>
                    </select>
                </div>

                <div class="control-actions">
                    <button class="btn-eco" id="btn-apply" onclick="loadAll()">
                        <i class="fa-solid fa-filter"></i>
                        Apply
                    </button>

                    <button class="btn-eco btn-eco-light" onclick="resetFilters()">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        Reset
                    </button>

                    <button class="btn-eco btn-eco-green" onclick="openBuildModal()">
                        <i class="fa-solid fa-hammer"></i>
                        Generate Snapshot
                    </button>
                </div>
            </div>

        </div>

        {{-- Single RM Profile (visible only when one RM is selected) --}}
        <div id="rm-profile-section" style="display:none; margin-bottom:14px;">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-title" id="rm-profile-title">
                            <i class="fa-solid fa-user-tie"></i>
                            RM Profile
                        </span>
                        <div class="panel-subtitle">Period comparison — WTD, MTD, YTD vs selected period</div>
                    </div>
                    <div id="rm-rank-badge"></div>
                </div>
                <div class="panel-body">
                    <div class="rm-period-grid" id="rm-period-cards">
                        <div class="text-muted" style="font-size:0.76rem;font-weight:700;padding:6px 0;">
                            Loading period stats...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Movement Infographics --}}
        <div class="analytics-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <div>
                        <h3 class="info-card-title">
                            <i class="fa-solid fa-chart-pie"></i>
                            RM Movement Split
                        </h3>
                        <div class="info-card-sub">Gainers, losers and flat RMs.</div>
                    </div>
                </div>

                <div class="info-card-body">
                    <div class="chart-box">
                        <canvas id="chart-split"></canvas>
                    </div>

                    <div class="mini-metric-row">
                        <div class="mini-metric">
                            <div class="mini-label">Gainers</div>
                            <div class="mini-value good" id="split-gainers">—</div>
                        </div>

                        <div class="mini-metric">
                            <div class="mini-label">Losers</div>
                            <div class="mini-value bad" id="split-losers">—</div>
                        </div>

                        <div class="mini-metric">
                            <div class="mini-label">Flat</div>
                            <div class="mini-value" id="split-flat">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div>
                        <h3 class="info-card-title">
                            <i class="fa-solid fa-layer-group"></i>
                            Movement Distribution
                        </h3>
                        <div class="info-card-sub">How RM movements are distributed by value band.</div>
                    </div>
                </div>

                <div class="info-card-body">
                    <div class="chart-box tall">
                        <canvas id="chart-distribution"></canvas>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div>
                        <h3 class="info-card-title" style="color:var(--success);">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                            Top 5 Gainers
                        </h3>
                        <div class="info-card-sub">RMs with the largest positive movements.</div>
                    </div>
                </div>
                <div class="info-card-body" id="top-gainers-body">
                    <div class="text-muted" style="font-size:0.76rem;font-weight:700;">Loading...</div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div>
                        <h3 class="info-card-title" style="color:var(--danger);">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                            Top 5 Losers
                        </h3>
                        <div class="info-card-sub">RMs with the largest negative movements.</div>
                    </div>
                </div>
                <div class="info-card-body" id="top-losers-body">
                    <div class="text-muted" style="font-size:0.76rem;font-weight:700;">Loading...</div>
                </div>
            </div>
        </div>

        {{-- Trend and Impact charts removed --}}

        {{-- All RMs Summary --}}
        <div class="panel">
            <div class="panel-header">
                <div>
                    <span class="panel-title">
                        <i class="fa-solid fa-table"></i>
                        All RMs Summary
                    </span>
                    <div class="panel-subtitle">
                        RM names will display automatically once the backend starts returning them.
                    </div>
                </div>

                <div class="panel-toolbar">
                    <div class="tab-bar">
                        <button class="tab-btn active" id="tab-all"   onclick="setTab('')">All</button>
                        <button class="tab-btn tab-gain" id="tab-gain" onclick="setTab('gain')">
                            <i class="fa-solid fa-arrow-up"></i> Gainers
                        </button>
                        <button class="tab-btn tab-loss" id="tab-loss" onclick="setTab('loss')">
                            <i class="fa-solid fa-arrow-down"></i> Losers
                        </button>
                        <button class="tab-btn" id="tab-flat" onclick="setTab('flat')">Flat</button>
                    </div>

                    <input type="text" id="search-rm" class="tiny-input" placeholder="Filter by code or name..."
                        oninput="filterTable(this.value)">

                    <button type="button" class="btn-eco btn-eco-light" onclick="exportTableCsv()"
                        style="padding:6px 10px;min-height:30px;">
                        <i class="fa-solid fa-file-export"></i>
                        Export
                    </button>
                </div>
            </div>

            <div class="table-scroll">
                <table class="rm-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">Rank</th>
                            <th style="width:90px;">RM Code</th>
                            <th>RM Name</th>
                            <th>Segment</th>
                            <th class="num">Opening (KES)</th>
                            <th class="num">Closing (KES)</th>
                            <th class="num">Movement</th>
                            <th class="num">Mvt %</th>
                            <th class="num">CIFs</th>
                            <th>Status</th>
                            <th style="width:30px;"></th>
                        </tr>
                    </thead>

                    <tbody id="rm-table-body">
                        <tr class="loading-row">
                            <td colspan="11">
                                <span class="spinner"></span>
                                Loading RM summaries...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="rm-table-foot"></tfoot>
                </table>
            </div>
        </div>

    </div>

    {{-- Drilldown Modal --}}
    <div class="modal-overlay" id="drilldown-modal" onclick="if(event.target===this) closeModal()">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modal-title">RM Drilldown</h3>
                    <div class="modal-subtitle" id="modal-subtitle">
                        Showing the largest customer movements first.
                    </div>
                </div>

                <button class="modal-close" onclick="closeModal()" title="Close">
                    &#x2715;
                </button>
            </div>

            <div class="modal-body" id="modal-content"></div>
        </div>
    </div>

    {{-- Build Modal --}}
    <div class="modal-overlay" id="build-modal" onclick="if(event.target===this) closeBuildModal()">
        <div class="modal-box small">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Generate RM Movement Snapshot</h3>
                    <div class="modal-subtitle">
                        Rebuild calculated RM movement data for the selected period.
                    </div>
                </div>

                <button class="modal-close" onclick="closeBuildModal()" title="Close">
                    &#x2715;
                </button>
            </div>

            <div class="modal-body">
                <div class="build-form">
                    <div class="build-info">
                        This action recalculates opening, closing and movement balances for the selected period.
                        Use it when data has changed or when the selected period has not yet been prepared.
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="build-start">Start Date</label>
                        <input type="date" id="build-start" class="control-input">
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="build-end">End Date</label>
                        <input type="date" id="build-end" class="control-input">
                    </div>

                    <div id="build-msg" class="build-msg text-muted"></div>

                    <button class="btn-eco btn-eco-green" id="btn-build-now" onclick="runBuild()"
                        style="padding:10px 0;font-size:0.88rem;">
                        <i class="fa-solid fa-hammer"></i>
                        Generate Snapshot Now
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        const BASE = '/finance/rm-movers';

        let splitChart = null;
        let distributionChart = null;

        let allRows = [];
        let filteredRows = [];
        let topMovers = {
            gainers: [],
            losers: [],
        };

        let currentDrilldownRm = null;
        let currentDrilldownRows = [];
        let currentDrilldownLimit = 5;
        let currentDrilldownFilter = 'all';
        let currentDrilldownSearch = '';

        const DRILLDOWN_DEFAULT_LIMIT = 5;
        const DRILLDOWN_MIN_FETCH = 100;   // always fetch at least this many so gainers/losers section has enough data
        const DRILLDOWN_MAX_FETCH_LIMIT = 1000;

        const DEFAULT_START = @json($resolvedDefaultStart);
        const DEFAULT_END = @json($resolvedDefaultEnd);

        const $ = id => document.getElementById(id);

        /* =========================================================
           BASIC HELPERS
        ========================================================= */
        function getStart() {
            return $('ctrl-start').value;
        }

        function getEnd() {
            return $('ctrl-end').value;
        }

        function getRm() {
            return $('ctrl-rm').value;
        }

        function getSegment() {
            return $('ctrl-segment') ? $('ctrl-segment').value : '';
        }

        function getSubsegment() {
            return $('ctrl-subsegment') ? $('ctrl-subsegment').value : '';
        }

        function safeNumber(value) {
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : 0;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function jsString(value) {
            return JSON.stringify(String(value ?? ''));
        }

        function getRmName(row) {
            return row.rm_name ||
                row.rm_full_name ||
                row.relationship_manager_name ||
                row.manager_name ||
                row.name ||
                '';
        }

        function fmt(n) {
            n = safeNumber(n);

            const abs = Math.abs(n);
            const pfx = n < 0 ? '-KES ' : 'KES ';

            if (abs >= 1e12) return pfx + (abs / 1e12).toFixed(2) + 'T';
            if (abs >= 1e9) return pfx + (abs / 1e9).toFixed(2) + 'B';
            if (abs >= 1e6) return pfx + (abs / 1e6).toFixed(2) + 'M';
            if (abs >= 1e3) return pfx + (abs / 1e3).toFixed(2) + 'K';

            return pfx + abs.toFixed(2);
        }

        function fmtFull(n) {
            n = safeNumber(n);

            const sign = n < 0 ? '-' : '';

            return sign + 'KES ' + Math.abs(n).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function movementPercent(opening, movement) {
            opening = safeNumber(opening);
            movement = safeNumber(movement);

            // Opening balance of 0 makes percentage growth mathematically undefined
            // (not 0%) — surface it distinctly instead of masking it as "no change".
            if (opening === 0) {
                return movement !== 0 ? null : 0;
            }

            return (movement / Math.abs(opening)) * 100;
        }

        function fmtPct(n) {
            if (n === null) return 'New';
            n = safeNumber(n);
            return n.toFixed(2) + '%';
        }

        function tableMovementClass(value) {
            const n = safeNumber(value);

            if (n > 0) return 'pos';
            if (n < 0) return 'neg';

            return 'flat';
        }

        function segmentBadge(seg) {
            if (!seg) return `<span style="color:var(--eco-mid-gray);font-style:italic;font-size:0.72rem;">—</span>`;
            const colors = {
                COMMERCIAL: { bg: 'rgba(0,91,130,0.10)',   color: 'var(--eco-dark-blue)' },
                CONSUMER:   { bg: 'rgba(102,148,56,0.12)', color: 'var(--eco-dark-green)' },
                CORPORATE:  { bg: 'rgba(183,121,31,0.12)', color: 'var(--warning)' },
            };
            const c = colors[seg] ?? { bg: 'rgba(151,151,151,0.12)', color: 'var(--eco-gray)' };
            return `<span style="display:inline-block;background:${c.bg};color:${c.color};border-radius:6px;padding:2px 8px;font-size:0.68rem;font-weight:950;white-space:nowrap;">${escapeHtml(seg)}</span>`;
        }

        function statusLabel(value) {
            const n = safeNumber(value);

            if (n > 0) {
                return `<span class="status-badge gain"><i class="fa-solid fa-arrow-up"></i> Gain</span>`;
            }

            if (n < 0) {
                return `<span class="status-badge loss"><i class="fa-solid fa-arrow-down"></i> Loss</span>`;
            }

            return `<span class="status-badge flat"><i class="fa-solid fa-minus"></i> Flat</span>`;
        }

        function formatDateLocal(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');

            return `${y}-${m}-${d}`;
        }

        function showDashboardLoader(show = true) {
            const loader = $('dashboard-loader');
            if (!loader) return;

            loader.classList.toggle('active', !!show);
        }

        function setButtonLoading(buttonId, loading, labelWhenLoading = 'Loading...') {
            const btn = $(buttonId);
            if (!btn) return;

            if (loading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML =
                    `<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> ${labelWhenLoading}`;
            } else {
                btn.disabled = false;

                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            }
        }

        function validateDateRange() {
            const start = getStart();
            const end = getEnd();

            if (!start || !end) {
                alert('Please select both start and end dates.');
                return false;
            }

            if (start > end) {
                alert('Start date cannot be later than end date.');
                return false;
            }

            return true;
        }

        function updateHeroPeriod() {
            $('hdr-period').textContent = `${getStart()} → ${getEnd()}`;
        }

        function updateLastRefreshed() {
            const now = new Date();
            $('hero-refresh-note').textContent = 'Last refreshed: ' + now.toLocaleString();
        }

        /* =========================================================
           DATE PRESETS
        ========================================================= */
        function resetFilters() {
            $('ctrl-start').value = DEFAULT_START;
            $('ctrl-end').value = DEFAULT_END;
            if ($('ctrl-segment'))    $('ctrl-segment').value = '';
            if ($('ctrl-subsegment')) $('ctrl-subsegment').value = '';
            $('ctrl-rm').value = '';
            $('search-rm').value = '';

            loadSubsegmentList();
            loadAll();
        }

        function onSegmentChange() {
            // When segment changes: reset subsegment + RM, reload subsegment list, reload data
            if ($('ctrl-subsegment')) $('ctrl-subsegment').value = '';
            $('ctrl-rm').value = '';
            loadSubsegmentList();
            loadRmList();
            loadAll();
        }

        function onSubsegmentChange() {
            // When subsegment changes: reset RM, reload data
            $('ctrl-rm').value = '';
            loadRmList();
            loadAll();
        }

        /* =========================================================
           MAIN LOAD
        ========================================================= */
        async function loadAll() {
            if (!validateDateRange()) return;

            updateHeroPeriod();
            showDashboardLoader(true);
            setButtonLoading('btn-apply', true, 'Applying...');

            const profileSection = $('rm-profile-section');
            if (profileSection) profileSection.style.display = 'none';

            // Reset tab to All on fresh load
            setTab('');

            try {
                await Promise.all([
                    loadData(),
                    loadRmList(),
                ]);

                renderInfographics();

                if (getRm()) {
                    loadSingleRmStats(); // non-blocking, shows its own loading state
                }

                updateLastRefreshed();

            } catch (error) {
                console.error(error);
                showInfographicError();
            } finally {
                setButtonLoading('btn-apply', false);
                showDashboardLoader(false);
            }
        }

        /* =========================================================
           LOAD RM SUMMARY DATA
        ========================================================= */
        async function loadData() {
            const rm         = getRm();
            const segment    = getSegment();
            const subsegment = getSubsegment();

            const params = new URLSearchParams({
                start: getStart(),
                end: getEnd(),
            });

            if (rm)         params.set('rm_code',    rm);
            if (segment)    params.set('segment',    segment);
            if (subsegment) params.set('subsegment', subsegment);

            $('rm-table-body').innerHTML = `
        <tr class="loading-row">
            <td colspan="11">
                <span class="spinner"></span>
                Loading RM summaries...
            </td>
        </tr>
    `;

            try {
                const res = await fetch(`${BASE}/data?${params}`);

                if (!res.ok) {
                    throw new Error('Failed to load RM summary data.');
                }

                const d = await res.json();

                allRows = Array.isArray(d.rows) ? d.rows : [];

                allRows = allRows
                    .map(r => ({
                        ...r,
                        start_balance: safeNumber(r.start_balance),
                        end_balance: safeNumber(r.end_balance),
                        movement: safeNumber(r.movement),
                        cif_count: safeNumber(r.cif_count),
                    }))
                    .sort((a, b) => Math.abs(b.movement) - Math.abs(a.movement));

                filteredRows = allRows;
                renderTable(filteredRows);

            } catch (error) {
                console.error(error);

                $('rm-table-body').innerHTML = `
            <tr class="loading-row">
                <td colspan="11">
                    <div class="empty-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="empty-title">Could not load RM summaries</div>
                        <div class="empty-sub">Please retry or generate a fresh snapshot for the selected period.</div>
                    </div>
                </td>
            </tr>
        `;

                throw error;
            }
        }

        function renderTableFoot(rows) {
            const foot = $('rm-table-foot');
            if (!foot) return;
            if (!rows.length) { foot.innerHTML = ''; return; }

            const totalOpening  = rows.reduce((s, r) => s + safeNumber(r.start_balance), 0);
            const totalClosing  = rows.reduce((s, r) => s + safeNumber(r.end_balance),   0);
            const totalMovement = rows.reduce((s, r) => s + safeNumber(r.movement),       0);
            const totalCifs     = rows.reduce((s, r) => s + safeNumber(r.cif_count),      0);
            const cls           = tableMovementClass(totalMovement);

            foot.innerHTML = `
                <tr>
                    <td colspan="4" class="sum-label">
                        <i class="fa-solid fa-sigma" style="margin-right:5px;"></i>
                        Totals — ${rows.length.toLocaleString()} RM${rows.length !== 1 ? 's' : ''}
                    </td>
                    <td class="num" title="${fmtFull(totalOpening)}">${fmt(totalOpening)}</td>
                    <td class="num" title="${fmtFull(totalClosing)}">${fmt(totalClosing)}</td>
                    <td class="num ${cls}" title="${fmtFull(totalMovement)}">${fmt(totalMovement)}</td>
                    <td class="num">—</td>
                    <td class="num">${totalCifs.toLocaleString()}</td>
                    <td colspan="2"></td>
                </tr>`;
        }

        function renderTable(rows) {
            if (!rows.length) {
                $('rm-table-body').innerHTML = `
            <tr class="loading-row">
                <td colspan="11">
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <div class="empty-title">No RM movement data found</div>
                        <div class="empty-sub">Try widening the date range or generate a fresh movement snapshot.</div>
                    </div>
                </td>
            </tr>
        `;
                renderTableFoot([]);
                return;
            }

            $('rm-table-body').innerHTML = rows.map((r, index) => {
                const opening = safeNumber(r.start_balance);
                const movement = safeNumber(r.movement);
                const pct = movementPercent(opening, movement);
                const cls = tableMovementClass(movement);
                const rmCode   = String(r.rm_code ?? '');
                const rmName   = String(r.rm_name ?? '');
                const segment  = String(r.rm_segment ?? '').toUpperCase();

                return `
            <tr class="row-link" onclick='openDrilldown(${jsString(rmCode)})'>
                <td class="num">${index + 1}</td>
                <td><span class="badge-rm">${escapeHtml(rmCode)}</span></td>
                <td style="color:var(--eco-gray);font-weight:600;font-size:0.8rem;">${
                    rmName
                        ? escapeHtml(rmName)
                        : `<span style="color:var(--eco-mid-gray);font-style:italic;font-size:0.74rem;">—</span>`
                }</td>
                <td>${segmentBadge(segment)}</td>
                <td class="num" title="${fmtFull(r.start_balance)}">${fmt(r.start_balance)}</td>
                <td class="num" title="${fmtFull(r.end_balance)}">${fmt(r.end_balance)}</td>
                <td class="num ${cls}" title="${fmtFull(movement)}">${fmt(movement)}</td>
                <td class="num ${cls}">${fmtPct(pct)}</td>
                <td class="num">${safeNumber(r.cif_count).toLocaleString()}</td>
                <td>${statusLabel(movement)}</td>
                <td style="text-align:center;">
                    <i class="fa-solid fa-chevron-right" style="color:var(--eco-mid-gray);font-size:0.75rem;"></i>
                </td>
            </tr>
        `;
            }).join('');

            renderTableFoot(rows);
        }

        let activeTabStatus = '';

        function setTab(status) {
            activeTabStatus = status;

            ['all', 'gain', 'loss', 'flat'].forEach(key => {
                const btn = $('tab-' + (key === '' ? 'all' : key));
                if (btn) btn.classList.remove('active');
            });

            const activeId = status === '' ? 'tab-all' : 'tab-' + status;
            const activeBtn = $(activeId);
            if (activeBtn) activeBtn.classList.add('active');

            filterTable($('search-rm') ? $('search-rm').value : '');
        }

        function filterTable(q) {
            const search = String(q || '').trim().toLowerCase();
            const status = activeTabStatus;

            filteredRows = allRows.filter(r => {
                if (search) {
                    const rmCode = String(r.rm_code || '').toLowerCase();
                    const rmName = String(r.rm_name || '').toLowerCase();
                    if (!rmCode.includes(search) && !rmName.includes(search)) return false;
                }
                if (status === 'gain' && safeNumber(r.movement) <= 0) return false;
                if (status === 'loss' && safeNumber(r.movement) >= 0) return false;
                if (status === 'flat' && safeNumber(r.movement) !== 0) return false;
                return true;
            });

            renderTable(filteredRows);
        }

        /* loadTopMovers and loadTrend removed — panels hidden */

        /* =========================================================
           LOAD TREND (stub — kept so no reference errors)
        ========================================================= */
        async function loadTrend() {
            // Trend chart removed. Stub kept to avoid JS errors if called elsewhere.
            const mode = 'stub';
            const rm = getRm();

            const params = new URLSearchParams({
                start: getStart(),
                end: getEnd(),
                mode,
            });

            if (rm) params.set('rm_code', rm);

            try {
                // No-op: trend panel removed
            } catch (error) {
                // intentionally silent
            }
        }


        /* =========================================================
           LOAD RM LIST
        ========================================================= */
        async function loadRmList() {
            const segment    = getSegment();
            const subsegment = getSubsegment();

            const params = new URLSearchParams({
                start: getStart(),
                end: getEnd(),
            });

            if (segment)    params.set('segment',    segment);
            if (subsegment) params.set('subsegment', subsegment);

            try {
                const res = await fetch(`${BASE}/rm-list?${params}`);

                if (!res.ok) {
                    throw new Error('Failed to load RM list.');
                }

                const rms = await res.json();

                const sel = $('ctrl-rm');
                const current = sel.value;
                const list = Array.isArray(rms) ? rms : [];

                sel.innerHTML = '<option value="">All RMs</option>' +
                    list.map(rm => {
                        const code = escapeHtml(rm.code ?? rm);
                        const name = escapeHtml(rm.name ?? '');
                        const label = name ? `${code} — ${name}` : code;
                        return `<option value="${code}" ${code === current ? 'selected' : ''}>${label}</option>`;
                    }).join('');

            } catch (error) {
                console.error(error);
                throw error;
            }
        }

        async function loadSegmentList() {
            const sel = $('ctrl-segment');
            if (!sel) return;

            try {
                const res = await fetch(`${BASE}/segment-list`);
                if (!res.ok) return;

                const segments = await res.json();
                const list = Array.isArray(segments) ? segments : [];

                sel.innerHTML = '<option value="">All Segments</option>' +
                    list.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');

            } catch (e) {
                // Non-critical: segment filter stays empty
            }
        }

        async function loadSubsegmentList() {
            const sel = $('ctrl-subsegment');
            if (!sel) return;

            const segment = getSegment();
            const params  = segment ? `?segment=${encodeURIComponent(segment)}` : '';

            try {
                const res = await fetch(`${BASE}/subsegment-list${params}`);
                if (!res.ok) return;

                const subsegments = await res.json();
                const list = Array.isArray(subsegments) ? subsegments : [];

                sel.innerHTML = '<option value="">All Sub-segments</option>' +
                    list.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');

            } catch (e) {
                // Non-critical: subsegment filter stays empty
            }
        }

        /* =========================================================
           INFOGRAPHICS
        ========================================================= */
        function renderInfographics() {
            renderSplitChart();
            renderDistributionChart();
            renderTopGainers();
            renderTopLosers();
        }

        function renderTopGainers() {
            const body = $('top-gainers-body');
            if (!body) return;

            const gainers = [...allRows]
                .filter(r => safeNumber(r.movement) > 0)
                .sort((a, b) => safeNumber(b.movement) - safeNumber(a.movement))
                .slice(0, 5);

            if (!gainers.length) {
                body.innerHTML = `<div class="empty-state" style="padding:14px 0;"><div class="empty-title">No gainers</div><div class="empty-sub">No positive movements in selected period.</div></div>`;
                return;
            }

            body.innerHTML = gainers.map((r, i) => {
                const rmCode   = String(r.rm_code ?? '');
                const rmName   = getRmName(r);
                const movement = safeNumber(r.movement);
                return `
                    <div class="top-mover-row" onclick='openDrilldown(${jsString(rmCode)})'>
                        <div class="top-mover-rank gain">${i + 1}</div>
                        <div class="top-mover-info">
                            <div class="top-mover-code">${escapeHtml(rmCode)}</div>
                            ${rmName ? `<div class="top-mover-name">${escapeHtml(rmName)}</div>` : ''}
                        </div>
                        <div class="top-mover-value text-success">${fmt(movement)}</div>
                    </div>`;
            }).join('');
        }

        function renderTopLosers() {
            const body = $('top-losers-body');
            if (!body) return;

            const losers = [...allRows]
                .filter(r => safeNumber(r.movement) < 0)
                .sort((a, b) => safeNumber(a.movement) - safeNumber(b.movement))
                .slice(0, 5);

            if (!losers.length) {
                body.innerHTML = `<div class="empty-state" style="padding:14px 0;"><div class="empty-title">No losers</div><div class="empty-sub">No negative movements in selected period.</div></div>`;
                return;
            }

            body.innerHTML = losers.map((r, i) => {
                const rmCode   = String(r.rm_code ?? '');
                const rmName   = getRmName(r);
                const movement = safeNumber(r.movement);
                return `
                    <div class="top-mover-row" onclick='openDrilldown(${jsString(rmCode)})'>
                        <div class="top-mover-rank loss">${i + 1}</div>
                        <div class="top-mover-info">
                            <div class="top-mover-code">${escapeHtml(rmCode)}</div>
                            ${rmName ? `<div class="top-mover-name">${escapeHtml(rmName)}</div>` : ''}
                        </div>
                        <div class="top-mover-value text-danger">${fmt(movement)}</div>
                    </div>`;
            }).join('');
        }

        /* =========================================================
           RENDER SPLIT CHART
        ========================================================= */
        function renderSplitChart() {
            const gainers = allRows.filter(r => safeNumber(r.movement) > 0).length;
            const losers = allRows.filter(r => safeNumber(r.movement) < 0).length;
            const flat = allRows.filter(r => safeNumber(r.movement) === 0).length;

            $('split-gainers').textContent = gainers.toLocaleString();
            $('split-losers').textContent = losers.toLocaleString();
            $('split-flat').textContent = flat.toLocaleString();

            const ctx = document.getElementById('chart-split').getContext('2d');

            if (splitChart) splitChart.destroy();

            splitChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Gainers', 'Losers', 'Flat'],
                    datasets: [{
                        data: [gainers, losers, flat],
                        backgroundColor: ['#BED600', '#0082BB', '#EDEDED'],
                        borderWidth: 0,
                        hoverOffset: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10
                                },
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.label}: ${context.raw.toLocaleString()} RMs`,
                            }
                        }
                    }
                }
            });
        }

        function getDistributionBuckets() {
            const buckets = [{
                    label: '> +50M',
                    min: 50000000,
                    max: Infinity,
                    count: 0
                },
                {
                    label: '+10M to +50M',
                    min: 10000000,
                    max: 50000000,
                    count: 0
                },
                {
                    label: '+1M to +10M',
                    min: 1000000,
                    max: 10000000,
                    count: 0
                },
                {
                    label: 'Near Flat',
                    min: -999999.999,
                    max: 999999.999,
                    count: 0
                },
                {
                    label: '-1M to -10M',
                    min: -10000000,
                    max: -1000000,
                    count: 0
                },
                {
                    label: '-10M to -50M',
                    min: -50000000,
                    max: -10000000,
                    count: 0
                },
                {
                    label: '< -50M',
                    min: -Infinity,
                    max: -50000000,
                    count: 0
                },
            ];

            allRows.forEach(row => {
                const movement = safeNumber(row.movement);

                const bucket = buckets.find(b => movement >= b.min && movement < b.max);

                if (bucket) {
                    bucket.count++;
                }
            });

            return buckets;
        }

        function renderDistributionChart() {
            const buckets = getDistributionBuckets();
            const ctx = document.getElementById('chart-distribution').getContext('2d');

            if (distributionChart) distributionChart.destroy();

            distributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: buckets.map(b => b.label),
                    datasets: [{
                        label: 'RMs',
                        data: buckets.map(b => b.count),
                        backgroundColor: buckets.map(b => {
                            if (b.label.startsWith('+') || b.label.startsWith('>'))
                                return '#BED600';
                            if (b.label.startsWith('-') || b.label.startsWith('<'))
                                return '#0082BB';
                            return '#EDEDED';
                        }),
                        borderRadius: 5,
                        maxBarThickness: 34,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 450
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.raw.toLocaleString()} RMs`,
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 10
                                },
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                            }
                        }
                    }
                }
            });
        }

        function showInfographicError() {
            $('split-gainers').textContent = '—';
            $('split-losers').textContent = '—';
            $('split-flat').textContent = '—';

            const gainersBody = $('top-gainers-body');
            if (gainersBody) gainersBody.innerHTML = `<div class="text-muted" style="font-size:0.76rem;font-weight:700;">Refresh failed.</div>`;

            const losersBody = $('top-losers-body');
            if (losersBody) losersBody.innerHTML = `<div class="text-muted" style="font-size:0.76rem;font-weight:700;">Refresh failed.</div>`;
        }

        /* =========================================================
           DRILLDOWN MODAL WITH LIMITED DISPLAY + LOADER
        ========================================================= */
        async function openDrilldown(rmCode, limit = DRILLDOWN_DEFAULT_LIMIT) {
            currentDrilldownRm = rmCode;
            currentDrilldownLimit = limit;
            currentDrilldownFilter = 'all';
            currentDrilldownSearch = '';

            const matchedRow = allRows.find(r => String(r.rm_code ?? '') === rmCode);
            const rmName = matchedRow ? getRmName(matchedRow) : '';

            $('modal-title').textContent = rmName
                ? `RM Drilldown — ${rmCode} · ${rmName}`
                : `RM Drilldown — ${rmCode}`;
            $('modal-subtitle').textContent =
                `Showing largest customer movements first. Default view is limited to top ${DRILLDOWN_DEFAULT_LIMIT}.`;
            $('modal-content').innerHTML = drilldownLoaderHtml();
            $('drilldown-modal').classList.add('open');

            // Always fetch at least DRILLDOWN_MIN_FETCH rows so the gainers/losers section has enough data
            const fetchLimit = limit === 'all'
                ? DRILLDOWN_MAX_FETCH_LIMIT
                : Math.max(DRILLDOWN_MIN_FETCH, limit);
            await fetchDrilldownRows(rmCode, fetchLimit);
        }

        async function fetchDrilldownRows(rmCode, limit = DRILLDOWN_DEFAULT_LIMIT) {
            const params = new URLSearchParams({
                start_date: getStart(),
                end_date: getEnd(),
                rm_code: rmCode,
                limit: limit === 'all' ? DRILLDOWN_MAX_FETCH_LIMIT : limit,
            });

            try {
                const res = await fetch(`${BASE}/drilldown?${params}`);

                if (!res.ok) {
                    throw new Error('Failed to load drilldown.');
                }

                const d = await res.json();

                currentDrilldownRows = Array.isArray(d.rows) ? d.rows : [];

                currentDrilldownRows = currentDrilldownRows
                    .map(r => ({
                        ...r,
                        start_balance: safeNumber(r.start_balance),
                        end_balance: safeNumber(r.end_balance),
                        movement: safeNumber(r.movement),
                    }))
                    .sort((a, b) => Math.abs(b.movement) - Math.abs(a.movement));

                renderDrilldown();

            } catch (error) {
                console.error(error);

                $('modal-content').innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div class="empty-title">Could not load RM drilldown</div>
                <div class="empty-sub">Please retry or generate a fresh snapshot for the selected period.</div>
            </div>
        `;
            }
        }

        function drilldownLoaderHtml() {
            return `
        <div class="modal-loader-box">
            <div class="modal-loader-summary">
                <div class="modal-loader-card">
                    <div class="skeleton sk-line sm" style="width:48%;margin-bottom:8px;"></div>
                    <div class="skeleton sk-line lg" style="width:80%;"></div>
                </div>

                <div class="modal-loader-card">
                    <div class="skeleton sk-line sm" style="width:48%;margin-bottom:8px;"></div>
                    <div class="skeleton sk-line lg" style="width:80%;"></div>
                </div>

                <div class="modal-loader-card">
                    <div class="skeleton sk-line sm" style="width:48%;margin-bottom:8px;"></div>
                    <div class="skeleton sk-line lg" style="width:80%;"></div>
                </div>

                <div class="modal-loader-card">
                    <div class="skeleton sk-line sm" style="width:48%;margin-bottom:8px;"></div>
                    <div class="skeleton sk-line lg" style="width:80%;"></div>
                </div>
            </div>

            <div class="modal-loader-table">
                <div class="skeleton sk-line" style="width:100%;"></div>
                <div class="skeleton sk-line" style="width:96%;"></div>
                <div class="skeleton sk-line" style="width:92%;"></div>
                <div class="skeleton sk-line" style="width:88%;"></div>
                <div class="skeleton sk-line" style="width:94%;"></div>
                <div class="skeleton sk-line" style="width:86%;"></div>
                <div class="skeleton sk-line" style="width:90%;"></div>
            </div>
        </div>
    `;
        }

        function renderDrilldown() {
            const rows = currentDrilldownRows || [];

            if (!rows.length) {
                $('modal-content').innerHTML = `
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                <div class="empty-title">No CIF data found for this RM</div>
                <div class="empty-sub">Try another RM, widen the selected period, or generate a fresh snapshot.</div>
            </div>
        `;
                return;
            }

            const summary = calculateDrilldownSummary(rows);
            const filtered = getFilteredDrilldownRows();
            const limited = applyDrilldownLimit(filtered);

            const showingText = currentDrilldownLimit === 'all' ?
                `Showing all ${filtered.length.toLocaleString()} matching records` :
                `Showing ${limited.length.toLocaleString()} of ${filtered.length.toLocaleString()} matching records`;

            $('modal-content').innerHTML = `
        <div class="drilldown-summary">
            <div class="drill-card">
                <div class="drill-label">Opening</div>
                <div class="drill-value" title="${fmtFull(summary.opening)}">${fmt(summary.opening)}</div>
            </div>

            <div class="drill-card">
                <div class="drill-label">Closing</div>
                <div class="drill-value" title="${fmtFull(summary.closing)}">${fmt(summary.closing)}</div>
            </div>

            <div class="drill-card">
                <div class="drill-label">Movement</div>
                <div class="drill-value ${tableMovementClass(summary.movement)}" title="${fmtFull(summary.movement)}">${fmt(summary.movement)}</div>
            </div>

            <div class="drill-card">
                <div class="drill-label">CIFs Loaded</div>
                <div class="drill-value">${rows.length.toLocaleString()}</div>
            </div>
        </div>

        ${buildDrillMoverSection(rows)}

        <div class="drill-toolbar">
            <div class="drill-toolbar-left">
                <input type="text"
                       id="modal-search"
                       class="tiny-input"
                       placeholder="Search CIF or customer..."
                       value="${escapeHtml(currentDrilldownSearch)}"
                       oninput="onDrilldownSearch(this.value)">

                <select id="modal-filter" class="tiny-select" onchange="onDrilldownFilter(this.value)">
                    <option value="all" ${currentDrilldownFilter === 'all' ? 'selected' : ''}>All</option>
                    <option value="gainers" ${currentDrilldownFilter === 'gainers' ? 'selected' : ''}>Gainers only</option>
                    <option value="losers" ${currentDrilldownFilter === 'losers' ? 'selected' : ''}>Losers only</option>
                    <option value="flat" ${currentDrilldownFilter === 'flat' ? 'selected' : ''}>Flat only</option>
                </select>

                <select id="modal-limit" class="tiny-select" onchange="onDrilldownLimitChange(this.value)">
                    <option value="5"   ${String(currentDrilldownLimit) === '5'   ? 'selected' : ''}>Show top 5</option>
                    <option value="10"  ${String(currentDrilldownLimit) === '10'  ? 'selected' : ''}>Show top 10</option>
                    <option value="25"  ${String(currentDrilldownLimit) === '25'  ? 'selected' : ''}>Show top 25</option>
                    <option value="50"  ${String(currentDrilldownLimit) === '50'  ? 'selected' : ''}>Show top 50</option>
                    <option value="all" ${String(currentDrilldownLimit) === 'all' ? 'selected' : ''}>Show all loaded</option>
                </select>
            </div>

            <div class="drill-toolbar-right">
                <span class="drill-note">${showingText}</span>

                <button type="button" class="btn-eco btn-eco-light" onclick="exportDrilldownCsv()" style="padding:6px 10px;min-height:30px;">
                    <i class="fa-solid fa-file-export"></i>
                    Export
                </button>
            </div>
        </div>

        <div class="drill-table-wrap">
            <table class="rm-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>CIF</th>
                        <th>Customer Name</th>
                        <th class="num">Opening</th>
                        <th class="num">Closing</th>
                        <th class="num">Movement</th>
                        <th class="num">Movement %</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    ${renderDrilldownRows(limited)}
                </tbody>
            </table>
        </div>
    `;
        }

        function renderDrilldownRows(rows) {
            if (!rows.length) {
                return `
            <tr class="loading-row">
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <div class="empty-title">No matching customers</div>
                        <div class="empty-sub">Adjust the search or movement filter.</div>
                    </div>
                </td>
            </tr>
        `;
            }

            return rows.map((r, index) => {
                const opening = safeNumber(r.start_balance);
                const movement = safeNumber(r.movement);
                const pct = movementPercent(opening, movement);
                const cls = tableMovementClass(movement);

                return `
            <tr>
                <td class="num">${index + 1}</td>
                <td><span class="badge-rm">${escapeHtml(r.cif)}</span></td>
                <td title="${escapeHtml(r.customer_name || r.cif)}">${escapeHtml(r.customer_name || r.cif)}</td>
                <td class="num" title="${fmtFull(r.start_balance)}">${fmt(r.start_balance)}</td>
                <td class="num" title="${fmtFull(r.end_balance)}">${fmt(r.end_balance)}</td>
                <td class="num ${cls}" title="${fmtFull(movement)}">${fmt(movement)}</td>
                <td class="num ${cls}">${fmtPct(pct)}</td>
                <td>${statusLabel(movement)}</td>
            </tr>
        `;
            }).join('');
        }

        function calculateDrilldownSummary(rows) {
            return rows.reduce((acc, row) => {
                acc.opening += safeNumber(row.start_balance);
                acc.closing += safeNumber(row.end_balance);
                acc.movement += safeNumber(row.movement);
                return acc;
            }, {
                opening: 0,
                closing: 0,
                movement: 0,
            });
        }

        function getFilteredDrilldownRows() {
            const search = String(currentDrilldownSearch || '').trim().toLowerCase();

            return currentDrilldownRows.filter(row => {
                const movement = safeNumber(row.movement);

                const matchesFilter =
                    currentDrilldownFilter === 'all' ||
                    (currentDrilldownFilter === 'gainers' && movement > 0) ||
                    (currentDrilldownFilter === 'losers' && movement < 0) ||
                    (currentDrilldownFilter === 'flat' && movement === 0);

                const matchesSearch = !search ||
                    String(row.cif || '').toLowerCase().includes(search) ||
                    String(row.customer_name || '').toLowerCase().includes(search);

                return matchesFilter && matchesSearch;
            });
        }

        function applyDrilldownLimit(rows) {
            if (currentDrilldownLimit === 'all') {
                return rows;
            }

            const limit = parseInt(currentDrilldownLimit || DRILLDOWN_DEFAULT_LIMIT, 10);
            return rows.slice(0, Number.isFinite(limit) ? limit : DRILLDOWN_DEFAULT_LIMIT);
        }

        function onDrilldownSearch(value) {
            currentDrilldownSearch = value;
            renderDrilldown();
        }

        function onDrilldownFilter(value) {
            currentDrilldownFilter = value;
            renderDrilldown();
        }

        async function onDrilldownLimitChange(value) {
            currentDrilldownLimit = value === 'all' ? 'all' : parseInt(value, 10);

            const loadedCount = currentDrilldownRows.length;
            const requested = currentDrilldownLimit === 'all' ?
                DRILLDOWN_MAX_FETCH_LIMIT :
                currentDrilldownLimit;

            if (requested > loadedCount && currentDrilldownRm) {
                $('modal-content').innerHTML = drilldownLoaderHtml();
                await fetchDrilldownRows(currentDrilldownRm, requested);
                return;
            }

            renderDrilldown();
        }

        function closeModal() {
            $('drilldown-modal').classList.remove('open');
            currentDrilldownRm = null;
            currentDrilldownRows = [];
            currentDrilldownLimit = DRILLDOWN_DEFAULT_LIMIT;
            currentDrilldownFilter = 'all';
            currentDrilldownSearch = '';
        }

        /* =========================================================
           BUILD MODAL
        ========================================================= */
        function openBuildModal() {
            $('build-start').value = getStart();
            $('build-end').value = getEnd();
            $('build-msg').className = 'build-msg text-muted';
            $('build-msg').textContent = '';
            $('build-modal').classList.add('open');
        }

        function closeBuildModal() {
            $('build-modal').classList.remove('open');
        }

        async function runBuild() {
            const start = $('build-start').value;
            const end = $('build-end').value;

            if (!start || !end) {
                $('build-msg').className = 'build-msg text-danger';
                $('build-msg').textContent = 'Please select both start and end dates.';
                return;
            }

            if (start > end) {
                $('build-msg').className = 'build-msg text-danger';
                $('build-msg').textContent = 'Start date cannot be later than end date.';
                return;
            }

            const token = document.querySelector('meta[name=csrf-token]')?.content;

            if (!token) {
                $('build-msg').className = 'build-msg text-danger';
                $('build-msg').textContent = 'CSRF token missing. Please refresh the page.';
                return;
            }

            setButtonLoading('btn-build-now', true, 'Generating...');
            $('build-msg').className = 'build-msg text-muted';
            $('build-msg').innerHTML =
                '<span class="spinner" style="width:15px;height:15px;"></span> Generating snapshot. Please keep this window open...';

            try {
                const res = await fetch(`${BASE}/build`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        start_date: start,
                        end_date: end,
                    }),
                });

                const d = await res.json();

                if (d.success) {
                    $('build-msg').className = 'build-msg text-success';
                    $('build-msg').innerHTML =
                        `<i class="fa-solid fa-circle-check"></i> ${escapeHtml(d.message || 'Snapshot generated successfully.')}`;

                    $('ctrl-start').value = start;
                    $('ctrl-end').value = end;

                    setTimeout(() => {
                        closeBuildModal();
                        loadAll();
                    }, 900);

                } else {
                    $('build-msg').className = 'build-msg text-danger';
                    $('build-msg').textContent = d.message || 'Snapshot generation failed.';
                }

            } catch (error) {
                console.error(error);
                $('build-msg').className = 'build-msg text-danger';
                $('build-msg').textContent = 'Error: ' + error.message;
            } finally {
                setButtonLoading('btn-build-now', false);
            }
        }

        /* =========================================================
           DRILLDOWN MOVER SECTION BUILDER
        ========================================================= */
        function buildDrillMoverSection(rows) {
            const gainers = [...rows]
                .filter(r => safeNumber(r.movement) > 0)
                .sort((a, b) => safeNumber(b.movement) - safeNumber(a.movement))
                .slice(0, 5);

            const losers = [...rows]
                .filter(r => safeNumber(r.movement) < 0)
                .sort((a, b) => safeNumber(a.movement) - safeNumber(b.movement))
                .slice(0, 5);

            const renderItems = (items, cls) => {
                if (!items.length) {
                    return `<div style="font-size:0.72rem;color:var(--eco-mid-gray);padding:6px 0;font-weight:700;">None</div>`;
                }
                return items.map(r => `
                    <div class="drill-mover-item">
                        <div class="drill-mover-cif-wrap">
                            <div class="drill-mover-cif">${escapeHtml(String(r.cif ?? ''))}</div>
                            <div class="drill-mover-cname">${escapeHtml(String(r.customer_name || r.cif || ''))}</div>
                        </div>
                        <div class="drill-mover-val ${cls}">${fmt(safeNumber(r.movement))}</div>
                    </div>`).join('');
            };

            return `
                <div class="drill-mover-section">
                    <div class="drill-mover-panel">
                        <div class="drill-mover-header gain">
                            <i class="fa-solid fa-arrow-trend-up"></i> Top ${gainers.length} CIF Gainers
                        </div>
                        <div class="drill-mover-rows">${renderItems(gainers, 'text-success')}</div>
                    </div>
                    <div class="drill-mover-panel">
                        <div class="drill-mover-header loss">
                            <i class="fa-solid fa-arrow-trend-down"></i> Top ${losers.length} CIF Losers
                        </div>
                        <div class="drill-mover-rows">${renderItems(losers, 'text-danger')}</div>
                    </div>
                </div>`;
        }

        /* =========================================================
           SINGLE RM PROFILE STATS
        ========================================================= */
        async function loadSingleRmStats() {
            const rm = getRm();
            if (!rm) return;

            const profileSection = $('rm-profile-section');
            if (profileSection) profileSection.style.display = '';

            $('rm-profile-title').innerHTML = `<i class="fa-solid fa-user-tie"></i> ${escapeHtml(rm)}`;
            $('rm-rank-badge').innerHTML = '';
            $('rm-period-cards').innerHTML = `<div class="text-muted" style="font-size:0.76rem;font-weight:700;padding:6px 0;">Loading period stats...</div>`;

            try {
                const params = new URLSearchParams({
                    rm_code:    rm,
                    start_date: getStart(),
                    end_date:   getEnd(),
                    segment:    getSegment(),
                    subsegment: getSubsegment(),
                });

                const res = await fetch(`${BASE}/single-rm-stats?${params}`);
                if (!res.ok) throw new Error('Failed to load RM stats.');

                const d = await res.json();
                renderSingleRmStats(d);

            } catch (e) {
                console.error(e);
                $('rm-period-cards').innerHTML = `<div class="text-danger" style="font-size:0.76rem;font-weight:700;padding:6px 0;">Could not load period stats.</div>`;
            }
        }

        function renderSingleRmStats(d) {
            // Update title
            const rmName  = d.rm_name  ? ` — ${escapeHtml(d.rm_name)}`  : '';
            const segBadge = d.segment
                ? ` <span style="font-size:0.7rem;font-weight:700;color:var(--eco-mid-gray);background:#f0f4f8;border-radius:6px;padding:2px 7px;">${escapeHtml(d.segment)}</span>`
                : '';
            $('rm-profile-title').innerHTML = `<i class="fa-solid fa-user-tie"></i> ${escapeHtml(d.rm_code)}${rmName}${segBadge}`;

            // Rank badge
            $('rm-rank-badge').innerHTML = d.rank
                ? `<span class="badge-rm" style="font-size:0.8rem;">Rank #${d.rank}</span>`
                : '';

            // Current period card from allRows
            const currentRow = allRows.find(r => String(r.rm_code).toUpperCase() === String(d.rm_code).toUpperCase());

            const periodCard = currentRow ? `
                <div class="rm-period-card" style="border-color:rgba(0,91,130,0.18);background:#f5f9fd;">
                    <div class="rm-period-label" style="color:var(--eco-dark-blue);">Selected Period</div>
                    <div class="rm-period-dates">${escapeHtml(getStart())} → ${escapeHtml(getEnd())}</div>
                    <div class="rm-period-movement ${tableMovementClass(currentRow.movement)}">${fmt(currentRow.movement)}</div>
                    <div class="rm-period-meta">
                        CIFs: ${safeNumber(currentRow.cif_count).toLocaleString()}
                        &nbsp;·&nbsp; Opening: ${fmt(currentRow.start_balance)}
                        &nbsp;·&nbsp; Closing: ${fmt(currentRow.end_balance)}
                    </div>
                </div>` : '';

            // Standard period cards
            const periods = [
                { label: 'WTD', title: 'Week to Date',  data: d.wtd },
                { label: 'MTD', title: 'Month to Date', data: d.mtd },
                { label: 'YTD', title: 'Year to Date',  data: d.ytd },
            ];

            const periodCards = periods.map(p => {
                if (!p.data) {
                    return `
                        <div class="rm-period-card">
                            <div class="rm-period-label">${p.label} — ${p.title}</div>
                            <div class="rm-period-dates" style="margin-top:6px;">No snapshot available</div>
                            <div class="rm-period-na">N / A</div>
                        </div>`;
                }
                return `
                    <div class="rm-period-card">
                        <div class="rm-period-label">${p.label} — ${p.title}</div>
                        <div class="rm-period-dates">${escapeHtml(String(p.data.start_date))} → ${escapeHtml(String(p.data.end_date))}</div>
                        <div class="rm-period-movement ${tableMovementClass(p.data.movement)}">${fmt(p.data.movement)}</div>
                        <div class="rm-period-meta">
                            CIFs: ${safeNumber(p.data.cif_count).toLocaleString()}
                            &nbsp;·&nbsp; Opening: ${fmt(p.data.start_balance)}
                            &nbsp;·&nbsp; Closing: ${fmt(p.data.end_balance)}
                        </div>
                    </div>`;
            }).join('');

            $('rm-period-cards').innerHTML = periodCard + periodCards;
        }

        /* =========================================================
           EXPORTS
        ========================================================= */
        function exportTableCsv() {
            const rows = filteredRows || [];

            if (!rows.length) {
                alert('No RM summary data available to export.');
                return;
            }

            const csvRows = [
                ['Rank', 'RM Code', 'RM Name', 'Opening', 'Closing', 'Movement', 'Movement %', 'CIFs', 'Status'],
            ];

            rows.forEach((r, index) => {
                const opening = safeNumber(r.start_balance);
                const movement = safeNumber(r.movement);
                const pct = movementPercent(opening, movement);

                csvRows.push([
                    index + 1,
                    r.rm_code,
                    getRmName(r),
                    opening,
                    safeNumber(r.end_balance),
                    movement,
                    fmtPct(pct),
                    safeNumber(r.cif_count),
                    movement > 0 ? 'Gain' : movement < 0 ? 'Loss' : 'Flat',
                ]);
            });

            downloadCsv(csvRows, `rm_movers_summary_${getStart()}_${getEnd()}.csv`);
        }

        function exportDrilldownCsv() {
            const rows = getFilteredDrilldownRows();

            if (!rows.length) {
                alert('No drilldown data available to export.');
                return;
            }

            const csvRows = [
                ['Rank', 'RM Code', 'CIF', 'Customer Name', 'Opening', 'Closing', 'Movement', 'Movement %', 'Status'],
            ];

            rows.forEach((r, index) => {
                const opening = safeNumber(r.start_balance);
                const movement = safeNumber(r.movement);
                const pct = movementPercent(opening, movement);

                csvRows.push([
                    index + 1,
                    currentDrilldownRm,
                    r.cif,
                    r.customer_name || r.cif,
                    opening,
                    safeNumber(r.end_balance),
                    movement,
                    fmtPct(pct),
                    movement > 0 ? 'Gain' : movement < 0 ? 'Loss' : 'Flat',
                ]);
            });

            downloadCsv(csvRows, `rm_${currentDrilldownRm}_drilldown_${getStart()}_${getEnd()}.csv`);
        }

        function downloadCsv(rows, filename) {
            const csv = rows.map(row => row.map(value => {
                const text = String(value ?? '');
                return `"${text.replaceAll('"', '""')}"`;
            }).join(',')).join('\n');

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;',
            });

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = filename;

            document.body.appendChild(link);
            link.click();

            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        /* =========================================================
           KEYBOARD / INIT
        ========================================================= */
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeModal();
                closeBuildModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            $('ctrl-start').value = DEFAULT_START;
            $('ctrl-end').value = DEFAULT_END;

            updateHeroPeriod();
            loadSegmentList();
            loadSubsegmentList();
            loadAll();
        });
    </script>
@endpush
