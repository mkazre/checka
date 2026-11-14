<?php

namespace Platform\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Platform\Models\PackageModel;

/**
 * Creates four starter packages (Bronze, Silver, Gold, Platinum) so the
 * pricing page has something to show. Safe to re-run: a package whose short
 * code already exists is left untouched. Edit or delete them freely in the
 * admin panel afterwards — nothing here is permanent.
 *
 *   php spark platform:sample-packages
 */
class PlatformSamplePackages extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:sample-packages';
    protected $description = 'Create sample Bronze/Silver/Gold/Platinum packages (skips existing ones).';

    public function run(array $params)
    {
        $bronze   = ['core', 'annotation', 'notifications'];
        $silver   = array_merge($bronze, ['offline', 'client_portal', 'billing', 'vin_decode', 'history']);
        $gold     = array_merge($silver, ['templates', 'vin_history', 'quotes', 'ai', 'purchasing', 'booking', 'guided_capture']);
        $platinum = array_merge($gold, ['analytics', 'api', 'white_label', 'branches', 'custom_roles', 'audit_log', 'fleet_portal', 'webhooks']);

        $packages = [
            [
                'name' => 'Bronze', 'slug' => 'bronze', 'sort' => 1, 'highlight' => 0,
                'tagline' => 'For one-person workshops going digital.',
                'description' => 'Digital inspections, photo evidence and branded PDF reports for a single inspector.',
                'price_monthly' => 399, 'price_annual' => 3990, 'trial_days' => 14, 'modules' => $bronze,
                'limits' => ['users' => 1, 'inspections_month' => 25, 'storage_mb' => 1024, 'vin_checks_month' => 0, 'ai_calls_month' => 0, 'clients' => 100],
            ],
            [
                'name' => 'Silver', 'slug' => 'silver', 'sort' => 2, 'highlight' => 0,
                'tagline' => 'For independent workshops starting out with digital inspections.',
                'description' => 'Everything a small workshop needs: offline app, client e-sign portal and invoicing.',
                'price_monthly' => 799, 'price_annual' => 7990, 'trial_days' => 14, 'modules' => $silver,
                'limits' => ['users' => 2, 'inspections_month' => 50, 'storage_mb' => 5120, 'vin_checks_month' => 0, 'ai_calls_month' => 0, 'clients' => 500],
            ],
            [
                'name' => 'Gold', 'slug' => 'gold', 'sort' => 3, 'highlight' => 1,
                'tagline' => 'For growing workshops and dealers who need VIN checks and purchasing.',
                'description' => 'Custom checklists, VIN history checks, repair quotes, AI insights and purchase orders.',
                'price_monthly' => 1499, 'price_annual' => 14990, 'trial_days' => 14, 'modules' => $gold,
                'limits' => ['users' => 5, 'inspections_month' => 200, 'storage_mb' => 20480, 'vin_checks_month' => 10, 'ai_calls_month' => 500, 'clients' => null],
            ],
            [
                'name' => 'Platinum', 'slug' => 'platinum', 'sort' => 4, 'highlight' => 0,
                'tagline' => 'For multi-branch dealer groups and fleets running high volumes.',
                'description' => 'Everything, plus analytics, API access, your own branding only, branches and audit trail.',
                'price_monthly' => 2799, 'price_annual' => 27990, 'trial_days' => 14, 'modules' => $platinum,
                'limits' => ['users' => 15, 'inspections_month' => 750, 'storage_mb' => 102400, 'vin_checks_month' => null, 'ai_calls_month' => null, 'clients' => null],
            ],
        ];

        $m       = model(PackageModel::class);
        $catalog = config('SaasModules')->modules;
        foreach ($packages as $p) {
            if ($m->where('slug', $p['slug'])->countAllResults() > 0) {
                CLI::write('Skipped ' . $p['name'] . ' (already exists).', 'yellow');

                continue;
            }
            $p['modules']   = json_encode(array_values(array_intersect(array_keys($catalog), array_unique($p['modules']))));
            $p['limits']    = json_encode($p['limits']);
            $p['is_public'] = 1;
            $p['is_active'] = 1;
            $m->insert($p);
            CLI::write('Created ' . $p['name'] . '.', 'green');
        }

        return EXIT_SUCCESS;
    }
}
