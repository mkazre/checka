<?php

namespace Platform\Controllers;

use Platform\Models\EventModel;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\TenantData;
use Platform\Services\TenantRegistry;

/** Customer workspaces: list, create (auto-provisioned), view usage, edit, overrides, status actions, log in as. */
class Tenants extends BaseController
{
    private function filters(): array
    {
        return [
            'status'  => (string) $this->request->getGet('status'),
            'q'       => trim((string) $this->request->getGet('q')),
            'package' => (int) $this->request->getGet('package') ?: null,
        ];
    }

    public function index()
    {
        $f = $this->filters();

        return $this->render('tenants/index', [
            'tenants'  => model(TenantModel::class)->listing($f['status'], $f['q'], $f['package']),
            'packages' => model(PackageModel::class)->ordered(),
            'f'        => $f,
        ]);
    }

    public function export()
    {
        $f    = $this->filters();
        $rows = [];
        foreach (model(TenantModel::class)->listing($f['status'], $f['q'], $f['package']) as $t) {
            $u      = TenantData::usage($t);
            $rows[] = [$t['company_name'], $t['slug'], pf_workspace_url($t['slug']), $t['contact_name'], $t['email'], $t['phone'], $t['vat_number'],
                $t['package_name'], $t['billing_cycle'], TenantModel::STATUSES[$t['status']] ?? $t['status'], $t['trial_ends_at'], $t['current_period_end'],
                $u['users'], $u['clients'], $u['inspections_total'], $u['inspections_month'], $t['created_at']];
        }

        return $this->csv('workspaces-' . date('Y-m-d') . '.csv', ['Company', 'Workspace', 'Address', 'Contact', 'Email', 'Phone', 'VAT no.',
            'Package', 'Billing', 'Status', 'Trial ends', 'Paid until', 'Users', 'Clients', 'Inspections (total)', 'Inspections (this month)', 'Created'], $rows);
    }

    public function create()
    {
        return $this->render('tenants/create', ['packages' => model(PackageModel::class)->ordered(true)]);
    }

