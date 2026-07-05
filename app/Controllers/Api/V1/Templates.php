<?php

namespace App\Controllers\Api\V1;

use App\Models\TemplateModel;
use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class Templates extends Controller
{
    use ResponseTrait;

    protected $format = 'json';

    public function index()
    {
        return $this->respond(['data' => model(TemplateModel::class)->where('is_active', 1)->findAll()]);
    }

    public function show($id = null)
    {
        $template = model(TemplateModel::class)->withTree((int) $id);

        return $template ? $this->respond(['data' => $template]) : $this->failNotFound('Template not found');
    }
}
