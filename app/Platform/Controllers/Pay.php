<?php

namespace Platform\Controllers;

use Platform\Models\TenantModel;
use Platform\Services\Billing;
use Platform\Services\Gateways\Ozow;
use Platform\Services\Gateways\PayFast;
use Platform\Services\Gateways\Paystack;
use Platform\Services\PlatformSettings;

/** Public pay page for a platform invoice (link in every invoice/reminder email). */
class Pay extends BaseController
{
    private function load(string $uid): array
    {
        $inv = Billing::invoiceByUid($uid);
        $t   = $inv ? model(TenantModel::class)->withDeleted()->find($inv['tenant_id']) : null;
        if (! $inv || ! $t) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return [$inv, $t];
    }

    public function show(string $uid)
    {
        [$inv, $t] = $this->load($uid);

        return view('Platform\Views\public\pay', [
            'inv'      => $inv,
            't'        => $t,
            'method'   => (string) $this->request->getGet('method'),
            'welcome'  => (string) $this->request->getGet('welcome'),
            'error'    => session()->getFlashdata('pay_error'),
            'paystack' => Paystack::enabled(),
            'payfast'  => PayFast::enabled(),
            'ozow'     => Ozow::enabled(),
            'eft'      => PlatformSettings::get('gateways')['eft'],
            'general'  => PlatformSettings::get('general'),
            'login'    => Billing::workspaceUrl($t['slug'], 'login'),
        ]);
    }

    public function invoice(string $uid)
    {
        [$inv, $t] = $this->load($uid);

        return view('Platform\Views\public\invoice', ['inv' => $inv, 't' => $t, 'general' => PlatformSettings::get('general'), 'eft' => PlatformSettings::get('gateways')['eft']]);
    }

    // ---- Paystack (card / EFT)

    public function paystack(string $uid)
    {
        [$inv, $t] = $this->load($uid);
        if ($inv['status'] !== 'unpaid' || ! Paystack::enabled()) {
            return redirect()->to(site_url('pay/' . $uid));
        }
        $channel  = $this->request->getPost('channel');
        $channels = $channel === 'eft' ? ['eft', 'bank_transfer'] : ($channel === 'card' ? ['card'] : []);
        $url      = Paystack::initialize($inv, (string) $t['email'], site_url('pay/' . $uid . '/paystack/callback'), $channels);
        if (! $url) {
            return redirect()->to(site_url('pay/' . $uid))->with('pay_error', 'Card payments are unavailable right now (' . Paystack::$lastError . '). Please try another method.');
        }

        return redirect()->to($url);
    }

    public function paystackCallback(string $uid)
    {
        [$inv] = $this->load($uid);
        $data  = Paystack::verify((string) $this->request->getGet('reference'));
        if ($data && ($data['status'] ?? '') === 'success' && Paystack::invoiceUid($data) === $inv['uid']) {
            Billing::saveCard((int) $inv['tenant_id'], $data);
            Billing::applyPayment($inv, 'paystack', (string) $data['reference'], ((int) $data['amount']) / 100, ['channel' => $data['channel'] ?? null]);

            return redirect()->to(site_url('pay/' . $uid) . '?paid=1');
        }

        return redirect()->to(site_url('pay/' . $uid))->with('pay_error', 'The payment was not completed. You have not been charged — please try again.');
    }

    // ---- PayFast & Ozow (auto-submitting form to the gateway)

    public function payfast(string $uid)
    {
        [$inv, $t] = $this->load($uid);
        if ($inv['status'] !== 'unpaid' || ! PayFast::enabled()) {
            return redirect()->to(site_url('pay/' . $uid));
        }
        $f = PayFast::form($inv, $t, site_url('pay/' . $uid . '/return/payfast'), site_url('pay/' . $uid), site_url('webhooks/payfast'));

        return view('Platform\Views\public\redirect', $f + ['gateway' => 'PayFast']);
    }

    public function ozow(string $uid)
    {
        [$inv] = $this->load($uid);
        if ($inv['status'] !== 'unpaid' || ! Ozow::enabled()) {
            return redirect()->to(site_url('pay/' . $uid));
        }
        $f = Ozow::form($inv, site_url('pay/' . $uid . '/return/ozow'), site_url('pay/' . $uid), site_url('pay/' . $uid . '/return/ozow-error'), site_url('webhooks/ozow'));

        return view('Platform\Views\public\redirect', $f + ['gateway' => 'Ozow']);
    }

    /** Customer comes back from PayFast/Ozow. Ozow's return carries a signed result we can apply immediately. */
    public function returned(string $uid, string $gateway)
    {
        [$inv] = $this->load($uid);
        if ($gateway === 'ozow') {
            $p = array_merge($this->request->getGet() ?? [], $this->request->getPost() ?? []);
            if (Ozow::validResponse($p) && strcasecmp((string) ($p['Status'] ?? ''), 'Complete') === 0
                && strtok((string) $p['TransactionReference'], '-') === $inv['uid'] && Ozow::confirmed((string) $p['TransactionReference']) !== false) {
                Billing::applyPayment($inv, 'ozow', (string) ($p['TransactionId'] ?? $p['TransactionReference']), (float) $p['Amount']);

                return redirect()->to(site_url('pay/' . $uid) . '?paid=1');
            }
        }
        if ($gateway === 'ozow-error') {
            return redirect()->to(site_url('pay/' . $uid))->with('pay_error', 'The EFT payment was not completed. Please try again.');
        }

        // PayFast confirms in the background (ITN); the pay page refreshes until it lands.
        return redirect()->to(site_url('pay/' . $uid) . '?processing=1');
    }
}
