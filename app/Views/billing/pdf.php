<?php
/**
 * mPDF wrapper for invoices and statements. $pages is a list of
 * ['type' => 'invoice', 'b' => invoiceBundle] or
 * ['type' => 'statement', 'client' => ..., 'statement' => ..., 'payUrl' => ...],
 * rendered one after another with page breaks between them.
 */
$primary = brand('primary');
?>
<style>
  body{font-family:sans-serif;font-size:9pt;color:#1f2933}
  .muted{color:#6b7682}
  .small{font-size:7.8pt}
  .mono{font-family:monospace}
  .right{text-align:right}
  .doc-title{font-size:21pt;font-weight:bold;letter-spacing:1px;color:<?= $primary ?>}
  .co-name{font-size:12pt;font-weight:bold;color:#12171d}
  .meta td{padding:2px 0;font-size:8.6pt}
  .meta .k{color:#6b7682;padding-right:10px}
  .meta .v{font-weight:bold;text-align:right}
  .rule{border-bottom:2.5px solid <?= $primary ?>;height:1px;margin:10px 0 12px}
  .label{font-size:7pt;letter-spacing:1.4px;text-transform:uppercase;color:#6b7682;font-weight:bold;margin-bottom:4px}
  .party{font-size:9pt;line-height:1.45}
  .party b{font-size:10.5pt}
  table.items{width:100%;border-collapse:collapse;margin-top:14px}
  table.items th{background:#12171d;color:#fff;font-size:7.4pt;letter-spacing:1px;text-transform:uppercase;padding:7px 8px;text-align:left}
  table.items th.r, table.items td.r{text-align:right}
  table.items td{padding:8px 8px;border-bottom:1px solid #e6e9ec;font-size:9pt;vertical-align:top}
  table.items tr.alt td{background:#f8f9fa}
  table.totals{border-collapse:collapse}
  table.totals td{padding:4px 8px;font-size:9pt}
  table.totals .k{color:#6b7682}
  table.totals .v{text-align:right;font-weight:bold}
  .due-box{background:<?= $primary ?>;color:#fff;padding:9px 12px}
  .due-box .k{font-size:7.5pt;letter-spacing:1.3px;text-transform:uppercase;color:#fff}
  .due-box .v{font-size:16pt;font-weight:bold;color:#fff}
  .stamp{font-size:8pt;font-weight:bold;letter-spacing:1.5px;padding:4px 10px;border:1.5px solid}
  .box{border:1px solid #dfe3e7;background:#fafbfc;padding:10px 12px}
  .box h4{margin:0 0 6px;font-size:8pt;letter-spacing:1.2px;text-transform:uppercase;color:#12171d}
  .bank td{padding:2px 0;font-size:8.6pt}
  .bank .k{color:#6b7682;width:38%}
  .bank .v{font-weight:bold}
  .paybtn{background:<?= $primary ?>;color:#fff;font-weight:bold;font-size:11pt;padding:9px 16px;text-align:center}
  .paybtn a{color:#fff;text-decoration:none}
  table.ledger{width:100%;border-collapse:collapse;margin-top:10px}
  table.ledger th{background:#12171d;color:#fff;font-size:7.2pt;letter-spacing:1px;text-transform:uppercase;padding:6px 7px;text-align:left}
  table.ledger th.r, table.ledger td.r{text-align:right}
  table.ledger td{padding:6px 7px;border-bottom:1px solid #e6e9ec;font-size:8.6pt}
  table.ledger tr.open td{background:#f3f5f7;font-weight:bold}
  table.ageing{width:100%;border-collapse:collapse;margin-top:12px}
  table.ageing th{font-size:7pt;letter-spacing:1px;text-transform:uppercase;color:#6b7682;padding:5px;border-bottom:1px solid #dfe3e7;text-align:center}
  table.ageing td{font-size:9.5pt;font-weight:bold;padding:7px 5px;text-align:center;border-bottom:1px solid #dfe3e7}
  .notes{font-size:8.2pt;color:#3d4753;line-height:1.5}
  .foot{margin-top:16px;padding-top:8px;border-top:1px solid #e6e9ec;font-size:7.6pt;color:#6b7682;text-align:center}
</style>

<?php foreach ($pages as $i => $page): ?>
  <?php if ($i > 0): ?><pagebreak /><?php endif; ?>
  <?php if ($page['type'] === 'invoice'): ?>
    <?= view('billing/_invoice_page', ['b' => $page['b']], ['saveData' => false]) ?>
  <?php else: ?>
    <?= view('billing/_statement_page', ['client' => $page['client'], 'st' => $page['statement'], 'payUrl' => $page['payUrl']], ['saveData' => false]) ?>
  <?php endif; ?>
<?php endforeach; ?>
