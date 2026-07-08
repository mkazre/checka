<?php

namespace App\Services;

/**
 * Builds a priced repair estimate from an assembled report. Any checklist item
 * that carries a cost-to-approve becomes a line item; totals include VAT.
 * Per-item client decisions (approved/declined) drive the "approved total".
 */
class QuoteService
{
    /** SA VAT default; override with checka.vatRate in .env. */
    public function vatRate(): float
    {
        $r = (float) (env('checka.vatRate') ?: 15);

        return $r > 0 ? $r : 15;
    }

    /**
     * @param array<string,mixed> $report
     * @return array<string,mixed>
     */
    public function build(array $report): array
    {
        $items = [];
        foreach ($report['sections'] as $section) {
            foreach ($section['items'] as $it) {
                $cost = (float) ($it['cost'] ?? 0);
                if ($cost <= 0) {
                    continue;
                }
                $items[] = [
                    'value_id' => $it['id'],
                    'ref'      => $it['ref'],
                    'name'     => $it['name'],
                    'status'   => $it['status'] ?? 'ok',
                    'comment'  => $it['comment'] ?? '',
                    'cost'     => $cost,
                    'approved' => $it['approved'] ?? null, // null undecided, 1 approve, 0 decline
                ];
            }
        }

        $rate     = $this->vatRate();
        $subtotal = array_sum(array_column($items, 'cost'));
        $approved = array_sum(array_map(
            static fn ($i) => (int) $i['approved'] === 1 ? $i['cost'] : 0,
            $items
        ));

        return [
            'items'         => $items,
            'count'         => count($items),
            'vatRate'       => $rate,
            'subtotal'      => $subtotal,
            'vat'           => $subtotal * $rate / 100,
            'total'         => $subtotal * (1 + $rate / 100),
            'approvedNet'   => $approved,
            'approvedTotal' => $approved * (1 + $rate / 100),
            'decided'       => count(array_filter($items, static fn ($i) => $i['approved'] !== null)),
        ];
    }

    /** Money formatter (R 1 234.56). */
    public static function money(float $v): string
    {
        return 'R ' . number_format($v, 2, '.', ' ');
    }
}
