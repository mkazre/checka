<?php

namespace Platform\Models;

use CodeIgniter\Model;

/** Sellable packages — entirely defined in the admin panel (names, prices, modules, limits). */
class PackageModel extends Model
{
    protected $DBGroup       = 'platform';
    protected $table         = 'packages';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'slug', 'tagline', 'description', 'price_monthly', 'price_annual', 'trial_days',
        'modules', 'limits', 'is_public', 'is_active', 'highlight', 'sort',
    ];

    /** @return list<string> */
    public static function modules(array $p): array
    {
        return array_values(array_unique((array) json_decode((string) ($p['modules'] ?? '[]'), true)));
    }

    /** @return array<string,int|null> */
    public static function limits(array $p): array
    {
        return (array) json_decode((string) ($p['limits'] ?? '{}'), true);
    }

    /** @return list<array<string,mixed>> */
    public function ordered(bool $activeOnly = false): array
    {
        if ($activeOnly) {
            $this->where('is_active', 1);
        }

        return $this->orderBy('sort', 'ASC')->orderBy('price_monthly', 'ASC')->findAll();
    }
}
