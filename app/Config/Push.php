<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Native push (Firebase Cloud Messaging) settings. Dormant until a server key
 * is entered on the Settings page. Used by NotificationService::pushToUser.
 */
class Push extends BaseConfig
{
    public string $fcmServerKey = '';
}
