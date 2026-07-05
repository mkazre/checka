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
            'photos'     => array_slice($photos, 0, 5),
        ]);
    }

    /** Scan-disc page (photo → PDF417 decode → pre-fill). */
    public function scan()
    {
        return view('vehicles/scan', ['title' => 'Scan Licence Disc']);
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
