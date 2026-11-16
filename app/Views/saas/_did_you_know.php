<?php
/**
 * "Did you know?" slideshow shown while a new workspace is being built.
 * Images: public/assets/slides/dyk-01.png … dyk-10.png (or .webp/.jpg). Until an image exists,
 * a built-in illustration in CheckA+ colours is shown instead.
 */
$slides = [
    ['Inspect where the signal isn\'t', 'CheckA+ keeps working with no signal — in the yard, the pit or out on the road. Everything syncs the moment you\'re back online.',
        'M4 9a12 12 0 0 1 16 0M7 12.5a7 7 0 0 1 10 0M10.5 16a2 2 0 0 1 3 0M3 3l18 18'],
    ['Scan a licence disc, skip the typing', 'Point your phone at the disc barcode and make, model, VIN, registration and expiry fill themselves in seconds.',
        'M4 6v12M7 6v12M10 6v12M13 6v12M16 6v12M19 6v12M2 4h4M18 4h4M2 20h4M18 20h4'],
    ['Every finding has proof', 'Attach timestamped photos and videos to any checklist item — and draw arrows and circles right on the damage.',
        'M4 7h3l2-3h6l2 3h3v12H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
    ['Know a car\'s past before you buy it', 'Run a VIN history check for accident, stolen and finance records — and attach the result to the report.',
        'M5 17h14l-1.5-6.5A2 2 0 0 0 15.6 9H8.4a2 2 0 0 0-1.9 1.5zM7 17v2M17 17v2M8 13h.01M16 13h.01M12 3l7 3v5'],
    ['Clients sign from their phone', 'Send a secure link — your client reviews the report, approves repairs line by line and e-signs. No app, no login.',
        'M4 18c3-6 5 2 8-3s4 1 8-2M4 21h16M15 4l5 5-9 9H6v-5z'],
    ['AI writes the summary for you', 'One tap turns your findings into a clear, plain-language summary with repair urgency — fix now, soon or monitor.',
        'M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9zM5 15l.6 1.4L7 17l-1.4.6L5 19l-.6-1.4L3 17l1.4-.6z'],
    ['Reports anyone can verify', 'Every report carries a QR code. Scan it and see the original — so buyers, banks and insurers know it\'s genuine.',
        'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2zM16 16h2v2h-2z'],
    ['From inspection to paid, in one flow', 'Turn findings into a quote, the quote into an invoice, and send a Pay Now link — card or EFT. Statements go out by themselves.',
        'M3 7h18v10H3zM3 10h18M7 14h4M17 4l2 3M7 4L5 7'],
    ['It\'s your brand, not ours', 'Your logo and colours on every report, client page and email. Your clients see your business — beautifully.',
        'M12 3a9 9 0 1 0 0 18c1 0 1.5-.7 1.5-1.5 0-.4-.2-.8-.4-1.1-.3-.3-.4-.6-.4-1 0-.8.7-1.4 1.5-1.4H16a5 5 0 0 0 5-5c0-4.4-4-8-9-8zM7.5 12h.01M9.5 8h.01M14.5 8h.01'],
    ['Bring the whole team', 'Add your mechanics and inspectors in a minute. Admins control checklists, pricing and settings — everyone else just inspects.',
        'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M17 11a3 3 0 1 0 0-6M22 21a6 6 0 0 0-4-5.6'],
];
$img = static function (int $n): ?string {
    foreach (['webp', 'png', 'jpg'] as $ext) {
        $f = 'assets/slides/dyk-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT) . '.' . $ext;
        if (is_file(FCPATH . $f)) {
            return '/' . $f;
        }
    }

    return null;
};
?>
<div class="dyk" id="dyk" aria-roledescription="carousel" aria-label="Did you know?">
  <style>
    .dyk{max-width:520px;margin:22px auto 0;text-align:left;font-family:inherit}
    .dyk-tag{display:inline-block;font:700 11px/1 'JetBrains Mono',ui-monospace,monospace;letter-spacing:.14em;text-transform:uppercase;color:#2a8f3a;background:#37b34a1a;border-radius:99px;padding:6px 11px;margin-bottom:12px}
    .dyk-stage{position:relative;overflow:hidden;border-radius:16px;background:#fff;border:1px solid #e2e4e6;box-shadow:0 14px 40px rgba(16,23,29,.07)}
    .dyk-track{display:flex;transition:transform .55s cubic-bezier(.2,.8,.2,1)}
    .dyk-slide{min-width:100%;box-sizing:border-box}
    .dyk-art{aspect-ratio:4/3;background:linear-gradient(160deg,#eef8f0,#f6f8f9);display:grid;place-items:center;position:relative;overflow:hidden}
    .dyk-art img{width:100%;height:100%;object-fit:cover;display:block}
    .dyk-art .blob{position:absolute;border-radius:50%;filter:blur(2px)}
    .dyk-body{padding:16px 20px 18px}
    .dyk-body h3{margin:0 0 6px;font-size:18px;line-height:1.3;color:#2d2d2d}
    .dyk-body p{margin:0;color:#5b6570;font-size:14.5px;line-height:1.55}
    .dyk-nav{display:flex;justify-content:center;gap:7px;margin-top:12px}
    .dyk-nav button{width:8px;height:8px;border-radius:99px;border:0;background:#d6dce1;padding:0;cursor:pointer;transition:all .3s}
    .dyk-nav button.on{width:22px;background:#37b34a}
    @media (prefers-reduced-motion: reduce){.dyk-track{transition:none}}
  </style>
  <span class="dyk-tag">Did you know?</span>
  <div class="dyk-stage">
    <div class="dyk-track">
      <?php foreach ($slides as $i => [$title, $text, $icon]): $src = $img($i + 1); ?>
        <div class="dyk-slide" role="group" aria-label="<?= $i + 1 ?> of <?= count($slides) ?>">
          <div class="dyk-art">
            <?php if ($src): ?>
              <img src="<?= esc($src, 'attr') ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>">
            <?php else: ?>
              <span class="blob" style="width:46%;height:60%;left:-6%;top:-12%;background:#37b34a22"></span>
              <span class="blob" style="width:34%;height:46%;right:-4%;bottom:-10%;background:#f5a52422"></span>
              <span class="blob" style="width:18%;height:24%;right:18%;top:12%;background:#2d2d2d10"></span>
              <svg viewBox="0 0 24 24" width="38%" height="38%" style="position:relative;filter:drop-shadow(0 10px 18px rgba(42,143,58,.25))" aria-hidden="true">
                <circle cx="12" cy="12" r="11.5" fill="#fff"/>
                <g transform="translate(4.2 4.2) scale(.65)" fill="none" stroke="#37b34a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></g>
              </svg>
            <?php endif; ?>
          </div>
          <div class="dyk-body"><h3><?= esc($title) ?></h3><p><?= esc($text) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="dyk-nav"><?php foreach ($slides as $i => $s): ?><button type="button" aria-label="Slide <?= $i + 1 ?>" class="<?= $i ? '' : 'on' ?>"></button><?php endforeach; ?></div>
  <script>
  (function(){
    var root = document.getElementById('dyk'), track = root.querySelector('.dyk-track'), dots = root.querySelectorAll('.dyk-nav button');
    var n = dots.length, i = 0, timer;
    function go(k){ i = (k + n) % n; track.style.transform = 'translateX(' + (-100 * i) + '%)'; dots.forEach(function(d, j){ d.classList.toggle('on', j === i); }); }
    function auto(){ clearInterval(timer); timer = setInterval(function(){ go(i + 1); }, 6500); }
    dots.forEach(function(d, j){ d.addEventListener('click', function(){ go(j); auto(); }); });
    var x0 = null;
    root.addEventListener('touchstart', function(e){ x0 = e.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', function(e){ if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); auto(); } x0 = null; });
    go(Math.floor(Math.random() * n)); auto();
  })();
  </script>
</div>
