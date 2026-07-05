<?php

namespace App\Controllers;

use App\Models\InspectionModel;

/**
 * Public QR-verification landing. Confirms a report is genuine and shows a
 * minimal summary without exposing the full private report.
 */
class Verify extends BaseController
{
    public function show(string $uid)
    {
        $inspection = model(InspectionModel::class)->findByUid($uid);

        $vehicle = null;
        if ($inspection && $inspection['vehicle_id']) {
            $vehicle = model(\App\Models\VehicleModel::class)->find($inspection['vehicle_id']);
        }

        return view('verify/show', [
            'title'      => 'Verify Report',
            'uid'        => $uid,
            'inspection' => $inspection,
            'vehicle'    => $vehicle,
            'valid'      => $inspection !== null && $inspection['status'] === 'completed',
        ]);
    }
}
