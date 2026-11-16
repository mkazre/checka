<?php

namespace App\Controllers;

use App\Tenancy\Tenant;
use Platform\Services\Provisioner;

/**
 * Builds this workspace on request from the platform (signed URL), so new
 * customers don't wait for the cron job. Keeps running if the caller hangs up.
 */
class PlatformProvision extends BaseController
{
    public function run(string $sig)
    {
        if (! Tenant::active() || ! hash_equals(Provisioner::signature((string) Tenant::slug()), $sig)) {
            return $this->response->setStatusCode(404);
        }
        ignore_user_abort(true);
        $res = Provisioner::run();

        return $this->response->setStatusCode($res['ok'] ? 200 : 500)->setJSON($res);
    }
}
