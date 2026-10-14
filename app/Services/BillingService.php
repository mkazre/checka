<?php

namespace App\Services;

use App\Models\ClientModel;
use App\Models\InspectionModel;
use App\Models\InvoiceItemModel;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Models\VehicleModel;
use RuntimeException;

/**
 * The invoicing ledger: numbering, totals, payment allocation, client
 * balances, statements and ageing. Every money figure the UI, PDFs and the
 * monthly statement run show comes from here so they can't disagree.
 *
 * Accounting rules:
 *  - A draft invoice is not owed yet; it counts once issued (sent/partial/paid).
 *  - A void invoice never counts.
 *  - Payments always count. A payment without an invoice is credit on account.
 */
class BillingService
{
    private const COUNTED = ['sent', 'partial', 'paid'];

    // ------------------------------------------------------------------
    // Invoices
    // ------------------------------------------------------------------

    public function nextInvoiceNo(): string
    {
        $prefix = (string) setting('Billing.invoicePrefix');
        $n      = max(1, (int) setting('Billing.nextInvoiceNumber'));
        $model  = model(InvoiceModel::class);

        // Skip any number already taken (e.g. the counter was lowered by hand).
        // withDeleted() only lasts one query, so it's re-applied each loop.
        while ($model->withDeleted()->where('invoice_no', $prefix . $n)->countAllResults() > 0) {
            $n++;
        }
        setting()->set('Billing.nextInvoiceNumber', $n + 1);

        return $prefix . $n;
    }

    /**
     * @param list<array{description:string,qty:float|int|string,unit_price:float|int|string}> $items
     * @param array<string,mixed> $opts inspection_id, issue_date, due_date, notes, status
     */
    public function createInvoice(int $clientId, array $items, array $opts = []): int
    {
        $client = model(ClientModel::class)->find($clientId);
        if ($client === null) {
            throw new RuntimeException('Client not found.');
        }

        $issue = $opts['issue_date'] ?? date('Y-m-d');
        $terms = (int) ($client['payment_terms_days'] ?? 0) ?: (int) setting('Billing.paymentTermsDays');

        $db = db_connect();
        $db->transStart();

        $id = (int) model(InvoiceModel::class)->insert([
            'uid'           => bin2hex(random_bytes(10)),
            'invoice_no'    => $this->nextInvoiceNo(),
            'client_id'     => $clientId,
            'inspection_id' => $opts['inspection_id'] ?? null,
            'status'        => $opts['status'] ?? 'draft',
            'issue_date'    => $issue,
            'due_date'      => $opts['due_date'] ?? date('Y-m-d', strtotime($issue . ' +' . $terms . ' days')),
            'vat_rate'      => setting('Billing.vatRegistered') ? (float) setting('Billing.vatRate') : 0,
            'notes'         => $opts['notes'] ?? null,
            'created_by'    => function_exists('auth') && auth()->loggedIn() ? auth()->id() : null,
        ], true);

        $this->replaceItems($id, $items);
        $this->recalc($id);

        $db->transComplete();

        return $id;
    }

    /** @param list<array{description:string,qty:mixed,unit_price:mixed}> $items */
    public function replaceItems(int $invoiceId, array $items): void
    {
        $itemModel = model(InvoiceItemModel::class);
        $itemModel->where('invoice_id', $invoiceId)->delete();

        $sort = 0;
        foreach ($items as $it) {
            $desc = trim((string) ($it['description'] ?? ''));
            $qty  = (float) ($it['qty'] ?? 0);
            $unit = round((float) ($it['unit_price'] ?? 0), 2);
            if ($desc === '' || $qty == 0.0) {
                continue;
            }
            $itemModel->insert([
                'invoice_id'  => $invoiceId,
                'description' => mb_substr($desc, 0, 255),
                'qty'         => $qty,
                'unit_price'  => $unit,
                'line_total'  => round($qty * $unit, 2),
                'sort'        => $sort++,
            ]);
        }
    }

