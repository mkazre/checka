<?php

namespace App\Controllers;

/**
 * Workshop settings — currently the AI provider (Claude / ChatGPT) keys.
 * Admin-only (group:admin filter in Routes).
 */
class Settings extends BaseController
{
    /** All Features.* flags (see Config\Features), toggled on the Settings page. */
    private const FEATURE_FLAGS = [
        'offline', 'vinDecode', 'clientPortal', 'quotes',
        'notifications', 'notifyEmail', 'notifyWhatsapp',
        'history', 'aiIntelligence', 'aiConclusion', 'aiPhotoAnalysis',
        'annotation', 'analytics', 'whiteLabel', 'payments', 'vinCheck',
    ];

    public function index()
    {
        $feat = [];
        foreach (self::FEATURE_FLAGS as $f) {
            $feat[$f] = (bool) setting('Features.' . $f);
        }

        return view('settings/index', [
            'title'  => 'Settings',
            'feat'   => $feat,
            'notify' => [
                'fromEmail'      => (string) setting('Notify.fromEmail'),
                'fromName'       => (string) (setting('Notify.fromName') ?: 'CheckA+ by WheelsTurn'),
                'protocol'       => (string) (setting('Notify.protocol') ?: 'mail'),
                'smtpHost'       => (string) setting('Notify.smtpHost'),
                'smtpUser'       => (string) setting('Notify.smtpUser'),
                'smtpPass'       => (string) setting('Notify.smtpPass'),
                'smtpPort'       => (int) (setting('Notify.smtpPort') ?: 587),
                'smtpCrypto'     => (string) (setting('Notify.smtpCrypto') ?? 'tls'),
                'waToken'        => (string) setting('Notify.waToken'),
                'waPhoneId'      => (string) setting('Notify.waPhoneId'),
                'notifyWorkshop' => (bool) setting('Notify.notifyWorkshop'),
                'workshopEmail'  => (string) setting('Notify.workshopEmail'),
                'fcmServerKey'   => (string) setting('Push.fcmServerKey'),
            ],
            'brand' => [
                'brandName'    => (string) (setting('Brand.brandName') ?: 'CheckA+'),
                'tagline'      => (string) (setting('Brand.tagline') ?? 'by WheelsTurn'),
                'primaryColor' => (string) (setting('Brand.primaryColor') ?: '#37b34a'),
                'darkColor'    => (string) (setting('Brand.darkColor') ?: '#2d2d2d'),
                'logoPath'     => (string) setting('Brand.logoPath'),
            ],
            'pay' => [
                'currency'       => (string) (setting('Pay.currency') ?: 'ZAR'),
                'paystackPublic' => (string) setting('Pay.paystackPublic'),
                'paystackSecret' => (string) setting('Pay.paystackSecret'),
                'inspectionFee'  => (float) setting('Pay.inspectionFee'),
            ],
            'vinProviders' => [
                'freeEnabled' => (bool) (setting('VinProviders.freeEnabled') ?? true),
                'p1Enabled'   => (bool) setting('VinProviders.p1Enabled'),
                'p1Label'     => (string) (setting('VinProviders.p1Label') ?: 'VinAudit'),
                'p1ApiKey'    => (string) setting('VinProviders.p1ApiKey'),
                'p1BaseUrl'   => (string) (setting('VinProviders.p1BaseUrl') ?: 'https://api.vinaudit.com/v2/vinhistory'),
                'p2Enabled'   => (bool) setting('VinProviders.p2Enabled'),
                'p2Label'     => (string) (setting('VinProviders.p2Label') ?: 'CarsXE'),
                'p2ApiKey'    => (string) setting('VinProviders.p2ApiKey'),
                'p2BaseUrl'   => (string) (setting('VinProviders.p2BaseUrl') ?: 'https://api.carsxe.com/history'),
                'p3Enabled'   => (bool) setting('VinProviders.p3Enabled'),
                'p3Label'     => (string) (setting('VinProviders.p3Label') ?: 'Didit NATIS (South Africa)'),
                'p3ApiKey'    => (string) setting('VinProviders.p3ApiKey'),
                'p3BaseUrl'   => (string) (setting('VinProviders.p3BaseUrl') ?: 'https://verification.didit.me/v3/database-validation/'),
            ],
            'ai'    => [
                'provider'    => setting('Ai.provider') ?: 'claude',
                'enabled'     => (bool) setting('Ai.enabled'),
                'claudeKey'   => (string) setting('Ai.claudeKey'),
                'claudeModel' => (string) (setting('Ai.claudeModel') ?: 'claude-opus-4-8'),
                'openaiKey'   => (string) setting('Ai.openaiKey'),
                'openaiModel' => (string) (setting('Ai.openaiModel') ?: 'gpt-4o'),
                'showVehicleImage' => (bool) setting('Ai.showVehicleImage'),
            ],
        ]);
    }

