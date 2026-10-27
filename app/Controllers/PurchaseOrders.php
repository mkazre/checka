<?php

namespace App\Controllers;

use App\Models\PurchaseOrderModel;
use App\Models\VehicleModel;
use App\Models\VendorModel;
use App\Services\Mailer;
use App\Services\PurchaseOrderService;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Purchase orders to vendors (feature: purchasing). */
class PurchaseOrders extends BaseController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        if (! feature('purchasing')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        $f = $this->filters();

        return view('purchase_orders/index', [
            'title'   => 'Purchase Orders',
            'orders'  => model(PurchaseOrderModel::class)->listing($f),
            'f'       => $f,
            'vendors' => model(VendorModel::class)->search('', 2000),
        ]);
    }

    /** Summary of POs in a date range (screen or PDF). */
    public function summary()
    {
        $f = $this->filters(true);
        $data = $this->summaryData($f);
        if ($this->request->getGet('pdf')) {
            $html = view('purchase_orders/summary_pdf', $data);
            $pdf  = service('pdf')->toString($html, 'Purchase order summary');

            return $this->response->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="PO-Summary-' . $f['from'] . '-to-' . $f['to'] . '.pdf"')
                ->setBody($pdf);
        }

        return view('purchase_orders/summary', $data + ['title' => 'Purchase Order Summary', 'vendors' => model(VendorModel::class)->search('', 2000)]);
    }

    public function new()
    {
        $vendor = ($u = (string) $this->request->getGet('vendor')) ? model(VendorModel::class)->findByUid($u) : null;

        return $this->form(null, [['description' => '', 'part_no' => '', 'qty' => 1, 'unit_price' => null]], (int) ($vendor['id'] ?? 0));
    }

    public function create()
    {
        $vendor = model(VendorModel::class)->find((int) $this->request->getPost('vendor_id'));
        if ($vendor === null) {
            return redirect()->back()->withInput()->with('error', 'Choose the vendor this order is for. New supplier? Add them under Vendors first.');
        }
        $items = $this->postedItems();
        if ($items === []) {
            return redirect()->back()->withInput()->with('error', 'Add at least one item to order.');
        }

        $svc  = new PurchaseOrderService();
        $data = $this->postedHeader() + [
            'uid'        => bin2hex(random_bytes(10)),
            'po_no'      => $svc->nextPoNo(),
            'vendor_id'  => (int) $vendor['id'],
            'status'     => 'draft',
            'created_by' => auth()->id(),
        ];
        $id = (int) model(PurchaseOrderModel::class)->insert($data, true);
        $svc->replaceItems($id, $items);

        return redirect()->to('purchase-orders/' . $data['uid'])->with('message', 'Purchase order ' . $data['po_no'] . ' created.');
    }

    public function show(string $uid)
    {
        $po = $this->findOr404($uid);

        return view('purchase_orders/show', ['title' => 'PO ' . $po['po_no']] + (new PurchaseOrderService())->bundle($po));
    }

    public function edit(string $uid)
    {
        $po = $this->findOr404($uid);

        return $this->form($po, model(\App\Models\PurchaseOrderItemModel::class)->forPo((int) $po['id']), (int) $po['vendor_id']);
    }

    public function update(string $uid)
    {
        $po     = $this->findOr404($uid);
        $vendor = model(VendorModel::class)->find((int) $this->request->getPost('vendor_id'));
        $items  = $this->postedItems();
        if ($vendor === null || $items === []) {
            return redirect()->back()->withInput()->with('error', $vendor === null ? 'Choose a vendor.' : 'Add at least one item.');
        }
        model(PurchaseOrderModel::class)->update($po['id'], $this->postedHeader() + ['vendor_id' => (int) $vendor['id']]);
        (new PurchaseOrderService())->replaceItems((int) $po['id'], $items);

        return redirect()->to('purchase-orders/' . $uid)->with('message', 'Purchase order updated.');
    }

    /** ?pricing=1|0 &vehicle=1|0 override the PO's own defaults. */
    public function pdf(string $uid)
    {
        $po  = $this->findOr404($uid);
        $pdf = (new PurchaseOrderService())->pdf($po, $this->boolParam('pricing'), $this->boolParam('vehicle'));

        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', ($this->request->getGet('download') ? 'attachment' : 'inline') . '; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }

    /** Flip the PO's default "show pricing" / "show vehicle" switches. */
    public function toggle(string $uid, string $what)
    {
        $po  = $this->findOr404($uid);
        $col = ['pricing' => 'show_pricing', 'vehicle' => 'show_vehicle'][$what] ?? null;
        if ($col !== null) {
            model(PurchaseOrderModel::class)->update($po['id'], [$col => $po[$col] ? 0 : 1]);
        }

        return redirect()->to('purchase-orders/' . $uid);
    }

    public function clearPrices(string $uid)
    {
        $po = $this->findOr404($uid);
        (new PurchaseOrderService())->clearPrices((int) $po['id']);

        return redirect()->to('purchase-orders/' . $uid)->with('message', 'All prices removed from ' . $po['po_no'] . '.');
    }

    public function status(string $uid)
    {
        $po = $this->findOr404($uid);
        $s  = (string) $this->request->getPost('status');
        if (array_key_exists($s, PurchaseOrderModel::STATUSES)) {
            model(PurchaseOrderModel::class)->update($po['id'], ['status' => $s]);
        }

        return redirect()->to('purchase-orders/' . $uid)->with('message', 'Status set to ' . (PurchaseOrderModel::STATUSES[$s] ?? $s) . '.');
    }

    public function send(string $uid)
    {
        $po      = $this->findOr404($uid);
        $to      = trim((string) $this->request->getPost('email'));
        $pricing = (bool) $this->request->getPost('pricing');
        $vehicle = (bool) $this->request->getPost('vehicle');
        $vendor  = model(VendorModel::class)->withDeleted()->find($po['vendor_id']);

        $pdf  = (new PurchaseOrderService())->pdf($po, $pricing, $vehicle);
        $body = '<p>Dear ' . esc($vendor['contact_person'] ?: $vendor['name']) . ',</p>'
            . '<p>Please find attached purchase order <b>' . esc($po['po_no']) . '</b>'
            . (trim((string) $po['description']) !== '' ? ' — ' . esc($po['description']) : '') . '.</p>'
            . '<p>Kindly quote <b>' . esc($po['po_no']) . '</b> on your invoice and delivery note.</p>';
        $res = (new Mailer())->send($to, 'Purchase order ' . $po['po_no'] . ' from ' . billing('companyName'), Mailer::layout('Purchase order ' . $po['po_no'], $body, [], '', po_logo()), [$pdf['filename'] => $pdf['bytes']]);

        if ($res['ok']) {
            model(PurchaseOrderModel::class)->update($po['id'], ['sent_at' => date('Y-m-d H:i:s')] + ($po['status'] === 'draft' ? ['status' => 'sent'] : []));
        }

        return redirect()->to('purchase-orders/' . $uid)->with($res['ok'] ? 'message' : 'error', $res['ok'] ? 'Purchase order emailed to ' . $to . '.' : $res['error']);
    }

    public function delete(string $uid)
    {
        $po = $this->findOr404($uid);
        model(PurchaseOrderModel::class)->delete($po['id']);

        return redirect()->to('purchase-orders')->with('message', 'Purchase order ' . $po['po_no'] . ' deleted.');
    }

    // ------------------------------------------------------------------

    private function form(?array $po, array $items, int $vendorId)
    {
        return view('purchase_orders/form', [
            'title'    => $po ? 'Edit ' . $po['po_no'] : 'New Purchase Order',
            'po'       => $po,
            'items'    => $items,
            'vendors'  => model(VendorModel::class)->search('', 2000),
            'vendorId' => (int) (old('vendor_id') ?: $vendorId),
            'vehicles' => model(VehicleModel::class)->orderBy('created_at', 'DESC')->findAll(1000),
        ]);
    }

    private function postedHeader(): array
    {
        $p = fn ($k) => trim((string) $this->request->getPost($k));
        $d = [
            'order_date'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $p('order_date')) ? $p('order_date') : date('Y-m-d'),
            'description'  => $p('description') ?: null,
            'reference'    => $p('reference') ?: null,
            'notes'        => $p('notes') ?: null,
            'vat_rate'     => max(0, min(100, (float) ($p('vat_rate') !== '' ? $p('vat_rate') : setting('Purchasing.defaultVatRate')))),
            'show_pricing' => $this->request->getPost('show_pricing') ? 1 : 0,
            'show_vehicle' => $this->request->getPost('show_vehicle') ? 1 : 0,
            'vehicle_id'   => (int) $p('vehicle_id') ?: null,
        ];
        foreach (array_keys(PurchaseOrderModel::VEHICLE) as $k) {
            $d[$k] = $p($k) ?: null;
        }

        return $d;
    }

    /** @return list<array{description:string,part_no:string,qty:float,unit_price:?string}> */
    private function postedItems(): array
    {
        $d = (array) $this->request->getPost('item_description');
        $n = (array) $this->request->getPost('item_part');
        $q = (array) $this->request->getPost('item_qty');
        $u = (array) $this->request->getPost('item_price');
        $out = [];
        foreach ($d as $i => $desc) {
            if (trim((string) $desc) === '') {
                continue;
            }
            $price = trim((string) ($u[$i] ?? ''));
            $out[] = ['description' => (string) $desc, 'part_no' => (string) ($n[$i] ?? ''), 'qty' => (float) ($q[$i] ?? 1), 'unit_price' => $price === '' ? null : $price];
        }

        return $out;
    }

    private function filters(bool $range = false): array
    {
        $g  = fn ($k) => trim((string) $this->request->getGet($k));
        $ok = static fn ($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        $f  = [
            'from'      => $ok($g('from')) ? $g('from') : ($range ? date('Y-m-01') : ''),
            'to'        => $ok($g('to')) ? $g('to') : ($range ? date('Y-m-d') : ''),
            'vendor_id' => (int) $g('vendor') ?: null,
            'status'    => array_key_exists($g('status'), PurchaseOrderModel::STATUSES) ? $g('status') : '',
            'q'         => $g('q'),
        ];

        return $f;
    }

    private function summaryData(array $f): array
    {
        $orders = model(PurchaseOrderModel::class)->listing(['from' => $f['from'], 'to' => $f['to'], 'vendor_id' => $f['vendor_id'], 'status' => $f['status']], 5000);
        $tot    = ['count' => 0, 'subtotal' => 0.0, 'vat' => 0.0, 'total' => 0.0];
        $byVendor = [];
        $byStatus = [];
        foreach ($orders as $o) {
            if ($o['status'] === 'cancelled') {
                $byStatus['cancelled'] = ($byStatus['cancelled'] ?? 0) + 1;

                continue; // cancelled orders are listed but not totalled
            }
            $tot['count']++;
            $tot['subtotal'] += (float) $o['subtotal'];
            $tot['vat']      += (float) $o['vat_amount'];
            $tot['total']    += (float) $o['total'];
            $vn = $o['vendor_name'] ?? '—';
            $byVendor[$vn] ??= ['count' => 0, 'total' => 0.0];
            $byVendor[$vn]['count']++;
            $byVendor[$vn]['total'] += (float) $o['total'];
            $byStatus[$o['status']] = ($byStatus[$o['status']] ?? 0) + 1;
        }
        uasort($byVendor, static fn ($a, $b) => $b['total'] <=> $a['total']);
        $vendor = $f['vendor_id'] ? model(VendorModel::class)->find($f['vendor_id']) : null;

        return compact('orders', 'tot', 'byVendor', 'byStatus', 'f', 'vendor');
    }

    private function boolParam(string $k): ?bool
    {
        $v = $this->request->getGet($k);

        return $v === null || $v === '' ? null : (bool) (int) $v;
    }

    private function findOr404(string $uid): array
    {
        $po = model(PurchaseOrderModel::class)->findByUid($uid);
        if ($po === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $po;
    }
}
