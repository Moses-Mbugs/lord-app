@extends('layouts.finance.template')

@section('title', 'Branch Dashboard')

@push('styles')
    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-dark-blue: #004d6e;
            --eco-deep-blue: #00364f;
            --eco-green: #639922;
            --eco-bg: #eef4f8;
            --eco-card: #ffffff;
            --eco-text: #1e293b;
            --eco-muted: #64748b;
            --eco-border: rgba(0, 91, 130, 0.10);
            --eco-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
            --eco-soft-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(0, 130, 187, 0.08), transparent 30%),
                linear-gradient(180deg, #f4f8fb 0%, var(--eco-bg) 100%);
        }

        .bd-page {
            padding: 10px 14px 26px;
        }

        /* =========================
                       HERO
                    ========================= */
        .bd-hero {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            background:
                radial-gradient(circle at top right, rgba(99, 153, 34, 0.35), transparent 30%),
                linear-gradient(135deg, var(--eco-deep-blue), var(--eco-dark-blue) 48%, #006e9e);
            padding: 22px 22px 16px;
            margin-bottom: 12px;
            box-shadow: 0 22px 45px rgba(0, 77, 110, 0.23);
            color: #fff;
        }

        #bd-particles {
            position: absolute;
            inset: 0;
            z-index: 0;
            opacity: 0.36;
            pointer-events: none;
        }

        .bd-hero::after {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            right: -120px;
            top: -150px;
            background: rgba(255, 255, 255, 0.08);
            z-index: 0;
        }

        .bd-hero-content {
            position: relative;
            z-index: 1;
        }

        .bd-hero-main {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .bd-hero-title-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 280px;
        }

        .bd-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
        }

        .bd-hero-eyebrow {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.68);
            margin-bottom: 4px;
        }

        .bd-hero-title {
            font-size: clamp(1.35rem, 2vw, 2rem);
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -0.04em;
            margin: 0;
        }

        .bd-hero-sub {
            margin-top: 6px;
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.72);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bd-hero-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bd-hero-pill,
        .bd-hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 8px 13px;
            font-size: 0.74rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            white-space: nowrap;
        }

        .bd-hero-pill {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
        }

        .bd-hero-btn {
            background: rgba(255, 255, 255, 0.95);
            color: var(--eco-dark-blue);
            border: 1px solid rgba(255, 255, 255, 0.48);
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .bd-hero-btn:hover {
            color: var(--eco-dark-blue);
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.14);
        }

        .bd-hero-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .bd-kpi {
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 16px;
            padding: 14px 15px 13px;
            backdrop-filter: blur(14px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.13);
            animation: bdFadeUp 0.55s ease both;
        }

        .bd-kpi:nth-child(2) {
            animation-delay: 0.04s;
        }

        .bd-kpi:nth-child(3) {
            animation-delay: 0.08s;
        }

        .bd-kpi:nth-child(4) {
            animation-delay: 0.12s;
        }

        .bd-kpi::after {
            content: "";
            position: absolute;
            right: -20px;
            top: -20px;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .bd-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .bd-kpi-lbl {
            font-size: 0.68rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.67);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .bd-kpi-ico {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.13);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.9);
            flex-shrink: 0;
        }

        .bd-kpi-val {
            font-size: 1.45rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }

        .bd-kpi-progress {
            height: 5px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .bd-kpi-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #9bd45c, #ffffff);
            width: 0;
            transition: width 0.9s ease;
        }

        .bd-kpi-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.72);
            font-weight: 700;
        }

        /* =========================
                       SHARED
                    ========================= */
        .bd-badge {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .bd-badge-great {
            background: #eaf3de;
            color: #3B6D11;
        }

        .bd-badge-good {
            background: #e1f5ee;
            color: #0F6E56;
        }

        .bd-badge-warn {
            background: #faeeda;
            color: #854F0B;
        }

        .bd-badge-danger {
            background: #fcebeb;
            color: #A32D2D;
        }

        .bd-badge-info {
            background: #e6f1fb;
            color: #185FA5;
        }

        .bd-surface {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid var(--eco-border);
            border-radius: 18px;
            box-shadow: var(--eco-soft-shadow);
        }

        /* =========================
                       INSIGHTS
                    ========================= */
        .bd-insights {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        .bd-insight {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--eco-border);
            border-radius: 16px;
            padding: 13px 15px;
            box-shadow: var(--eco-soft-shadow);
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 84px;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .bd-insight:hover {
            transform: translateY(-2px);
            box-shadow: var(--eco-shadow);
        }

        .bd-insight-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 19px;
        }

        .bd-insight-best .bd-insight-icon {
            background: #eaf3de;
            color: #3B6D11;
        }

        .bd-insight-risk .bd-insight-icon {
            background: #fcebeb;
            color: #A32D2D;
        }

        .bd-insight-gap .bd-insight-icon {
            background: #e6f1fb;
            color: #185FA5;
        }

        .bd-insight-label {
            font-size: 0.67rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .bd-insight-value {
            font-size: 0.9rem;
            font-weight: 900;
            color: var(--eco-text);
            line-height: 1.15;
        }

        .bd-insight-sub {
            margin-top: 3px;
            color: var(--eco-muted);
            font-size: 0.72rem;
        }

        /* =========================
                       TABS
                    ========================= */
        .bd-tabs-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .bd-tabs {
            display: flex;
            gap: 4px;
            background: #fff;
            border: 1px solid var(--eco-border);
            border-radius: 14px;
            padding: 5px;
            width: fit-content;
            box-shadow: var(--eco-soft-shadow);
        }

        .bd-tab-btn {
            padding: 8px 18px;
            font-size: 0.75rem;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.16s ease;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .bd-tab-btn:hover {
            background: #f1f5f9;
            color: var(--eco-text);
        }

        .bd-tab-btn.active {
            background: linear-gradient(135deg, var(--eco-dark-blue), var(--eco-blue));
            color: #fff;
            box-shadow: 0 8px 18px rgba(0, 130, 187, 0.20);
        }

        .bd-tab-pane {
            display: none;
        }

        .bd-tab-pane.active {
            display: block;
            animation: bdFadeUp 0.35s ease both;
        }

        .bd-mini-key {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.74);
            border: 1px solid var(--eco-border);
            border-radius: 999px;
            padding: 8px 12px;
            box-shadow: var(--eco-soft-shadow);
            flex-wrap: wrap;
        }

        .bd-mini-key-lbl {
            font-size: 0.67rem;
            font-weight: 900;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .bd-mini-key-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.68rem;
            font-weight: 700;
            color: #64748b;
        }

        .bd-key-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        /* =========================
                       OVERVIEW
                    ========================= */
        .bd-ov-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            align-items: start;
        }

        .bd-ov-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--eco-border);
            box-shadow: var(--eco-soft-shadow);
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .bd-ov-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--eco-shadow);
        }

        .bd-ov-card-header {
            padding: 13px 15px;
            display: flex;
            align-items: center;
            gap: 11px;
            border-bottom: 1px solid transparent;
        }

        .bd-ov-card-header i {
            width: 38px;
            height: 38px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .bd-ov-card-header-text {
            font-size: 0.84rem;
            font-weight: 900;
            color: var(--eco-text);
        }

        .bd-ov-card-header-sub {
            font-size: 0.68rem;
            color: #64748b;
            margin-top: 2px;
        }

        .bd-ov-card-dep .bd-ov-card-header {
            background: linear-gradient(135deg, #EFF7FF, #ffffff);
            border-bottom-color: #DCEEFB;
        }

        .bd-ov-card-dep .bd-ov-card-header i {
            background: #dceefc;
            color: #185FA5;
        }

        .bd-ov-card-loan .bd-ov-card-header {
            background: linear-gradient(135deg, #FFF8F0, #ffffff);
            border-bottom-color: #FFE9D0;
        }

        .bd-ov-card-loan .bd-ov-card-header i {
            background: #ffecd4;
            color: #B45309;
        }

        .bd-ov-card-ntb .bd-ov-card-header {
            background: linear-gradient(135deg, #F0FDF4, #ffffff);
            border-bottom-color: #D1FAE5;
        }

        .bd-ov-card-ntb .bd-ov-card-header i {
            background: #dcfce7;
            color: #166534;
        }

        .bd-ov-table-wrap {
            max-height: 470px;
            overflow: auto;
        }

        .bd-ov-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bd-ov-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #fff;
            padding: 8px 10px;
            font-size: 0.62rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            text-align: right;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }

        .bd-ov-table thead th:first-child {
            text-align: left;
        }

        .bd-ov-table tbody tr {
            border-bottom: 1px solid #f8fafc;
            transition: background 0.12s ease;
        }

        .bd-ov-table tbody tr:last-child {
            border-bottom: none;
        }

        .bd-ov-table tbody tr:hover {
            background: #f8fafc;
        }

        .bd-ov-table tbody td {
            padding: 8px 10px;
            font-size: 0.72rem;
            color: #374151;
            text-align: right;
            vertical-align: middle;
        }

        .bd-ov-table tbody td:first-child {
            text-align: left;
        }

        .bd-ov-br-name {
            display: block;
            font-weight: 900;
            font-size: 0.73rem;
            color: var(--eco-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 145px;
        }

        .bd-ov-br-code {
            font-size: 0.62rem;
            color: #94a3b8;
        }

        .bd-grade {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 23px;
            height: 23px;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 900;
        }

        .bd-grade-1 {
            background: #FEF2F2;
            color: #B91C1C;
        }

        .bd-grade-2 {
            background: #FFF7ED;
            color: #92400E;
        }

        .bd-grade-3 {
            background: #FEFCE8;
            color: #713F12;
        }

        .bd-grade-4 {
            background: #F0FDF4;
            color: #166534;
        }

        .bd-grade-5 {
            background: #DCFCE7;
            color: #14532D;
        }

        .bd-pending {
            color: #cbd5e1;
            font-style: italic;
            font-size: 0.68rem;
        }

        .bd-ov-table tfoot td {
            padding: 9px 10px;
            font-size: 0.73rem;
            font-weight: 900;
            color: var(--eco-text);
            text-align: right;
            border-top: 2px solid #e2e8f0;
            background: #f8fafc;
            white-space: nowrap;
        }

        .bd-ov-table tfoot td:first-child {
            text-align: left;
        }

        /* =========================
                       DETAIL LAYOUT
                    ========================= */
        .bd-body {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 12px;
            align-items: start;
        }

        .bd-sidebar {
            background: #fff;
            border: 1px solid var(--eco-border);
            border-radius: 18px;
            overflow: hidden;
            position: sticky;
            top: 14px;
            box-shadow: var(--eco-soft-shadow);
        }

        .bd-sidebar-head {
            padding: 12px 13px;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(135deg, #ffffff, #f8fbfd);
        }

        .bd-sidebar-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }

        .bd-sidebar-head-title {
            font-size: 0.68rem;
            font-weight: 900;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bd-sidebar-head-title i {
            color: var(--eco-blue);
        }

        .bd-sidebar-count {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 800;
        }

        .bd-search {
            position: relative;
        }

        .bd-search i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .bd-search input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 10px 8px 31px;
            font-size: 0.73rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .bd-search input:focus {
            border-color: rgba(0, 130, 187, 0.45);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, 0.10);
        }

        .bd-sidebar-list {
            max-height: 620px;
            overflow: auto;
        }

        .bd-sb-item {
            padding: 10px 13px;
            border-bottom: 1px solid #f8fafc;
            cursor: pointer;
            transition: background 0.14s ease, transform 0.14s ease;
        }

        .bd-sb-item:last-child {
            border-bottom: none;
        }

        .bd-sb-item:hover {
            background: #f8fafc;
        }

        .bd-sb-item.active {
            background: #e6f1fb;
            box-shadow: inset 3px 0 0 #185FA5;
        }

        .bd-sb-main {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .bd-sb-rank {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .bd-sb-item.active .bd-sb-rank {
            background: #185FA5;
            color: #fff;
        }

        .bd-sb-info {
            flex: 1;
            min-width: 0;
        }

        .bd-sb-name {
            font-size: 0.76rem;
            font-weight: 900;
            color: var(--eco-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bd-sb-code {
            font-size: 0.64rem;
            color: #94a3b8;
            margin-top: 1px;
        }

        .bd-sb-pct {
            font-size: 0.73rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .bd-sb-mini-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 8px;
        }

        .bd-sb-mini-fill {
            height: 100%;
            border-radius: 999px;
        }

        /* =========================
                       REDESIGNED BRANCH DETAIL
                    ========================= */
        .bd-detail {
            background:
                radial-gradient(circle at top right, rgba(0, 130, 187, 0.08), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid var(--eco-border);
            border-radius: 20px;
            overflow: hidden;
            min-height: 620px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07);
        }

        .bd-detail-shell {
            opacity: 0;
            transform: translateY(8px);
        }

        .bd-detail-shell.is-visible {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.22s ease, transform 0.22s ease;
        }

        .bd-detail-header {
            padding: 18px 20px;
            border-bottom: 1px solid #edf2f7;
            background:
                radial-gradient(circle at top right, rgba(99, 153, 34, 0.08), transparent 24%),
                linear-gradient(135deg, #ffffff, #f8fbfd);
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: start;
        }

        .bd-detail-header-left {
            min-width: 0;
        }

        .bd-detail-identity {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .bd-detail-avatar {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--eco-dark-blue), var(--eco-blue));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            box-shadow: 0 14px 28px rgba(0, 91, 130, 0.18);
        }

        .bd-detail-heading-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .bd-detail-title {
            font-size: 1.3rem;
            line-height: 1.1;
            font-weight: 900;
            color: var(--eco-text);
            letter-spacing: -0.03em;
            margin: 0;
        }

        .bd-detail-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 0.7rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .bd-status-great {
            background: #eaf6e5;
            color: #2f6f13;
        }

        .bd-status-good {
            background: #e6f7f0;
            color: #0F6E56;
        }

        .bd-status-warn {
            background: #fff4e4;
            color: #9a5a0f;
        }

        .bd-status-danger {
            background: #fdeaea;
            color: #A32D2D;
        }

        .bd-detail-submeta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 7px;
            color: #6b7280;
            font-size: 0.73rem;
            font-weight: 700;
        }

        .bd-detail-submeta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .bd-detail-submeta i {
            color: var(--eco-blue);
        }

        .bd-detail-narrative {
            margin-top: 12px;
            background: #ffffff;
            border: 1px solid #eaf0f6;
            border-radius: 14px;
            padding: 11px 12px;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.55;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .bd-detail-body {
            padding: 16px 18px 18px;
        }

        .bd-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .bd-summary-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid #ebf1f5;
            border-radius: 16px;
            padding: 13px 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .bd-summary-card::after {
            content: "";
            position: absolute;
            width: 88px;
            height: 88px;
            right: -26px;
            top: -32px;
            border-radius: 50%;
            background: rgba(0, 130, 187, 0.05);
        }

        .bd-summary-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .bd-summary-card-label {
            font-size: 0.66rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
        }

        .bd-summary-card-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6fb;
            color: #185FA5;
            flex-shrink: 0;
        }

        .bd-summary-card-value {
            font-size: 1.22rem;
            font-weight: 900;
            line-height: 1;
            color: var(--eco-text);
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }

        .bd-summary-card-sub {
            font-size: 0.69rem;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .bd-summary-progress {
            height: 6px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .bd-summary-progress-fill {
            height: 100%;
            border-radius: 999px;
            width: 0;
            transition: width 0.85s ease;
        }

        .bd-summary-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .bd-detail-content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.95fr);
            gap: 12px;
            align-items: start;
        }

        .bd-detail-main,
        .bd-detail-side {
            display: grid;
            gap: 12px;
        }

        .bd-panel {
            background: #fff;
            border: 1px solid #ebf1f5;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .bd-panel-header {
            padding: 14px 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bd-panel-title-wrap {
            min-width: 0;
        }

        .bd-panel-title {
            font-size: 0.82rem;
            font-weight: 900;
            color: var(--eco-text);
            display: flex;
            align-items: center;
            gap: 7px;
            line-height: 1.2;
        }

        .bd-panel-title i {
            color: var(--eco-blue);
        }

        .bd-panel-sub {
            margin-top: 4px;
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 700;
        }

        .bd-panel-body {
            padding: 14px 15px 15px;
        }

        .bd-performance-list {
            display: grid;
            gap: 12px;
        }

        .bd-performance-item {
            border: 1px solid #eef2f7;
            border-radius: 14px;
            padding: 12px 12px 11px;
            background: #fcfdff;
        }

        .bd-performance-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .bd-performance-copy {
            min-width: 0;
        }

        .bd-performance-lbl {
            font-size: 0.7rem;
            font-weight: 900;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .bd-performance-main {
            font-size: 0.98rem;
            font-weight: 900;
            color: var(--eco-text);
            line-height: 1.1;
        }

        .bd-performance-main small {
            font-size: 0.73rem;
            color: #94a3b8;
            font-weight: 800;
        }

        .bd-performance-bar {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-bottom: 7px;
        }

        .bd-performance-fill {
            height: 100%;
            border-radius: 999px;
        }

        .bd-performance-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 0.69rem;
            font-weight: 800;
            color: #64748b;
        }

        .bd-inline-grade {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .bd-bullet-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .bd-bullet-card {
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 14px;
            padding: 12px 13px;
        }

        .bd-bullet-title {
            font-size: 0.67rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .bd-bullet-value {
            font-size: 1rem;
            font-weight: 900;
            color: var(--eco-text);
            line-height: 1.1;
            margin-bottom: 5px;
        }

        .bd-bullet-note {
            font-size: 0.69rem;
            color: #64748b;
            font-weight: 700;
            line-height: 1.45;
        }

        .bd-chart-card canvas {
            display: block;
        }

        .bd-mix-wrap {
            display: grid;
            gap: 12px;
        }

        .bd-mix-compact {
            background: #fcfdff;
            border: 1px solid #eef2f7;
            border-radius: 14px;
            padding: 12px 13px;
        }

        .bd-mix-title {
            font-size: 0.72rem;
            font-weight: 900;
            color: var(--eco-text);
            margin-bottom: 9px;
        }

        .bd-mix-bar {
            display: flex;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #e5ecf3;
            margin-bottom: 10px;
        }

        .bd-mix-seg {
            height: 100%;
        }

        .bd-mix-legend {
            display: grid;
            gap: 7px;
        }

        .bd-mix-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.7rem;
            font-weight: 800;
            color: #475569;
        }

        .bd-mix-legend-left {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .bd-mix-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .bd-note-list {
            display: grid;
            gap: 10px;
        }

        .bd-note-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 12px;
            border: 1px solid #eef2f7;
            border-radius: 14px;
            background: #fcfdff;
        }

        .bd-note-icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            background: #eaf2fb;
            color: #185FA5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bd-note-text {
            font-size: 0.71rem;
            color: #475569;
            font-weight: 700;
            line-height: 1.55;
        }

        .bd-highlight-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: linear-gradient(135deg, #f6fbff, #ffffff);
            border: 1px solid #dcebfb;
            border-radius: 14px;
            padding: 12px 13px;
        }

        .bd-highlight-banner i {
            color: #185FA5;
            font-size: 1rem;
            margin-top: 1px;
        }

        .bd-highlight-banner strong {
            display: block;
            font-size: 0.73rem;
            color: var(--eco-text);
            margin-bottom: 3px;
        }

        .bd-highlight-banner span {
            display: block;
            color: #64748b;
            font-size: 0.7rem;
            line-height: 1.5;
            font-weight: 700;
        }

        .bd-detail-footer {
            padding: 12px 16px;
            border-top: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #fbfdff;
        }

        .bd-footer-info {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 800;
        }

        .bd-footer-nav {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .bd-nav-btn {
            padding: 8px 12px;
            font-size: 0.71rem;
            font-weight: 800;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.14s ease;
        }

        .bd-nav-btn:hover {
            background: #f8fafc;
            color: var(--eco-text);
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .bd-empty {
            min-height: 620px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            text-align: center;
            color: #94a3b8;
        }

        .bd-empty-box {
            max-width: 360px;
        }

        .bd-empty-box i {
            width: 74px;
            height: 74px;
            border-radius: 22px;
            background: linear-gradient(135deg, #eff7fc, #ffffff);
            border: 1px solid #dbeaf8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--eco-blue);
            margin-bottom: 14px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
        }

        .bd-empty-box h3 {
            margin: 0 0 7px;
            font-size: 1rem;
            font-weight: 900;
            color: var(--eco-text);
        }

        .bd-empty-box p {
            margin: 0;
            font-size: 0.78rem;
            line-height: 1.6;
            color: #64748b;
            font-weight: 700;
        }

        @media (max-width: 1200px) {
            .bd-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bd-detail-content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .bd-body {
                grid-template-columns: 1fr;
            }

            .bd-sidebar {
                position: static;
            }

            .bd-sidebar-list {
                max-height: 360px;
            }

            .bd-detail-header {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .bd-summary-grid {
                grid-template-columns: 1fr;
            }

            .bd-bullet-grid {
                grid-template-columns: 1fr;
            }

            .bd-detail-title {
                font-size: 1.08rem;
            }

            .bd-detail-submeta {
                gap: 8px;
            }
        }



        /* =========================
                       BRANCH DETAIL V2 - COMPACT 150% ZOOM
                    ========================= */
        .bd-priority-panel {
            border-color: rgba(0, 130, 187, 0.18);
            box-shadow: 0 14px 30px rgba(0, 91, 130, 0.07);
        }

        .bd-signal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .bd-signal-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff, #f8fbfd);
            border: 1px solid #edf2f7;
            border-radius: 15px;
            padding: 12px 13px;
            min-height: 96px;
        }

        .bd-signal-card::after {
            content: "";
            position: absolute;
            right: -24px;
            top: -28px;
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: rgba(0, 130, 187, 0.05);
        }

        .bd-signal-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .bd-signal-label {
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
        }

        .bd-signal-icon {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6fb;
            color: #185FA5;
            flex-shrink: 0;
        }

        .bd-signal-value {
            position: relative;
            z-index: 1;
            font-size: 1.05rem;
            font-weight: 900;
            color: var(--eco-text);
            line-height: 1.1;
            margin-bottom: 5px;
        }

        .bd-signal-note {
            position: relative;
            z-index: 1;
            font-size: 0.68rem;
            color: #64748b;
            line-height: 1.45;
            font-weight: 700;
        }

        .bd-signal-split {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 4px;
            margin-bottom: 5px;
        }

        .bd-signal-split-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }

        .bd-signal-split-lbl {
            font-size: 0.63rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }

        .bd-signal-split-val {
            font-size: 0.86rem;
            font-weight: 900;
            line-height: 1.1;
            text-align: right;
        }

        .bd-detail-content-grid.bd-detail-content-grid-v2 {
            grid-template-columns: minmax(0, 1.3fr) minmax(310px, 0.8fr);
        }

        .bd-chart-card-v2 .bd-panel-body {
            padding-top: 12px;
        }

        .bd-mini-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bd-mini-table th,
        .bd-mini-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.72rem;
            vertical-align: middle;
        }

        .bd-mini-table th {
            color: #94a3b8;
            font-size: 0.64rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: right;
            background: #fbfdff;
        }

        .bd-mini-table th:first-child,
        .bd-mini-table td:first-child {
            text-align: left;
        }

        .bd-mini-table td {
            color: #334155;
            font-weight: 800;
            text-align: right;
        }

        .bd-mini-table tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 1200px) {

            .bd-signal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .bd-detail-content-grid.bd-detail-content-grid-v2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {

            .bd-signal-grid {
                grid-template-columns: 1fr;
            }
        }

        /* =========================
                       HELPERS
                    ========================= */
        .fill-great {
            background: #639922;
        }

        .fill-good {
            background: #1D9E75;
        }

        .fill-warn {
            background: #EF9F27;
        }

        .fill-danger {
            background: #E24B4A;
        }

        .fill-blue {
            background: #185FA5;
        }

        .dot-great {
            background: #639922;
        }

        .dot-good {
            background: #1D9E75;
        }

        .dot-warn {
            background: #EF9F27;
        }

        .dot-danger {
            background: #E24B4A;
        }

        .dot-blue {
            background: #185FA5;
        }

        .pct-great {
            color: #3B6D11;
        }

        .pct-good {
            color: #0F6E56;
        }

        .pct-warn {
            color: #854F0B;
        }

        .pct-danger {
            color: #A32D2D;
        }

        @keyframes bdFadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================
                       RESPONSIVE
                    ========================= */
        @media (max-width: 1200px) {
            .bd-hero-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bd-insights {
                grid-template-columns: 1fr;
            }

            .bd-ov-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .bd-body {
                grid-template-columns: 1fr;
            }

            .bd-sidebar {
                position: static;
            }

            .bd-sidebar-list {
                max-height: 360px;
            }

            .bd-metrics-2col {
                grid-template-columns: 1fr;
            }

            .bd-stat-grid {
                grid-template-columns: 1fr;
            }

            .bd-mix-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .bd-page {
                padding: 8px 10px 22px;
            }

            .bd-hero {
                padding: 18px 15px 14px;
                border-radius: 16px;
            }

            .bd-hero-title-wrap {
                align-items: flex-start;
            }

            .bd-hero-kpis {
                grid-template-columns: 1fr;
            }

            .bd-tabs-wrap {
                align-items: stretch;
            }

            .bd-tabs {
                width: 100%;
            }

            .bd-tab-btn {
                flex: 1;
                justify-content: center;
                padding-inline: 10px;
            }

            .bd-mini-key {
                border-radius: 14px;
            }

            .bd-detail-header {
                flex-direction: column;
            }

            .bd-detail-badges {
                justify-content: flex-start;
            }

            .bd-lending-chip {
                align-items: flex-start;
                flex-direction: column;
            }

            .bd-lending-note {
                text-align: left;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ─────────────────────────────────────
                   SHOWCASE / PRINT MODE
                   ───────────────────────────────────── */
        .bd-showcase-only {
            display: none;
        }

        .bd-section-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--eco-deep-blue), var(--eco-dark-blue));
            border-radius: 12px;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .bd-section-banner i {
            font-size: 1rem;
            opacity: 0.8;
        }

        body.bd-showcase .bd-tab-pane,
        body.bd-showcase .bd-showcase-only {
            display: block !important;
            animation: none !important;
        }

        body.bd-showcase .bd-tabs,
        body.bd-showcase .bd-showcase-hide {
            display: none !important;
        }

        body.bd-showcase .bd-ov-table-wrap {
            max-height: none !important;
            overflow: visible !important;
        }

        body.bd-showcase .bd-sidebar-list {
            max-height: none !important;
            overflow: visible !important;
        }

        body.bd-showcase .bd-sidebar {
            position: static !important;
        }

        body.bd-showcase #bd-tab-detail .bd-body {
            grid-template-columns: 1fr !important;
        }

        body.bd-showcase .bd-detail {
            display: none !important;
        }

        body.bd-showcase .bd-search {
            display: none !important;
        }

        @media print {
            body {
                background: #fff !important;
            }

            .bd-tab-pane,
            .bd-showcase-only {
                display: block !important;
                animation: none !important;
            }

            .bd-tabs,
            .bd-showcase-hide {
                display: none !important;
            }

            .bd-ov-table-wrap {
                max-height: none !important;
                overflow: visible !important;
            }

            .bd-sidebar-list {
                max-height: none !important;
                overflow: visible !important;
            }

            .bd-sidebar {
                position: static !important;
            }

            #bd-tab-detail .bd-body {
                grid-template-columns: 1fr !important;
            }

            .bd-detail,
            .bd-detail-footer {
                display: none !important;
            }

            .bd-search {
                display: none !important;
            }

            #bd-particles {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /* ── Helpers ── */
        if (!function_exists('bdFmt')) {
            function bdFmt(float $n): string
            {
                if (abs($n) >= 1_000_000_000) {
                    return number_format($n / 1_000_000_000, 2) . 'B';
                }

                if (abs($n) >= 1_000_000) {
                    return number_format($n / 1_000_000, 1) . 'M';
                }

                if (abs($n) >= 1_000) {
                    return number_format($n / 1_000, 0) . 'K';
                }

                return number_format($n, 0);
            }
        }

        if (!function_exists('bdPct')) {
            function bdPct(float $actual, float $target): float
            {
                return $target > 0 ? min(100, round(($actual / $target) * 100, 1)) : 0.0;
            }
        }

        if (!function_exists('bdCls')) {
            function bdCls(float $pct): string
            {
                if ($pct >= 80) {
                    return 'great';
                }
                if ($pct >= 60) {
                    return 'good';
                }
                if ($pct >= 40) {
                    return 'warn';
                }

                return 'danger';
            }
        }

        if (!function_exists('bdRawPct')) {
            function bdRawPct(float $actual, float $target): string
            {
                if ($target <= 0) {
                    return '0%';
                }

                return number_format(($actual / $target) * 100, 1) . '%';
            }
        }

        if (!function_exists('bdGrade')) {
            function bdGrade(float $actual, float $target): int
            {
                if ($target <= 0) {
                    return 1;
                }

                $pct = ($actual / $target) * 100;

                if ($pct >= 120) {
                    return 5;
                }
                if ($pct >= 101) {
                    return 4;
                }
                if ($pct >= 96) {
                    return 3;
                }
                if ($pct >= 50) {
                    return 2;
                }

                return 1;
            }
        }

        if (!function_exists('bdGradeLabel')) {
            function bdGradeLabel(int $grade): string
            {
                return match ($grade) {
                    5 => 'Far Exceeds Expectations',
                    4 => 'Exceeds Expectations',
                    3 => 'Meets Expectations',
                    2 => 'Partially Meets Expectations',
                    default => "Doesn't Meet Expectations",
                };
            }
        }

        /* ── Collections ── */
        $branchCollection = collect($branches);
        $sortedBranches = $branchCollection->sortByDesc('deposit_pct')->values();
        $riskBranches = $branchCollection->sortBy('deposit_pct')->values()->take(3);
        $topBranches = $sortedBranches->take(3);

        /* ── Totals ── */
        $overallDepositPct = bdPct($totalActualDeposits, $targetTotals['deposits']);
        $overallAccountPct = bdPct($totalActualAccounts, $targetTotals['accounts']);
        $onTrack = $branchCollection->filter(fn($b) => $b['deposit_pct'] >= 80)->count();
        $branchCount = count($branches);

        $depositGapTotal = max(0, $targetTotals['deposits'] - $totalActualDeposits);
        $accountGapTotal = max(0, $targetTotals['accounts'] - $totalActualAccounts);

        $topDepositBranch = $sortedBranches->first();
        $lowestDepositBranch = $branchCollection->sortBy('deposit_pct')->values()->first();

        $overallDepositCls = bdCls($overallDepositPct);
        $overallAccountCls = bdCls($overallAccountPct);
    @endphp

    <div class="bd-page">

        {{-- Animated Hero --}}
        <div class="bd-hero">
            <div id="bd-particles"></div>

            <div class="bd-hero-content">
                <div class="bd-hero-main">
                    <div class="bd-hero-title-wrap">
                        <div class="bd-hero-icon">
                            <i class="ti ti-building-bank"></i>
                        </div>

                        <div>
                            <div class="bd-hero-eyebrow">Finance Performance</div>
                            <h1 class="bd-hero-title">Branch Performance Command Center</h1>
                            <div class="bd-hero-sub">
                                <span>2026 KPIs &amp; performance tracker</span>
                                <span>&bull;</span>
                                <span>All retail branches</span>
                            </div>
                        </div>
                    </div>

                    <div class="bd-hero-actions">
                        <span class="bd-hero-pill">
                            <i class="ti ti-calendar-event"></i>
                            {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}
                        </span>

                        <span class="bd-hero-pill">
                            <i class="ti ti-git-branch"></i>
                            {{ $branchCount }} branches
                        </span>

                        <button type="button" id="bd-showcase-btn" class="bd-hero-btn" onclick="bdToggleShowcase(this)">
                            <i class="ti ti-printer"></i>
                            Print All
                        </button>

                        <a href="{{ url('/finance/branch-dashboard') }}" class="bd-hero-btn bd-showcase-hide">
                            <i class="ti ti-refresh"></i>
                            Refresh
                        </a>
                    </div>
                </div>

                <div class="bd-hero-kpis">
                    <div class="bd-kpi">
                        <div class="bd-kpi-top">
                            <div class="bd-kpi-lbl">Total deposits</div>
                            <div class="bd-kpi-ico"><i class="ti ti-coins"></i></div>
                        </div>

                        <div class="bd-kpi-val bd-count" data-count="{{ $totalActualDeposits }}" data-format="money">
                            {{ bdFmt($totalActualDeposits) }}
                        </div>

                        <div class="bd-kpi-progress">
                            <div class="bd-kpi-progress-fill" data-width="{{ $overallDepositPct }}"></div>
                        </div>

                        <div class="bd-kpi-foot">
                            <span>{{ $overallDepositPct }}% of target</span>
                            <span>{{ bdFmt($depositGapTotal) }} gap</span>
                        </div>
                    </div>

                    <div class="bd-kpi">
                        <div class="bd-kpi-top">
                            <div class="bd-kpi-lbl">Total accounts</div>
                            <div class="bd-kpi-ico"><i class="ti ti-users"></i></div>
                        </div>

                        <div class="bd-kpi-val bd-count" data-count="{{ $totalActualAccounts }}" data-format="number">
                            {{ number_format($totalActualAccounts) }}
                        </div>

                        <div class="bd-kpi-progress">
                            <div class="bd-kpi-progress-fill" data-width="{{ $overallAccountPct }}"></div>
                        </div>

                        <div class="bd-kpi-foot">
                            <span>{{ $overallAccountPct }}% of target</span>
                            <span>{{ number_format($accountGapTotal) }} gap</span>
                        </div>
                    </div>

                    <div class="bd-kpi">
                        <div class="bd-kpi-top">
                            <div class="bd-kpi-lbl">Branches on track</div>
                            <div class="bd-kpi-ico"><i class="ti ti-circle-check"></i></div>
                        </div>

                        <div class="bd-kpi-val">
                            {{ $onTrack }} / {{ $branchCount }}
                        </div>

                        <div class="bd-kpi-progress">
                            <div class="bd-kpi-progress-fill"
                                data-width="{{ $branchCount > 0 ? round(($onTrack / $branchCount) * 100, 1) : 0 }}"></div>
                        </div>

                        <div class="bd-kpi-foot">
                            <span>&ge;80% achievement</span>
                            <span>{{ $branchCount > 0 ? round(($onTrack / $branchCount) * 100, 1) : 0 }}%</span>
                        </div>
                    </div>

                    @php
                        $overallLendingPct = bdPct($totalActualLending, $targetTotals['lending']);
                        $lendingGapTotal = max(0, $targetTotals['lending'] - $totalActualLending);
                    @endphp
                    <div class="bd-kpi">
                        <div class="bd-kpi-top">
                            <div class="bd-kpi-lbl">Total lending</div>
                            <div class="bd-kpi-ico"><i class="ti ti-cash"></i></div>
                        </div>

                        <div class="bd-kpi-val bd-count" data-count="{{ $totalActualLending }}" data-format="money">
                            {{ bdFmt($totalActualLending) }}
                        </div>

                        <div class="bd-kpi-progress">
                            <div class="bd-kpi-progress-fill" data-width="{{ $overallLendingPct }}"></div>
                        </div>

                        <div class="bd-kpi-foot">
                            <span>{{ $overallLendingPct }}% of target</span>
                            <span>{{ bdFmt($lendingGapTotal) }} gap</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Insights --}}
        <div class="bd-insights">
            <div class="bd-insight bd-insight-best">
                <div class="bd-insight-icon">
                    <i class="ti ti-trophy"></i>
                </div>

                <div>
                    <div class="bd-insight-label">Best performer</div>

                    @if ($topDepositBranch)
                        <div class="bd-insight-value">{{ $topDepositBranch['name'] }}</div>
                        <div class="bd-insight-sub">
                            {{ $topDepositBranch['deposit_pct'] }}% deposit achievement &bull;
                            {{ $topDepositBranch['code'] }}
                        </div>
                    @else
                        <div class="bd-insight-value">No branch data</div>
                        <div class="bd-insight-sub">Waiting for branch performance data</div>
                    @endif
                </div>
            </div>

            <div class="bd-insight bd-insight-risk">
                <div class="bd-insight-icon">
                    <i class="ti ti-alert-triangle"></i>
                </div>

                <div>
                    <div class="bd-insight-label">Needs attention</div>

                    @if ($lowestDepositBranch)
                        <div class="bd-insight-value">{{ $lowestDepositBranch['name'] }}</div>
                        <div class="bd-insight-sub">
                            {{ $lowestDepositBranch['deposit_pct'] }}% deposit achievement &bull;
                            follow-up recommended
                        </div>
                    @else
                        <div class="bd-insight-value">No branch data</div>
                        <div class="bd-insight-sub">Waiting for branch performance data</div>
                    @endif
                </div>
            </div>

            <div class="bd-insight bd-insight-gap">
                <div class="bd-insight-icon">
                    <i class="ti ti-target-arrow"></i>
                </div>

                <div>
                    <div class="bd-insight-label">Total deposit gap</div>
                    <div class="bd-insight-value">{{ bdFmt($depositGapTotal) }}</div>
                    <div class="bd-insight-sub">
                        Remaining amount needed to hit full deposit target
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs and Legend --}}
        <div class="bd-tabs-wrap">
            <div class="bd-tabs">
                <button class="bd-tab-btn active" type="button" data-tab="overview">
                    <i class="ti ti-layout-grid"></i>
                    Overview
                </button>

                <button class="bd-tab-btn" type="button" data-tab="detail">
                    <i class="ti ti-building-bank"></i>
                    Branch Detail
                </button>
            </div>

            <div class="bd-mini-key">
                <span class="bd-mini-key-lbl">Rating Scale</span>
                <span class="bd-mini-key-item"><span class="bd-grade bd-grade-5"
                        style="width:18px;height:18px;font-size:0.6rem;flex-shrink:0;">5</span> Far Exceeds &ge;120%</span>
                <span class="bd-mini-key-item"><span class="bd-grade bd-grade-4"
                        style="width:18px;height:18px;font-size:0.6rem;flex-shrink:0;">4</span> Exceeds 101–119%</span>
                <span class="bd-mini-key-item"><span class="bd-grade bd-grade-3"
                        style="width:18px;height:18px;font-size:0.6rem;flex-shrink:0;">3</span> Meets 96–100%</span>
                <span class="bd-mini-key-item"><span class="bd-grade bd-grade-2"
                        style="width:18px;height:18px;font-size:0.6rem;flex-shrink:0;">2</span> Partial 50–95%</span>
                <span class="bd-mini-key-item"><span class="bd-grade bd-grade-1"
                        style="width:18px;height:18px;font-size:0.6rem;flex-shrink:0;">1</span> Below &lt;50%</span>
            </div>
        </div>

        {{-- Tab 1: Overview --}}
        <div id="bd-tab-overview" class="bd-tab-pane active">
            <div class="bd-showcase-only bd-section-banner">
                <i class="ti ti-layout-grid"></i>
                Section 1 — Performance Overview: Deposits &bull; Loans &bull; NTB
            </div>
            <div class="bd-ov-grid">

                {{-- Deposits --}}
                <div class="bd-ov-card bd-ov-card-dep">
                    <div class="bd-ov-card-header">
                        <i class="ti ti-coins"></i>
                        <div>
                            <div class="bd-ov-card-header-text">Deposits</div>
                            <div class="bd-ov-card-header-sub">2026 target vs actual, KES</div>
                        </div>
                    </div>

                    <div class="bd-ov-table-wrap">
                        <table class="bd-ov-table">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Actual</th>
                                    <th>Target</th>
                                    <th>%</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($sortedBranches as $b)
                                    @php $g = bdGrade($b['actual_deposits'], $b['target_deposits']); @endphp

                                    <tr>
                                        <td>
                                            <span class="bd-ov-br-name">{{ $b['name'] }}</span>
                                            <span class="bd-ov-br-code">{{ $b['code'] }}</span>
                                        </td>
                                        <td>{{ bdFmt($b['actual_deposits']) }}</td>
                                        <td>{{ bdFmt($b['target_deposits']) }}</td>
                                        <td>{{ bdRawPct($b['actual_deposits'], $b['target_deposits']) }}</td>
                                        <td><span class="bd-grade bd-grade-{{ $g }}"
                                                title="{{ bdGradeLabel($g) }}">{{ $g }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td>
                                        <span class="bd-ov-br-name">Total</span>
                                        <span class="bd-ov-br-code">Consumer &amp; Commercial</span>
                                    </td>
                                    <td>{{ bdFmt($totalActualDeposits) }}</td>
                                    <td>{{ bdFmt($targetTotals['deposits']) }}</td>
                                    <td>{{ bdRawPct($totalActualDeposits, $targetTotals['deposits']) }}</td>
                                    <td>—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Loans --}}
                <div class="bd-ov-card bd-ov-card-loan">
                    <div class="bd-ov-card-header">
                        <i class="ti ti-moneybag"></i>
                        <div>
                            <div class="bd-ov-card-header-text">Loans</div>
                            <div class="bd-ov-card-header-sub">2026 lending actual vs target, KES &bull; as of
                                {{ \Carbon\Carbon::parse($loanAsOfDate)->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="bd-ov-table-wrap">
                        <table class="bd-ov-table">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Actual</th>
                                    <th>Target</th>
                                    <th>%</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($sortedBranches as $b)
                                    @php $lg = bdGrade($b['actual_lending'], $b['target_lending']); @endphp
                                    <tr>
                                        <td>
                                            <span class="bd-ov-br-name">{{ $b['name'] }}</span>
                                            <span class="bd-ov-br-code">{{ $b['code'] }}</span>
                                        </td>
                                        <td>{{ bdFmt($b['actual_lending']) }}</td>
                                        <td>{{ bdFmt($b['target_lending']) }}</td>
                                        <td>{{ bdRawPct($b['actual_lending'], $b['target_lending']) }}</td>
                                        <td><span class="bd-grade bd-grade-{{ $lg }}"
                                                title="{{ bdGradeLabel($lg) }}">{{ $lg }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td><span class="bd-ov-br-name">Total</span></td>
                                    <td>{{ bdFmt($totalActualLending) }}</td>
                                    <td>{{ bdFmt($targetTotals['lending']) }}</td>
                                    <td>{{ bdRawPct($totalActualLending, $targetTotals['lending']) }}</td>
                                    <td>—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- NTB / Accounts --}}
                <div class="bd-ov-card bd-ov-card-ntb">
                    <div class="bd-ov-card-header">
                        <i class="ti ti-user-plus"></i>
                        <div>
                            <div class="bd-ov-card-header-text">NTB</div>
                            <div class="bd-ov-card-header-sub">New accounts opened Jan 2026 onwards</div>
                        </div>
                    </div>

                    <div class="bd-ov-table-wrap">
                        <table class="bd-ov-table">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Actual</th>
                                    <th>Target</th>
                                    <th>%</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($sortedBranches as $b)
                                    @php $g = bdGrade($b['actual_accounts'], $b['target_accounts']); @endphp

                                    <tr>
                                        <td>
                                            <span class="bd-ov-br-name">{{ $b['name'] }}</span>
                                            <span class="bd-ov-br-code">{{ $b['code'] }}</span>
                                        </td>
                                        <td>{{ number_format($b['actual_accounts']) }}</td>
                                        <td>{{ number_format($b['target_accounts']) }}</td>
                                        <td>{{ bdRawPct($b['actual_accounts'], $b['target_accounts']) }}</td>
                                        <td><span class="bd-grade bd-grade-{{ $g }}"
                                                title="{{ bdGradeLabel($g) }}">{{ $g }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td><span class="bd-ov-br-name">Total</span></td>
                                    <td>{{ number_format($totalActualAccounts) }}</td>
                                    <td>{{ number_format($targetTotals['accounts']) }}</td>
                                    <td>{{ bdRawPct($totalActualAccounts, $targetTotals['accounts']) }}</td>
                                    <td>—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- Tab 2: Branch Detail --}}
        <div id="bd-tab-detail" class="bd-tab-pane">
            <div class="bd-showcase-only bd-section-banner" style="margin-top:12px;">
                <i class="ti ti-building-bank"></i>
                Section 2 — Branch Rankings (sorted by deposit achievement)
            </div>
            <div class="bd-body">

                {{-- Sidebar --}}
                <div class="bd-sidebar">
                    <div class="bd-sidebar-head">
                        <div class="bd-sidebar-title-row">
                            <span class="bd-sidebar-head-title">
                                <i class="ti ti-list"></i>
                                All branches
                            </span>

                            <span class="bd-sidebar-count">{{ $branchCount }} total</span>
                        </div>

                        <div class="bd-search">
                            <i class="ti ti-search"></i>
                            <input type="text" id="bd-branch-search" placeholder="Search branch or code...">
                        </div>
                    </div>

                    <div class="bd-sidebar-list" id="bd-sidebar-list">
                        @foreach ($sortedBranches as $i => $b)
                            @php
                                $dCls = bdCls($b['deposit_pct']);
                                $safeWidth = min(100, max(0, $b['deposit_pct']));
                            @endphp

                            <div class="bd-sb-item{{ $i === 0 ? ' active' : '' }}" data-code="{{ $b['code'] }}"
                                data-name="{{ strtolower($b['name']) }}"
                                data-search="{{ strtolower($b['name'] . ' ' . $b['code']) }}">

                                <div class="bd-sb-main">
                                    <div class="bd-sb-rank">{{ $i + 1 }}</div>

                                    <div class="bd-sb-info">
                                        <div class="bd-sb-name">{{ $b['name'] }}</div>
                                        <div class="bd-sb-code">{{ $b['code'] }}</div>
                                    </div>

                                    <span class="bd-sb-pct pct-{{ $dCls }}">{{ $b['deposit_pct'] }}%</span>
                                </div>

                                <div class="bd-sb-mini-bar">
                                    <div class="bd-sb-mini-fill fill-{{ $dCls }}"
                                        style="width: {{ $safeWidth }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Detail panel --}}
                <div class="bd-detail" id="bd-detail-panel">
                    <div class="bd-empty">
                        <div class="bd-empty-box">
                            <i class="ti ti-building-bank"></i>
                            <h3>Select a branch to view details</h3>
                            <p>
                                Choose a branch from the left list to open an executive performance view with
                                targets, gaps, portfolio mix, and movement trends.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- Pass branches to JS as JSON --}}
    <script>
        const bdBranches = @json($sortedBranches->values());
        const bdMtdDate = @json($mtdDate ? \Carbon\Carbon::parse($mtdDate)->format('d M Y') : null);
        const bdYtdDate = @json($ytdDate ? \Carbon\Carbon::parse($ytdDate)->format('d M Y') : null);
        const bdMtdLoanDate = @json($mtdLoanDate ? \Carbon\Carbon::parse($mtdLoanDate)->format('d M Y') : null);
        const bdYtdLoanDate = @json($ytdLoanDate ? \Carbon\Carbon::parse($ytdLoanDate)->format('d M Y') : null);
    </script>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

    <script>
        (function() {
            let bdChart = null;
            let bdActive = bdBranches.length ? bdBranches[0].code : null;

            function fmt(n) {
                n = parseFloat(n || 0);

                if (Math.abs(n) >= 1e9) return (n / 1e9).toFixed(2) + 'B';
                if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(1) + 'M';
                if (Math.abs(n) >= 1e3) return Math.round(n / 1e3) + 'K';

                return Math.round(n).toLocaleString();
            }

            function fmtN(n) {
                return Math.round(parseFloat(n || 0)).toLocaleString();
            }

            function cls(p) {
                p = parseFloat(p || 0);

                if (p >= 80) return 'great';
                if (p >= 60) return 'good';
                if (p >= 40) return 'warn';

                return 'danger';
            }

            function grade(actual, target) {
                actual = parseFloat(actual || 0);
                target = parseFloat(target || 0);
                if (target <= 0) return 1;
                var pct = (actual / target) * 100;
                if (pct >= 120) return 5;
                if (pct >= 101) return 4;
                if (pct >= 96) return 3;
                if (pct >= 50) return 2;
                return 1;
            }

            function gradeLabel(g) {
                var labels = {
                    5: 'Far Exceeds Expectations',
                    4: 'Exceeds Expectations',
                    3: 'Meets Expectations',
                    2: 'Partially Meets Expectations',
                    1: "Doesn't Meet Expectations"
                };
                return labels[g] || "Doesn't Meet Expectations";
            }

            function clsColor(p) {
                p = parseFloat(p || 0);

                if (p >= 80) return '#639922';
                if (p >= 60) return '#1D9E75';
                if (p >= 40) return '#EF9F27';

                return '#E24B4A';
            }

            // Dormancy reading: lower is better, unlike achievement percentages above.
            function dormancyCls(pct) {
                pct = parseFloat(pct || 0);

                if (pct <= 5) return 'great';
                if (pct <= 10) return 'good';
                if (pct <= 20) return 'warn';

                return 'danger';
            }

            // LDR reading: <50% underutilized, 50-90% healthy zone, >100% over-lent vs deposit base.
            function ldrCls(pct) {
                pct = parseFloat(pct || 0);

                if (pct > 100) return 'danger';
                if (pct >= 65) return 'great';
                if (pct >= 50) return 'good';
                if (pct >= 35) return 'warn';

                return 'danger';
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function safePct(value) {
                value = parseFloat(value || 0);
                return Math.max(0, Math.min(100, value));
            }

            function animateHeroProgress() {
                document.querySelectorAll('.bd-kpi-progress-fill').forEach(function(el) {
                    var width = safePct(el.dataset.width || 0);

                    requestAnimationFrame(function() {
                        el.style.width = width + '%';
                    });
                });
            }

            function animateCounts() {
                document.querySelectorAll('.bd-count').forEach(function(el) {
                    var target = parseFloat(el.dataset.count || 0);
                    var format = el.dataset.format || 'number';
                    var duration = 850;
                    var startTime = null;

                    function tick(timestamp) {
                        if (!startTime) startTime = timestamp;

                        var progress = Math.min((timestamp - startTime) / duration, 1);
                        var eased = 1 - Math.pow(1 - progress, 3);
                        var current = target * eased;

                        el.textContent = format === 'money' ? fmt(current) : fmtN(current);

                        if (progress < 1) {
                            requestAnimationFrame(tick);
                        } else {
                            el.textContent = format === 'money' ? fmt(target) : fmtN(target);
                        }
                    }

                    requestAnimationFrame(tick);
                });
            }

            function initParticles() {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                if (typeof particlesJS === 'undefined' || !document.getElementById('bd-particles')) {
                    return;
                }

                particlesJS('bd-particles', {
                    particles: {
                        number: {
                            value: 42,
                            density: {
                                enable: true,
                                value_area: 800
                            }
                        },
                        color: {
                            value: '#ffffff'
                        },
                        shape: {
                            type: 'circle'
                        },
                        opacity: {
                            value: 0.35,
                            random: true
                        },
                        size: {
                            value: 3,
                            random: true
                        },
                        line_linked: {
                            enable: true,
                            distance: 135,
                            color: '#ffffff',
                            opacity: 0.22,
                            width: 1
                        },
                        move: {
                            enable: true,
                            speed: 1.4,
                            direction: 'none',
                            random: false,
                            straight: false,
                            out_mode: 'out',
                            bounce: false
                        }
                    },
                    interactivity: {
                        detect_on: 'canvas',
                        events: {
                            onhover: {
                                enable: true,
                                mode: 'grab'
                            },
                            onclick: {
                                enable: false
                            },
                            resize: true
                        },
                        modes: {
                            grab: {
                                distance: 130,
                                line_linked: {
                                    opacity: 0.35
                                }
                            }
                        }
                    },
                    retina_detect: true
                });
            }

            function showTab(tab, btn) {
                document.querySelectorAll('.bd-tab-pane').forEach(function(pane) {
                    pane.classList.remove('active');
                });

                document.querySelectorAll('.bd-tab-btn').forEach(function(tabBtn) {
                    tabBtn.classList.remove('active');
                });

                var pane = document.getElementById('bd-tab-' + tab);

                if (pane) {
                    pane.classList.add('active');
                }

                if (btn) {
                    btn.classList.add('active');
                }

                if (tab === 'detail' && bdBranches.length) {
                    var currentCode = bdActive || bdBranches[0].code;

                    setTimeout(function() {
                        selectBranch(currentCode);
                    }, 40);
                }
            }

            function selectBranch(code) {
                bdActive = code;

                document.querySelectorAll('.bd-sb-item').forEach(function(item) {
                    item.classList.toggle('active', item.dataset.code === code);
                });

                var branch = bdBranches.find(function(item) {
                    return String(item.code) === String(code);
                });

                if (branch) {
                    renderDetail(branch);
                }
            }

            function bindDetailButtons() {
                document.querySelectorAll('.bd-nav-btn[data-code]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        selectBranch(this.dataset.code);
                    });
                });
            }

            function statusLabel(p) {
                p = parseFloat(p || 0);

                if (p >= 100) return 'Leading';
                if (p >= 80) return 'On Track';
                if (p >= 60) return 'Watchlist';

                return 'Needs Attention';
            }

            function renderDetail(b) {
                var dCls = cls(b.deposit_pct);
                var aCls = cls(b.account_pct);
                var lCls = cls(b.lending_pct);
                var dormCls = dormancyCls(b.dormancy_rate);

                var depGap = Math.max(0, parseFloat(b.target_deposits || 0) - parseFloat(b.actual_deposits || 0));
                var accGap = Math.max(0, parseFloat(b.target_accounts || 0) - parseFloat(b.actual_accounts || 0));
                var loanGap = Math.max(0, parseFloat(b.target_lending || 0) - parseFloat(b.actual_lending || 0));

                var dGrade = grade(b.actual_deposits, b.target_deposits);
                var aGrade = grade(b.actual_accounts, b.target_accounts);
                var lGrade = grade(b.actual_lending, b.target_lending);

                var idx = bdBranches.findIndex(function(item) {
                    return String(item.code) === String(b.code);
                });

                var prev = bdBranches[idx - 1] || null;
                var next = bdBranches[idx + 1] || null;

                var prevBtn = prev ?
                    '<button type="button" class="bd-nav-btn" data-code="' + escapeHtml(prev.code) + '">' +
                    '<i class="ti ti-chevron-left"></i>' + escapeHtml(prev.name) +
                    '</button>' : '';

                var nextBtn = next ?
                    '<button type="button" class="bd-nav-btn" data-code="' + escapeHtml(next.code) + '">' +
                    escapeHtml(next.name) + '<i class="ti ti-chevron-right"></i>' +
                    '</button>' : '';

                var depositPct = safePct(b.deposit_pct);
                var accountPct = safePct(b.account_pct);
                var lendingPct = safePct(b.lending_pct);

                var ldrPct = parseFloat(b.ldr_pct || 0);
                var ldrHealthLabel = ldrPct > 100 ? 'Over-lent' : (ldrPct >= 65 ? 'Healthy' : (ldrPct >= 50 ?
                    'Moderate' : 'Underutilized'));

                var hasMtd = b.mtd_movement !== null && b.mtd_movement !== undefined;
                var hasYtd = b.ytd_movement !== null && b.ytd_movement !== undefined;
                var mtdVal = parseFloat(b.mtd_movement || 0);
                var ytdVal = parseFloat(b.ytd_movement || 0);

                var hasMtdLoan = b.mtd_loan_movement !== null && b.mtd_loan_movement !== undefined;
                var hasYtdLoan = b.ytd_loan_movement !== null && b.ytd_loan_movement !== undefined;
                var mtdLoanVal = parseFloat(b.mtd_loan_movement || 0);
                var ytdLoanVal = parseFloat(b.ytd_loan_movement || 0);

                var lcyPct = parseFloat(b.lcy_pct || 0);
                var fcyPct = parseFloat(b.fcy_pct || 0);
                var currentPct = parseFloat(b.current_pct || 0);
                var savingsPct = parseFloat(b.savings_pct || 0);
                var termPct = parseFloat(b.term_pct || 0);
                var casaPct = parseFloat(b.casa_pct || 0);

                var branchState = statusLabel(b.deposit_pct);
                var stateClass = 'bd-status-' + dCls;

                var topCustomers = Array.isArray(b.top_customers) ? b.top_customers : [];
                var topCustomersRows = topCustomers.length ? topCustomers.map(function(c, i) {
                    return '<tr>' +
                        '<td><span class="bd-sb-rank" style="margin-right:8px;">' + (i + 1) + '</span>' +
                        escapeHtml(c.name) + '</td>' +
                        '<td>' + fmt(c.balance) + '</td>' +
                        '</tr>';
                }).join('') : '<tr><td colspan="2" class="bd-pending">No customer data available</td></tr>';

                var topLoanCustomers = Array.isArray(b.top_loan_customers) ? b.top_loan_customers : [];
                var topLoanCustomersRows = topLoanCustomers.length ? topLoanCustomers.map(function(c, i) {
                    return '<tr>' +
                        '<td><span class="bd-sb-rank" style="margin-right:8px;">' + (i + 1) + '</span>' +
                        escapeHtml(c.name) + '</td>' +
                        '<td>' + fmt(c.balance) + '</td>' +
                        '</tr>';
                }).join('') : '<tr><td colspan="2" class="bd-pending">No loan customer data available</td></tr>';

                var html = `
                    <div class="bd-detail-shell" id="bd-detail-shell">
                        <div class="bd-detail-header">
                            <div class="bd-detail-header-left">
                                <div class="bd-detail-identity">
                                    <div class="bd-detail-avatar">
                                        <i class="ti ti-building-bank"></i>
                                    </div>

                                    <div style="min-width:0;">
                                        <div class="bd-detail-heading-row">
                                            <h2 class="bd-detail-title">${escapeHtml(b.name)}</h2>
                                            <span class="bd-detail-status-chip ${stateClass}">
                                                <i class="ti ti-pulse"></i> ${branchState}
                                            </span>
                                        </div>

                                        <div class="bd-detail-submeta">
                                            <span><i class="ti ti-map-pin"></i> Code: ${escapeHtml(b.code)}</span>
                                            <span><i class="ti ti-trophy"></i> Rank ${idx + 1} of ${bdBranches.length}</span>
                                            <span><i class="ti ti-scale"></i> LDR ${ldrPct.toFixed(1)}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bd-detail-body">
                            <div class="bd-panel bd-priority-panel" style="margin-bottom:12px;">
                                <div class="bd-panel-header">
                                    <div class="bd-panel-title-wrap">
                                        <div class="bd-panel-title">
                                            <i class="ti ti-arrows-diff"></i>
                                            Movement and Trend Signals
                                        </div>
                                        <div class="bd-panel-sub">
                                            Visible first for quick branch performance review
                                        </div>
                                    </div>
                                </div>

                                <div class="bd-panel-body">
                                    <div class="bd-signal-grid">
                                        <div class="bd-signal-card">
                                            <div class="bd-signal-top">
                                                <div class="bd-signal-label">MTD Movement</div>
                                                <div class="bd-signal-icon"><i class="ti ti-calendar-stats"></i></div>
                                            </div>
                                            <div class="bd-signal-split">
                                                <div class="bd-signal-split-row">
                                                    <span class="bd-signal-split-lbl">Deposits</span>
                                                    <span class="bd-signal-split-val" style="color:${hasMtd ? (mtdVal >= 0 ? '#2f6f13' : '#A32D2D') : '#94a3b8'};">
                                                        ${hasMtd ? ((mtdVal >= 0 ? '+' : '-') + fmt(Math.abs(mtdVal))) : 'No data'}
                                                    </span>
                                                </div>
                                                <div class="bd-signal-split-row">
                                                    <span class="bd-signal-split-lbl">Loans</span>
                                                    <span class="bd-signal-split-val" style="color:${hasMtdLoan ? (mtdLoanVal >= 0 ? '#2f6f13' : '#A32D2D') : '#94a3b8'};">
                                                        ${hasMtdLoan ? ((mtdLoanVal >= 0 ? '+' : '-') + fmt(Math.abs(mtdLoanVal))) : 'No data'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="bd-signal-note">${bdMtdDate ? 'Deposits vs ' + bdMtdDate + (bdMtdLoanDate ? ', Loans vs ' + bdMtdLoanDate : '') : 'No prior month balance'}</div>
                                        </div>

                                        <div class="bd-signal-card">
                                            <div class="bd-signal-top">
                                                <div class="bd-signal-label">YTD Movement</div>
                                                <div class="bd-signal-icon"><i class="ti ti-calendar-event"></i></div>
                                            </div>
                                            <div class="bd-signal-split">
                                                <div class="bd-signal-split-row">
                                                    <span class="bd-signal-split-lbl">Deposits</span>
                                                    <span class="bd-signal-split-val" style="color:${hasYtd ? (ytdVal >= 0 ? '#2f6f13' : '#A32D2D') : '#94a3b8'};">
                                                        ${hasYtd ? ((ytdVal >= 0 ? '+' : '-') + fmt(Math.abs(ytdVal))) : 'No data'}
                                                    </span>
                                                </div>
                                                <div class="bd-signal-split-row">
                                                    <span class="bd-signal-split-lbl">Loans</span>
                                                    <span class="bd-signal-split-val" style="color:${hasYtdLoan ? (ytdLoanVal >= 0 ? '#2f6f13' : '#A32D2D') : '#94a3b8'};">
                                                        ${hasYtdLoan ? ((ytdLoanVal >= 0 ? '+' : '-') + fmt(Math.abs(ytdLoanVal))) : 'No data'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="bd-signal-note">${bdYtdDate ? 'Deposits vs ' + bdYtdDate + (bdYtdLoanDate ? ', Loans vs ' + bdYtdLoanDate : '') : 'No prior year-end balance'}</div>
                                        </div>

                                        <div class="bd-signal-card">
                                            <div class="bd-signal-top">
                                                <div class="bd-signal-label">Deposit Gap</div>
                                                <div class="bd-signal-icon"><i class="ti ti-target-arrow"></i></div>
                                            </div>
                                            <div class="bd-signal-value">${fmt(depGap)}</div>
                                            <div class="bd-signal-note">Remaining deposit amount to target</div>
                                        </div>

                                        <div class="bd-signal-card">
                                            <div class="bd-signal-top">
                                                <div class="bd-signal-label">Loan Gap</div>
                                                <div class="bd-signal-icon"><i class="ti ti-cash"></i></div>
                                            </div>
                                            <div class="bd-signal-value">${fmt(loanGap)}</div>
                                            <div class="bd-signal-note">Remaining loan amount to target</div>
                                        </div>

                                        <div class="bd-signal-card">
                                            <div class="bd-signal-top">
                                                <div class="bd-signal-label">LDR Health</div>
                                                <div class="bd-signal-icon"><i class="ti ti-scale"></i></div>
                                            </div>
                                            <div class="bd-signal-value">${ldrHealthLabel}</div>
                                            <div class="bd-signal-note">${ldrPct.toFixed(1)}% loan-to-deposit</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bd-summary-grid">
                                <div class="bd-summary-card">
                                    <div class="bd-summary-card-head">
                                        <div class="bd-summary-card-label">Deposits</div>
                                        <div class="bd-summary-card-icon"><i class="ti ti-coins"></i></div>
                                    </div>
                                    <div class="bd-summary-card-value">${fmt(b.actual_deposits)}</div>
                                    <div class="bd-summary-card-sub">Target ${fmt(b.target_deposits)}</div>
                                    <div class="bd-summary-progress">
                                        <div class="bd-summary-progress-fill fill-${dCls}" data-detail-width="${depositPct}"></div>
                                    </div>
                                    <div class="bd-summary-card-foot">
                                        <span class="pct-${dCls}">${escapeHtml(b.deposit_pct)}% achieved</span>
                                        <span>Grade ${dGrade}</span>
                                    </div>
                                </div>

                                <div class="bd-summary-card">
                                    <div class="bd-summary-card-head">
                                        <div class="bd-summary-card-label">Loans</div>
                                        <div class="bd-summary-card-icon"><i class="ti ti-cash"></i></div>
                                    </div>
                                    <div class="bd-summary-card-value">${fmt(b.actual_lending)}</div>
                                    <div class="bd-summary-card-sub">Target ${fmt(b.target_lending)}</div>
                                    <div class="bd-summary-progress">
                                        <div class="bd-summary-progress-fill fill-${lCls}" data-detail-width="${lendingPct}"></div>
                                    </div>
                                    <div class="bd-summary-card-foot">
                                        <span class="pct-${lCls}">${escapeHtml(b.lending_pct)}% achieved</span>
                                        <span>Grade ${lGrade}</span>
                                    </div>
                                </div>

                                <div class="bd-summary-card">
                                    <div class="bd-summary-card-head">
                                        <div class="bd-summary-card-label">Accounts</div>
                                        <div class="bd-summary-card-icon"><i class="ti ti-users"></i></div>
                                    </div>
                                    <div class="bd-summary-card-value">${fmtN(b.actual_accounts)}</div>
                                    <div class="bd-summary-card-sub">Target ${fmtN(b.target_accounts)}</div>
                                    <div class="bd-summary-progress">
                                        <div class="bd-summary-progress-fill fill-${aCls}" data-detail-width="${accountPct}"></div>
                                    </div>
                                    <div class="bd-summary-card-foot">
                                        <span class="pct-${aCls}">${escapeHtml(b.account_pct)}% achieved</span>
                                        <span>Grade ${aGrade}</span>
                                    </div>
                                </div>

                                <div class="bd-summary-card">
                                    <div class="bd-summary-card-head">
                                        <div class="bd-summary-card-label">CASA Mix</div>
                                        <div class="bd-summary-card-icon"><i class="ti ti-chart-pie"></i></div>
                                    </div>
                                    <div class="bd-summary-card-value">${casaPct.toFixed(1)}%</div>
                                    <div class="bd-summary-card-sub">Current + Savings deposits</div>
                                    <div class="bd-summary-progress">
                                        <div class="bd-summary-progress-fill fill-blue" data-detail-width="${safePct(casaPct)}"></div>
                                    </div>
                                    <div class="bd-summary-card-foot">
                                        <span>LDR ${ldrPct.toFixed(1)}%</span>
                                        <span>${ldrHealthLabel}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bd-detail-content-grid bd-detail-content-grid-v2">
                                <div class="bd-detail-main">
                                    <div class="bd-panel bd-chart-card bd-chart-card-v2">
                                        <div class="bd-panel-header">
                                            <div class="bd-panel-title-wrap">
                                                <div class="bd-panel-title">
                                                    <i class="ti ti-chart-bar"></i>
                                                    Actual vs Target Snapshot
                                                </div>
                                                <div class="bd-panel-sub">
                                                    Deposits, loans, and accounts compared in one view
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bd-panel-body">
                                            <div style="position:relative;height:270px;">
                                                <canvas id="bd-branch-chart" role="img" aria-label="Bar chart comparing deposit, loan, and account achievement for ${escapeHtml(b.name)}"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bd-panel">
                                        <div class="bd-panel-header">
                                            <div class="bd-panel-title-wrap">
                                                <div class="bd-panel-title">
                                                    <i class="ti ti-list-check"></i>
                                                    Target Breakdown
                                                </div>
                                                <div class="bd-panel-sub">
                                                    Compact figures for the selected branch
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bd-panel-body" style="padding:0;">
                                            <table class="bd-mini-table">
                                                <thead>
                                                    <tr>
                                                        <th>Metric</th>
                                                        <th>Actual</th>
                                                        <th>Target</th>
                                                        <th>Gap</th>
                                                        <th>Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Deposits</td>
                                                        <td>${fmt(b.actual_deposits)}</td>
                                                        <td>${fmt(b.target_deposits)}</td>
                                                        <td>${fmt(depGap)}</td>
                                                        <td><span class="bd-badge bd-badge-${dCls}">${escapeHtml(b.deposit_pct)}%</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Loans</td>
                                                        <td>${fmt(b.actual_lending)}</td>
                                                        <td>${fmt(b.target_lending)}</td>
                                                        <td>${fmt(loanGap)}</td>
                                                        <td><span class="bd-badge bd-badge-${lCls}">${escapeHtml(b.lending_pct)}%</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Accounts</td>
                                                        <td>${fmtN(b.actual_accounts)}</td>
                                                        <td>${fmtN(b.target_accounts)}</td>
                                                        <td>${fmtN(accGap)}</td>
                                                        <td><span class="bd-badge bd-badge-${aCls}">${escapeHtml(b.account_pct)}%</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="bd-detail-side">
                                    <div class="bd-panel">
                                        <div class="bd-panel-header">
                                            <div class="bd-panel-title-wrap">
                                                <div class="bd-panel-title">
                                                    <i class="ti ti-chart-pie"></i>
                                                    Portfolio Structure
                                                </div>
                                                <div class="bd-panel-sub">
                                                    Currency and deposit product mix
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bd-panel-body">
                                            <div class="bd-mix-wrap">
                                                <div class="bd-mix-compact">
                                                    <div class="bd-mix-title">Currency Mix</div>
                                                    <div class="bd-mix-bar">
                                                        <div class="bd-mix-seg fill-blue" style="width:${safePct(lcyPct)}%;"></div>
                                                        <div class="bd-mix-seg fill-great" style="width:${safePct(fcyPct)}%;"></div>
                                                    </div>

                                                    <div class="bd-mix-legend">
                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left">
                                                                <span class="bd-mix-dot dot-blue"></span>
                                                                LCY
                                                            </span>
                                                            <span>${fmt(b.lcy_amount)} (${lcyPct.toFixed(1)}%)</span>
                                                        </div>

                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left">
                                                                <span class="bd-mix-dot dot-great"></span>
                                                                FCY
                                                            </span>
                                                            <span>${fmt(b.fcy_amount)} (${fcyPct.toFixed(1)}%)</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="bd-mix-compact">
                                                    <div class="bd-mix-title">CASA / Term Mix</div>
                                                    <div class="bd-mix-bar">
                                                        <div class="bd-mix-seg fill-blue" style="width:${safePct(currentPct)}%;"></div>
                                                        <div class="bd-mix-seg fill-good" style="width:${safePct(savingsPct)}%;"></div>
                                                        <div class="bd-mix-seg fill-warn" style="width:${safePct(termPct)}%;"></div>
                                                    </div>

                                                    <div class="bd-mix-legend">
                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left"><span class="bd-mix-dot dot-blue"></span>Current</span>
                                                            <span>${currentPct.toFixed(1)}%</span>
                                                        </div>
                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left"><span class="bd-mix-dot dot-good"></span>Savings</span>
                                                            <span>${savingsPct.toFixed(1)}%</span>
                                                        </div>
                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left"><span class="bd-mix-dot dot-warn"></span>Term</span>
                                                            <span>${termPct.toFixed(1)}%</span>
                                                        </div>
                                                        <div class="bd-mix-legend-item">
                                                            <span class="bd-mix-legend-left"><span class="bd-mix-dot dot-great"></span>CASA</span>
                                                            <span>${casaPct.toFixed(1)}%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bd-panel" style="margin-top:12px;">
                                        <div class="bd-panel-header">
                                            <div class="bd-panel-title-wrap">
                                                <div class="bd-panel-title">
                                                    <i class="ti ti-users-group"></i>
                                                    Account Breakdown
                                                </div>
                                                <div class="bd-panel-sub">
                                                    CIFs, total accounts, and dormancy at this branch
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bd-panel-body" style="padding:0;">
                                            <table class="bd-mini-table">
                                                <tbody>
                                                    <tr>
                                                        <td>Total Customers</td>
                                                        <td>${fmtN(b.total_cifs)}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Accounts</td>
                                                        <td>${fmtN(b.total_accounts)}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dormant Accounts</td>
                                                        <td>${fmtN(b.dormant_accounts)}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dormancy Rate</td>
                                                        <td><span class="bd-badge bd-badge-${dormCls}">${(parseFloat(b.dormancy_rate || 0)).toFixed(1)}%</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bd-bullet-grid" style="margin-top:12px;">
                                <div class="bd-panel">
                                    <div class="bd-panel-header">
                                        <div class="bd-panel-title-wrap">
                                            <div class="bd-panel-title">
                                                <i class="ti ti-crown"></i>
                                                Top 10 Best Deposit Customers
                                            </div>
                                            <div class="bd-panel-sub">
                                                Highest deposit balances at this branch
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bd-panel-body" style="padding:0;">
                                        <div style="max-height:320px;overflow:auto;">
                                            <table class="bd-mini-table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${topCustomersRows}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="bd-panel">
                                    <div class="bd-panel-header">
                                        <div class="bd-panel-title-wrap">
                                            <div class="bd-panel-title">
                                                <i class="ti ti-cash"></i>
                                                Top 10 Best Loan Customers
                                            </div>
                                            <div class="bd-panel-sub">
                                                Highest loan outstanding at this branch
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bd-panel-body" style="padding:0;">
                                        <div style="max-height:320px;overflow:auto;">
                                            <table class="bd-mini-table">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Outstanding</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${topLoanCustomersRows}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bd-detail-footer">
                            <div class="bd-footer-info">
                                Viewing branch ${idx + 1} of ${bdBranches.length}
                            </div>

                            <div class="bd-footer-nav">
                                ${prevBtn}
                                ${nextBtn}
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('bd-detail-panel').innerHTML = html;

                requestAnimationFrame(function() {
                    var shell = document.getElementById('bd-detail-shell');

                    if (shell) {
                        shell.classList.add('is-visible');
                    }

                    document.querySelectorAll('[data-detail-width]').forEach(function(el) {
                        el.style.width = safePct(el.dataset.detailWidth || 0) + '%';
                    });
                });

                bindDetailButtons();
                renderChart(b, depositPct, accountPct, lendingPct);
            }

            function renderChart(b, depositPct, accountPct, lendingPct) {
                if (bdChart) {
                    bdChart.destroy();
                    bdChart = null;
                }

                if (typeof Chart === 'undefined') {
                    return;
                }

                var canvas = document.getElementById('bd-branch-chart');

                if (!canvas) {
                    return;
                }

                lendingPct = safePct(lendingPct || b.lending_pct || 0);

                bdChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: ['Deposits', 'Loans', 'Accounts'],
                        datasets: [{
                                label: 'Achieved',
                                data: [depositPct, lendingPct, accountPct],
                                backgroundColor: [clsColor(depositPct), clsColor(lendingPct), clsColor(
                                    accountPct)],
                                borderRadius: 9,
                                barPercentage: 0.52,
                                categoryPercentage: 0.62
                            },
                            {
                                label: 'Remaining',
                                data: [
                                    Math.max(0, 100 - depositPct),
                                    Math.max(0, 100 - lendingPct),
                                    Math.max(0, 100 - accountPct)
                                ],
                                backgroundColor: '#e2e8f0',
                                borderRadius: 9,
                                barPercentage: 0.52,
                                categoryPercentage: 0.62
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 10,
                                titleFont: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 11
                                },
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.datasetIndex === 1) {
                                            return 'Remaining: ' + ctx.parsed.y.toFixed(1) + '%';
                                        }

                                        if (ctx.label === 'Deposits') {
                                            return 'Deposits: ' + fmt(b.actual_deposits) + ' / ' + fmt(b
                                                    .target_deposits) + ' (' + ctx.parsed.y.toFixed(1) +
                                                '%)';
                                        }

                                        if (ctx.label === 'Loans') {
                                            return 'Loans: ' + fmt(b.actual_lending) + ' / ' + fmt(b
                                                .target_lending) + ' (' + ctx.parsed.y.toFixed(1) + '%)';
                                        }

                                        return 'Accounts: ' + fmtN(b.actual_accounts) + ' / ' + fmtN(b
                                            .target_accounts) + ' (' + ctx.parsed.y.toFixed(1) + '%)';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11,
                                        weight: 'bold'
                                    },
                                    color: '#64748b'
                                }
                            },
                            y: {
                                stacked: true,
                                min: 0,
                                max: 100,
                                grid: {
                                    color: 'rgba(15, 23, 42, 0.06)'
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    },
                                    color: '#94a3b8',
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function bindSidebar() {
                document.querySelectorAll('.bd-sb-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        selectBranch(this.dataset.code);
                    });
                });
            }

            function bindSearch() {
                var input = document.getElementById('bd-branch-search');

                if (!input) {
                    return;
                }

                input.addEventListener('input', function() {
                    var query = this.value.trim().toLowerCase();

                    document.querySelectorAll('.bd-sb-item').forEach(function(item) {
                        var haystack = item.dataset.search || '';
                        item.style.display = haystack.indexOf(query) !== -1 ? '' : 'none';
                    });
                });
            }

            function bindTabs() {
                document.querySelectorAll('.bd-tab-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        showTab(this.dataset.tab, this);
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                initParticles();
                animateHeroProgress();
                animateCounts();
                bindTabs();
                bindSidebar();
                bindSearch();

                if (bdBranches.length) {
                    selectBranch(bdBranches[0].code);
                }
            });

            window.bdSelectBranch = selectBranch;
        })();

        window.bdToggleShowcase = function(btn) {
            var entering = !document.body.classList.contains('bd-showcase');
            document.body.classList.toggle('bd-showcase', entering);
            if (btn) {
                btn.innerHTML = entering ?
                    '<i class="ti ti-x"></i> Exit Showcase' :
                    '<i class="ti ti-printer"></i> Print All';
            }
            if (entering) {
                setTimeout(function() {
                    window.print();
                }, 400);
            }
        };
    </script>
@endpush
