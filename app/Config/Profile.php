<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Per-user profile values, stored with the settings library under the context "user:<id>". */
class Profile extends BaseConfig
{
    public string $name = '';
}
