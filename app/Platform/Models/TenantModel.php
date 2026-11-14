<?php

namespace Platform\Models;

use CodeIgniter\Model;

/** Customer workspaces. */
class TenantModel extends Model
{
    protected $DBGroup        = 'platform';
    protected $table          = 'tenants';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'slug', 'company_name', 'contact_name', 'email', 'phone', 'vat_number', 'package_id',
        'billing_cycle', 'status', 'trial_ends_at', 'current_period_end', 'db_mode', 'db_prefix',
        'extra_modules', 'limit_overrides', 'pending_admin', 'provisioned_at', 'provision_error',
        'suspended_at', 'cancelled_at', 'notes',
    ];

    public const STATUSES = [
        'provisioning' => 'Setting up',
        'trial'        => 'Trial',
        'active'       => 'Active',
        'past_due'     => 'Past due',
        'suspended'    => 'Suspended',
        'cancelled'    => 'Cancelled',
    ];

    /** Words that can't be workspace addresses. */
    public const RESERVED = ['www', 'app', 'api', 'admin', 'mail', 'webmail', 'cpanel', 'ftp', 'smtp', 'pop', 'imap', 'support',
        'help', 'billing', 'status', 'docs', 'blog', 'dev', 'test', 'staging', 'static', 'cdn', 'assets', 'checka', 'checkaplus'];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first() ?: null;
    }

    /** @return list<array<string,mixed>> tenants joined with package name */
    public function listing(string $status = '', string $q = '', ?int $packageId = null): array
    {
        $b = $this->select('tenants.*, packages.name AS package_name')
            ->join('packages', 'packages.id = tenants.package_id', 'left')
            ->orderBy('tenants.created_at', 'DESC');
        if ($status !== '') {
            $b->where('tenants.status', $status);
        }
        if ($packageId) {
            $b->where('tenants.package_id', $packageId);
        }
        if ($q !== '') {
            $b->groupStart()->like('tenants.company_name', $q)->orLike('tenants.slug', $q)
                ->orLike('tenants.email', $q)->orLike('tenants.contact_name', $q)->groupEnd();
        }

        return $b->findAll();
    }

    public static function slugValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])$/', $slug) && ! in_array($slug, self::RESERVED, true);
    }

    public static function slugFrom(string $company): string
    {
        $s = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $company));
        $s = trim(preg_replace('/-(pty|ltd|cc|inc|t-a)(?=-|$)/', '', $s), '-');

        return substr($s ?: 'workspace', 0, 30);
    }
}
