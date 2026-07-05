<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\Controller;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

/**
 * API authentication — issue and inspect access tokens for the future
 * React / Flutter client.
 */
class Auth extends Controller
{
    use ResponseTrait;

    /**
     * POST /api/v1/auth/token
     * Body: { email, password, device? }  → { token, user }
     */
    public function token()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $credentials = [
            'email'    => $this->request->getVar('email'),
            'password' => $this->request->getVar('password'),
        ];

        $result = auth('session')->check($credentials);
        if (! $result->isOK()) {
            return $this->failUnauthorized('Invalid credentials.');
        }

        /** @var \CodeIgniter\Shield\Entities\User $user */
        $user   = $result->extraInfo();
        $device = $this->request->getVar('device') ?: 'api-client';
        $token  = $user->generateAccessToken($device);

        return $this->respond([
            'token' => $token->raw_token,
            'user'  => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'groups'   => $user->getGroups(),
            ],
        ]);
    }

    /** GET /api/v1/me  (token-authed) */
    public function me()
    {
        $user = auth()->user();
        if ($user === null) {
            return $this->failUnauthorized();
        }

        return $this->respond([
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
            'groups'   => $user->getGroups(),
        ]);
    }
}
