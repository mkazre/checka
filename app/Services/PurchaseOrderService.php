<?php

namespace App\Services;

use App\Models\PurchaseOrderItemModel;
use App\Models\PurchaseOrderModel;
use App\Models\VendorModel;

/**
 * Purchase orders: numbering, line items, totals, and the PDF.
 *
 * Prices are optional per line (null = not priced yet). Totals only add up
 * priced lines, entered VAT-exclusive. Whether pricing / vehicle details
 * appear on the PDF is a per-PO default that can be overridden per download.
 */
class PurchaseOrderService
{
    public function nextPoNo(): string
    {
        $prefix = (string) (setting('Purchasing.poPrefix') ?: 'PO-');
        $n      = max(1, (int) (setting('Purchasing.nextPoNumber') ?: 1001));
        $model  = model(PurchaseOrderModel::class);
        while ($model->withDeleted()->where('po_no', $prefix . $n)->countAllResults() > 0) {
            $n++;
        }
        setting()->set('Purchasing.nextPoNumber', $n + 1);

        return $prefix . $n;
    }

    /** @param list<array{description:string,part_no?:?string,qty:mixed,unit_price:mixed}> $items */
    public function replaceItems(int $poId, array $items): void
    {
        $m = model(PurchaseOrderItemModel::class);
        $m->where('po_id', $poId)->delete();
        $sort = 0;
        foreach ($items as $it) {
            $desc = trim((string) ($it['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $price = $it['unit_price'] ?? null;
            $m->insert([
                'po_id'       => $poId,
                'description' => mb_substr($desc, 0, 255),
                'part_no'     => trim((string) ($it['part_no'] ?? '')) ?: null,
                'qty'         => (float) ($it['qty'] ?? 1) ?: 1,
                'unit_price'  => ($price === null || $price === '') ? null : round((float) $price, 2),
                'sort'        => $sort++,
            ]);
        }
        $this->recalc($poId);
    }

    public function recalc(int $poId): void
    {
        $po = model(PurchaseOrderModel::class)->find($poId);
        if ($po === null) {
            return;
        }
        $sub = 0.0;
        foreach (model(PurchaseOrderItemModel::class)->forPo($poId) as $it) {
            if ($it['unit_price'] !== null) {
                $sub += (float) $it['qty'] * (float) $it['unit_price'];
            }
        }
        $sub = round($sub, 2);
        $vat = round($sub * (float) $po['vat_rate'] / 100, 2);
        model(PurchaseOrderModel::class)->update($poId, ['subtotal' => $sub, 'vat_amount' => $vat, 'total' => round($sub + $vat, 2)]);
    }

    /** Remove every price on the PO (keeps the lines). */
    public function clearPrices(int $poId): void
    {
        model(PurchaseOrderItemModel::class)->where('po_id', $poId)->set('unit_price', null)->update();
        $this->recalc($poId);
    }

    public function bundle(array $po): array
    {
        $items  = model(PurchaseOrderItemModel::class)->forPo((int) $po['id']);
        $priced = count(array_filter($items, static fn ($i) => $i['unit_price'] !== null));

        return [
            'po'       => $po,
            'vendor'   => model(VendorModel::class)->withDeleted()->find($po['vendor_id']),
            'items'    => $items,
            'priced'   => $priced,
            'unpriced' => count($items) - $priced,
            'author'   => $this->authorName($po['created_by'] ?? null),
        ];
    }

    /**
     * @param bool|null $pricing null = the PO's own setting
     * @param bool|null $vehicle null = the PO's own setting
     * @return array{bytes:string, filename:string}
     */
    public function pdf(array $po, ?bool $pricing = null, ?bool $vehicle = null): array
    {
        $b = $this->bundle($po);
        $b['showPricing'] = $pricing ?? (bool) $po['show_pricing'];
        $b['showVehicle'] = ($vehicle ?? (bool) $po['show_vehicle']) && PurchaseOrderModel::hasVehicle($po);

        $html = view('purchase_orders/pdf', $b);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', 'PurchaseOrder-' . $po['po_no']
            . (($b['vendor']['name'] ?? '') !== '' ? '-' . $b['vendor']['name'] : '')) . ($b['showPricing'] ? '' : '-unpriced') . '.pdf';

        return ['bytes' => service('pdf')->toString($html, 'Purchase Order ' . $po['po_no']), 'filename' => trim($name, '-')];
    }

    private function authorName($userId): string
    {
        if (! $userId) {
            return '';
        }
        $u = db_connect()->table('users')->select('username')->where('id', $userId)->get()->getRowArray();

        return (string) ($u['username'] ?? '');
    }
}
