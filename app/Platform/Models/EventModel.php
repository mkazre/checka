<?php

namespace Platform\Models;

use CodeIgniter\Model;

/** Activity log for the platform (who did what to which workspace). */
class EventModel extends Model
{
    protected $DBGroup       = 'platform';
    protected $table         = 'events';
    protected $returnType    = 'array';
    protected $allowedFields = ['tenant_id', 'type', 'message', 'actor', 'created_at'];

    public static function log(?int $tenantId, string $type, string $message, ?string $actor = null): void
    {
        model(self::class)->insert([
            'tenant_id'  => $tenantId,
            'type'       => $type,
            'message'    => $message,
            'actor'      => $actor ?? (is_cli() ? 'system' : (session()->get('pf_admin_name') ?: 'system')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
