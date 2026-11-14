<?php

namespace Platform\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $DBGroup       = 'platform';
    protected $table         = 'admins';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'email', 'password_hash', 'role', 'active', 'last_login_at'];
}
