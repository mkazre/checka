<?php

namespace App\Controllers\Api\V1;

use App\Models\VehicleModel;
use CodeIgniter\RESTful\ResourceController;

class Vehicles extends ResourceController
{
    protected $modelName = VehicleModel::class;
    protected $format    = 'json';

    public function index()
    {
        return $this->respond(['data' => $this->model->orderBy('created_at', 'DESC')->findAll()]);
    }

    public function show($id = null)
    {
        $vehicle = $this->model->find($id);

        return $vehicle ? $this->respond(['data' => $vehicle]) : $this->failNotFound('Vehicle not found');
    }

    public function create()
    {
        $data              = $this->request->getJSON(true) ?: $this->request->getPost();
        $data['created_by'] = auth()->id();

        if (! $id = $this->model->insert($data, true)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated(['data' => $this->model->find($id)]);
    }

    public function update($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound('Vehicle not found');
        }
        $data = $this->request->getJSON(true) ?: $this->request->getRawInput();
        if (! $this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond(['data' => $this->model->find($id)]);
    }

    public function delete($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound('Vehicle not found');
        }
        $this->model->delete($id);

        return $this->respondDeleted(['id' => $id]);
    }
}
