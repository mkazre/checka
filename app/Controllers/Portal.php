<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use App\Models\InspectionValueModel;
use App\Services\QuoteService;

/**
 * Public client-facing approval portal (feature: clientPortal). Reached via an
 * unguessable report_uid link the workshop shares. Lets the customer review the
 * report, approve/decline recommended repairs (feature: quotes) and e-sign.
 */
class Portal extends BaseController
{
    private function guard(): void
    {
        if (! feature('clientPortal')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function show(string $uid)
    {
        $this->guard();

        $report = model(InspectionModel::class)->assembleReport($uid);
        $valid  = $report !== null && ($report['inspection']['status'] ?? '') === 'completed';

        return view('portal/show', [
            'title'   => 'Vehicle Report — Approval',
            'uid'     => $uid,
            'valid'   => $valid,
            'report'  => $report,
            'compute' => $valid ? service('intelligence')->compute($report) : null,
            'quote'   => ($valid && feature('quotes')) ? (new QuoteService())->build($report) : null,
            'ratings' => CHECKA_RATINGS,
        ]);
    }

    public function submit(string $uid)
    {
        $this->guard();

        $inspection = model(InspectionModel::class)->findByUid($uid);
        if ($inspection === null || $inspection['status'] !== 'completed') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $decision = $this->request->getPost('decision') === 'declined' ? 'declined' : 'approved';
        $name     = trim((string) $this->request->getPost('client_name'));
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Please enter your name to sign.');
        }

        model(InspectionModel::class)->update($inspection['id'], [
            'client_status'    => $decision,
            'client_name'      => $name,
            'client_note'      => trim((string) $this->request->getPost('client_note')) ?: null,
            'client_signature' => (string) $this->request->getPost('signature') ?: null,
            'client_signed_at' => date('Y-m-d H:i:s'),
        ]);

        // Per-item repair approvals (feature: quotes).
        if (feature('quotes')) {
            $approvals = (array) ($this->request->getPost('approve') ?? []); // [value_id => '1'|'0']
            $vm        = model(InspectionValueModel::class);
            foreach ($approvals as $valueId => $val) {
                $row = $vm->find((int) $valueId);
                if ($row !== null && (int) $row['inspection_id'] === (int) $inspection['id']) {
                    $vm->update((int) $valueId, ['repair_approved' => $val === '1' ? 1 : 0]);
                }
            }
        }

        return redirect()->to('portal/' . $uid)->with('message', 'Thank you — your response has been recorded.');
    }
}