    public function save()
    {
        // Feature flags (checkbox present = on).
        foreach (self::FEATURE_FLAGS as $f) {
            setting()->set('Features.' . $f, $this->request->getPost('feat_' . $f) ? true : false);
        }

        // Notifications (email + WhatsApp). Secrets keep their stored value if left blank.
        setting()->set('Notify.fromEmail', trim((string) $this->request->getPost('n_fromEmail')));
        setting()->set('Notify.fromName', trim((string) $this->request->getPost('n_fromName')) ?: 'CheckA+ by WheelsTurn');
        setting()->set('Notify.protocol', $this->request->getPost('n_protocol') === 'smtp' ? 'smtp' : 'mail');
        setting()->set('Notify.smtpHost', trim((string) $this->request->getPost('n_smtpHost')));
        setting()->set('Notify.smtpUser', trim((string) $this->request->getPost('n_smtpUser')));
        setting()->set('Notify.smtpPort', (int) ($this->request->getPost('n_smtpPort') ?: 587));
        setting()->set('Notify.smtpCrypto', (string) $this->request->getPost('n_smtpCrypto'));
        setting()->set('Notify.waPhoneId', trim((string) $this->request->getPost('n_waPhoneId')));
        setting()->set('Notify.notifyWorkshop', $this->request->getPost('n_notifyWorkshop') ? true : false);
        setting()->set('Notify.workshopEmail', trim((string) $this->request->getPost('n_workshopEmail')));
        if (($sp = trim((string) $this->request->getPost('n_smtpPass'))) !== '') {
            setting()->set('Notify.smtpPass', $sp);
        }
        if (($wt = trim((string) $this->request->getPost('n_waToken'))) !== '') {
            setting()->set('Notify.waToken', $wt);
        }
        if (($fk = trim((string) $this->request->getPost('n_fcmKey'))) !== '') {
            setting()->set('Push.fcmServerKey', $fk);
        }

        // White-label branding.
        setting()->set('Brand.brandName', trim((string) $this->request->getPost('b_brandName')) ?: 'CheckA+');
        setting()->set('Brand.tagline', trim((string) $this->request->getPost('b_tagline')));
        setting()->set('Brand.primaryColor', trim((string) $this->request->getPost('b_primaryColor')) ?: '#37b34a');
        setting()->set('Brand.darkColor', trim((string) $this->request->getPost('b_darkColor')) ?: '#2d2d2d');
        $logo = $this->request->getFile('b_logo');
        if ($logo !== null && $logo->isValid() && str_starts_with((string) $logo->getMimeType(), 'image/')) {
            $name = 'brand-logo.' . ($logo->getExtension() ?: 'png');
            $logo->move(FCPATH . 'assets/img', $name, true);
            setting()->set('Brand.logoPath', 'assets/img/' . $name);
        }

        // Payments (Paystack). Secret keeps its stored value if left blank.
        setting()->set('Pay.provider', 'paystack');
        setting()->set('Pay.currency', trim((string) $this->request->getPost('p_currency')) ?: 'ZAR');
        setting()->set('Pay.paystackPublic', trim((string) $this->request->getPost('p_paystackPublic')));
        setting()->set('Pay.inspectionFee', (float) $this->request->getPost('p_inspectionFee'));
        if (($psk = trim((string) $this->request->getPost('p_paystackSecret'))) !== '') {
            setting()->set('Pay.paystackSecret', $psk);
        }

        // VIN History Check providers. Secrets keep their stored value if left blank.
        $vinDefaultLabels = ['p1' => 'VinAudit', 'p2' => 'CarsXE', 'p3' => 'Didit NATIS (South Africa)'];
        setting()->set('VinProviders.freeEnabled', $this->request->getPost('vin_freeEnabled') ? true : false);
        foreach (['p1', 'p2', 'p3'] as $slot) {
            setting()->set("VinProviders.{$slot}Enabled", $this->request->getPost("vin_{$slot}Enabled") ? true : false);
            setting()->set("VinProviders.{$slot}Label", trim((string) $this->request->getPost("vin_{$slot}Label")) ?: $vinDefaultLabels[$slot]);
            $url = trim((string) $this->request->getPost("vin_{$slot}BaseUrl"));
            if ($url !== '') {
                setting()->set("VinProviders.{$slot}BaseUrl", $url);
            }
            $key = trim((string) $this->request->getPost("vin_{$slot}ApiKey"));
            if ($key !== '') {
                setting()->set("VinProviders.{$slot}ApiKey", $key);
            }
        }

        setting()->set('Ai.provider', $this->request->getPost('provider') === 'openai' ? 'openai' : 'claude');
        setting()->set('Ai.enabled', $this->request->getPost('enabled') ? true : false);
        setting()->set('Ai.showVehicleImage', $this->request->getPost('showVehicleImage') ? true : false);
        setting()->set('Ai.claudeModel', trim((string) $this->request->getPost('claudeModel')) ?: 'claude-opus-4-8');
        setting()->set('Ai.openaiModel', trim((string) $this->request->getPost('openaiModel')) ?: 'gpt-4o');

        // Only overwrite a key if a new value was typed (blank leaves the stored key).
        $ck = trim((string) $this->request->getPost('claudeKey'));
        if ($ck !== '') {
            setting()->set('Ai.claudeKey', $ck);
        }
        $ok = trim((string) $this->request->getPost('openaiKey'));
        if ($ok !== '') {
            setting()->set('Ai.openaiKey', $ok);
        }

        return redirect()->to('settings')->with('message', 'Settings saved.');
    }
}