    /** Recompute subtotal / VAT / total from the line items. Prices are entered VAT-exclusive. */
    public function recalc(int $invoiceId): void
    {
        $model = model(InvoiceModel::class);
        $inv   = $model->find($invoiceId);
        if ($inv === null) {
            return;
        }
        $subtotal = 0.0;
        foreach (model(InvoiceItemModel::class)->forInvoice($invoiceId) as $it) {
            $subtotal += (float) $it['line_total'];
        }
        $subtotal = round($subtotal, 2);
        $vat      = round($subtotal * ((float) $inv['vat_rate']) / 100, 2);

        $model->update($invoiceId, [
            'subtotal'   => $subtotal,
            'vat_amount' => $vat,
            'total'      => round($subtotal + $vat, 2),
        ]);
        $this->refreshPaid($invoiceId);
    }

    /** Build a draft invoice from an inspection's charge. Returns the existing one if already invoiced. */
    public function fromInspection(array $inspection): int
    {
        $existing = model(InvoiceModel::class)->where('inspection_id', $inspection['id'])->where('status !=', 'void')->first();
        if ($existing) {
            return (int) $existing['id'];
        }
        if (empty($inspection['client_id'])) {
            throw new RuntimeException('This inspection has no client — link a client before invoicing.');
        }

        $vehicle = $inspection['vehicle_id'] ? model(VehicleModel::class)->find($inspection['vehicle_id']) : null;
        $label   = $vehicle ? model(VehicleModel::class)->label($vehicle) : 'Vehicle';
        $reg     = $vehicle['plate'] ?? '';
        $desc    = trim((string) ($inspection['charge_description'] ?: setting('Billing.defaultChargeDescription')))
            . ' — ' . $label . ($reg ? ' (' . $reg . ')' : '')
            . ' · Report ' . strtoupper(substr((string) $inspection['report_uid'], 0, 8))
            . ' · ' . ($inspection['inspection_date'] ?: date('Y-m-d'));

        $amount = $inspection['charge_amount'] !== null && $inspection['charge_amount'] !== ''
            ? (float) $inspection['charge_amount']
            : (float) setting('Billing.defaultInspectionFee');

        return $this->createInvoice((int) $inspection['client_id'], [
            ['description' => $desc, 'qty' => 1, 'unit_price' => $amount],
        ], ['inspection_id' => (int) $inspection['id']]);
    }

    /** Draft → issued. Called when the invoice is emailed or marked as issued by hand. */
    public function markIssued(int $invoiceId): void
    {
        $inv = model(InvoiceModel::class)->find($invoiceId);
        if ($inv && $inv['status'] === 'draft') {
            model(InvoiceModel::class)->update($invoiceId, ['status' => 'sent']);
            $this->refreshPaid($invoiceId);
        }
    }

    /** amount_paid from the payments table; status follows it (draft/void are left alone). */
    public function refreshPaid(int $invoiceId): void
    {
        $model = model(InvoiceModel::class);
        $inv   = $model->find($invoiceId);
        if ($inv === null) {
            return;
        }
        $paid = (float) (model(PaymentModel::class)->selectSum('amount')->where('invoice_id', $invoiceId)->first()['amount'] ?? 0);
        $data = ['amount_paid' => round($paid, 2)];

        if (! in_array($inv['status'], ['draft', 'void'], true)) {
            $total          = (float) $inv['total'];
            $data['status'] = ($total > 0 && $paid >= $total - 0.005) ? 'paid' : ($paid > 0 ? 'partial' : 'sent');
        }
        $model->update($invoiceId, $data);
    }

    // ------------------------------------------------------------------
    // Payments
    // ------------------------------------------------------------------

