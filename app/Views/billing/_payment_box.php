<?php
/**
 * "How to pay" block: Pay Now button + QR (online), and EFT bank details.
 * Styles are inline on purpose — mPDF ignores class rules on links and
 * on divs nested in table cells.
 *
 * @var float  $amountDue
 * @var string $payUrl     public pay page
 * @var string $reference  EFT reference to quote
 */
$online = setting('Billing.payOnlineEnabled') && trim((string) setting('Pay.paystackSecret')) !== '';
$eft    = setting('Billing.payEftEnabled') && trim((string) setting('Billing.bankAccountNumber')) !== '';
if ($amountDue <= 0 || (! $online && ! $eft)) {
    return;
}
$primary = brand('primary');
$bank    = array_filter([
    'Bank'           => setting('Billing.bankName'),
    'Account name'   => setting('Billing.bankAccountName'),
    'Account number' => setting('Billing.bankAccountNumber'),
    'Branch code'    => setting('Billing.bankBranchCode'),
    'Account type'   => setting('Billing.bankAccountType'),
    'SWIFT / BIC'    => setting('Billing.bankSwift'),
], static fn ($v) => trim((string) $v) !== '');
$boxStyle = 'border:1px solid #dfe3e7;background-color:#f7f9fa;padding:10px 12px;vertical-align:top';
$hStyle   = 'font-size:8pt;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#12171d;padding-bottom:6px';
?>
<table width="100%" style="margin-top:16px;border-collapse:separate;border-spacing:0"><tr>
  <?php if ($online): ?>
  <td width="<?= $eft ? '46%' : '100%' ?>" style="<?= $boxStyle ?>">
    <div style="<?= $hStyle ?>">Pay online</div>
    <table width="100%"><tr>
      <td style="vertical-align:top">
        <div style="font-size:7.8pt;color:#6b7682;padding-bottom:8px">Card or instant EFT — secure checkout. Click the button or scan the code.</div>
        <table style="border-collapse:collapse"><tr>
          <td style="background-color:<?= $primary ?>;padding:9px 16px;text-align:center">
            <a href="<?= esc($payUrl) ?>" style="color:#ffffff;text-decoration:none;font-weight:bold;font-size:11pt">PAY NOW &nbsp;<?= esc(money($amountDue)) ?></a>
          </td>
        </tr></table>
      </td>
      <td width="72" style="text-align:right;vertical-align:top"><barcode code="<?= esc($payUrl) ?>" type="QR" size="0.62" error="M" disableborder="1" /></td>
    </tr></table>
  </td>
  <?php endif; ?>
  <?php if ($online && $eft): ?><td width="2%"></td><?php endif; ?>
  <?php if ($eft): ?>
  <td style="<?= $boxStyle ?>">
    <div style="<?= $hStyle ?>">Pay by EFT</div>
    <table width="100%" style="border-collapse:collapse">
      <?php foreach ($bank as $k => $v): ?>
        <tr><td style="padding:2px 0;font-size:8.6pt;color:#6b7682;width:40%"><?= esc($k) ?></td><td style="padding:2px 0;font-size:8.6pt;font-weight:bold"><?= esc((string) $v) ?></td></tr>
      <?php endforeach; ?>
      <tr><td style="padding:4px 0 2px;font-size:8.6pt;color:#6b7682">Reference</td><td style="padding:4px 0 2px;font-size:9.5pt;font-weight:bold;color:<?= $primary ?>"><?= esc($reference) ?></td></tr>
    </table>
  </td>
  <?php endif; ?>
</tr></table>
<?php if (! empty($accountRef)): ?>
  <div style="font-size:7.8pt;color:#6b7682;margin-top:6px">Paying the full balance? Use your account reference <b><?= esc($reference) ?></b>. Paying one invoice? Use that invoice's number.</div>
<?php elseif (trim((string) setting('Billing.paymentInstructions')) !== ''): ?>
  <div style="font-size:7.8pt;color:#6b7682;margin-top:6px"><?= esc((string) setting('Billing.paymentInstructions')) ?></div>
<?php endif; ?>
