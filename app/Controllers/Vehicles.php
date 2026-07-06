<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use App\Models\VehicleModel;
use App\Services\LicenceDiscParser;

class Vehicles extends BaseController
{
    /** Fields accepted from the profile/vehicle forms. */
    private array $fields = [
        'make', 'model', 'year', 'colour', 'vin', 'plate', 'odometer',
        'seller', 'service_date', 'service_km',
        'vehicle_type', 'derivative', 'body_type', 'doors', 'engine_number',
        'power_displacement', 'fuel', 'transmission', 'paint_finish', 'options',
        'registered_owner', 'register_number', 'licence_no', 'disc_control_no',
        'date_first_licensing', 'last_cor', 'licence_expiry', 'gvm', 'tare',
        'seating', 'date_of_test', 'client', 'inspection_location', 'disc_raw',
    ];

    public function index()
    {
        return view('vehicles/index', [
            'title'    => 'Vehicles',
            'vehicles' => model(VehicleModel::class)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    /** Dedicated vehicle-profile page (Condition-Report style). */
    public function profile(int $id)
    {
        $vehicle = model(VehicleModel::class)->find($id);
        if ($vehicle === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Latest inspection for this vehicle → photo strip + damage findings.
        $inspection = model(InspectionModel::class)
            ->where('vehicle_id', $id)
            ->orderBy('created_at', 'DESC')
            ->first();

        $findings = [];
        $photos   = [];
        if ($inspection !== null) {
            $report = model(InspectionModel::class)->assembleReport($inspection['report_uid']);
            if ($report !== null) {
                $findings = service('intelligence')->findings($report);
                foreach ($report['sections'] as $s) {
                    foreach ($s['items'] as $it) {
                        foreach ($it['photos'] as $p) {
                            if (! $p['video']) {
                                $photos[] = $p;
                            }
                        }
                    }
                }
            }
        }

        return view('vehicles/profile', [
            'title'      => 'Vehicle Profile',
            'vehicle'    => $vehicle,
            'inspection' => $inspection,
            'findings'   => $findings,
            'photos'       => array_slice($photos, 0, 5),
            'stockImage'   => (new \App\Services\VehicleImageService())->imageFor($vehicle),
            'imageEnabled' => \App\Services\VehicleImageService::enabled(),
        ]);
    }

    /** Scan-disc page (photo → PDF417 decode → pre-fill). */
    public function scan()
    {
        return view('vehicles/scan', ['title' => 'Scan Licence Disc']);
    }

    /** AJAX: ask the configured AI to infer missing spec fields. */
    public function aiFill(int $id)
    {
        $vehicle = model(VehicleModel::class)->find($id);
        if ($vehicle === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Vehicle not found']);
        }

        $known = array_filter([
            'make'          => $vehicle['make'],
            'model'         => $vehicle['model'],
            'derivative'    => $vehicle['derivative'],
            'year'          => $vehicle['year'],
            'vin'           => $vehicle['vin'],
            'engine_number' => $vehicle['engine_number'],
            'colour'        => $vehicle['colour'],
            'body_type'     => $vehicle['body_type'],
            'plate'         => $vehicle['plate'],
        ], static fn ($v) => $v !== null && $v !== '');

        if (($known['vin'] ?? '') === '' && ($known['make'] ?? '') === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Add at least a VIN or Make/Model first, then try AI fill.']);
        }

        return $this->response->setJSON((new \App\Services\AiService())->enrichVehicle($known));
    }

    /**
     * AJAX: manually override the reference image.
     *  - action=url   + url=…    → set an explicit image URL
     *  - action=search + query=… → re-search Wikimedia for a term
     *  - action=remove           → clear the image
     */
    public function setImage(int $id)
    {
        $model   = model(VehicleModel::class);
        $vehicle = $model->find($id);
        if ($vehicle === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Vehicle not found']);
        }

        $action = (string) $this->request->getPost('action');
        $url    = null;

        if ($action === 'remove') {
            $url = null;
        } elseif ($action === 'url') {
            $url = trim((string) $this->request->getPost('url'));
            if ($url === '' || ! preg_match('#^https?://#i', $url)) {
                return $this->response->setJSON(['ok' => false, 'error' => 'Enter a valid image URL (must start with http).']);
            }
        } elseif ($action === 'search') {
            $query = trim((string) $this->request->getPost('query'));
            if ($query === '') {
                $query = trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '') . ' ' . ($vehicle['year'] ?? ''));
            }
            $url = (new \App\Services\VehicleImageService())->lookup($query);
            if ($url === null) {
                return $this->response->setJSON(['ok' => false, 'error' => 'No image found for that search. Try a different term or paste a URL.']);
            }
        } else {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Unknown action']);
        }

        $model->update($id, ['stock_image_url' => $url]);

        return $this->response->setJSON(['ok' => true, 'url' => $url]);
    }

    /** AJAX: decode string in → parsed vehicle fields out. */
    public function parseDisc()
    {
        $raw = (string) ($this->request->getPost('raw') ?? $this->request->getJSON(true)['raw'] ?? '');
        if (trim($raw) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No barcode data received']);
        }

        $fields = (new LicenceDiscParser())->parse($raw);

        return $this->response->setJSON(['ok' => true, 'fields' => $fields]);
    }

    public function create()
    {
        $data = $this->collect();
        $data['created_by'] = auth()->id();

        $model = model(VehicleModel::class);
        if (! $id = $model->insert($data, true)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('vehicles/' . $id . '/profile')->with('message', 'Vehicle saved.');
    }

    public function update(int $id)
    {
        $model = model(VehicleModel::class);
        if ($model->find($id) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! $model->update($id, $this->collect())) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('vehicles/' . $id . '/profile')->with('message', 'Vehicle updated.');
    }

    public function delete(int $id)
    {
        model(VehicleModel::class)->delete($id);

        return redirect()->to('vehicles')->with('message', 'Vehicle deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function collect(): array
    {
        $data = [];
        foreach ($this->fields as $f) {
            $val = $this->request->getPost($f);
            if ($val !== null) {
                $data[$f] = $val === '' ? null : $val;
            }
        }

        return $data;
    }
}
