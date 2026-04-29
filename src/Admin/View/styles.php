<?php
declare(strict_types=1);

function admin_render_styles(): void
{
    ?>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: linear-gradient(180deg, #f7f5ef 0%, #eef2f5 100%); color: #18212b; }
        a { color: inherit; }
        .shell { min-height: 100vh; }
        .center { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .auth, .card { background: rgba(255,255,255,0.92); backdrop-filter: blur(10px); border: 1px solid rgba(24,33,43,0.08); border-radius: 24px; box-shadow: 0 24px 60px rgba(24,33,43,0.08); }
        .auth { width: 100%; max-width: 440px; padding: 32px; }
        .brand { width: 56px; height: 56px; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1f5eff, #f15b2a); color: white; font-weight: 800; font-size: 20px; margin-bottom: 18px; }
        h1, h2, h3 { margin: 0; }
        p { line-height: 1.55; }
        .muted { color: #68717c; }
        .stack { display: grid; gap: 18px; }
        label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: #4b5560; margin-bottom: 6px; }
        input[type=text], input[type=password], textarea, select { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #d5dbe2; background: #fff; font: inherit; color: #18212b; }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #1f5eff; box-shadow: 0 0 0 4px rgba(31,94,255,0.12); }
        .btn { appearance: none; border: none; border-radius: 14px; padding: 12px 18px; font: inherit; font-weight: 700; cursor: pointer; transition: transform .12s ease, opacity .12s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, #1f5eff, #f15b2a); color: white; }
        .btn-secondary { background: #edf1f6; color: #18212b; }
        .btn-danger { background: #fff1f0; color: #b42318; }
        .alert { border-radius: 16px; padding: 14px 16px; font-size: 14px; }
        .alert.success { background: #eefaf0; color: #136c2e; border: 1px solid #a8ddb5; }
        .alert.error { background: #fff3f2; color: #a33024; border: 1px solid #f1b0a8; }
        .page { max-width: 1120px; margin: 0 auto; padding: 28px 20px 56px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 22px; }
        .title-line { display: flex; align-items: center; gap: 14px; }
        .title-chip { display: inline-flex; gap: 8px; flex-wrap: wrap; }
        .chip { font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.7); border: 1px solid rgba(24,33,43,0.08); color: #4b5560; }
        .grid { display: grid; gap: 20px; }
        .grid.two { grid-template-columns: 1.1fr .9fr; }
        .card { padding: 24px; }
        .card h2 { font-size: 20px; margin-bottom: 6px; }
        .card h3 { font-size: 16px; margin-bottom: 6px; }
        .stepper { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 20px; }
        .step { padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; background: #edf1f6; color: #5a6470; }
        .step.active { background: #18212b; color: white; }
        .step.done { background: #dff4e4; color: #136c2e; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
        .table th, .table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #edf1f6; vertical-align: top; }
        .table th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #68717c; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; color: #68717c; }
        .dropzone { border: 2px dashed #cdd6df; border-radius: 18px; padding: 26px; background: #fafcfe; }
        .dropzone p { margin: 0; }
        .result-list { display: grid; gap: 12px; margin-top: 14px; }
        .result-item { border-radius: 16px; padding: 14px; background: #f8fafc; border: 1px solid #e6ebf0; }
        details { border-top: 1px solid #edf1f6; padding-top: 16px; margin-top: 18px; }
        summary { cursor: pointer; font-weight: 700; color: #18212b; }
        @media (max-width: 900px) {
            .grid.two { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
    <?php
}

