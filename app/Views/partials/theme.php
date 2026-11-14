<style>
  :root{
    --ink:#2d2d2d; --ink-2:#4a4a4a; --ink-3:#7a7a7a;
    --paper:#ffffff; --ground:#f4f5f6; --line:#e2e4e6; --line-strong:#b8bcc0;
    --panel:#2d2d2d; --panel-2:#3a3a3a;
    --brand:#37b34a; --brand-ink:#2a8f3a;
    --logo-h:46px;   /* header logo height — set higher (e.g. 70px) for a taller nav */
    --ok:#15803d;   --ok-bg:#e7f6ec;   --ok-line:#8fd3a7;
    --minor:#1f4e79;--minor-bg:#e6eef6;--minor-line:#9db9d6;
    --lesser:#b45309;--lesser-bg:#fbeee0;--lesser-line:#e9c48c;
    --major:#b42318;--major-bg:#fbeae8;--major-line:#e9a29c;
    --radius:12px;
    --mono:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;
    --display:'Inter',system-ui,sans-serif;
    --body:'Inter',system-ui,sans-serif;
  }
  <?php if (own_branding()): ?>
  :root{ --brand:<?= brand('primary') ?>; --brand-ink:<?= brand('primary') ?>; --ink:<?= brand('dark') ?>; --panel:<?= brand('dark') ?>; }
  <?php endif; ?>
  *{box-sizing:border-box}
  html,body{margin:0;max-width:100%;overflow-x:hidden}
  *{box-sizing:border-box}
  body{background:var(--ground);color:var(--ink);font-family:var(--body);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
  a{color:var(--brand-ink);text-decoration:none;font-weight:500} a:hover{text-decoration:underline}
  h1,h2,h3,h4{font-family:var(--display);margin:0;letter-spacing:-.015em}

  /* App shell — light header carrying the CheckA+ logo */
  .topbar{background:#fff;color:var(--ink);position:sticky;top:0;z-index:30;border-bottom:1px solid var(--line);box-shadow:0 1px 3px rgba(45,45,45,.05)}
  .topbar .wrap{max-width:none;margin:0;display:flex;align-items:center;gap:18px;padding:8px 28px}
  .logo{display:flex;align-items:center}
  .logo img{height:var(--logo-h);width:auto;display:block}
  .nav{display:flex;gap:2px;margin-left:8px}
  .nav a{color:var(--ink-3);font-weight:600;font-size:13.5px;padding:9px 13px;border-radius:8px}
  .nav a:hover{color:var(--ink);background:#2d2d2d0d;text-decoration:none}
  .nav a.active{color:var(--brand-ink);background:#37b34a1f}
  .topbar .right{margin-left:auto;display:flex;align-items:center;gap:14px}
  .who{text-align:right;font-family:var(--mono);font-size:11px;color:var(--ink-3);line-height:1.5}
  .who b{color:var(--ink);font-weight:600}
  .btn{display:inline-flex;align-items:center;gap:7px;font-family:var(--display);font-weight:600;font-size:13.5px;white-space:nowrap;
       padding:9px 15px;border-radius:9px;border:1px solid transparent;cursor:pointer;background:var(--brand);color:#fff}
  .btn:hover{filter:brightness(1.07);text-decoration:none}
  .btn.ghost{background:transparent;border-color:var(--line);color:var(--ink)}
  .btn.dark{background:var(--ink);color:#fff}
  .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
  .btn.danger{background:var(--major)}
  .btn svg{width:15px;height:15px;flex:none}
  .nav a{white-space:nowrap}
  .topnav .right{flex:none}

  .container{max-width:1180px;margin:0 auto;padding:24px 20px 80px}
  .page-head{display:flex;align-items:flex-end;gap:16px;margin-bottom:20px;flex-wrap:wrap}
  .page-head h1{font-size:24px;font-weight:700;letter-spacing:-.01em}
  .page-head .sub{font-family:var(--mono);font-size:12px;color:var(--ink-3)}
  .page-head .spacer{margin-left:auto}

  .card{background:var(--paper);border:1px solid var(--line);border-radius:var(--radius);box-shadow:0 1px 2px rgba(16,23,29,.04)}
  .card .card-h{padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px}
  .card .card-h h3{font-size:15px;font-weight:600}
  .card .card-b{padding:18px}

  .grid{display:grid;gap:16px}
  /* let grid/flex children shrink so wide content scrolls inside its own box
     instead of stretching the card and the whole page */
  .grid > *{min-width:0}
  .card{min-width:0}
  .cols-3{grid-template-columns:repeat(3,1fr)}
  .cols-4{grid-template-columns:repeat(4,1fr)}
  .cols-2{grid-template-columns:repeat(2,1fr)}
  @media(max-width:820px){.cols-3,.cols-4,.cols-2{grid-template-columns:1fr 1fr}}
  @media(max-width:560px){.cols-3,.cols-4,.cols-2{grid-template-columns:1fr}}

  /* KPI tiles */
  .kpi{background:var(--paper);border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px}
  .kpi .k{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink-3)}
  .kpi .v{font-family:var(--display);font-weight:700;font-size:30px;line-height:1.1;margin-top:6px}

  /* status chips */
  .status{display:inline-flex;align-items:center;gap:7px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;border:1px solid;white-space:nowrap}
  .status .dot{width:8px;height:8px;border-radius:50%}
  .status.ok{color:var(--ok);background:var(--ok-bg);border-color:var(--ok-line)} .status.ok .dot{background:var(--ok)}
  .status.minor{color:var(--minor);background:var(--minor-bg);border-color:var(--minor-line)} .status.minor .dot{background:var(--minor)}
  .status.lesser{color:var(--lesser);background:var(--lesser-bg);border-color:var(--lesser-line)} .status.lesser .dot{background:var(--lesser)}
  .status.major{color:var(--major);background:var(--major-bg);border-color:var(--major-line)} .status.major .dot{background:var(--major)}
  .pill{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;border:1px solid var(--line);color:var(--ink-2)}
  .pill.draft{color:var(--lesser);background:var(--lesser-bg);border-color:var(--lesser-line)}
  .pill.completed{color:var(--ok);background:var(--ok-bg);border-color:var(--ok-line)}

  /* invoice status badges */
  .badge-s{font-family:var(--mono);font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:3px 8px;border-radius:999px;border:1px solid;white-space:nowrap}
  .s-draft{color:#6b7682;border-color:#d6dce1;background:#f4f5f6}
  .s-sent{color:#1f4e79;border-color:#bcd0e6;background:#e6eef6}
  .s-partial{color:#b45309;border-color:#f1d2a8;background:#fbeee0}
  .s-paid{color:#15803d;border-color:#b7e2c4;background:#e7f6ec}
  .s-void{color:#6b7682;border-color:#d6dce1;background:#fff;text-decoration:line-through}
  .s-overdue{color:#b42318;border-color:#f1bdb7;background:#fbeae8}
  [x-cloak]{display:none!important}

  /* tables */
  table.tbl{width:100%;border-collapse:collapse}
  table.tbl th{text-align:left;font-family:var(--mono);font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-3);font-weight:700;padding:10px 14px;border-bottom:1px solid var(--line)}
  table.tbl td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  table.tbl tr:last-child td{border-bottom:none}
  table.tbl tr:hover td{background:#fafbfc}

  /* forms */
  label.fld{display:flex;flex-direction:column;gap:5px}
  label.fld > span{font-family:var(--mono);font-size:11px;letter-spacing:.05em;color:var(--ink-3);text-transform:uppercase}
  input[type=text],input[type=email],input[type=password],input[type=number],input[type=date],select,textarea{
    border:1px solid var(--line);border-radius:9px;padding:10px 12px;font-size:14px;font-family:var(--body);background:#fff;color:var(--ink);width:100%}
  input:focus,select:focus,textarea:focus{outline:2px solid #37b34a55;outline-offset:1px;border-color:var(--brand)}
  .mono{font-family:var(--mono)}

  /* flash */
  .flash{padding:11px 15px;border-radius:10px;margin-bottom:16px;font-weight:500;border:1px solid}
  .flash.ok{background:var(--ok-bg);border-color:var(--ok-line);color:#0f5f2e}
  .flash.err{background:var(--major-bg);border-color:var(--major-line);color:#8a1a12}

  .empty{padding:44px 20px;text-align:center;color:var(--ink-3)}
  .empty svg{width:40px;height:40px;opacity:.4;margin-bottom:10px}

  /* ---------- header nav + mobile menu ---------- */
  .menu-toggle{display:none;background:none;border:1px solid var(--line);border-radius:9px;padding:6px 9px;cursor:pointer;color:var(--ink);margin-left:auto}
  .menu-toggle svg{width:22px;height:22px;display:block}
  .topnav{display:flex;align-items:center;gap:18px;flex:1}

  /* wrap wide tables so they scroll instead of breaking the layout */
  .tbl-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%}

  /* ---------- responsive ---------- */
  @media(max-width:900px){
    .topbar .wrap{flex-wrap:wrap;gap:10px}
    .logo img{height:40px}
    .menu-toggle{display:block}
    .topnav{display:none;flex-basis:100%;flex-direction:column;align-items:stretch;gap:6px;border-top:1px solid var(--line);padding-top:10px}
    .topbar.open .topnav{display:flex}
    .nav{flex-direction:column;margin-left:0;gap:2px}
    .nav a{padding:11px 12px;border-radius:9px}
    .topbar .right{margin-left:0;flex-direction:column;align-items:stretch;gap:10px}
    .who{text-align:left;order:2}
    .topbar .right .btn{justify-content:center;width:100%}
  }
  @media(max-width:640px){
    .container{padding:18px 14px 72px}
    .page-head h1{font-size:20px}
    .page-head{gap:10px;margin-bottom:16px}
    .page-head .spacer{flex-basis:100%;margin-left:0;height:0}
    .card .card-b{padding:14px}
    .card .card-h{flex-wrap:wrap;gap:8px}
    table.tbl th,table.tbl td{padding:9px 10px;white-space:nowrap}
    .btn{font-size:13px;padding:8px 13px}
    .btn.sm{padding:6px 10px;font-size:12px}
  }
</style>
