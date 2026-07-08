<?php

namespace App\Controllers;

use App\Models\DeviceTokenModel;

/**
 * Registers a device's FCM token so the workshop can push native
 * notifications to it. Called by the Capacitor shell after it obtains a token.
 */
class Push extends BaseController
{
    public function register()
    {
        $token = trim((string) $this->request->getPost('token'));
        if ($token === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'No token']);
        }
        $platform = trim((string) $this->request->getPost('platform')) ?: 'android';

        model(DeviceTokenModel::class)->upsertToken(auth()->id(), $token, $platform);

        return $this->response->setJSON(['ok' => true]);
    }
}
