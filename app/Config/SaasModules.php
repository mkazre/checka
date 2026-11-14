<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * The catalogue of sellable modules. This is the ONLY place modules are
 * defined, because each one is code. Packages (names, prices, which
 * modules they include, limits) are created in the platform admin panel
 * and stored in the database — nothing about packages is hard-coded.
 *
 *  - flags:  Config\Features switches the module unlocks (feature() checks them).
 *  - routes: URI prefixes that belong to the module (blocked when not in the package).
 *  - status: 'live' (built) or 'roadmap' (shown in the admin panel, can be put in
 *            packages now, switches on automatically when it ships).
 */
class SaasModules extends BaseConfig
{
    /** @var array<string, array{name:string, category:string, description:string, flags?:list<string>, routes?:list<string>, status:string, core?:bool}> */
    public array $modules = [
        // ---------------- Core (always included) ----------------
        'core' => ['name' => 'Inspections & reports', 'category' => 'Core', 'status' => 'live', 'core' => true,
            'description' => 'Digital inspections, vehicles, clients, branded report PDFs, QR verification, users & roles.'],

        // ---------------- Existing Checka modules ----------------
        'templates'     => ['name' => 'Checklist template builder', 'category' => 'Inspections', 'status' => 'live', 'routes' => ['templates', 'sections', 'fields'],
            'description' => 'Build and edit your own inspection checklists.'],
        'offline'       => ['name' => 'Offline capture & mobile app', 'category' => 'Inspections', 'status' => 'live', 'flags' => ['offline'],
            'description' => 'Installable app; capture with no signal and sync later.'],
        'vin_decode'    => ['name' => 'VIN auto-decode', 'category' => 'Vehicles', 'status' => 'live', 'flags' => ['vinDecode'],
            'description' => 'Decode a VIN into make, model, body and engine.'],
        'vin_history'   => ['name' => 'VIN history checks', 'category' => 'Vehicles', 'status' => 'live', 'flags' => ['vinCheck'], 'routes' => ['vin-checks'],
            'description' => 'Accident, stolen and registry checks with an archive, PDF and sharing.'],
        'client_portal' => ['name' => 'Client portal & e-sign', 'category' => 'Clients', 'status' => 'live', 'flags' => ['clientPortal'],
            'description' => 'Clients view, approve and sign reports online.'],
        'quotes'        => ['name' => 'Repair quotes', 'category' => 'Sales', 'status' => 'live', 'flags' => ['quotes'],
            'description' => 'Turn findings into a priced repair estimate.'],
        'notifications' => ['name' => 'Email & WhatsApp delivery', 'category' => 'Clients', 'status' => 'live', 'flags' => ['notifications', 'notifyEmail', 'notifyWhatsapp'],
            'description' => 'Send reports to clients automatically.'],
        'history'       => ['name' => 'Inspection history & trends', 'category' => 'Vehicles', 'status' => 'live', 'flags' => ['history'],
            'description' => 'Track a vehicle across inspections with wear trends.'],
        'ai'            => ['name' => 'AI report intelligence', 'category' => 'AI', 'status' => 'live', 'flags' => ['aiIntelligence', 'aiConclusion', 'aiPhotoAnalysis'],
            'description' => 'AI conclusions, finding triage and photo analysis.'],
        'annotation'    => ['name' => 'Photo annotation', 'category' => 'Inspections', 'status' => 'live', 'flags' => ['annotation'],
            'description' => 'Draw on damage photos.'],
        'analytics'     => ['name' => 'Analytics dashboard', 'category' => 'Insights', 'status' => 'live', 'flags' => ['analytics'], 'routes' => ['analytics'],
            'description' => 'Workshop KPIs, common failures and productivity.'],
        'billing'       => ['name' => 'Invoicing, payments & statements', 'category' => 'Finance', 'status' => 'live', 'flags' => ['billing', 'payments'], 'routes' => ['invoices', 'payments'],
            'description' => 'Invoices, Pay Now links, payments, statements and monthly statement runs.'],
        'purchasing'    => ['name' => 'Vendors & purchase orders', 'category' => 'Finance', 'status' => 'live', 'flags' => ['purchasing'], 'routes' => ['vendors', 'purchase-orders', 'items'],
            'description' => 'Supplier register, purchase orders, items catalogue and PO summaries.'],
        'white_label'   => ['name' => 'Remove CheckA+ branding', 'category' => 'Branding', 'status' => 'live', 'flags' => ['whiteLabel'],
            'description' => 'Every workspace uses its own logo, colours and company details; this removes "Powered by CheckA+" from reports, the client portal and emails.'],
        'api'           => ['name' => 'API access', 'category' => 'Integrations', 'status' => 'live', 'routes' => ['api'],
            'description' => 'REST API with tokens for integrations.'],

        // ---------------- Roadmap: 30 new features ----------------
        'booking'        => ['name' => 'Online booking & calendar', 'category' => 'Operations', 'status' => 'roadmap',
            'description' => 'Clients book inspections online; team calendar with slots, reminders and no-show tracking.'],
        'job_board'      => ['name' => 'Workshop job board', 'category' => 'Operations', 'status' => 'roadmap',
            'description' => 'Kanban from booked → inspecting → quoted → approved → in repair → done, with turnaround (TAT) timers.'],
        'field_dispatch' => ['name' => 'Mobile inspector dispatch & GPS', 'category' => 'Operations', 'status' => 'roadmap',
            'description' => 'Assign on-site inspections, route to the client, GPS check-in and geo-tagged, time-stamped evidence.'],
        'guided_capture' => ['name' => 'Guided 360° photo capture', 'category' => 'Inspections', 'status' => 'roadmap',
            'description' => 'On-screen shot list and overlays so every vehicle is photographed the same way.'],
        'ai_damage'      => ['name' => 'AI damage detection', 'category' => 'AI', 'status' => 'roadmap',
            'description' => 'Automatically spots and marks dents, scratches and cracks on photos and suggests the finding.'],
        'voice_notes'    => ['name' => 'Voice-to-findings', 'category' => 'AI', 'status' => 'roadmap',
            'description' => 'Inspectors dictate notes; AI writes them up against the right checklist item.'],
        'smart_scan'     => ['name' => 'Smart document scan', 'category' => 'Vehicles', 'status' => 'roadmap',
            'description' => 'Scan licence discs, registration papers and ID to auto-fill vehicle and client details.'],
        'odometer_guard' => ['name' => 'Odometer fraud alerts', 'category' => 'Vehicles', 'status' => 'roadmap',
            'description' => 'Flags mileage that goes backwards or jumps abnormally across inspections and sources.'],
        'wear_predict'   => ['name' => 'Tyre & brake wear prediction', 'category' => 'AI', 'status' => 'roadmap',
            'description' => 'Predicts when tyres and brakes will need replacing from tread/measurement history.'],
        'tamper_proof'   => ['name' => 'Tamper-proof reports', 'category' => 'Trust', 'status' => 'roadmap',
            'description' => 'Digitally sealed PDFs with a public verification ledger — proof a report was never altered.'],
        'verified_badge' => ['name' => '"CheckA+ Verified" listing badge', 'category' => 'Trust', 'status' => 'roadmap',
            'description' => 'Embeddable badge for car listings (AutoTrader, Cars.co.za, dealer sites) linking to the verified report.'],
        'trade_in'       => ['name' => 'Trade-in valuation assistant', 'category' => 'Sales', 'status' => 'roadmap',
            'description' => 'Turns condition score and findings into a reconditioning cost and trade-in offer adjustment.'],
        'parts_pricing'  => ['name' => 'Repair cost estimator', 'category' => 'Sales', 'status' => 'roadmap',
            'description' => 'Labour times and parts prices to price repairs automatically from findings.'],
        'rfq'            => ['name' => 'Supplier quote requests (RFQ)', 'category' => 'Finance', 'status' => 'roadmap',
            'description' => 'Send one parts request to several vendors, compare their quotes and convert the best to a PO.'],
        'inventory'      => ['name' => 'Parts stock & goods received', 'category' => 'Finance', 'status' => 'roadmap',
            'description' => 'Stock levels, goods-received notes against POs, and parts used per job.'],
        'accounting_sync' => ['name' => 'Accounting integrations', 'category' => 'Integrations', 'status' => 'roadmap',
            'description' => 'Two-way sync of invoices, payments and suppliers with Xero, Sage and QuickBooks.'],
        'webhooks'       => ['name' => 'Webhooks & Zapier', 'category' => 'Integrations', 'status' => 'roadmap',
            'description' => 'Trigger other systems when inspections complete, invoices are paid, etc.'],
        'crm'            => ['name' => 'Client CRM & service reminders', 'category' => 'Clients', 'status' => 'roadmap',
            'description' => 'Follow-ups, licence-expiry and next-service reminders by email/SMS/WhatsApp.'],
        'whatsapp_flow'  => ['name' => 'WhatsApp approvals & payments', 'category' => 'Clients', 'status' => 'roadmap',
            'description' => 'Clients receive the report, approve repairs and pay — all inside WhatsApp.'],
        'nps'            => ['name' => 'Customer satisfaction (NPS)', 'category' => 'Clients', 'status' => 'roadmap',
            'description' => 'Automatic post-inspection survey with NPS dashboard and Google review prompts.'],
        'fleet_portal'   => ['name' => 'Fleet client portal', 'category' => 'Fleet', 'status' => 'roadmap',
            'description' => 'Corporate clients see their whole fleet: inspection status, compliance and costs.'],
        'schedules'      => ['name' => 'Recurring inspection schedules', 'category' => 'Fleet', 'status' => 'roadmap',
            'description' => 'Auto-create inspections on a schedule (monthly, by km, COF/roadworthy due dates).'],
        'driver_checks'  => ['name' => 'Driver pre-trip checks', 'category' => 'Fleet', 'status' => 'roadmap',
            'description' => 'Simple daily walk-around checklist for drivers with defect escalation.'],
        'branches'       => ['name' => 'Multi-branch', 'category' => 'Operations', 'status' => 'roadmap',
            'description' => 'Branches with their own users, numbering and reporting, rolled up for head office.'],
        'custom_roles'   => ['name' => 'Custom roles & permissions', 'category' => 'Security', 'status' => 'roadmap',
            'description' => 'Build your own roles with fine-grained permissions.'],
        'approvals'      => ['name' => 'Approval workflows', 'category' => 'Operations', 'status' => 'roadmap',
            'description' => 'Manager sign-off before reports are released or POs above a limit are sent.'],
        'audit_log'      => ['name' => 'Audit trail', 'category' => 'Security', 'status' => 'roadmap',
            'description' => 'Who changed what and when, on every record — POPIA-ready.'],
        'two_factor'     => ['name' => 'Two-factor login', 'category' => 'Security', 'status' => 'roadmap',
            'description' => 'Authenticator-app or email codes for staff logins.'],
        'multilingual'   => ['name' => 'Multi-language reports', 'category' => 'Branding', 'status' => 'roadmap',
            'description' => 'Reports and client emails in English, Afrikaans, isiZulu and more.'],
        'backups'        => ['name' => 'Scheduled backups & export', 'category' => 'Security', 'status' => 'roadmap',
            'description' => 'Automatic backups and one-click full data export (Excel/PDF/ZIP) to your own Google Drive.'],
    ];

    /** Limits a package can set (null/blank = unlimited). */
    public array $limits = [
        'users'              => ['name' => 'Users',                      'unit' => 'users'],
        'inspections_month'  => ['name' => 'Inspections per month',      'unit' => 'inspections'],
        'storage_mb'         => ['name' => 'Storage',                    'unit' => 'MB'],
        'vin_checks_month'   => ['name' => 'VIN history checks per month', 'unit' => 'checks'],
        'ai_calls_month'     => ['name' => 'AI actions per month',       'unit' => 'actions'],
        'clients'            => ['name' => 'Clients',                    'unit' => 'clients'],
    ];

    /** Module key for a Config\Features flag, or null if the flag isn't sold separately. */
    public function moduleForFlag(string $flag): ?string
    {
        foreach ($this->modules as $key => $m) {
            if (in_array($flag, $m['flags'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }

    /** Module key that owns a URI path (first segment match), or null. */
    public function moduleForPath(string $path): ?string
    {
        $first = strtolower(explode('/', trim($path, '/'))[0] ?? '');
        foreach ($this->modules as $key => $m) {
            if (in_array($first, $m['routes'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }
}
