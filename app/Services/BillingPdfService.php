<?php

namespace App\Services;

/**
 * Renders invoices and client statements to PDF. A statement can carry the
 * client's open invoices behind it in the same file — that's the monthly
 * "statement + invoices" bundle.
 */
class BillingPdfService
{
    /** @return array{bytes:string, filename:string} */
    public function invoice(array $invoice): array
    {
        $bundle = (new BillingService())->invoiceBundle($invoice);
        $html   = view('billing/pdf', ['pages' => [['type' => 'invoice', 'b' => $bundle]]]);

        return [
            'bytes'    => service('pdf')->toString($html, 'Invoice ' . $invoice['invoice_no']),
            'filename' => $this->safe('Invoice-' . $invoice['invoice_no']) . '.pdf',
        ];
    }

    /** @return array{bytes:string, filename:string} */
    public function statement(array $client, string $from, string $to, bool $withOpenInvoices = true): array
    {
        $svc       = new BillingService();
        $statement = $svc->statement((int) $client['id'], $from, $to);

        $pages = [[
            'type'      => 'statement',
            'client'    => $client,
            'statement' => $statement,
            'payUrl'    => site_url('pay/account/' . $client['uid']),
        ]];
        if ($withOpenInvoices) {
            foreach ($statement['open'] as $inv) {
                $pages[] = ['type' => 'invoice', 'b' => $svc->invoiceBundle($inv)];
            }
        }

        $html = view('billing/pdf', ['pages' => $pages]);

        return [
            'bytes'    => service('pdf')->toString($html, 'Statement — ' . $client['name']),
            'filename' => $this->safe('Statement-' . $client['name'] . '-' . date('Y-m', strtotime($to))) . '.pdf',
        ];
    }

    private function safe(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', $name), '-');
    }
}
