@extends('layouts.finance.template')

@section('title', 'Branch Movers Dashboard')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-dark-blue: #005B82;
            --eco-green: #BED600;
            --eco-dark-green: #669438;
            --eco-purple: #7C3AED;
            --eco-teal: #0EA5A4;
            --eco-gold: #D97706;
            --eco-pink: #DB2777;
            --eco-gray: #464646;
            --eco-light-gray: #EDEDED;
            --eco-mid-gray: #979797;
            --eco-bg: #F5F8FB;
            --eco-danger: #b91c1c;
            --eco-success: #15803d;
            --eco-warning: #b45309;
            --card-shadow: 0 8px 28px rgba(0, 0, 0, 0.06);
            --card-shadow-soft: 0 4px 16px rgba(0, 0, 0, 0.04);
            --border-soft: 1px solid rgba(0, 91, 130, 0.08);
        }

        body {
            background: var(--eco-bg);
        }

        .branch-dashboard {
            padding: 14px 18px 22px;
        }

        .dashboard-header {
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, var(--eco-dark-blue) 0%, var(--eco-blue) 45%, var(--eco-teal) 75%, var(--eco-dark-green) 100%);
            background-size: 300% 300%;
            animation: heroGradientShift 14s ease-in-out infinite;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 14px;
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .hero-glow {
            position: absolute;
            top: -60%;
            right: -10%;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(190, 214, 0, 0.35) 0%, rgba(190, 214, 0, 0) 70%);
            z-index: 0;
            pointer-events: none;
            animation: heroGlowFloat 9s ease-in-out infinite;
        }

        .dashboard-header-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        @keyframes heroGradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        @keyframes heroGlowFloat {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-30px, 40px) scale(1.15);
            }
        }

        @keyframes pillPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(190, 214, 0, 0.45);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(190, 214, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(190, 214, 0, 0);
            }
        }

        .dashboard-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .dashboard-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .dashboard-title {
            margin: 0;
            color: #fff;
            font-size: 1.18rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .dashboard-subtitle {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meta-pill {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .meta-pill.live {
            background: rgba(190, 214, 0, 0.22);
            border-color: rgba(190, 214, 0, 0.4);
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--eco-green);
            animation: pillPulse 1.6s infinite;
        }

        .dashboard-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(245, 248, 251, 0.92);
            backdrop-filter: blur(8px);
            padding: 10px 0 14px;
        }

        .toolbar-inner {
            background: #fff;
            border-radius: 14px;
            box-shadow: var(--card-shadow-soft);
            border: var(--border-soft);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .toolbar-left,
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--eco-mid-gray);
            margin-right: 4px;
        }

        .filter-control {
            border: 1.5px solid #dce7ee;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--eco-gray);
            background: #fff;
            outline: none;
            min-width: 250px;
            transition: all .2s ease;
        }

        .filter-control.sm {
            min-width: 120px;
            padding: 8px 10px;
            font-size: 0.78rem;
        }

        .filter-control.search {
            min-width: 220px;
        }

        .filter-control:focus {
            border-color: var(--eco-blue);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, 0.08);
        }

        .btn-refresh,
        .btn-soft {
            border: none;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.8rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-refresh {
            background: var(--eco-blue);
            color: #fff;
        }

        .btn-refresh:hover {
            background: var(--eco-dark-blue);
            transform: translateY(-1px);
        }

        .btn-refresh:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        .btn-soft {
            background: #f3f8fb;
            color: var(--eco-dark-blue);
            border: 1px solid #dce7ee;
        }

        .btn-soft:hover {
            border-color: var(--eco-blue);
            color: var(--eco-blue);
            background: #fff;
        }

        .view-switch {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f3f8fb;
            border: 1px solid #dce7ee;
            border-radius: 12px;
            padding: 4px;
        }

        .view-btn {
            border: none;
            background: transparent;
            color: var(--eco-mid-gray);
            border-radius: 9px;
            padding: 8px 12px;
            font-size: 0.76rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .18s ease;
        }

        .view-btn.active {
            background: #fff;
            color: var(--eco-dark-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            box-shadow: var(--card-shadow-soft);
            border: var(--border-soft);
            min-height: 112px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kpi-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .kpi-label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--eco-mid-gray);
        }

        .kpi-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 130, 187, 0.10);
            color: var(--eco-blue);
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .kpi-card {
            border-top: 3px solid transparent;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .kpi-card.accent-blue {
            border-top-color: var(--eco-blue);
        }

        .kpi-card.accent-teal {
            border-top-color: var(--eco-teal);
        }

        .kpi-card.accent-purple {
            border-top-color: var(--eco-purple);
        }

        .kpi-card.accent-green {
            border-top-color: var(--eco-success);
        }

        .kpi-card.accent-red {
            border-top-color: var(--eco-danger);
        }

        .kpi-icon.icon-blue {
            background: rgba(0, 130, 187, 0.12);
            color: var(--eco-blue);
        }

        .kpi-icon.icon-teal {
            background: rgba(14, 165, 164, 0.14);
            color: var(--eco-teal);
        }

        .kpi-icon.icon-purple {
            background: rgba(124, 58, 237, 0.12);
            color: var(--eco-purple);
        }

        .kpi-value {
            font-size: 1.18rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--eco-dark-blue);
            font-family: 'DM Mono', monospace;
        }

        .kpi-value.gain {
            color: var(--eco-success);
        }

        .kpi-value.loss {
            color: var(--eco-danger);
        }

        .kpi-foot {
            font-size: 0.72rem;
            color: var(--eco-mid-gray);
            margin-top: 6px;
            font-weight: 600;
            min-height: 18px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(320px, 1fr);
            gap: 14px;
            margin-bottom: 14px;
        }

        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: var(--card-shadow-soft);
            border: var(--border-soft);
            overflow: hidden;
            position: relative;
        }

        .section-header {
            padding: 14px 16px;
            border-bottom: 1px solid #edf3f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--eco-dark-blue);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .section-title-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(0, 130, 187, .08);
            color: var(--eco-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .section-title-icon.icon-teal {
            background: rgba(14, 165, 164, .12);
            color: var(--eco-teal);
        }

        .section-title-icon.icon-purple {
            background: rgba(124, 58, 237, .12);
            color: var(--eco-purple);
        }

        .section-title-icon.icon-gold {
            background: rgba(217, 119, 6, .12);
            color: var(--eco-gold);
        }

        .section-title-icon.icon-pink {
            background: rgba(219, 39, 119, .12);
            color: var(--eco-pink);
        }

        .section-subtext {
            font-size: 0.72rem;
            color: var(--eco-mid-gray);
            font-weight: 700;
        }

        .section-body {
            padding: 14px 16px 16px;
        }

        .chart-box {
            height: 380px;
        }

        .side-stack {
            display: grid;
            grid-template-rows: auto auto;
            gap: 14px;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .insight-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: var(--card-shadow-soft);
            border: var(--border-soft);
            padding: 14px;
            min-height: 120px;
        }

        .insight-label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--eco-mid-gray);
            margin-bottom: 10px;
        }

        .insight-value {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--eco-dark-blue);
            line-height: 1.2;
            font-family: 'DM Mono', monospace;
        }

        .insight-note {
            margin-top: 8px;
            font-size: 0.76rem;
            color: var(--eco-gray);
            font-weight: 600;
        }

        .insight-list {
            display: grid;
            gap: 10px;
        }

        .insight-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e9f0f4;
        }

        .insight-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .insight-row span:first-child {
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--eco-mid-gray);
        }

        .insight-row span:last-child {
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--eco-dark-blue);
            text-align: right;
        }

        .donut-wrap {
            position: relative;
            height: 220px;
        }

        .donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            pointer-events: none;
        }

        .donut-center-pct {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--eco-success);
            font-family: 'DM Mono', monospace;
            line-height: 1;
        }

        .donut-center-label {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--eco-mid-gray);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 4px;
        }

        .donut-legend {
            margin-top: 12px;
            display: grid;
            gap: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.78rem;
        }

        .legend-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--eco-gray);
            font-weight: 700;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .legend-value {
            color: var(--eco-dark-blue);
            font-weight: 800;
            font-family: 'DM Mono', monospace;
        }

        .panel-wrap {
            margin-top: 2px;
        }

        .panel {
            display: none;
        }

        .panel.active {
            display: block;
        }

        .panel-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .dir-toggle {
            display: inline-flex;
            align-items: center;
            border: 1px solid #dce7ee;
            background: #f3f8fb;
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }

        .dir-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 9px;
            font-size: 0.74rem;
            font-weight: 800;
            cursor: pointer;
            background: transparent;
            color: var(--eco-mid-gray);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .18s ease;
        }

        .dir-btn.active.gain {
            background: rgba(34, 197, 94, .12);
            color: var(--eco-success);
        }

        .dir-btn.active.loss {
            background: rgba(239, 68, 68, .10);
            color: var(--eco-danger);
        }

        .branch-filter-bar {
            padding: 0 16px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .branch-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 0 16px 14px;
        }

        .branch-tab {
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 800;
            cursor: pointer;
            border: 1px solid #dce7ee;
            background: #fff;
            color: var(--eco-mid-gray);
            transition: all .18s ease;
        }

        .branch-tab:hover {
            border-color: var(--eco-blue);
            color: var(--eco-blue);
        }

        .branch-tab.active {
            background: var(--eco-blue);
            border-color: var(--eco-blue);
            color: #fff;
            box-shadow: 0 6px 14px rgba(0, 130, 187, 0.18);
        }

        .tbl-wrap {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .data-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f7fafc;
            color: var(--eco-dark-blue);
            padding: 11px 14px;
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid #e7eef3;
            white-space: nowrap;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #edf3f7;
            transition: background .12s ease;
        }

        .data-table tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .data-table tbody tr:hover {
            background: #f4faff;
        }

        .data-table tbody tr.total-row {
            background: rgba(0, 130, 187, .05);
            font-weight: 800;
            border-top: 2px solid rgba(0, 130, 187, 0.18);
        }

        .data-table td {
            padding: 11px 14px;
            color: var(--eco-gray);
            vertical-align: middle;
        }

        .num {
            text-align: right;
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
        }

        .text-strong {
            font-weight: 700;
            color: var(--eco-dark-blue);
        }

        .badge-gain,
        .badge-loss,
        .badge-neutral {
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-family: 'DM Mono', monospace;
        }

        .badge-gain {
            background: rgba(34, 197, 94, .12);
            color: var(--eco-success);
        }

        .badge-loss {
            background: rgba(239, 68, 68, .10);
            color: var(--eco-danger);
        }

        .badge-neutral {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
        }

        .rank-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef4f8;
            color: var(--eco-gray);
            font-size: 0.72rem;
            font-weight: 800;
        }

        .rank-circle.top3 {
            background: rgba(190, 214, 0, .20);
            color: var(--eco-dark-blue);
        }

        .split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .split-side {
            padding: 16px;
        }

        .split-side:first-child {
            border-right: 1px solid #edf3f7;
        }

        .split-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 12px;
        }

        .split-title.gain {
            color: var(--eco-success);
        }

        .split-title.loss {
            color: var(--eco-danger);
        }

        .empty-state {
            text-align: center;
            padding: 36px 20px;
            color: var(--eco-mid-gray);
        }

        .empty-state i {
            font-size: 1.7rem;
            display: block;
            margin-bottom: 8px;
            opacity: 0.45;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .loading-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.68);
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
            z-index: 9;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 3px solid #e2eaf0;
            border-top-color: var(--eco-blue);
            animation: spin .7s linear infinite;
        }

        .toast-holder {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 9999;
            display: grid;
            gap: 10px;
            max-width: 380px;
        }

        .dash-toast {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .12);
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--eco-gray);
            font-size: .82rem;
            font-weight: 700;
            animation: toastIn .18s ease-out;
        }

        .dash-toast.error {
            border-left: 4px solid var(--eco-danger);
        }

        .dash-toast.success {
            border-left: 4px solid var(--eco-success);
        }

        .dash-toast.info {
            border-left: 4px solid var(--eco-blue);
        }

        .dash-toast i {
            margin-top: 2px;
        }

        .dash-toast.error i {
            color: var(--eco-danger);
        }

        .dash-toast.success i {
            color: var(--eco-success);
        }

        .dash-toast.info i {
            color: var(--eco-blue);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes toastIn {
            from {
                transform: translateY(-6px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1300px) {
            .kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .mini-grid,
            .split-grid {
                grid-template-columns: 1fr;
            }

            .split-side:first-child {
                border-right: none;
                border-bottom: 1px solid #edf3f7;
            }

            .chart-box {
                height: 330px;
            }
        }

        @media (max-width: 640px) {
            .branch-dashboard {
                padding: 10px 10px 18px;
            }

            .dashboard-header {
                padding: 16px;
                border-radius: 14px;
            }

            .toolbar-inner {
                padding: 12px;
            }

            .filter-control {
                min-width: 100%;
                width: 100%;
            }

            .toolbar-left,
            .toolbar-right,
            .view-switch,
            .btn-refresh,
            .btn-soft {
                width: 100%;
            }

            .view-switch {
                justify-content: space-between;
            }

            .view-btn {
                flex: 1;
                justify-content: center;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .chart-box {
                height: 300px;
            }

            .toast-holder {
                left: 12px;
                right: 12px;
                top: 12px;
                max-width: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="branch-dashboard">
        <div class="toast-holder" id="toastHolder"></div>

        {{-- Header --}}
        <div class="dashboard-header">
            <canvas class="hero-particles" id="heroParticles"></canvas>
            <div class="hero-glow"></div>

            <div class="dashboard-header-top">
                <div class="dashboard-title-wrap">
                    <div class="dashboard-title-icon">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </div>
                    <div>
                        <h1 class="dashboard-title">Branch Movers Dashboard</h1>
                        <p class="dashboard-subtitle">LCY balance movement analysis across branches and top CIF movements
                        </p>
                    </div>
                </div>

                <div class="header-meta">
                    <div class="meta-pill">
                        <i class="bi bi-calendar3"></i>
                        <span id="heroPeriod">—</span>
                    </div>
                    <div class="meta-pill">
                        <i class="bi bi-diagram-3"></i>
                        <span id="heroSummary">Waiting for data</span>
                    </div>
                    <div class="meta-pill">
                        <i class="bi bi-clock-history"></i>
                        <span id="lastRefreshed">Not refreshed</span>
                    </div>
                    <div class="meta-pill live">
                        <span class="live-dot"></span>
                        <span id="heroClock">—</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="dashboard-toolbar">
            <div class="toolbar-inner">
                <div class="toolbar-left">
                    {{-- Period / Branch / Chart controls are kept in the DOM (hidden) so the
                         dashboard still knows which reporting period & scope to query --}}
                    <div class="d-none">
                        <select id="periodSelect" class="filter-control">
                            @foreach ($periods as $p)
                                <option value="{{ $p->start_date }}|{{ $p->end_date }}|{{ $loop->first ? '1' : '0' }}">
                                    {{ \Carbon\Carbon::parse($p->start_date)->format('d M Y') }} →
                                    {{ \Carbon\Carbon::parse($p->end_date)->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>

                        <select id="branchSelect" class="filter-control">
                            <option value="">All Branches</option>
                        </select>

                        <select id="chartLimitSelect" class="filter-control sm">
                            <option value="10">Top 10</option>
                            <option value="20" selected>Top 20</option>
                            <option value="50">Top 50</option>
                            <option value="0">All</option>
                        </select>
                    </div>

                    <button class="btn-refresh" id="btnRefresh">
                        <i class="bi bi-arrow-clockwise"></i>
                        Refresh
                    </button>
                </div>

                <div class="toolbar-right">
                    <span class="toolbar-label">View</span>
                    <div class="view-switch">
                        <button type="button" class="view-btn active" data-view="summary">
                            <i class="bi bi-table"></i> Summary
                        </button>
                        <button type="button" class="view-btn" data-view="movers">
                            <i class="bi bi-trophy"></i> Movers
                        </button>
                        <button type="button" class="view-btn" data-view="cif">
                            <i class="bi bi-people"></i> CIF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="kpi-grid">
            <div class="kpi-card accent-blue">
                <div class="kpi-head">
                    <div class="kpi-label">Opening Balance</div>
                    <div class="kpi-icon icon-blue"><i class="bi bi-bank"></i></div>
                </div>
                <div class="kpi-value" id="kpiStart">—</div>
                <div class="kpi-foot">Period opening value in KES</div>
            </div>

            <div class="kpi-card accent-teal">
                <div class="kpi-head">
                    <div class="kpi-label">Closing Balance</div>
                    <div class="kpi-icon icon-teal"><i class="bi bi-wallet2"></i></div>
                </div>
                <div class="kpi-value" id="kpiEnd">—</div>
                <div class="kpi-foot">Period closing value in KES</div>
            </div>

            <div class="kpi-card accent-purple">
                <div class="kpi-head">
                    <div class="kpi-label">Net Movement</div>
                    <div class="kpi-icon icon-purple" id="kpiMovIcon"><i class="bi bi-arrow-left-right"></i></div>
                </div>
                <div class="kpi-value" id="kpiMov">—</div>
                <div class="kpi-foot" id="kpiMovFoot">Closing minus opening</div>
            </div>

            <div class="kpi-card accent-green">
                <div class="kpi-head">
                    <div class="kpi-label">Gaining Branches</div>
                    <div class="kpi-icon" style="background: rgba(34,197,94,.12); color:#15803d;">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <div class="kpi-value gain" id="kpiGainers">—</div>
                <div class="kpi-foot" id="kpiTopGainer">—</div>
            </div>

            <div class="kpi-card accent-red">
                <div class="kpi-head">
                    <div class="kpi-label">Losing Branches</div>
                    <div class="kpi-icon" style="background: rgba(239,68,68,.10); color:#b91c1c;">
                        <i class="bi bi-graph-down-arrow"></i>
                    </div>
                </div>
                <div class="kpi-value loss" id="kpiLosers">—</div>
                <div class="kpi-foot" id="kpiTopLoser">—</div>
            </div>
        </div>

        {{-- Charts / Insights --}}
        <div class="content-grid">
            <div class="section-card">
                <div class="loading-overlay" id="loadMovChart">
                    <div class="spinner"></div>
                </div>

                <div class="section-header">
                    <div>
                        <div class="section-title">
                            <div class="section-title-icon"><i class="bi bi-bar-chart-fill"></i></div>
                            Branch Movement Overview
                        </div>
                        <div class="section-subtext">Horizontal view ranked by absolute movement</div>
                    </div>
                </div>

                <div class="section-body">
                    <div class="chart-box">
                        <canvas id="movChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="side-stack">
                <div class="section-card">
                    <div class="loading-overlay" id="loadDonut">
                        <div class="spinner"></div>
                    </div>

                    <div class="section-header">
                        <div class="section-title">
                            <div class="section-title-icon icon-teal"><i class="bi bi-pie-chart-fill"></i></div>
                            Branch Direction Mix
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="donut-wrap">
                            <canvas id="donutChart"></canvas>
                            <div class="donut-center">
                                <div class="donut-center-pct" id="donutPct">—</div>
                                <div class="donut-center-label">Gaining</div>
                            </div>
                        </div>

                        <div class="donut-legend" id="donutLegend"></div>
                    </div>
                </div>

                <div class="mini-grid">
                    <div class="insight-card">
                        <div class="insight-label">Movement Snapshot</div>
                        <div class="insight-value" id="insightNet">—</div>
                        <div class="insight-note" id="insightNetNote">Waiting for movement data</div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-label">Branch Highlights</div>
                        <div class="insight-list">
                            <div class="insight-row">
                                <span>Top Gainer</span>
                                <span id="insightTopGainer">—</span>
                            </div>
                            <div class="insight-row">
                                <span>Top Loser</span>
                                <span id="insightTopLoser">—</span>
                            </div>
                            <div class="insight-row">
                                <span>Direction Mix</span>
                                <span id="insightMix">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Panels --}}
        <div class="panel-wrap">
            {{-- Summary Panel --}}
            <div class="panel active" id="panel-summary">
                <div class="section-card">
                    <div class="loading-overlay" id="loadSummary">
                        <div class="spinner"></div>
                    </div>

                    <div class="section-header">
                        <div>
                            <div class="section-title">
                                <div class="section-title-icon icon-purple"><i class="bi bi-table"></i></div>
                                Branch Balance Summary
                            </div>
                            <div class="section-subtext">Opening, closing, movement and movement percentage</div>
                        </div>

                        <button class="btn-soft" id="btnExportSummaryPanel">
                            <i class="bi bi-download"></i>
                            Export
                        </button>
                    </div>

                    <div class="tbl-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th style="text-align:right;">Opening (KES)</th>
                                    <th style="text-align:right;">Closing (KES)</th>
                                    <th style="text-align:right;">Movement (KES)</th>
                                    <th style="text-align:right;">Movement %</th>
                                    <th style="text-align:center;">Direction</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTbody">
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Movers Panel --}}
            <div class="panel" id="panel-movers">
                <div class="section-card">
                    <div class="loading-overlay" id="loadMovers">
                        <div class="spinner"></div>
                    </div>

                    <div class="section-header">
                        <div>
                            <div class="section-title">
                                <div class="section-title-icon icon-gold"><i class="bi bi-trophy-fill"></i></div>
                                Top Branch Movers
                            </div>
                            <div class="section-subtext">Best and weakest performing branches by movement</div>
                        </div>

                        <button class="btn-soft" id="btnExportMoversPanel">
                            <i class="bi bi-download"></i>
                            Export
                        </button>
                    </div>

                    <div class="split-grid">
                        <div class="split-side">
                            <div class="split-title gain">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                                Top Gainers
                            </div>

                            <div class="tbl-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Branch</th>
                                            <th style="text-align:right;">Movement</th>
                                        </tr>
                                    </thead>
                                    <tbody id="gainersTbody">
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="split-side">
                            <div class="split-title loss">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                                Top Losers
                            </div>

                            <div class="tbl-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Branch</th>
                                            <th style="text-align:right;">Movement</th>
                                        </tr>
                                    </thead>
                                    <tbody id="losersTbody">
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CIF Panel --}}
            <div class="panel" id="panel-cif">
                <div class="section-card">
                    <div class="loading-overlay" id="loadCif">
                        <div class="spinner"></div>
                    </div>

                    <div class="section-header">
                        <div>
                            <div class="section-title">
                                <div class="section-title-icon icon-pink"><i class="bi bi-people-fill"></i></div>
                                Top CIF Movers by Branch
                            </div>
                            <div class="section-subtext">Search branch, switch direction and export customer movement</div>
                        </div>

                        <div class="panel-tools">
                            <select id="cifLimitSelect" class="filter-control sm">
                                <option value="10" selected>Top 10</option>
                                <option value="20">Top 20</option>
                                <option value="50">Top 50</option>
                            </select>

                            <button class="btn-soft" id="btnExportCif">
                                <i class="bi bi-download"></i>
                                Export CIF
                            </button>

                            <div class="dir-toggle">
                                <button type="button" class="dir-btn gain active" id="dirGain" data-dir="GAIN">
                                    <i class="bi bi-arrow-up"></i> Gainers
                                </button>
                                <button type="button" class="dir-btn loss" id="dirLoss" data-dir="LOSS">
                                    <i class="bi bi-arrow-down"></i> Losers
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="branch-filter-bar">
                        <input type="text" id="branchSearch" class="filter-control search"
                            placeholder="Search branch code or name...">
                        <div class="section-subtext" id="branchCountText">Loading branches...</div>
                    </div>

                    <div class="branch-tabs" id="branchTabs">
                        <span class="text-muted" style="font-size: .78rem;">Loading branches...</span>
                    </div>

                    <div class="tbl-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>CIF</th>
                                    <th>Customer Name</th>
                                    <th style="text-align:right;">Opening (KES)</th>
                                    <th style="text-align:right;">Closing (KES)</th>
                                    <th style="text-align:right;">Movement (KES)</th>
                                    <th style="text-align:right;">Movement %</th>
                                </tr>
                            </thead>
                            <tbody id="cifTbody">
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">Select a branch above</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
        (function() {
            const state = {
                start: null,
                end: null,
                branch: null,
                cifBranch: null,
                cifDir: 'GAIN',
                currentView: 'summary',
                chartLimit: 20,
                cifLimit: 10,
                gainCount: 0,
                lossCount: 0,
                summaryRows: [],
                gainers: [],
                losers: [],
                branches: [],
                cifRows: [],
                chartPayload: {
                    labels: [],
                    movements: [],
                    colors: []
                }
            };

            let movChartInst = null;
            let donutInst = null;
            let toastTimer = null;

            const els = {};

            const cacheEls = () => {
                [
                    'toastHolder',
                    'periodSelect',
                    'chartLimitSelect',
                    'cifLimitSelect',
                    'btnRefresh',
                    'btnExportSummary',
                    'btnExportSummaryPanel',
                    'btnExportMovers',
                    'btnExportMoversPanel',
                    'btnExportCif',
                    'heroPeriod',
                    'heroSummary',
                    'lastRefreshed',
                    'kpiStart',
                    'kpiEnd',
                    'kpiMov',
                    'kpiMovIcon',
                    'kpiMovFoot',
                    'kpiGainers',
                    'kpiLosers',
                    'kpiTopGainer',
                    'kpiTopLoser',
                    'insightNet',
                    'insightNetNote',
                    'insightTopGainer',
                    'insightTopLoser',
                    'insightMix',
                    'donutPct',
                    'donutLegend',
                    'summaryTbody',
                    'gainersTbody',
                    'losersTbody',
                    'branchSelect',
                    'branchTabs',
                    'branchSearch',
                    'branchCountText',
                    'cifTbody',
                    'dirGain',
                    'dirLoss'
                ].forEach(id => {
                    els[id] = document.getElementById(id);
                });
            };

            const parseNum = value => {
                const n = parseFloat(value);
                return Number.isFinite(n) ? n : 0;
            };

            const fmt = n => {
                const v = parseNum(n);
                const a = Math.abs(v);
                const sign = v < 0 ? '-' : '';

                if (a >= 1e9) return sign + 'KES ' + (a / 1e9).toFixed(2) + 'B';
                if (a >= 1e6) return sign + 'KES ' + (a / 1e6).toFixed(2) + 'M';
                if (a >= 1e3) return sign + 'KES ' + (a / 1e3).toFixed(1) + 'K';

                return sign + 'KES ' + a.toFixed(0);
            };

            const fmtFull = n => {
                const v = parseNum(n);

                return 'KES ' + v.toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            const fmtSigned = n => {
                const v = parseNum(n);
                return (v >= 0 ? '+' : '') + fmt(v);
            };

            const movementPercent = (opening, movement) => {
                const start = parseNum(opening);
                const mv = parseNum(movement);

                if (start === 0) return null;

                return (mv / Math.abs(start)) * 100;
            };

            const fmtPercent = value => {
                if (value === null || value === undefined || !Number.isFinite(value)) {
                    return '—';
                }

                return (value >= 0 ? '+' : '') + value.toFixed(2) + '%';
            };

            const escapeHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const escapeAttr = value => escapeHtml(value).replaceAll('`', '&#096;');

        const load = (id, on) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('active', !!on);
        };

        const setText = (el, value) => {
            if (el) el.textContent = value;
        };

        const url = (path, params = {}) => {
            const q = {
                start: state.start || '',
                end: state.end || '',
                ...params
            };
            if (state.branch) q.branch = state.branch;
            return `/finance/branch-movers/${path}?` + new URLSearchParams(q);
        };

        const fetchJson = async (path, params = {}) => {
            const response = await fetch(url(path, params), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            return await response.json();
        };

        const showToast = (message, type = 'info') => {
            if (!els.toastHolder) return;

            clearTimeout(toastTimer);

            const icon = type === 'error' ?
                'bi-exclamation-triangle-fill' :
                type === 'success' ?
                'bi-check-circle-fill' :
                'bi-info-circle-fill';

            els.toastHolder.innerHTML = `
                            <div class="dash-toast ${escapeAttr(type)}">
                                <i class="bi ${icon}"></i>
                                <span>${escapeHtml(message)}</span>
                            </div>
                        `;

            toastTimer = setTimeout(() => {
                els.toastHolder.innerHTML = '';
            }, 4500);
        };

        const setRefreshState = loading => {
            if (!els.btnRefresh) return;

            els.btnRefresh.disabled = loading;
            els.btnRefresh.innerHTML = loading ?
                '<i class="bi bi-arrow-repeat"></i> Loading' :
                '<i class="bi bi-arrow-clockwise"></i> Refresh';
        };

        const selectedPeriodLabel = () => {
            const fmtDate = d => new Date(d).toLocaleDateString('en-KE', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            if (!state.start || !state.end) return '—';

            return `${fmtDate(state.start)} → ${fmtDate(state.end)}`;
        };

        const filenameStamp = () => {
            const clean = value => String(value || '')
                .replaceAll('-', '')
                .replaceAll('/', '')
                .replaceAll(' ', '');

            return `${clean(state.start)}_${clean(state.end)}`;
        };

        document.addEventListener('DOMContentLoaded', () => {
            cacheEls();
            initializeState();
            bindEvents();

            Chart.defaults.font.family =
                'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
            Chart.defaults.font.size = 11;

            startHeroClock();
            initHeroParticles();

            loadAll();
        });

        function startHeroClock() {
            const clockEl = document.getElementById('heroClock');
            if (!clockEl) return;

            const tick = () => {
                clockEl.textContent = new Date().toLocaleTimeString('en-KE', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            };

            tick();
            setInterval(tick, 1000);
        }

        function initHeroParticles() {
            const canvas = document.getElementById('heroParticles');
            if (!canvas || !canvas.getContext) return;

            const ctx = canvas.getContext('2d');
            const header = canvas.closest('.dashboard-header');
            const colors = ['rgba(255,255,255,0.8)', 'rgba(190,214,0,0.85)', 'rgba(255,255,255,0.5)'];

            let width = 0;
            let height = 0;
            let particles = [];
            let mouseX = null;
            let mouseY = null;
            let rafId = null;

            const rand = (min, max) => Math.random() * (max - min) + min;

            const makeParticle = () => ({
                x: rand(0, width),
                y: rand(0, height),
                r: rand(1, 2.6),
                vx: rand(-0.15, 0.15),
                vy: rand(-0.35, -0.08),
                color: colors[Math.floor(Math.random() * colors.length)],
                twinkle: rand(0, Math.PI * 2)
            });

            const resize = () => {
                const rect = header.getBoundingClientRect();
                width = canvas.width = rect.width;
                height = canvas.height = rect.height;

                const count = Math.max(24, Math.min(60, Math.round((width * height) / 12000)));
                particles = Array.from({
                    length: count
                }, makeParticle);
            };

            const step = () => {
                ctx.clearRect(0, 0, width, height);

                particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.twinkle += 0.04;

                    if (mouseX !== null && mouseY !== null) {
                        const dx = p.x - mouseX;
                        const dy = p.y - mouseY;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < 70 && dist > 0) {
                            p.x += (dx / dist) * 0.6;
                            p.y += (dy / dist) * 0.6;
                        }
                    }

                    if (p.y < -10) {
                        p.y = height + 10;
                        p.x = rand(0, width);
                    }
                    if (p.x < -10) p.x = width + 10;
                    if (p.x > width + 10) p.x = -10;

                    const alpha = 0.4 + Math.sin(p.twinkle) * 0.3;

                    ctx.beginPath();
                    ctx.globalAlpha = Math.max(0.15, alpha);
                    ctx.fillStyle = p.color;
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fill();
                });

                ctx.globalAlpha = 1;
                rafId = requestAnimationFrame(step);
            };

            header.addEventListener('mousemove', e => {
                const rect = header.getBoundingClientRect();
                mouseX = e.clientX - rect.left;
                mouseY = e.clientY - rect.top;
            });

            header.addEventListener('mouseleave', () => {
                mouseX = null;
                mouseY = null;
            });

            window.addEventListener('resize', resize);

            resize();
            if (rafId) cancelAnimationFrame(rafId);
            step();
        }

        function initializeState() {
            if (els.periodSelect && els.periodSelect.options.length) {
                const [s, e] = els.periodSelect.options[0].value.split('|');
                state.start = s;
                state.end = e;
            }

            if (els.chartLimitSelect) {
                state.chartLimit = parseInt(els.chartLimitSelect.value || 20, 10);
            }

            if (els.cifLimitSelect) {
                state.cifLimit = parseInt(els.cifLimitSelect.value || 10, 10);
            }
        }

        function bindEvents() {
            if (els.periodSelect) {
                els.periodSelect.addEventListener('change', () => {
                    const [s, e] = els.periodSelect.value.split('|');
                    state.start = s;
                    state.end = e;
                    state.branch = null;
                    state.cifBranch = null;
                    if (els.branchSelect) els.branchSelect.value = '';
                    loadAll();
                });
            }

            if (els.branchSelect) {
                els.branchSelect.addEventListener('change', () => {
                    state.branch = els.branchSelect.value || null;
                    state.cifBranch = null;
                    loadAll();
                });
            }

            if (els.chartLimitSelect) {
                els.chartLimitSelect.addEventListener('change', () => {
                    state.chartLimit = parseInt(els.chartLimitSelect.value || 20, 10);
                    renderMovementChart();
                });
            }

            if (els.cifLimitSelect) {
                els.cifLimitSelect.addEventListener('change', () => {
                    state.cifLimit = parseInt(els.cifLimitSelect.value || 10, 10);
                    loadCif();
                });
            }

            if (els.btnRefresh) {
                els.btnRefresh.addEventListener('click', loadAll);
            }

            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    setView(btn.dataset.view, btn);
                });
            });

            document.querySelectorAll('.dir-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    setCifDir(btn.dataset.dir);
                });
            });

            if (els.branchSearch) {
                els.branchSearch.addEventListener('input', renderBranchTabs);
            }

            if (els.branchTabs) {
                els.branchTabs.addEventListener('click', event => {
                    const btn = event.target.closest('.branch-tab');
                    if (!btn) return;

                    selectBranch(btn.dataset.code, btn);
                });
            }

            [
                els.btnExportSummary,
                els.btnExportSummaryPanel
            ].forEach(btn => {
                if (btn) btn.addEventListener('click', exportSummary);
            });

            [
                els.btnExportMovers,
                els.btnExportMoversPanel
            ].forEach(btn => {
                if (btn) btn.addEventListener('click', exportMovers);
            });

            if (els.btnExportCif) {
                els.btnExportCif.addEventListener('click', exportCif);
            }
        }

        async function loadAll() {
            if (!state.start || !state.end) {
                setText(els.heroSummary, 'No period available');
                showToast('No reporting period found for this dashboard.', 'error');
                return;
            }

            setRefreshState(true);
            setText(els.heroPeriod, selectedPeriodLabel());
            setText(els.heroSummary, 'Refreshing dashboard');

            const jobs = await Promise.allSettled([
                loadKpis(),
                loadMovChart(),
                loadSummary(),
                loadTopMovers(),
                loadBranchTabs()
            ]);

            const failed = jobs.some(job => job.status === 'rejected');

            if (failed) {
                showToast('Some dashboard sections failed to load. Please refresh again.', 'error');
            } else {
                const now = new Date().toLocaleString('en-KE', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                setText(els.lastRefreshed, `Updated ${now}`);
            }

            setRefreshState(false);
        }

        async function loadKpis() {
            try {
                const d = await fetchJson('kpis');

                const start = parseNum(d.start_balance);
                const end = parseNum(d.end_balance);
                const movement = parseNum(d.movement);
                const gainCount = parseInt(d.gain_count || 0, 10);
                const lossCount = parseInt(d.loss_count || 0, 10);

                state.gainCount = gainCount;
                state.lossCount = lossCount;

                setText(els.kpiStart, fmt(start));
                setText(els.kpiEnd, fmt(end));

                if (els.kpiMov) {
                    els.kpiMov.textContent = fmtSigned(movement);
                    els.kpiMov.className = 'kpi-value ' + (movement >= 0 ? 'gain' : 'loss');
                }

                if (els.kpiMovIcon) {
                    els.kpiMovIcon.innerHTML = movement >= 0 ?
                        '<i class="bi bi-arrow-up-right-circle-fill"></i>' :
                        '<i class="bi bi-arrow-down-right-circle-fill"></i>';
                }

                setText(
                    els.kpiMovFoot,
                    movement >= 0 ? 'Net positive movement' : 'Net negative movement'
                );

                setText(els.kpiGainers, gainCount);
                setText(els.kpiLosers, lossCount);

                setText(
                    els.kpiTopGainer,
                    d.top_gainer ?
                    `${d.top_gainer.name} · ${fmtSigned(d.top_gainer.movement)}` :
                    'No gain data'
                );

                setText(
                    els.kpiTopLoser,
                    d.top_loser ?
                    `${d.top_loser.name} · ${fmtSigned(d.top_loser.movement)}` :
                    'No loss data'
                );

                setText(els.insightNet, fmtSigned(movement));
                setText(
                    els.insightNetNote,
                    movement >= 0 ?
                    'Branches closed stronger than they opened' :
                    'Closing balances fell below opening balances'
                );

                setText(els.insightTopGainer, d.top_gainer ? d.top_gainer.name : '—');
                setText(els.insightTopLoser, d.top_loser ? d.top_loser.name : '—');
                setText(els.insightMix, `${gainCount} up / ${lossCount} down`);
                setText(els.heroSummary, `${gainCount} gaining • ${lossCount} losing`);

                renderDonut(gainCount, lossCount);
            } catch (e) {
                console.error(e);
                showToast('Failed to load KPI data.', 'error');
                throw e;
            }
        }

        async function loadMovChart() {
            load('loadMovChart', true);

            try {
                const d = await fetchJson('movement-chart');

                state.chartPayload = {
                    labels: d.labels || [],
                    movements: d.movements || [],
                    colors: d.colors || []
                };

                state.gainCount = state.chartPayload.movements
                    .filter(v => parseNum(v) > 0).length;

                state.lossCount = state.chartPayload.movements
                    .filter(v => parseNum(v) < 0).length;

                renderMovementChart();
                renderDonut(state.gainCount, state.lossCount);
            } catch (e) {
                console.error(e);
                showToast('Failed to load movement chart.', 'error');
                throw e;
            } finally {
                load('loadMovChart', false);
            }
        }

        function renderMovementChart() {
            const rows = state.chartPayload.labels.map((label, index) => {
                const movement = parseNum(state.chartPayload.movements[index]);
                const color = state.chartPayload.colors[index] || (movement >= 0 ? '#BED600' : '#0082BB');

                return {
                    label,
                    movement,
                    color
                };
            });

            rows.sort((a, b) => Math.abs(b.movement) - Math.abs(a.movement));

            const selectedRows = state.chartLimit > 0 ?
                rows.slice(0, state.chartLimit) :
                rows;

            if (movChartInst) movChartInst.destroy();

            const canvas = document.getElementById('movChart');
            if (!canvas) return;

            movChartInst = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: selectedRows.map(r => r.label),
                    datasets: [{
                        data: selectedRows.map(r => r.movement),
                        backgroundColor: selectedRows.map(r => r.color),
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 18,
                        maxBarThickness: 24
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            callbacks: {
                                title: items => items.length ? items[0].label : '',
                                label: ctx => ' ' + fmtSigned(ctx.raw)
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 8,
                            right: 10,
                            bottom: 0,
                            left: 0
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: '#edf3f7'
                            },
                            ticks: {
                                color: '#52606d',
                                callback: v => {
                                    const a = Math.abs(v);
                                    if (a >= 1e9) return (v / 1e9).toFixed(1) + 'B';
                                    if (a >= 1e6) return (v / 1e6).toFixed(0) + 'M';
                                    if (a >= 1e3) return (v / 1e3).toFixed(0) + 'K';
                                    return v;
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#52606d',
                                font: {
                                    size: 10,
                                    weight: '700'
                                },
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label && label.length > 24 ?
                                        label.substring(0, 24) + '…' :
                                        label;
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderDonut(gainCount, lossCount) {
            load('loadDonut', true);

            const total = gainCount + lossCount;
            const pct = total > 0 ? Math.round((gainCount / total) * 100) : 0;

            setText(els.donutPct, pct + '%');

            if (donutInst) donutInst.destroy();

            const donutCanvas = document.getElementById('donutChart');

            if (donutCanvas) {
                donutInst = new Chart(donutCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Gaining', 'Losing'],
                        datasets: [{
                            data: [gainCount, lossCount],
                            backgroundColor: ['#BED600', '#0082BB'],
                            borderColor: '#ffffff',
                            borderWidth: 4,
                            hoverOffset: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: c => {
                                        const p = total > 0 ? Math.round((c.raw / total) * 100) : 0;
                                        return ` ${c.label}: ${c.raw} branches (${p}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            if (els.donutLegend) {
                els.donutLegend.innerHTML = [{
                        label: 'Gaining Branches',
                        count: gainCount,
                        color: '#BED600'
                    },
                    {
                        label: 'Losing Branches',
                        count: lossCount,
                        color: '#0082BB'
                    }
                ].map(item => {
                    const p = total > 0 ? Math.round((item.count / total) * 100) : 0;

                    return `
                                    <div class="legend-item">
                                        <div class="legend-left">
                                            <span class="legend-dot" style="background:${item.color};"></span>
                                            <span>${escapeHtml(item.label)}</span>
                                        </div>
                                        <div class="legend-value">${item.count} (${p}%)</div>
                                    </div>
                                `;
                }).join('');
            }

            load('loadDonut', false);
        }

        async function loadSummary() {
            load('loadSummary', true);

            try {
                const rows = await fetchJson('summary');
                state.summaryRows = Array.isArray(rows) ? rows : [];

                renderSummary();
            } catch (e) {
                console.error(e);
                if (els.summaryTbody) els.summaryTbody.innerHTML = errRow(6);
                showToast('Failed to load branch summary.', 'error');
                throw e;
            } finally {
                load('loadSummary', false);
            }
        }

        function renderSummary() {
            if (!els.summaryTbody) return;

            const rows = state.summaryRows;

            if (!rows.length) {
                els.summaryTbody.innerHTML = emptyRow(6);
                return;
            }

            els.summaryTbody.innerHTML = rows.map(r => {
                const mv = parseNum(r.movement);
                const pct = movementPercent(r.start_balance, mv);
                const isTotal = r.group_key === 'ALL';

                let badge = '<span class="badge-neutral"><i class="bi bi-dash"></i> Flat</span>';

                if (mv > 0) {
                    badge =
                        `<span class="badge-gain"><i class="bi bi-arrow-up"></i> ${fmtSigned(mv)}</span>`;
                } else if (mv < 0) {
                    badge =
                        `<span class="badge-loss"><i class="bi bi-arrow-down"></i> ${fmtSigned(mv)}</span>`;
                }

                return `
                                <tr class="${isTotal ? 'total-row' : ''}">
                                    <td class="text-strong">${escapeHtml(r.group_name)}</td>
                                    <td class="num">${fmt(r.start_balance)}</td>
                                    <td class="num">${fmt(r.end_balance)}</td>
                                    <td class="num">${fmtSigned(mv)}</td>
                                    <td class="num">${fmtPercent(pct)}</td>
                                    <td style="text-align:center;">${badge}</td>
                                </tr>
                            `;
            }).join('');
        }

        async function loadTopMovers() {
            load('loadMovers', true);

            try {
                const d = await fetchJson('top-movers');

                state.gainers = Array.isArray(d.gainers) ? d.gainers : [];
                state.losers = Array.isArray(d.losers) ? d.losers : [];

                renderTopMovers();
            } catch (e) {
                console.error(e);

                if (els.gainersTbody) els.gainersTbody.innerHTML = errRow(3);
                if (els.losersTbody) els.losersTbody.innerHTML = errRow(3);

                showToast('Failed to load top movers.', 'error');
                throw e;
            } finally {
                load('loadMovers', false);
            }
        }

        function renderTopMovers() {
            const buildRows = (arr, isGain) => {
                if (!arr || !arr.length) {
                    return `<tr><td colspan="3" class="text-center py-3 text-muted">No data</td></tr>`;
                }

                return arr.map((r, i) => `
                                <tr>
                                    <td><span class="${i < 3 ? 'rank-circle top3' : 'rank-circle'}">${i + 1}</span></td>
                                    <td class="text-strong">${escapeHtml(r.group_name)}</td>
                                    <td class="num">
                                        <span class="${isGain ? 'badge-gain' : 'badge-loss'}">${fmtSigned(parseNum(r.movement))}</span>
                                    </td>
                                </tr>
                            `).join('');
            };

            if (els.gainersTbody) {
                els.gainersTbody.innerHTML = buildRows(state.gainers, true);
            }

            if (els.losersTbody) {
                els.losersTbody.innerHTML = buildRows(state.losers, false);
            }
        }

        async function loadBranchTabs() {
            try {
                const rows = await fetchJson('branches');
                state.branches = Array.isArray(rows) ? rows : [];

                if (!state.branches.length) {
                    if (els.branchTabs) {
                        els.branchTabs.innerHTML =
                            '<span class="text-muted" style="font-size:.78rem;">No branches available</span>';
                    }

                    setText(els.branchCountText, 'No branches available');
                    return;
                }

                // Populate the branch dropdown (preserving current selection)
                if (els.branchSelect) {
                    const currentVal = state.branch || '';
                    els.branchSelect.innerHTML = '<option value="">All Branches</option>';
                    state.branches.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.group_key;
                        opt.textContent = `${r.group_key} — ${r.group_name}`;
                        if (r.group_key === currentVal) opt.selected = true;
                        els.branchSelect.appendChild(opt);
                    });
                }

                if (!state.cifBranch || !state.branches.find(r => String(r.group_key) === String(state
                        .cifBranch))) {
                    state.cifBranch = state.branches[0].group_key;
                }

                renderBranchTabs();
                loadCif();
            } catch (e) {
                console.error(e);

                if (els.branchTabs) {
                    els.branchTabs.innerHTML =
                        '<span class="text-danger" style="font-size:.78rem;">Failed to load branches</span>';
                }

                setText(els.branchCountText, 'Failed to load branches');
                showToast('Failed to load branch list.', 'error');
                throw e;
            }
        }

        function renderBranchTabs() {
            if (!els.branchTabs) return;

            const query = (els.branchSearch ? els.branchSearch.value : '').trim().toLowerCase();

            const filtered = state.branches.filter(r => {
                const key = String(r.group_key ?? '').toLowerCase();
                const name = String(r.group_name ?? '').toLowerCase();

                return !query || key.includes(query) || name.includes(query);
            });

            setText(
                els.branchCountText,
                `${filtered.length} of ${state.branches.length} branches shown`
            );

            if (!filtered.length) {
                els.branchTabs.innerHTML =
                    '<span class="text-muted" style="font-size:.78rem;">No matching branch found</span>';
                return;
            }

            if (!filtered.find(r => String(r.group_key) === String(state.cifBranch))) {
                state.cifBranch = filtered[0].group_key;
                loadCif();
            }

            els.branchTabs.innerHTML = filtered.map(r => {
                const code = String(r.group_key ?? '');
                const name = String(r.group_name ?? code);
                const isActive = String(code) === String(state.cifBranch);

                return `
                                <button type="button"
                                    class="branch-tab ${isActive ? 'active' : ''}"
                                    data-code="${escapeAttr(code)}"
                                    title="${escapeAttr(name)}">
                                    ${escapeHtml(code)}
                                </button>
                            `;
            }).join('');
        }

        async function loadCif() {
            if (!state.cifBranch) return;

            load('loadCif', true);

            try {
                const rows = await fetchJson('cif-movers', {
                    branch: state.cifBranch,
                    direction: state.cifDir,
                    limit: state.cifLimit
                });

                state.cifRows = Array.isArray(rows) ? rows : [];
                renderCif();
            } catch (e) {
                console.error(e);

                if (els.cifTbody) {
                    els.cifTbody.innerHTML = errRow(7);
                }

                showToast('Failed to load CIF movers.', 'error');
                throw e;
            } finally {
                load('loadCif', false);
            }
        }

        function renderCif() {
            if (!els.cifTbody) return;

            if (!state.cifRows.length) {
                els.cifTbody.innerHTML = emptyRow(7);
                return;
            }

            els.cifTbody.innerHTML = state.cifRows.map(r => {
                const mv = parseNum(r.movement);
                const pct = movementPercent(r.start_balance, mv);
                const rank = parseInt(r.rank || 0, 10);

                return `
                                <tr>
                                    <td><span class="${rank <= 3 ? 'rank-circle top3' : 'rank-circle'}">${rank || '—'}</span></td>
                                    <td style="font-family:'DM Mono', monospace; font-size:.75rem;">${escapeHtml(r.cif)}</td>
                                    <td class="text-strong"
                                        style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                        title="${escapeAttr(r.customer_name)}">
                                        ${escapeHtml(r.customer_name)}
                                    </td>
                                    <td class="num">${fmt(r.start_balance)}</td>
                                    <td class="num">${fmt(r.end_balance)}</td>
                                    <td class="num">
                                        <span class="${mv >= 0 ? 'badge-gain' : 'badge-loss'}">${fmtSigned(mv)}</span>
                                    </td>
                                    <td class="num">${fmtPercent(pct)}</td>
                                </tr>
                            `;
            }).join('');
        }

        function setView(view, el) {
            state.currentView = view;

            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            if (el) el.classList.add('active');

            document.querySelectorAll('.panel').forEach(panel => panel.classList.remove('active'));

            const panel = document.getElementById(`panel-${view}`);
            if (panel) panel.classList.add('active');
        }

        function selectBranch(code, el) {
            state.cifBranch = code;

            document.querySelectorAll('.branch-tab').forEach(tab => tab.classList.remove('active'));
            if (el) el.classList.add('active');

            loadCif();
        }

        function setCifDir(dir) {
            state.cifDir = dir;

            if (els.dirGain) els.dirGain.classList.toggle('active', dir === 'GAIN');
            if (els.dirLoss) els.dirLoss.classList.toggle('active', dir === 'LOSS');

            loadCif();
        }

        const emptyRow = cols => `
                        <tr>
                            <td colspan="${cols}">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>No data found</p>
                                </div>
                            </td>
                        </tr>
                    `;

        const errRow = cols => `
                        <tr>
                            <td colspan="${cols}" class="text-center py-3 text-danger" style="font-size:.8rem;">
                                Failed to load data
                            </td>
                        </tr>
                    `;

        const csvEscape = value => {
            const text = String(value ?? '');
            return `"${text.replaceAll('"', '""')}"`;
        };

        function downloadCsv(filename, rows, columns) {
            if (!rows || !rows.length) {
                showToast('No data available to export.', 'error');
                return;
            }

            const header = columns.map(col => csvEscape(col.header)).join(',');

            const body = rows.map(row => {
                return columns.map(col => {
                    const value = typeof col.value === 'function' ?
                        col.value(row) :
                        row[col.value];

                    return csvEscape(value);
                }).join(',');
            }).join('\n');

            const csv = header + '\n' + body;
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });

            const link = document.createElement('a');
            const objectUrl = URL.createObjectURL(blob);

            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();

            URL.revokeObjectURL(objectUrl);
            link.remove();

            showToast('Export downloaded successfully.', 'success');
        }

        function exportSummary() {
            downloadCsv(
                `branch_summary_${filenameStamp()}.csv`,
                state.summaryRows,
                [{
                        header: 'Branch Code',
                        value: 'group_key'
                    },
                    {
                        header: 'Branch Name',
                        value: 'group_name'
                    },
                    {
                        header: 'Opening Balance',
                        value: row => parseNum(row.start_balance).toFixed(2)
                    },
                    {
                        header: 'Closing Balance',
                        value: row => parseNum(row.end_balance).toFixed(2)
                    },
                    {
                        header: 'Movement',
                        value: row => parseNum(row.movement).toFixed(2)
                    },
                    {
                        header: 'Movement %',
                        value: row => {
                            const pct = movementPercent(row.start_balance, row.movement);
                            return pct === null ? '' : pct.toFixed(2);
                        }
                    },
                    {
                        header: 'Direction',
                        value: row => parseNum(row.movement) > 0 ? 'GAIN' : parseNum(row.movement) < 0 ?
                            'LOSS' : 'FLAT'
                    }
                ]
            );
        }

        function exportMovers() {
            const rows = [
                ...state.gainers.map((row, index) => ({
                    ...row,
                    rank: index + 1,
                    direction: 'GAIN'
                })),
                ...state.losers.map((row, index) => ({
                    ...row,
                    rank: index + 1,
                    direction: 'LOSS'
                }))
            ];

            downloadCsv(
                `branch_top_movers_${filenameStamp()}.csv`,
                rows,
                [{
                        header: 'Direction',
                        value: 'direction'
                    },
                    {
                        header: 'Rank',
                        value: 'rank'
                    },
                    {
                        header: 'Branch Code',
                        value: 'group_key'
                    },
                    {
                        header: 'Branch Name',
                        value: 'group_name'
                    },
                    {
                        header: 'Movement',
                        value: row => parseNum(row.movement).toFixed(2)
                    }
                ]
            );
        }

        function exportCif() {
            downloadCsv(
                `cif_movers_${state.cifBranch}_${state.cifDir.toLowerCase()}_${filenameStamp()}.csv`,
                    state.cifRows,
                    [{
                            header: 'Branch',
                            value: () => state.cifBranch
                        },
                        {
                            header: 'Direction',
                            value: () => state.cifDir
                        },
                        {
                            header: 'Rank',
                            value: 'rank'
                        },
                        {
                            header: 'CIF',
                            value: 'cif'
                        },
                        {
                            header: 'Customer Name',
                            value: 'customer_name'
                        },
                        {
                            header: 'Opening Balance',
                            value: row => parseNum(row.start_balance).toFixed(2)
                        },
                        {
                            header: 'Closing Balance',
                            value: row => parseNum(row.end_balance).toFixed(2)
                        },
                        {
                            header: 'Movement',
                            value: row => parseNum(row.movement).toFixed(2)
                        },
                        {
                            header: 'Movement %',
                            value: row => {
                                const pct = movementPercent(row.start_balance, row.movement);
                                return pct === null ? '' : pct.toFixed(2);
                            }
                        }
                    ]
                );
            }
        })();
    </script>
@endpush
