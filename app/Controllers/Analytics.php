<?php

namespace App\Controllers;

use App\Services\AnalyticsService;

/**
 * Workshop analytics dashboard (feature: analytics). Admin-only via the
 * group:admin filter in Routes.
 */
class Analytics extends BaseController
{
    public function index()
    {
        if (! feature('analytics')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $svc = new AnalyticsService();

        return view('analytics/index', [
            'title'     => 'Analytics',
            'summary'   => $svc->summary(),
            'monthly'   => $svc->monthly(6),
            'verdict'   => $svc->verdictDist(),
            'failures'  => $svc->topFailures(8),
            'mechanics' => $svc->mechanics(),
        ]);
    }
}
