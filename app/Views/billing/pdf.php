<?php
/**
 * mPDF wrapper for invoices and statements. $pages is a list of
 * ['type' => 'invoice', 'b' => invoiceBundle] or
 * ['type' => 'statement', 'client' => ..., 'statement' => ..., 'payUrl' => ...],
 * rendered one after another with page breaks between them.
 */
?>
<?= view('billing/_pdf_styles', [], ['saveData' => false]) ?>

<?php foreach ($pages as $i => $page): ?>
  <?php if ($i > 0): ?><pagebreak /><?php endif; ?>
  <?php if ($page['type'] === 'invoice'): ?>
    <?= view('billing/_invoice_page', ['b' => $page['b']], ['saveData' => false]) ?>
  <?php else: ?>
    <?= view('billing/_statement_page', ['client' => $page['client'], 'st' => $page['statement'], 'payUrl' => $page['payUrl']], ['saveData' => false]) ?>
  <?php endif; ?>
<?php endforeach; ?>
