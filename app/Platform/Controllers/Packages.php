<?php

namespace Platform\Controllers;

use Platform\Models\EventModel;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\TenantData;
use Platform\Services\TenantRegistry;

/** Package builder: you name each package, set prices/trial, tick its modules and set its limits. */
class Packages extends BaseController
{
    public function index()
    {
        $counts = [];
        foreach (db_connect('platform')->table('tenants')->select('package_id, COUNT(*) n')->where('deleted_at', null)
            ->whereNotIn('status', ['cancelled'])->groupBy('package_id')->get()->getResultArray() as $r) {
            $counts[$r['package_id']] = (int) $r['n'];
        }

        return $this->render('packages/index', [
            'packages' => model(PackageModel::class)->ordered(),
            'counts'   => $counts,
            'catalog'  => config('SaasModules')->modules,
            'limitDef' => config('SaasModules')->limits,
        ]);
    }

    public function form(?int $id = null, ?array $copyFrom = null)
    {
        $pkg = $id ? model(PackageModel::class)->find($id) : $copyFrom;
        if ($id && ! $pkg) {
            return redirect()->to(site_url('packages'))->with('error', 'Package not found.');
        }

        return $this->render('packages/form', [
            'pkg'      => $pkg,
            'id'       => $id,
            'modules'  => $pkg ? PackageModel::modules($pkg) : ['core'],
            'limits'   => $pkg ? PackageModel::limits($pkg) : [],
            'catalog'  => config('SaasModules')->modules,
            'limitDef' => config('SaasModules')->limits,
            'trialDefault' => \Platform\Services\PlatformSettings::value('billing', 'trialDays', 14),
        ]);
    }

    public function copy(int $id)
    {
        $pkg = model(PackageModel::class)->find($id);
        if (! $pkg) {
            return redirect()->to(site_url('packages'));
        }
        $pkg['name'] .= ' (copy)';
        $pkg['slug'] = '';

        return $this->form(null, $pkg);
    }

    public function save(?int $id = null)
    {
        $m    = model(PackageModel::class);
        $old  = $id ? $m->find($id) : null;
        if ($id && ! $old) {
            return redirect()->to(site_url('packages'));
        }
        $post = $this->request->getPost();
        $name = trim((string) ($post['name'] ?? ''));
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string) (($post['slug'] ?? '') ?: $name)), '-'));

        $errors = [];
        if ($name === '') {
            $errors[] = 'Give the package a name.';
        }
        if ($slug === '' || $m->where('slug', $slug)->where('id !=', (int) $id)->countAllResults() > 0) {
            $errors[] = 'That short code is already used by another package.';
        }
        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $catalog = config('SaasModules')->modules;
        $modules = array_values(array_intersect(array_keys($catalog), array_merge(['core'], (array) ($post['modules'] ?? []))));
        $limits  = [];
        foreach (array_keys(config('SaasModules')->limits) as $k) {
            $v           = trim((string) ($post['limits'][$k] ?? ''));
            $limits[$k]  = $v === '' ? null : max(0, (int) $v);
        }

        $data = [
            'name'          => mb_substr($name, 0, 80),
            'slug'          => substr($slug, 0, 60),
            'tagline'       => mb_substr(trim((string) ($post['tagline'] ?? '')), 0, 160),
            'description'   => trim((string) ($post['description'] ?? '')),
            'price_monthly' => round((float) ($post['price_monthly'] ?? 0), 2),
            'price_annual'  => round((float) ($post['price_annual'] ?? 0), 2),
            'trial_days'    => max(0, (int) ($post['trial_days'] ?? 0)),
            'modules'       => json_encode($modules),
            'limits'        => json_encode($limits),
            'is_public'     => ! empty($post['is_public']) ? 1 : 0,
            'is_active'     => ! empty($post['is_active']) ? 1 : 0,
            'highlight'     => ! empty($post['highlight']) ? 1 : 0,
            'sort'          => (int) ($post['sort'] ?? 0),
        ];

        if ($id) {
            $m->update($id, $data);
            // Push the change to every workspace on this package, switching on newly added modules.
            $added = array_diff($modules, PackageModel::modules($old));
            TenantRegistry::writeForPackage($id);
            if ($added) {
                foreach (model(TenantModel::class)->where('package_id', $id)->where('provisioned_at IS NOT NULL')->findAll() as $t) {
                    TenantData::enableModules($t, $added);
                }
            }
            EventModel::log(null, 'package_updated', 'Package "' . $data['name'] . '" updated.');
            $msg = 'Package saved — changes applied to its workspaces.';
        } else {
            $m->insert($data);
            EventModel::log(null, 'package_created', 'Package "' . $data['name'] . '" created.');
            $msg = 'Package created.';
        }

        return redirect()->to(site_url('packages'))->with('success', $msg);
    }

    public function delete(int $id)
    {
        $pkg = model(PackageModel::class)->find($id);
        if (! $pkg) {
            return redirect()->to(site_url('packages'));
        }
        if (model(TenantModel::class)->withDeleted()->where('package_id', $id)->countAllResults() > 0) {
            model(PackageModel::class)->update($id, ['is_active' => 0, 'is_public' => 0]);

            return redirect()->to(site_url('packages'))->with('info', 'Workspaces still use "' . $pkg['name'] . '", so it was retired instead (hidden and no longer sold). Move them to another package to delete it.');
        }
        model(PackageModel::class)->delete($id);
        EventModel::log(null, 'package_deleted', 'Package "' . $pkg['name'] . '" deleted.');

        return redirect()->to(site_url('packages'))->with('success', 'Package deleted.');
    }
}
