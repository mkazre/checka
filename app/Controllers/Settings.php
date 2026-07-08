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
        'annotation', 'analytics', 'whiteLabel', 'payments',
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