    /**
     * Record money received. With an invoice, it's applied there first;
     * anything left over (or a payment with no invoice) is allocated to the
     * client's oldest open invoices, and any final remainder is kept as
     * credit on account. Split payments share the same reference.
     *
     * @param array{method?:string,reference?:string,gateway_ref?:string,paid_at?:string,notes?:string} $meta
     * @return list<int> payment row ids
     */
    public function recordPayment(int $clientId, float $amount, array $meta = [], ?int $invoiceId = null): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be more than zero.');
        }

        $targets = [];
        if ($invoiceId !== null) {
            $inv = model(InvoiceModel::class)->find($invoiceId);
            if ($inv && (int) $inv['client_id'] === $clientId && $inv['status'] !== 'void') {
                if ($inv['status'] === 'draft') {
                    $this->markIssued($invoiceId);
                    $inv = model(InvoiceModel::class)->find($invoiceId);
                }
                $targets[] = $inv;
            }
        }
        foreach ($this->openInvoices($clientId) as $inv) {
            if ($invoiceId === null || (int) $inv['id'] !== $invoiceId) {
                $targets[] = $inv;
            }
        }

        $base = [
            'client_id'   => $clientId,
            'method'      => array_key_exists($meta['method'] ?? '', PaymentModel::METHODS) ? $meta['method'] : 'eft',
            'reference'   => $meta['reference'] ?? null,
            'gateway_ref' => $meta['gateway_ref'] ?? null,
            'paid_at'     => $meta['paid_at'] ?? date('Y-m-d'),
            'notes'       => $meta['notes'] ?? null,
            'recorded_by' => function_exists('auth') && auth()->loggedIn() ? auth()->id() : null,
        ];

        $db = db_connect();
        $db->transStart();

        $ids       = [];
        $remaining = $amount;
        foreach ($targets as $inv) {
            if ($remaining <= 0) {
                break;
            }
            $due = InvoiceModel::balance($inv);
            if ($due <= 0) {
                continue;
            }
            $apply = min($due, $remaining);
            $ids[] = (int) model(PaymentModel::class)->insert($base + ['invoice_id' => $inv['id'], 'amount' => $apply], true);
            $this->refreshPaid((int) $inv['id']);
            $remaining = round($remaining - $apply, 2);
        }
        if ($remaining > 0) {
            $ids[] = (int) model(PaymentModel::class)->insert($base + ['invoice_id' => null, 'amount' => $remaining, 'notes' => trim(($base['notes'] ?? '') . ' (credit on account)')], true);
        }

        $db->transComplete();

        return $ids;
    }

    public function deletePayment(int $paymentId): void
    {
        $p = model(PaymentModel::class)->find($paymentId);
        if ($p === null) {
            return;
        }
        model(PaymentModel::class)->delete($paymentId);
        if ($p['invoice_id']) {
            $this->refreshPaid((int) $p['invoice_id']);
        }
    }

    public function gatewayRefUsed(string $ref): bool
    {
        return $ref !== '' && model(PaymentModel::class)->where('gateway_ref', $ref)->countAllResults() > 0;
    }

    // ------------------------------------------------------------------
    // Balances, ageing, statements
    // ------------------------------------------------------------------

    /** @return list<array<string,mixed>> issued invoices with money still owing, oldest first */
    public function openInvoices(int $clientId): array
    {
        return model(InvoiceModel::class)
            ->where('client_id', $clientId)
            ->whereIn('status', InvoiceModel::OPEN)
            ->orderBy('due_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** Positive = client owes us; negative = credit on account. Optionally as at end of a date. */
    public function balance(int $clientId, ?string $asAt = null): float
    {
        $inv = model(InvoiceModel::class)->selectSum('total')->where('client_id', $clientId)->whereIn('status', self::COUNTED);
        $pay = model(PaymentModel::class)->selectSum('amount')->where('client_id', $clientId);
        if ($asAt !== null) {
            $inv->where('issue_date <=', $asAt);
            $pay->where('paid_at <=', $asAt);
        }

        return round((float) ($inv->first()['total'] ?? 0) - (float) ($pay->first()['amount'] ?? 0), 2);
    }

    /** Outstanding amounts bucketed by how far past due they are. */
    public function ageing(int $clientId): array
    {
        $b     = ['current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'd90plus' => 0.0];
        $today = new \DateTimeImmutable(date('Y-m-d'));
        foreach ($this->openInvoices($clientId) as $inv) {
            $due  = InvoiceModel::balance($inv);
            $days = (int) $today->diff(new \DateTimeImmutable($inv['due_date']))->format('%r%a') * -1;
            $key  = $days <= 0 ? 'current' : ($days <= 30 ? 'd30' : ($days <= 60 ? 'd60' : ($days <= 90 ? 'd90' : 'd90plus')));
            $b[$key] += $due;
        }

        return array_map(static fn ($v) => round($v, 2), $b);
    }

    /**
     * Statement for a period: opening balance, every invoice (debit) and
     * payment (credit) in date order with a running balance, closing balance.
     */
    public function statement(int $clientId, string $from, string $to): array
    {
        $opening = $this->balance($clientId, date('Y-m-d', strtotime($from . ' -1 day')));

        $lines = [];
        $invoices = model(InvoiceModel::class)->where('client_id', $clientId)->whereIn('status', self::COUNTED)
            ->where('issue_date >=', $from)->where('issue_date <=', $to)->findAll();
        foreach ($invoices as $i) {
            $lines[] = ['date' => $i['issue_date'], 'sort' => 0, 'type' => 'invoice', 'ref' => $i['invoice_no'], 'uid' => $i['uid'],
                'description' => 'Invoice ' . $i['invoice_no'] . ' (due ' . $i['due_date'] . ')', 'debit' => (float) $i['total'], 'credit' => 0.0];
        }
        $payments = model(PaymentModel::class)->select('payments.*, invoices.invoice_no')
            ->join('invoices', 'invoices.id = payments.invoice_id', 'left')
            ->where('payments.client_id', $clientId)->where('paid_at >=', $from)->where('paid_at <=', $to)->findAll();
        foreach ($payments as $p) {
            $lines[] = ['date' => $p['paid_at'], 'sort' => 1, 'type' => 'payment', 'ref' => $p['reference'] ?: $p['gateway_ref'], 'uid' => null,
                'description' => 'Payment received — ' . (PaymentModel::METHODS[$p['method']] ?? $p['method'])
                    . ($p['invoice_no'] ? ' · ' . $p['invoice_no'] : ' · on account'),
                'debit' => 0.0, 'credit' => (float) $p['amount']];
        }
        usort($lines, static fn ($a, $b) => [$a['date'], $a['sort']] <=> [$b['date'], $b['sort']]);

        $running = $opening;
        $debits  = 0.0;
        $credits = 0.0;
        foreach ($lines as &$l) {
            $running += $l['debit'] - $l['credit'];
            $debits  += $l['debit'];
            $credits += $l['credit'];
            $l['balance'] = round($running, 2);
        }
        unset($l);

        return [
            'from'    => $from,
            'to'      => $to,
            'opening' => $opening,
            'lines'   => $lines,
            'debits'  => round($debits, 2),
            'credits' => round($credits, 2),
            'closing' => round($running, 2),
            'ageing'  => $this->ageing($clientId),
            'open'    => $this->openInvoices($clientId),
        ];
    }

    /** Full invoice bundle for views/PDFs: invoice + items + client + payments + linked inspection. */
    public function invoiceBundle(array $invoice): array
    {
        $inspection = $invoice['inspection_id'] ? model(InspectionModel::class)->find($invoice['inspection_id']) : null;
        $vehicle    = ($inspection && $inspection['vehicle_id']) ? model(VehicleModel::class)->find($inspection['vehicle_id']) : null;

        return [
            'invoice'    => $invoice,
            'items'      => model(InvoiceItemModel::class)->forInvoice((int) $invoice['id']),
            'client'     => model(ClientModel::class)->withDeleted()->find($invoice['client_id']),
            'payments'   => model(PaymentModel::class)->where('invoice_id', $invoice['id'])->orderBy('paid_at', 'ASC')->findAll(),
            'inspection' => $inspection,
            'vehicle'    => $vehicle,
            'balance'    => InvoiceModel::balance($invoice),
            'payUrl'     => site_url('pay/' . $invoice['uid']),
        ];
    }
}
