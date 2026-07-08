<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use App\Models\InspectionValueModel;
use App\Services\PaymentService;
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

        $pay = null;
        if ($valid && feature('payments')) {
            $svc = new PaymentService();
            $pay = [
                'configured' => $svc->configured(),
                'amount'     => $this->amountFor($report),
                'status'     => $report['inspection']['payment_status'] ?? null,
                'currency'   => $svc->currency(),
            ];
        }

        return view('portal/show', [
            'title'   => 'Vehicle Report — Approval',
            'uid'     => $uid,
            'valid'   => $valid,
            'report'  => $report,
            'compute' => $valid ? service('intelligence')->compute($report) : null,
            'quote'   => ($valid && feature('quotes')) ? (new QuoteService())->build($report) : null,
            'pay'     => $pay,
            'ratings' => CHECKA_RATINGS,
        ]);
    }

    /** Compute the payable amount: approved repairs → full repair total → flat fee. */
    private function amountFor(array $report): float
    {
        $a = 0.0;
        if (feature('quotes')) {
            $q = (new QuoteService())->build($report);
            $a = $q['approvedTotal'] > 0 ? $q['approvedTotal'] : $q['total'];
        }
        if ($a <= 0) {
            $a = (float) setting('Pay.inspectionFee');
        }

        return round($a, 2);
    }

    /** Start a Paystack payment and redirect to the hosted checkout. */
    public function pay(string $uid)
    {
        $this->guard();
        if (! feature('payments')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $ins = $report['inspection'];
        if (($ins['payment_status'] ?? '') === 'paid') {
            return redirect()->to('portal/' . $uid)->with('message', 'This report is already paid.');
        }

        $amount = $this->amountFor($report);
        if ($amount <= 0) {
            return redirect()->to('portal/' . $uid)->with('error', 'There is nothing to pay yet.');
        }

        $ref = 'CHK-' . strtoupper(bin2hex(random_bytes(4)));
        $res = (new PaymentService())->initialize(
            $amount,
            (string) ($report['vehicle']['client_email'] ?? ''),
            $ref,
            site_url('portal/' . $uid . '/paid')
        );
        if (! ($res['ok'] ?? false)) {
            return redirect()->to('portal/' . $uid)->with('error', $res['error'] ?? 'Could not start payment.');
        }

        model(InspectionModel::class)->update($ins['id'], [
            'payment_ref'    => $ref,
            'payment_amount' => $amount,
            'payment_status' => 'unpaid',
        ]);

        return redirect()->to($res['url']);
    }

    /** Paystack callback — verify and mark paid. */
    public function paid(string $uid)
    {
        $this->guard();
        if (! feature('payments')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $ins = model(InspectionModel::class)->findByUid($uid);
        if ($ins === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $ref = (string) ($this->request->getGet('reference') ?: $ins['payment_ref']);
        if ($ref === '') {
            return redirect()->to('portal/' . $uid)->with('error', 'No payment reference.');
        }

        $res = (new PaymentService())->verify($ref);
        if ($res['paid'] ?? false) {
            model(InspectionModel::class)->update($ins['id'], [
                'payment_status' => 'paid',
                'paid_at'        => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('portal/' . $uid)->with('message', 'Payment received — thank you!');
        }

        return redirect()->to('portal/' . $uid)->with('error', 'Payment was not completed.');
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
