<style>
:root{--bg:#f6f7f8;--card:#fff;--ink:#2d2d2d;--mut:#6b7178;--line:#e2e4e6;--pri:#37b34a;--pri2:#2a8f3a;--soft:#37b34a1a;--red:#c0392b;--amb:#b7791f;--blue:#2563eb;--r:12px}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:14.5px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
a{color:var(--pri)}
.shell{display:flex;min-height:100vh}
.side{width:236px;background:#fff;border-right:1px solid var(--line);color:var(--ink);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;flex-shrink:0}
.logo{display:flex;flex-direction:column;align-items:flex-start;gap:4px;padding:20px 18px 14px;text-decoration:none}
.logo img{height:40px;width:auto;display:block}
.logo small{display:block;font-weight:600;font-size:10.5px;color:var(--mut);letter-spacing:.12em;text-transform:uppercase}
.side nav{padding:6px 10px;flex:1}
.side nav a{display:flex;gap:11px;align-items:center;padding:9px 11px;border-radius:9px;color:var(--pri2);text-decoration:none;margin-bottom:2px;font-weight:500}
.side nav a svg{width:18px;height:18px;flex-shrink:0}
.side nav a:hover{background:var(--soft);color:var(--pri2)}
.side nav a.on{background:var(--pri);color:#fff}
.me{border-top:1px solid var(--line);padding:14px 18px;font-size:13px}
.me a{color:var(--ink);text-decoration:none;display:block}
.me small{display:block;color:var(--mut);overflow:hidden;text-overflow:ellipsis}
.me .out{margin-top:8px;color:var(--pri2);font-weight:600}
main{flex:1;padding:26px 30px 60px;min-width:0;max-width:1320px}
.burger{display:none}
h1{font-size:23px;margin:0 0 4px}
.sub{color:var(--mut);margin:0 0 20px}
.head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.head .sub{margin:0}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:20px;margin-bottom:18px}
.card h2{font-size:16px;margin:0 0 14px}
.card h2 small{font-weight:400;color:var(--mut);font-size:13px;margin-left:6px}
.grid{display:grid;gap:16px}
.g2{grid-template-columns:repeat(2,minmax(0,1fr))}.g3{grid-template-columns:repeat(3,minmax(0,1fr))}.g4{grid-template-columns:repeat(4,minmax(0,1fr))}
.stat{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:16px 18px}
.stat b{display:block;font-size:26px;letter-spacing:-.02em}
.stat span{color:var(--mut);font-size:13px}
.btn{display:inline-flex;align-items:center;gap:6px;background:var(--pri);color:#fff;border:0;border-radius:9px;padding:9px 16px;font:600 14px/1.2 inherit;cursor:pointer;text-decoration:none;white-space:nowrap}
.btn:hover{background:var(--pri2)}
.btn.sec{background:#fff;color:var(--ink);border:1px solid var(--line)}
.btn.sec:hover{background:#f6f8f9}
.btn.red{background:var(--red)}.btn.amb{background:var(--amb)}
.btn.sm{padding:6px 11px;font-size:13px}
.row-actions{display:flex;gap:8px;flex-wrap:wrap}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:10px 10px;border-bottom:1px solid var(--line);vertical-align:top}
th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);font-weight:600;background:#fafbfc}
tr:last-child td{border-bottom:0}
.tbl{overflow-x:auto;border:1px solid var(--line);border-radius:var(--r);background:#fff}
.tbl table td a{font-weight:600;text-decoration:none}
.muted{color:var(--mut)}.small{font-size:12.5px}.right{text-align:right}.nowrap{white-space:nowrap}
.badge{display:inline-block;padding:2px 9px;border-radius:99px;font-size:12px;font-weight:600;background:#eef1f4;color:#4a5560;white-space:nowrap}
.s-active{background:#e6f6e9;color:#1f7a2e}.s-trial{background:#e7efff;color:#2453c4}.s-provisioning{background:#f1ecff;color:#6b46c1}
.s-past_due{background:#fff4dc;color:#a0640c}.s-suspended{background:#fde8e6;color:#b42318}.s-cancelled{background:#eceff2;color:#59636e}
.tag{display:inline-block;padding:1px 7px;border-radius:6px;font-size:11.5px;background:#eef1f4;color:#4a5560;margin:0 4px 4px 0}
.tag.road{background:#fff4dc;color:#8a5a00}
label{display:block;font-weight:600;font-size:13px;margin-bottom:5px}
.f{margin-bottom:14px}
.f small,.hint{display:block;color:var(--mut);font-size:12.5px;font-weight:400;margin-top:4px}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=date],input[type=url],select,textarea{width:100%;padding:9px 11px;border:1px solid #cfd6dd;border-radius:9px;font:inherit;background:#fff;color:inherit}
input:focus,select:focus,textarea:focus{outline:2px solid #37b34a55;border-color:var(--pri)}
textarea{min-height:110px;resize:vertical}
.check{display:flex;gap:9px;align-items:flex-start;font-weight:500;margin-bottom:8px;cursor:pointer}
.check input{margin-top:3px;width:16px;height:16px;accent-color:var(--pri)}
.check small{display:block;color:var(--mut);font-weight:400;font-size:12.5px}
.mods{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:4px 18px}
.cat{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;margin:14px 0 8px;grid-column:1/-1}
.flash{padding:11px 14px;border-radius:10px;margin-bottom:16px;font-weight:500}
.flash.ok{background:#e6f6e9;color:#1f6b2c}.flash.err{background:#fde8e6;color:#9b1c13}.flash.info{background:#e7efff;color:#1e3f99}
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.filters input,.filters select{width:auto;min-width:160px}
.tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:18px;flex-wrap:wrap}
.tabs a{padding:9px 14px;text-decoration:none;color:var(--mut);font-weight:600;border-bottom:2px solid transparent;margin-bottom:-1px}
.tabs a.on{color:var(--pri);border-color:var(--pri)}
dl.kv{display:grid;grid-template-columns:150px 1fr;gap:7px 12px;margin:0}
dl.kv dt{color:var(--mut)}dl.kv dd{margin:0;word-break:break-word}
.bar{height:7px;background:#eef1f4;border-radius:9px;overflow:hidden;margin-top:5px}
.bar i{display:block;height:100%;background:var(--pri)}
.bar i.hi{background:var(--red)}
.empty{padding:34px;text-align:center;color:var(--mut)}
code,.mono{font-family:ui-monospace,Consolas,monospace;font-size:12.5px;background:#f1f3f5;padding:2px 6px;border-radius:5px;word-break:break-all}
.ev{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);font-size:13.5px}
.ev:last-child{border:0}.ev time{color:var(--mut);white-space:nowrap;min-width:118px}
.grid>*{min-width:0}
.card,.ev,dl.kv dd,.flash{overflow-wrap:anywhere}
@media(max-width:900px){.g3,.g4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){
  .side{position:fixed;left:-250px;z-index:20;transition:left .2s}.side.open{left:0}
  main{padding:16px}.burger{display:block;background:#fff;border:1px solid var(--line);border-radius:9px;padding:6px 11px;font-size:18px;margin-bottom:12px}
  .g2,.g3{grid-template-columns:1fr}.g4{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .stat{padding:12px 14px}.stat b{font-size:20px}
  dl.kv{grid-template-columns:1fr;gap:2px}dl.kv dd{margin-bottom:8px}
  .filters input,.filters select{width:100%}
  .card{padding:16px}h1{font-size:21px}
  .tbl table{min-width:640px}
  .ev{flex-direction:column;gap:2px}.ev time{min-width:0}
  .row-actions .btn{flex:1 1 auto;justify-content:center}
  .mods{grid-template-columns:1fr}
}
</style>
