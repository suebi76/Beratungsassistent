<?php
declare(strict_types=1);

function admin_render_styles(): void
{
    ?>
    <style>
        :root {
            --font-body: "Aptos", "Segoe UI Variable", "Source Sans 3", "Helvetica Neue", sans-serif;
            --font-mono: "Cascadia Mono", "SFMono-Regular", Consolas, monospace;
            --color-bg: #f4f1e8;
            --color-bg-soft: #eef3f1;
            --color-surface: rgba(255, 255, 255, .94);
            --color-surface-strong: #fff;
            --color-text: #172026;
            --color-muted: #66727d;
            --color-border: rgba(23, 32, 38, .1);
            --color-border-strong: rgba(23, 32, 38, .16);
            --color-primary: #0f766e;
            --color-primary-dark: #12423f;
            --color-accent: #c75b12;
            --color-danger: #b42318;
            --color-danger-bg: #fff1ef;
            --color-success: #136c2e;
            --color-success-bg: #eefaf0;
            --shadow-card: 0 24px 70px rgba(23, 32, 38, .08);
            --radius-card: 24px;
            --radius-field: 14px;
            --space-page: 28px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--font-body);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, .16), transparent 34rem),
                radial-gradient(circle at 80% 8%, rgba(199, 91, 18, .12), transparent 30rem),
                linear-gradient(180deg, var(--color-bg) 0%, var(--color-bg-soft) 100%);
            color: var(--color-text);
        }
        a { color: inherit; }
        code { font-family: var(--font-mono); font-size: .92em; }
        .shell { min-height: 100vh; }
        .center { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .auth, .card, .section-nav, .mobile-section-nav, .section-header, .metric-card {
            background: var(--color-surface);
            backdrop-filter: blur(10px);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
        }
        .auth { width: 100%; max-width: 440px; padding: 32px; }
        .brand {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary) 52%, var(--color-accent));
            color: white;
            font-weight: 850;
            font-size: 20px;
            margin-bottom: 18px;
            letter-spacing: -.03em;
        }
        h1, h2, h3 { margin: 0; letter-spacing: -.03em; }
        p { line-height: 1.55; }
        .muted { color: var(--color-muted); }
        .stack { display: grid; gap: 18px; }
        label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: #47545f;
            margin-bottom: 6px;
        }
        input[type=text], input[type=password], input[type=url], input[type=file], textarea, select {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--radius-field);
            border: 1px solid #d3dbe0;
            background: var(--color-surface-strong);
            font: inherit;
            color: var(--color-text);
        }
        input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--color-primary); }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, textarea:focus, select:focus, button:focus-visible, a:focus-visible {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .16);
        }
        .btn {
            appearance: none;
            border: none;
            border-radius: var(--radius-field);
            padding: 12px 18px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform .12s ease, opacity .12s ease, box-shadow .12s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
        .btn-primary { background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); color: white; box-shadow: 0 12px 28px rgba(15, 118, 110, .22); }
        .btn-secondary { background: #edf3f1; color: var(--color-text); }
        .btn-danger { background: var(--color-danger-bg); color: var(--color-danger); }
        .btn-compact { padding: 8px 12px; font-size: 13px; }
        .alert { border-radius: 16px; padding: 14px 16px; font-size: 14px; }
        .alert.success { background: var(--color-success-bg); color: var(--color-success); border: 1px solid #a8ddb5; }
        .alert.error { background: #fff3f2; color: #a33024; border: 1px solid #f1b0a8; }
        .page { max-width: 1360px; margin: 0 auto; padding: var(--space-page) 20px 56px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 22px; }
        .title-line { display: flex; align-items: center; gap: 14px; }
        .title-chip { display: inline-flex; gap: 8px; flex-wrap: wrap; }
        .chip {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.72);
            border: 1px solid var(--color-border);
            color: #47545f;
        }
        .admin-workspace { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 22px; align-items: start; }
        .admin-sidebar { position: sticky; top: 20px; }
        .admin-content { display: grid; gap: 20px; min-width: 0; }
        .section-nav { padding: 14px; display: grid; gap: 6px; }
        .section-nav-label, .eyebrow {
            color: var(--color-muted);
            font-size: 11px;
            font-weight: 850;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .section-link {
            display: grid;
            gap: 2px;
            padding: 12px 14px;
            border-radius: 16px;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .section-link span { font-weight: 850; }
        .section-link small { color: var(--color-muted); }
        .section-link:hover { background: rgba(15, 118, 110, .07); }
        .section-link.active { background: #123b38; color: white; box-shadow: 0 12px 30px rgba(18, 59, 56, .18); }
        .section-link.active small { color: rgba(255,255,255,.72); }
        .mobile-section-nav { display: none; padding: 16px; }
        .select-row { display: flex; gap: 10px; align-items: center; }
        .section-header { padding: 26px; }
        .section-header h2 { font-size: clamp(28px, 4vw, 42px); margin-top: 8px; }
        .section-header p { max-width: 760px; margin: 8px 0 0; color: var(--color-muted); }
        .grid { display: grid; gap: 20px; }
        .grid.two { grid-template-columns: minmax(0, 1.08fr) minmax(320px, .92fr); }
        .card { padding: 24px; overflow-x: auto; }
        .card h2 { font-size: 20px; margin-bottom: 6px; }
        .card h3 { font-size: 16px; margin-bottom: 6px; }
        .card-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .metric-card { padding: 20px; display: grid; gap: 6px; }
        .metric-card strong { font-size: 24px; letter-spacing: -.04em; }
        .metric-label { color: var(--color-muted); font-size: 12px; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .count-pill {
            white-space: nowrap;
            font-size: 12px;
            font-weight: 850;
            color: var(--color-primary-dark);
            background: rgba(15, 118, 110, .1);
            border-radius: 999px;
            padding: 7px 10px;
        }
        .task-list, .document-list { display: grid; gap: 10px; margin-top: 14px; }
        .task-item, .document-item, .empty-state, .result-item {
            border-radius: 16px;
            padding: 14px;
            background: #f8faf8;
            border: 1px solid #e3eae7;
        }
        .task-item { display: grid; gap: 4px; text-decoration: none; }
        .task-item span { color: var(--color-muted); }
        .empty-state p { margin: 6px 0 0; }
        .definition-grid { display: grid; gap: 12px; }
        .definition-grid div {
            display: grid;
            grid-template-columns: minmax(160px, .32fr) 1fr;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e8eeeb;
        }
        .definition-grid span { color: var(--color-muted); }
        .stepper { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 20px; }
        .step { padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 800; background: #edf3f1; color: #56656f; }
        .step.active { background: var(--color-text); color: white; }
        .step.done { background: var(--color-success-bg); color: var(--color-success); }
        .actions, .table-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 16px; }
        .table { width: 100%; min-width: 760px; border-collapse: collapse; margin-top: 12px; font-size: 14px; }
        .table th, .table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e8eeeb; vertical-align: top; }
        .table th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: var(--color-muted); }
        .select-cell { width: 42px; text-align: center !important; }
        .mono { font-family: var(--font-mono); font-size: 12px; color: var(--color-muted); }
        .dropzone { border: 2px dashed #c5d4d0; border-radius: 18px; padding: 26px; background: #f8fbfa; }
        .dropzone p { margin: 0; }
        .result-list { display: grid; gap: 12px; margin-top: 14px; }
        .template-admin { display: grid; gap: 18px; }
        .template-section { display: grid; gap: 14px; border: 1px solid #e3eae7; border-radius: 18px; padding: 18px; background: #fbfcfb; }
        .template-section-head { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; flex-wrap: wrap; }
        .template-option { display: grid; grid-template-columns: minmax(180px, .35fr) minmax(260px, 1fr); gap: 14px; align-items: start; }
        details { border-top: 1px solid #e8eeeb; padding-top: 16px; margin-top: 18px; }
        summary { cursor: pointer; font-weight: 800; color: var(--color-text); }
        .working-status {
            margin: 0;
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(15, 118, 110, .1);
            color: var(--color-primary-dark);
            font-weight: 800;
        }
        .is-working { opacity: .94; }

        @media (max-width: 1100px) {
            .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid.two { grid-template-columns: 1fr; }
        }

        @media (max-width: 900px) {
            .admin-workspace { display: block; }
            .admin-sidebar { display: none; }
            .mobile-section-nav { display: block; }
            .admin-content { gap: 16px; }
            .template-option { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }

        @media (max-width: 640px) {
            :root { --space-page: 18px; }
            .title-line { align-items: flex-start; }
            .metric-grid { grid-template-columns: 1fr; }
            .card, .section-header { padding: 18px; }
            .select-row, .actions, .table-actions { flex-direction: column; align-items: stretch; }
            .btn { width: 100%; }
            .definition-grid div { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
    <?php
}