    public function store()
    {
        $p      = $this->request->getPost();
        $m      = model(TenantModel::class);
        $slug   = strtolower(trim((string) ($p['slug'] ?? '')));
        $pkg    = model(PackageModel::class)->find((int) ($p['package_id'] ?? 0));
        $errors = [];

        if (trim((string) ($p['company_name'] ?? '')) === '') {
            $errors[] = 'Company name is required.';
        }
        if (! filter_var($p['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required (it becomes their admin login).';
        }
        if (! TenantModel::slugValid($slug)) {
            $errors[] = 'Workspace address must be 3–30 lowercase letters, numbers or dashes, and not a reserved word.';
        } elseif ($m->withDeleted()->where('slug', $slug)->countAllResults() > 0) {
            $errors[] = 'That workspace address is taken.';
        }
        if (! $pkg) {
            $errors[] = 'Choose a package.';
        }
        $password = (string) ($p['admin_password'] ?? '');
        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'The admin password must be at least 8 characters (or leave it empty to generate one).';
        }
        $attach = ($p['db_choice'] ?? 'new') === 'existing' && pf_admin()['role'] === 'owner';
        $prefix = trim((string) ($p['db_prefix'] ?? ''));
        if ($attach && ! preg_match('/^[a-z0-9_]{0,20}$/i', $prefix)) {
            $errors[] = 'Existing table prefix may only contain letters, numbers and underscores.';
        }
        if ($attach && $m->withDeleted()->where('db_prefix', $prefix)->countAllResults() > 0) {
            $errors[] = 'Another workspace already uses that table prefix.';
        }
        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $generated = $password === '';
        if ($generated) {
            $password = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
        }
        $start     = ($p['start'] ?? 'trial') === 'active' ? 'active' : 'trial';
        $trialDays = max(1, (int) (($p['trial_days'] ?? '') !== '' ? $p['trial_days'] : $pkg['trial_days']));

        $id = $m->insert([
            'slug'          => $slug,
            'company_name'  => trim($p['company_name']),
            'contact_name'  => trim((string) ($p['contact_name'] ?? '')),
            'email'         => strtolower(trim($p['email'])),
            'phone'         => trim((string) ($p['phone'] ?? '')),
            'vat_number'    => trim((string) ($p['vat_number'] ?? '')),
            'package_id'    => $pkg['id'],
            'billing_cycle' => ($p['billing_cycle'] ?? '') === 'annual' ? 'annual' : 'monthly',
            'status'        => 'provisioning',
            'trial_ends_at' => $start === 'trial' ? date('Y-m-d 23:59:59', strtotime('+' . $trialDays . ' days')) : null,
            'current_period_end' => $start === 'active' ? date('Y-m-d', strtotime(($p['billing_cycle'] ?? '') === 'annual' ? '+1 year' : '+1 month')) : null,
            'db_mode'       => 'prefix',
            'db_prefix'     => $attach ? $prefix : '',
            'pending_admin' => json_encode(['email' => strtolower(trim($p['email'])), 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'status' => $start]),
            'notes'         => trim((string) ($p['notes'] ?? '')),
        ]);
        if (! $attach) {
            $m->update($id, ['db_prefix' => 'w' . $id . '_']);
        }
        TenantRegistry::write($m->find($id));
        \Platform\Services\Provisioner::kick($m->find($id));
        EventModel::log($id, 'created', 'Workspace created on ' . $pkg['name'] . ($attach ? ' (attached to existing tables "' . $prefix . '")' : '') . '.');

        $msg = 'Workspace created and being set up now — it will be ready in a moment.';
        if ($generated) {
            $msg .= ' Their admin password is: ' . $password . ' — copy it now, it is not shown again.';
        }

        return redirect()->to(site_url('tenants/' . $id))->with('success', $msg);
    }

    public function show(int $id)
    {
        $t = model(TenantModel::class)->find($id);
        if (! $t) {
            return redirect()->to(site_url('tenants'))->with('error', 'Workspace not found.');
        }

        return $this->render('tenants/show', [
            't'        => $t,
            'pkg'      => $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null,
            'eff'      => TenantRegistry::effective($t),
            'usage'    => TenantData::usage($t, true),
            'events'   => model(EventModel::class)->where('tenant_id', $id)->orderBy('id', 'DESC')->findAll(50),
            'invoices' => \Platform\Services\Billing::invoicesFor($id),
            'nextPkg'  => $t['next_package_id'] ? model(PackageModel::class)->find($t['next_package_id']) : null,
            'catalog'  => config('SaasModules')->modules,
            'limitDef' => config('SaasModules')->limits,
        ]);
    }

    public function edit(int $id)
    {
        $t = model(TenantModel::class)->find($id);
        if (! $t) {
            return redirect()->to(site_url('tenants'));
        }

        return $this->render('tenants/edit', [
            't'        => $t,
            'packages' => model(PackageModel::class)->ordered(),
            'extra'    => (array) json_decode((string) ($t['extra_modules'] ?? '[]'), true),
            'over'     => (array) json_decode((string) ($t['limit_overrides'] ?? '{}'), true),
            'catalog'  => config('SaasModules')->modules,
            'limitDef' => config('SaasModules')->limits,
        ]);
    }

    public function update(int $id)
    {
        $m = model(TenantModel::class);
        $t = $m->find($id);
        if (! $t) {
            return redirect()->to(site_url('tenants'));
        }
        $p   = $this->request->getPost();
        $pkg = model(PackageModel::class)->find((int) ($p['package_id'] ?? 0));
        if (trim((string) ($p['company_name'] ?? '')) === '' || ! filter_var($p['email'] ?? '', FILTER_VALIDATE_EMAIL) || ! $pkg) {
            return redirect()->back()->withInput()->with('error', 'Company name, a valid email and a package are required.');
        }

        $catalog = config('SaasModules')->modules;
        $extra   = array_values(array_intersect(array_keys($catalog), (array) ($p['extra_modules'] ?? [])));
        $over    = [];
        foreach (array_keys(config('SaasModules')->limits) as $k) {
            $mode = $p['override_mode'][$k] ?? 'package';
            if ($mode === 'unlimited') {
                $over[$k] = '';
            } elseif ($mode === 'custom') {
                $over[$k] = max(0, (int) ($p['override'][$k] ?? 0));
            }
        }
        $date = static fn ($v) => ($v = trim((string) $v)) !== '' && strtotime($v) ? date('Y-m-d', strtotime($v)) : null;

        $before = TenantRegistry::effective($t)['modules'];
        $m->update($id, [
            'company_name'       => trim($p['company_name']),
            'contact_name'       => trim((string) ($p['contact_name'] ?? '')),
            'email'              => strtolower(trim($p['email'])),
            'phone'              => trim((string) ($p['phone'] ?? '')),
            'vat_number'         => trim((string) ($p['vat_number'] ?? '')),
            'package_id'         => $pkg['id'],
            'billing_cycle'      => ($p['billing_cycle'] ?? '') === 'annual' ? 'annual' : 'monthly',
            'trial_ends_at'      => ($d = $date($p['trial_ends_at'] ?? '')) ? $d . ' 23:59:59' : null,
            'current_period_end' => $date($p['current_period_end'] ?? ''),
            'extra_modules'      => json_encode($extra),
            'limit_overrides'    => json_encode($over),
            'notes'              => trim((string) ($p['notes'] ?? '')),
        ]);
        $t = $m->find($id);
        TenantRegistry::write($t);
        $added = array_diff(TenantRegistry::effective($t)['modules'], $before);
        if ($added && $t['provisioned_at']) {
            TenantData::enableModules($t, $added);
        }
        if ((int) $pkg['id'] !== (int) ($p['old_package_id'] ?? 0)) {
            EventModel::log($id, 'package_changed', 'Package changed to ' . $pkg['name'] . '.');
        } else {
            EventModel::log($id, 'updated', 'Workspace details updated.');
        }

        return redirect()->to(site_url('tenants/' . $id))->with('success', 'Saved.');
    }

    /** suspend | resume | cancel | reopen | extend_trial | retry */
    public function action(int $id)
    {
        $m  = model(TenantModel::class);
        $t  = $m->find($id);
        $do = (string) $this->request->getPost('do');
        if (! $t) {
            return redirect()->to(site_url('tenants'));
        }
        $now  = date('Y-m-d H:i:s');
        $live = static fn (array $t) => ($t['trial_ends_at'] && strtotime($t['trial_ends_at']) > time() && ! $t['current_period_end']) ? 'trial' : 'active';

        switch ($do) {
            case 'suspend':
                $m->update($id, ['status' => 'suspended', 'suspended_at' => $now]);
                $msg = 'Workspace paused. Their users see a "paused" page; nothing is deleted.';
                break;

            case 'resume':
            case 'reopen':
                $m->update($id, ['status' => $live($t), 'suspended_at' => null, 'cancelled_at' => null]);
                $msg = 'Workspace is live again.';
                break;

            case 'cancel':
                $m->update($id, ['status' => 'cancelled', 'cancelled_at' => $now]);
                $msg = 'Workspace cancelled. Data is kept for the retention period set under Settings → Billing rules.';
                break;

            case 'extend_trial':
                $days = max(1, min(365, (int) $this->request->getPost('days')));
                $from = ($t['trial_ends_at'] && strtotime($t['trial_ends_at']) > time()) ? strtotime($t['trial_ends_at']) : time();
                $data = ['trial_ends_at' => date('Y-m-d 23:59:59', strtotime('+' . $days . ' days', $from))];
                if (in_array($t['status'], ['suspended', 'past_due'], true) && ! $t['current_period_end']) {
                    $data += ['status' => 'trial', 'suspended_at' => null];
                }
                $m->update($id, $data);
                $msg = 'Trial extended to ' . pf_date($data['trial_ends_at']) . '.';
                break;

            case 'retry':
                $m->update($id, ['provision_error' => null, 'status' => 'provisioning']);
                $msg = 'Setup will run again within a minute.';
                break;

            default:
                return redirect()->back();
        }

        TenantRegistry::write($m->find($id));
        if ($do === 'retry') {
            \Platform\Services\Provisioner::kick($m->find($id));
        }
        EventModel::log($id, $do, $msg);

        return redirect()->to(site_url('tenants/' . $id))->with('success', $msg);
    }

    /** One-time, 60-second sign-in link into the workspace as its first admin. */
    public function loginAs(int $id)
    {
        $t = model(TenantModel::class)->find($id);
        if (! $t || ! $t['provisioned_at']) {
            return redirect()->back()->with('error', 'This workspace is not set up yet.');
        }
        $token = bin2hex(random_bytes(24));
        $dir   = TenantRegistry::dir() . '/sso';
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        foreach (glob($dir . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - 300) {
                @unlink($old);
            }
        }
        file_put_contents($dir . '/' . $token . '.json', json_encode([
            'slug'    => $t['slug'],
            'expires' => time() + 60,
            'admin'   => pf_admin()['email'],
        ]));
        EventModel::log($id, 'login_as', 'Signed in to the workspace as its admin.');

        return redirect()->to(pf_workspace_url($t['slug'], 'platform-sso/' . $token));
    }
}
