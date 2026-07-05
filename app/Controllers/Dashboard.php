<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use App\Models\TemplateModel;
use App\Models\VehicleModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $inspections = model(InspectionModel::class);

        $status = $this->request->getGet('status');
        $rows   = $inspections->listWithVehicle($status);

        $all    = $inspections->listWithVehicle();
        $counts = [
            'total'     => count($all),
            'draft'     => count(array_filter($all, static fn ($r) => $r['status'] === 'draft')),
            'completed' => count(array_filter($all, static fn ($r) => $r['status'] === 'completed')),
        ];

        // Average condition score over completed inspections.
        $scores = array_filter(array_column($all, 'score'), static fn ($s) => $s !== null);
        $counts['avgScore'] = $scores ? (int) round(array_sum($scores) / count($scores)) : null;

        return view('dashboard/index', [
            'title'       => 'Dashboard',
            'rows'        => $rows,
            'counts'      => $counts,
            'status'      => $status,
            'templates'   => model(TemplateModel::class)->where('is_active', 1)->findAll(),
            'vehicles'    => model(VehicleModel::class)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }
}
