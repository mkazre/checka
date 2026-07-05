<style>
  :root{
    --ink:#12171d; --ink-2:#3d4753; --ink-3:#6b7682;
    --paper:#ffffff; --ground:#eef1f3; --line:#d6dce1; --line-strong:#aab3bc;
    --panel:#151b22; --panel-2:#1e2731;
    --brand:#0d5e6e; --brand-ink:#0a3f4a;
    --ok:#15803d;   --ok-bg:#e7f6ec;   --ok-line:#8fd3a7;
    --minor:#1f4e79;--minor-bg:#e6eef6;--minor-line:#9db9d6;
    --lesser:#b45309;--lesser-bg:#fbeee0;--lesser-line:#e9c48c;
    --major:#b42318;--major-bg:#fbeae8;--major-line:#e9a29c;
    --radius:12px;
    --mono:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;
    --display:'Space Grotesk',system-ui,sans-serif;
    --body:'Inter',system-ui,sans-serif;
  }
  *{box-sizing:border-box}
  html,body{margin:0}
  body{background:var(--ground);color:var(--ink);font-family:var(--body);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
  a{color:var(--brand);text-decoration:none} a:hover{text-decoration:underline}
  h1,h2,h3,h4{font-family:var(--display);margin:0}

  /* App shell */
  .topbar{background:var(--panel);color:#e9eef2;position:sticky;top:0;z-index:30}
  .topbar .wrap{max-width:1180px;margin:0 auto;display:flex;align-items:center;gap:18px;padding:12px 20px}
  .logo{display:flex;align-items:center;gap:11px}
  .logo .mark{width:38px;height:38px;border-radius:9px;background:var(--brand);display:grid;place-items:center;flex:none;box-shadow:inset 0 0 0 1px rgba(255,255,255,.15)}
  .logo .mark svg{width:22px;height:22px}
  .logo b{font-family:var(--display);font-size:16px;font-weight:700;letter-spacing:.06em}
  .logo small{display:block;font-family:var(--mono);font-size:9.5px;letter-spacing:.22em;color:#8395a6;text-transform:uppercase}
  .nav{display:flex;gap:2px;margin-left:8px}
  .nav a{color:#93a3b4;font-weight:600;font-size:13.5px;padding:9px 13px;border-radius:8px}
  .nav a:hover{color:#fff;background:#ffffff12;text-decoration:none}
  .nav a.active{color:#fff;background:#ffffff1c}
  .topbar .right{margin-left:auto;display:flex;align-items:center;gap:14px}
  .who{text-align:right;font-family:var(--mono);font-size:11px;color:#8395a6;line-height:1.5}
  .who b{color:#e9eef2;font-weight:600}
  .btn{display:inline-flex;align-items:center;gap:7px;font-family:var(--display);font-weight:600;font-size:13.5px;
       padding:9px 15px;border-radius:9px;border:1px solid transparent;cursor:pointer;background:var(--brand);color:#fff}
  .btn:hover{filter:brightness(1.07);text-decoration:none}
  .btn.ghost{background:transparent;border-color:var(--line);color:var(--ink)}
  .btn.dark{background:var(--ink);color:#fff}
  .btn.sm{padding:6px 11px;font-size:12.5px;border-radius:8px}
  .btn.danger{background:var(--major)}
  .btn svg{width:15px;height:15px}

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
  .cols-3{grid-template-columns:repeat(3,1fr)}
  .cols-4{grid-template-columns:repeat(4,1fr)}
  .cols-2{grid-template-columns:repeat(2,1fr)}
  @media(max-width:820px){.cols-3,.cols-4,.cols-2{grid-template-columns:1fr 1fr}}
  @media(max-width:560px){.cols-3,.cols-4,.cols-2{grid-template-columns:1fr}.nav{display:none}}

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
  input:focus,select:focus,textarea:focus{outline:2px solid #0d5e6e40;outline-offset:1px;border-color:var(--brand)}
  .mono{font-family:var(--mono)}

  /* flash */
  .flash{padding:11px 15px;border-radius:10px;margin-bottom:16px;font-weight:500;border:1px solid}
  .flash.ok{background:var(--ok-bg);border-color:var(--ok-line);color:#0f5f2e}
  .flash.err{background:var(--major-bg);border-color:var(--major-line);color:#8a1a12}

  .empty{padding:44px 20px;text-align:center;color:var(--ink-3)}
  .empty svg{width:40px;height:40px;opacity:.4;margin-bottom:10px}
</style>
