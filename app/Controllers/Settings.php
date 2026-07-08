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
            'title' => 'Settings',
            'feat'  => $feat,
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
