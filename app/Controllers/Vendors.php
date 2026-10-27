<?php

namespace App\Controllers;

use App\Models\PurchaseOrderModel;
use App\Models\VendorModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Suppliers we raise purchase orders against (feature: purchasing). */
class Vendors extends BaseController
{
    private const FIELDS = ['name', 'contact_person', 'phone', 'email', 'address', 'vat_number', 'registration_number', 'account_number', 'notes'];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        if (! feature('purchasing')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        return view('vendors/index', ['title' => 'Vendors', 'vendors' => model(VendorModel::class)->search($q), 'q' => $q]);
    }

    public function new()
    {
        return view('vendors/form', ['title' => 'New Vendor', 'vendor' => [], 'return' => $this->safeReturn((string) $this->request->getGet('return'))]);
    }

    public function create()
    {
        $data               = $this->collect();
        $data['created_by'] = auth()->id();
        $model              = model(VendorModel::class);
        $id                 = $model->insert($data, true);
        if ($id === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        $v      = $model->find($id);
        $return = $this->safeReturn((string) $this->request->getPost('return'));

        return $return !== ''
            ? redirect()->to($return . (str_contains($return, '?') ? '&' : '?') . 'vendor=' . $v['uid'])->with('message', 'Vendor "' . $v['name'] . '" added.')
            : redirect()->to('vendors/' . $v['uid'])->with('message', 'Vendor added.');
    }

    public function show(string $uid)
    {
        $v = $this->findOr404($uid);

        return view('vendors/show', [
            'title'  => $v['name'],
            'vendor' => $v,
            'orders' => model(PurchaseOrderModel::class)->listing(['vendor_id' => $v['id']], 300),
        ]);
    }

    public function edit(string $uid)
    {
        return view('vendors/form', ['title' => 'Edit Vendor', 'vendor' => $this->findOr404($uid), 'return' => '']);
    }

    public function update(string $uid)
    {
        $v     = $this->findOr404($uid);
        $model = model(VendorModel::class);
        if (! $model->update($v['id'], $this->collect())) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('vendors/' . $uid)->with('message', 'Vendor updated.');
    }

    public function delete(string $uid)
    {
        $v = $this->findOr404($uid);
        if (model(PurchaseOrderModel::class)->withDeleted()->where('vendor_id', $v['id'])->countAllResults() > 0) {
            return redirect()->back()->with('error', 'This vendor has purchase orders on record, so it can\'t be deleted. Edit it instead.');
        }
        model(VendorModel::class)->delete($v['id']);

        return redirect()->to('vendors')->with('message', 'Vendor deleted.');
    }

    private function collect(): array
    {
        $d = [];
        foreach (self::FIELDS as $f) {
            $val   = trim((string) $this->request->getPost($f));
            $d[$f] = $val === '' ? null : $val;
        }
        $d['name'] = (string) $d['name'];

        return $d;
    }

    private function findOr404(string $uid): array
    {
        $v = model(VendorModel::class)->findByUid($uid);
        if ($v === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $v;
    }

    private function safeReturn(string $r): string
    {
        $r = ltrim(trim($r), '/');

        return preg_match('#^[a-z0-9][a-z0-9/_-]*$#i', $r) ? $r : '';
    }
}
