<?php

namespace App\Controllers;

use App\Models\VehicleModel;

class Vehicles extends BaseController
{
    public function index()
    {
        return view('vehicles/index', [
            'title'    => 'Vehicles',
            'vehicles' => model(VehicleModel::class)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function create()
    {
        $data = $this->vehicleData();
        $model = model(VehicleModel::class);

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('vehicles')->with('message', 'Vehicle added.');
    }

    public function update(int $id)
    {
        $model = model(VehicleModel::class);
        if ($model->find($id) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $model->update($id, $this->vehicleData())) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('vehicles')->with('message', 'Vehicle updated.');
    }

    public function delete(int $id)
    {
        model(VehicleModel::class)->delete($id);

        return redirect()->to('vehicles')->with('message', 'Vehicle deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleData(): array
    {
        return [
            'make'         => $this->request->getPost('make'),
            'model'        => $this->request->getPost('model'),
            'year'         => $this->request->getPost('year') ?: null,
            'colour'       => $this->request->getPost('colour'),
            'vin'          => $this->request->getPost('vin'),
            'plate'        => $this->request->getPost('plate'),
            'odometer'     => $this->request->getPost('odometer'),
            'seller'       => $this->request->getPost('seller'),
            'service_date' => $this->request->getPost('service_date'),
            'service_km'   => $this->request->getPost('service_km'),
            'created_by'   => auth()->id(),
        ];
    }
}
