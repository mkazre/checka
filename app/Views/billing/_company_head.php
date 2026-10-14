<?php
/**
 * Letterhead for invoice/statement PDF pages.
 * @var string $docTitle  e.g. "TAX INVOICE" / "STATEMENT"
 * @var array<string,string> $meta  label => value, printed top-right
 * @var string|null $stamp  optional status stamp text
 * @var string|null $stampColour
 */
$brandRel = feature('whiteLabel') ? trim((string) setting('Brand.logoPath')) : '';
$logoFile = $brandRel !== '' ? FCPATH . $brandRel : FCPATH . 'assets/img/logo.png';
$coLines  = array_filter([
    nl2br(esc((string) setting('Billing.companyAddress'))),
    trim(implode(' · ', array_filter([esc((string) setting('Billing.companyPhone')), esc((string) setting('Billing.companyEmail'))]))),
    trim(implode(' · ', array_filter([
        setting('Billing.companyRegNo') ? 'Reg ' . esc((string) setting('Billing.companyRegNo')) : '',
        setting('Billing.companyVatNo') ? 'VAT ' . esc((string) setting('Billing.companyVatNo')) : '',
    ]))),
]);
?>
<table width="100%"><tr>
  <td width="55%" style="vertical-align:top">
    <?php if (is_file($logoFile)): ?><img src="<?= $logoFile ?>" style="height:46px"><br><?php endif; ?>
    <div class="co-name" style="margin-top:6px"><?= esc((string) billing('companyName')) ?></div>
    <div class="small muted" style="line-height:1.5"><?= implode('<br>', $coLines) ?></div>
  </td>
  <td width="45%" style="vertical-align:top;text-align:right">
    <div class="doc-title"><?= esc($docTitle) ?></div>
    <table class="meta" align="right" style="margin-top:6px">
      <?php foreach ($meta as $k => $v): ?>
        <tr><td class="k"><?= esc($k) ?></td><td class="v"><?= esc($v) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php if (! empty($stamp)): ?>
      <div style="margin-top:8px"><span class="stamp" style="color:<?= $stampColour ?>;border-color:<?= $stampColour ?>"><?= esc($stamp) ?></span></div>
    <?php endif; ?>
  </td>
</tr></table>
<div class="rule"></div>
