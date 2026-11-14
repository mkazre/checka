<?php

/**
 * App-wide helper functions (auto-loaded by CodeIgniter).
 */

use Config\Features;

if (! function_exists('feature')) {
    /**
     * Is a feature switched on? Reads the persisted Settings value, falling
     * back to the Config\Features default. Usage: feature('vinDecode').
     */
    function feature(string $key): bool
    {
        static $config = null;
        $config ??= new Features();

        // Only accept known flags so a typo can't silently read "true".
        if (! property_exists($config, $key)) {
            return false;
        }

        // In a SaaS workspace the switch only counts if the package includes the module.
        if (\App\Tenancy\Tenant::active()) {
            $module = config('SaasModules')->moduleForFlag($key);
            if ($module !== null && ! module_enabled($module)) {
                return false;
            }
        }

        return (bool) setting('Features.' . $key);
    }
}

if (! function_exists('module_enabled')) {
    /**
     * Does this workspace's package include the module? Always true outside
     * SaaS mode, for core, and for modules still on the roadmap (they have
     * nothing to unlock yet).
     */
    function module_enabled(string $module): bool
    {
        if (! \App\Tenancy\Tenant::active()) {
            return true;
        }
        $m = config('SaasModules')->modules[$module] ?? null;
        if ($m === null || ! empty($m['core'])) {
            return true;
        }

        return in_array($module, \App\Tenancy\Tenant::modules(), true);
    }
}

if (! function_exists('plan_limit')) {
    /** The package limit for this workspace, or null for unlimited (and always null outside SaaS mode). */
    function plan_limit(string $key): ?int
    {
        return \App\Tenancy\Tenant::active() ? \App\Tenancy\Tenant::limit($key) : null;
    }
}

if (! function_exists('limit_reached')) {
    /** True when adding one more would exceed the package limit. */
    function limit_reached(string $key, int $currentCount): bool
    {
        $max = plan_limit($key);

        return $max !== null && $currentCount >= $max;
    }
}

if (! function_exists('tenant_files')) {
    /**
     * Where a workspace's public files (logos) live: public/tenant-files/<slug>/
     * in SaaS mode, public/assets/img/ otherwise. 'dir' = filesystem path,
     * 'rel' = path relative to public/ (for base_url / settings).
     */
    function tenant_files(string $as = 'rel'): string
    {
        $rel = \App\Tenancy\Tenant::active() ? 'tenant-files/' . \App\Tenancy\Tenant::slug() . '/' : 'assets/img/';
        if ($as === 'dir') {
            if (! is_dir(FCPATH . $rel)) {
                @mkdir(FCPATH . $rel, 0755, true);
            }

            return FCPATH . $rel;
        }

        return $rel;
    }
}

if (! function_exists('own_branding')) {
    /**
     * Use the company's own logo/colours? Always in a SaaS workspace (every
     * customer brands their instance); otherwise only with the White-label feature.
     */
    function own_branding(): bool
    {
        return \App\Tenancy\Tenant::active() || feature('whiteLabel');
    }
}

if (! function_exists('brand')) {
    /**
     * Branding value with white-label overrides applied.
     * Keys: name, tagline, logo (absolute URL), primary, dark.
     */
    function brand(string $key): string
    {
        $defaults = [
            'name'    => 'CheckA+',
            'tagline' => 'by WheelsTurn',
            'logo'    => base_url('assets/img/logo.png'),
            'primary' => '#37b34a',
            'dark'    => '#2d2d2d',
        ];

        if (! own_branding()) {
            return $defaults[$key] ?? '';
        }

        switch ($key) {
            case 'name':    return (string) (setting('Brand.brandName') ?: $defaults['name']);
            case 'tagline': return (string) (setting('Brand.tagline') ?? $defaults['tagline']);
            case 'primary': return (string) (setting('Brand.primaryColor') ?: $defaults['primary']);
            case 'dark':    return (string) (setting('Brand.darkColor') ?: $defaults['dark']);
            case 'logo':
                $p = trim((string) setting('Brand.logoPath'));
                return $p !== '' ? base_url($p) : $defaults['logo'];
            default: return $defaults[$key] ?? '';
        }
    }
}

