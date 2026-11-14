// Shared header/footer + small interactive helpers for the static CheckA+ site.
// Include after config.js on every page: <body data-active-page="Home"> ... <script src="./config.js"></script><script src="./site.js"></script>
(function () {
  var NAV = [
    ['Home', 'Home.html'],
    ['Features', 'Features.html'],
    ['Pricing', 'Pricing.html'],
    ['Industries', 'Industries.html'],
    ['About', 'About.html'],
    ['Contact', 'Contact.html']
  ];

  function navLink(label, href, active, mobile) {
    var base = mobile
      ? "font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:15px;padding:12px 10px;border-radius:9px;text-decoration:none;"
      : "font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:14px;padding:9px 13px;border-radius:8px;text-decoration:none;white-space:nowrap;";
    var color = active ? 'color:#2a8f3a;background:#37b34a1f;' : (mobile ? 'color:#2d2d2d;' : 'color:#4a4a4a;');
    return '<a href="' + href + '" style="' + base + color + '">' + label + '</a>';
  }

  function headerHTML(active) {
    var links = NAV.map(function (l) { return navLink(l[0], l[1], l[0] === active, false); }).join('');
    var mobileLinks = NAV.map(function (l) { return navLink(l[0], l[1], l[0] === active, true); }).join('');
    return '' +
      '<header style="background:#ffffff;border-bottom:1px solid #e2e4e6;position:sticky;top:0;z-index:40">' +
      '<div style="max-width:1240px;margin:0 auto;display:flex;align-items:center;gap:20px;padding:14px 24px">' +
      '<a href="Home.html" style="display:flex;align-items:center;gap:10px;flex:none"><img src="assets/logo.png" alt="CheckA+" style="height:34px;width:auto;display:block"></a>' +
      '<nav id="desktopNav" style="display:flex;align-items:center;gap:2px;margin-left:8px;flex:1">' + links + '</nav>' +
      '<div id="desktopCtas" style="display:flex;align-items:center;gap:10px;flex:none;margin-left:auto">' +
      '<a href="Login.html" style="font-family:\'Inter\',system-ui,sans-serif;font-weight:600;font-size:14px;color:#2d2d2d;padding:9px 14px;border-radius:9px;text-decoration:none">Log in</a>' +
      '<a href="SignUp.html" style="font-family:\'Inter\',system-ui,sans-serif;font-weight:700;font-size:14px;color:#ffffff;background:#37b34a;padding:10px 18px;border-radius:9px;text-decoration:none;white-space:nowrap">Start free trial</a>' +
      '</div>' +
      '<button type="button" id="menuToggle" aria-label="Toggle menu" style="display:none;background:none;border:1px solid #e2e4e6;border-radius:9px;padding:8px 10px;margin-left:auto;cursor:pointer;color:#2d2d2d">' +
      '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"></path></svg></button>' +
      '</div>' +
      '<div id="mobileNav" style="display:none;border-top:1px solid #e2e4e6;padding:10px 18px 18px;flex-direction:column;gap:2px">' + mobileLinks +
      '<div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">' +
      '<a href="Login.html" style="font-family:\'Inter\',system-ui,sans-serif;font-weight:600;font-size:14px;color:#2d2d2d;border:1px solid #e2e4e6;text-align:center;padding:11px 14px;border-radius:9px;text-decoration:none">Log in</a>' +
      '<a href="SignUp.html" style="font-family:\'Inter\',system-ui,sans-serif;font-weight:700;font-size:14px;color:#ffffff;background:#37b34a;text-align:center;padding:12px 14px;border-radius:9px;text-decoration:none">Start free trial</a>' +
      '</div></div>' +
      '</header>';
  }

  function footerHTML() {
    var year = new Date().getFullYear();
    return '' +
      '<footer style="background:#2d2d2d;color:#c9cdd1">' +
      '<div style="max-width:1240px;margin:0 auto;padding:56px 24px 28px;display:grid;grid-template-columns:1.4fr repeat(3,1fr);gap:40px">' +
      '<div style="min-width:0"><img src="assets/logo.png" alt="CheckA+" style="height:30px;width:auto;display:block;filter:brightness(0) invert(1)">' +
      '<p style="font-family:\'Inter\',system-ui,sans-serif;font-size:13.5px;line-height:1.7;color:#9aa0a6;margin:16px 0 0;max-width:300px">Digital vehicle condition inspections, branded reports, client e-sign and invoicing — in one cloud app built for South African workshops, dealers and fleets.</p></div>' +
      '<div style="min-width:0"><div style="font-family:\'JetBrains Mono\',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#7a8187;margin-bottom:14px">Product</div>' +
      '<div style="display:flex;flex-direction:column;gap:10px">' +
      '<a href="Features.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Features</a>' +
      '<a href="Pricing.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Pricing</a>' +
      '<a href="Industries.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Industries</a>' +
      '<a href="SignUp.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Start free trial</a></div></div>' +
      '<div style="min-width:0"><div style="font-family:\'JetBrains Mono\',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#7a8187;margin-bottom:14px">Company</div>' +
      '<div style="display:flex;flex-direction:column;gap:10px">' +
      '<a href="About.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">About</a>' +
      '<a href="Contact.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Contact</a>' +
      '<a href="Login.html" style="font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd;text-decoration:none">Log in</a></div></div>' +
      '<div style="min-width:0"><div style="font-family:\'JetBrains Mono\',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#7a8187;margin-bottom:14px">Get in touch</div>' +
      '<div style="display:flex;flex-direction:column;gap:10px;font-family:\'Inter\',system-ui,sans-serif;font-size:14px;color:#d7dadd">' +
      '<a href="mailto:sales@wheelsturn.co.za" style="color:#d7dadd;text-decoration:none">sales@wheelsturn.co.za</a>' +
      '<a href="tel:0100232189" style="color:#d7dadd;text-decoration:none">010 023 2189</a>' +
      '<span style="color:#9aa0a6;font-size:13px">Roodepoort · Boksburg, South Africa</span></div></div>' +
      '</div>' +
      '<div style="border-top:1px solid #3a3a3a;max-width:1240px;margin:0 auto;padding:20px 24px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px">' +
      '<span style="font-family:\'JetBrains Mono\',monospace;font-size:11.5px;color:#7a8187">© ' + year + ' CheckA+ by WheelsTurn. All rights reserved.</span>' +
      '<span style="font-family:\'JetBrains Mono\',monospace;font-size:11.5px;color:#7a8187">POPIA compliant · Made in South Africa</span>' +
      '</div></footer>';
  }

  function mount() {
    var active = document.body.getAttribute('data-active-page') || '';
    var h = document.getElementById('site-header');
    var f = document.getElementById('site-footer');
    if (h) h.innerHTML = headerHTML(active);
    if (f) f.innerHTML = footerHTML();
    var toggle = document.getElementById('menuToggle');
    var mobileNav = document.getElementById('mobileNav');
    var desktopNav = document.getElementById('desktopNav');
    var desktopCtas = document.getElementById('desktopCtas');
    function layout() {
      var narrow = window.innerWidth <= 900;
      if (toggle) toggle.style.display = narrow ? 'block' : 'none';
      if (desktopNav) desktopNav.style.display = narrow ? 'none' : 'flex';
      if (desktopCtas) desktopCtas.style.display = narrow ? 'none' : 'flex';
      if (!narrow && mobileNav) mobileNav.style.display = 'none';
    }
    layout();
    window.addEventListener('resize', layout);
    if (toggle) toggle.addEventListener('click', function () {
      mobileNav.style.display = mobileNav.style.display === 'flex' ? 'none' : 'flex';
    });
  }

  document.addEventListener('DOMContentLoaded', mount);
})();
