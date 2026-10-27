<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/** The "Items" catalogue behind purchase-order lines (feature: purchasing). */
class CatalogItems extends BaseController
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
        $q    = trim((string) $this->request->getGet('q'));
        $sort = (string) ($this->request->getGet('sort') ?: 'name');

        return view('catalog_items/index', ['title' => 'Items', 'items' => model(CatalogItemModel::class)->search($q, $sort), 'q' => $q, 'sort' => $sort]);
    }

    /** JSON suggestions for the PO item field (3+ characters). */
    public function suggest()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 3) {
            return $this->response->setJSON([]);
        }
        $rows = array_map(static fn ($r) => [
            'id'      => (int) $r['id'],
            'name'    => $r['name'],
            'part_no' => (string) $r['part_no'],
            'price'   => $r['default_price'] === null ? '' : (string) (float) $r['default_price'],
            'used'    => (int) $r['use_count'],
        ], model(CatalogItemModel::class)->suggest($q));

        return $this->response->setJSON($rows);
    }

    public function new()
    {
        return view('catalog_items/form', ['title' => 'New Item', 'item' => []]);
    }

    public function create()
    {
        $m = model(CatalogItemModel::class);
        if ($m->insert($this->collect() + ['use_count' => 0]) === false) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        return redirect()->to('items')->with('message', 'Item added.');
    }

    public function edit(int $id)
    {
        return view('catalog_items/form', ['title' => 'Edit Item', 'item' => $this->findOr404($id)]);
    }

    public function update(int $id)
    {
        $this->findOr404($id);
        $m = model(CatalogItemModel::class);
        if (! $m->update($id, $this->collect())) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        return redirect()->to('items')->with('message', 'Item updated.');
    }

    public function delete(int $id)
    {
        $i = $this->findOr404($id);
        model(CatalogItemModel::class)->delete($id);

        return redirect()->to('items')->with('message', '"' . $i['name'] . '" removed from the list. Purchase orders that used it are unchanged.');
    }

    private function collect(): array
    {
        $p     = fn ($k) => trim((string) $this->request->getPost($k));
        $price = $p('default_price');

        return [
            'name'          => $p('name'),
            'part_no'       => $p('part_no') ?: null,
            'default_price' => $price === '' ? null : round((float) $price, 2),
            'notes'         => $p('notes') ?: null,
        ];
    }

    private function findOr404(int $id): array
    {
        $i = model(CatalogItemModel::class)->find($id);
        if ($i === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $i;
    }
}