if (! function_exists('money')) {
    /** 1234.5 → "R 1 234.50" (symbol from Pay.currency; ZAR → R). */
    function money($amount, bool $symbol = true): string
    {
        $n = number_format((float) $amount, 2, '.', ' ');
        if (! $symbol) {
            return $n;
        }
        $cur = strtoupper((string) (setting('Pay.currency') ?: 'ZAR'));
        $sym = ['ZAR' => 'R', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦', 'KES' => 'KSh', 'GHS' => 'GH₵'][$cur] ?? $cur;

        return $sym . ' ' . $n;
    }
}

if (! function_exists('billing')) {
    /** Billing setting with the Config\Billing default as fallback: billing('bankName'). */
    function billing(string $key)
    {
        $v = setting('Billing.' . $key);
        if ($key === 'companyName' && trim((string) $v) === '') {
            return brand('name');
        }

        return $v;
    }
}

if (! function_exists('po_logo')) {
    /**
     * Logo for purchase orders only (Settings → Purchase order logo).
     * 'url' for web pages/emails, 'file' for PDFs. Falls back to the
     * system logo when no PO logo is uploaded.
     */
    function po_logo(string $as = 'url'): string
    {
        $rel = trim((string) setting('Purchasing.logoPath'));
        if ($rel !== '' && is_file(FCPATH . $rel)) {
            return $as === 'file' ? FCPATH . $rel : base_url($rel) . '?v=' . filemtime(FCPATH . $rel);
        }
        if ($as === 'file') {
            $brandRel = own_branding() ? trim((string) setting('Brand.logoPath')) : '';

            return $brandRel !== '' ? FCPATH . $brandRel : FCPATH . 'assets/img/logo.png';
        }

        return brand('logo');
    }
}

if (! function_exists('po_company')) {
    /**
     * Company details printed on purchase orders (Settings → Purchase order
     * company details). Each blank field falls back to the main company
     * details in Billing Settings.
     *
     * @return array{name:string,address:string,phone:string,email:string,regNo:string,vatNo:string}
     */
    function po_company(): array
    {
        $map = ['name' => 'companyName', 'address' => 'companyAddress', 'phone' => 'companyPhone',
            'email' => 'companyEmail', 'regNo' => 'companyRegNo', 'vatNo' => 'companyVatNo'];
        $out = [];
        foreach ($map as $k => $key) {
            $v       = trim((string) setting('Purchasing.' . $key));
            $out[$k] = $v !== '' ? $v : trim((string) ($key === 'companyName' ? billing('companyName') : setting('Billing.' . $key)));
        }

        return $out;
    }
}

if (! function_exists('invoice_badge')) {
    /** Status chip for an invoice row (Draft / Due / Overdue / Part paid / Paid / Void). */
    function invoice_badge(array $inv): string
    {
        $s = \App\Models\InvoiceModel::isOverdue($inv) ? 'overdue' : $inv['status'];
        $l = ['draft' => 'Draft', 'sent' => 'Due', 'partial' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void', 'overdue' => 'Overdue'][$s] ?? $s;

        return '<span class="badge-s s-' . esc($s, 'attr') . '">' . esc($l) . '</span>';
    }
}

if (! function_exists('vin_render_raw')) {
    /**
     * Renders ANY nested array (a VIN provider's raw response, whatever
     * shape it happens to be) as nested HTML definition lists — so the VIN
     * Check report can show "everything this provider returned" without
     * needing bespoke UI per field for every current and future provider.
     * Keys are turned into readable labels (camelCase / snake_case → Title
     * Case); purely numeric/list arrays are rendered as a series of blocks
     * rather than a key label.
     */
    function vin_render_raw($value, int $depth = 0): string
    {
        if ($depth > 6) {
            return '<i>(too deeply nested to display)</i>';
        }

        if (is_array($value)) {
            if ($value === []) {
                return '<span class="vrr-empty">—</span>';
            }

            $isList = array_is_list($value);
            $html   = '<div class="vrr-block' . ($depth > 0 ? ' vrr-nested' : '') . '">';
            foreach ($value as $k => $v) {
                $label = $isList ? ('#' . ((int) $k + 1)) : vin_label_key((string) $k);
                if (is_array($v)) {
                    $html .= '<div class="vrr-row vrr-group"><div class="vrr-k">' . esc($label) . '</div>' . vin_render_raw($v, $depth + 1) . '</div>';
                } else {
                    $html .= '<div class="vrr-row"><div class="vrr-k">' . esc($label) . '</div><div class="vrr-v">' . esc((string) $v) . '</div></div>';
                }
            }

            return $html . '</div>';
        }

        return '<span class="vrr-v">' . esc((string) $value) . '</span>';
    }
}

if (! function_exists('vin_label_key')) {
    /** "VehicleObtainedDate" / "vehicle_obtained_date" → "Vehicle Obtained Date". */
    function vin_label_key(string $key): string
    {
        $spaced = preg_replace('/(?<!^)([A-Z][a-z])/', ' $1', str_replace('_', ' ', $key));

        return trim(preg_replace('/\s+/', ' ', ucwords((string) $spaced)));
    }
}
