<?php

/**
 * Routes for the platform host (app.<rootDomain>) — the SaaS admin panel.
 * Loaded by app/Config/Routes.php instead of the workspace app's routes.
 *
 * @var CodeIgniter\Router\RouteCollection $routes
 */
$routes->setDefaultNamespace('Platform\Controllers');
$routes->setDefaultController('Dashboard');

// The bare domain and www serve the marketing website (site/ folder).
$host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$root = strtolower((string) (\App\Tenancy\Tenant::platformConfig()['rootDomain'] ?? ''));
if (! is_cli() && ($host === $root || $host === 'www.' . $root)) {
    $routes->get('/', 'Site::page', ['namespace' => 'Platform\Controllers']);
    $routes->get('(:any)', 'Site::page/$1', ['namespace' => 'Platform\Controllers']);

    return;
}

$routes->group('', ['namespace' => 'Platform\Controllers'], static function ($routes) {
    // ---- Public (no admin login): sign-up, pay, website API, gateway callbacks
    $routes->post('signup', 'Signup::create');
    $routes->get('signup', 'Signup::redirectToSite');
    $routes->get('welcome/(:segment)', 'Signup::welcome/$1');
    $routes->post('find-workspace', 'Api::findWorkspace');
    $routes->get('api/plans', 'Api::plans');
    $routes->get('api/workspace-availability', 'Api::availability');
    $routes->get('api/signup-status/(:segment)', 'Api::signupStatus/$1');
    $routes->post('api/contact', 'Api::contact');
    $routes->options('api/(:any)', 'Api::preflight');

    $routes->get('pay/(:segment)', 'Pay::show/$1');
    $routes->get('pay/(:segment)/invoice', 'Pay::invoice/$1');
    $routes->post('pay/(:segment)/paystack', 'Pay::paystack/$1');
    $routes->get('pay/(:segment)/paystack/callback', 'Pay::paystackCallback/$1');
    $routes->post('pay/(:segment)/payfast', 'Pay::payfast/$1');
    $routes->post('pay/(:segment)/ozow', 'Pay::ozow/$1');
    $routes->match(['get', 'post'], 'pay/(:segment)/return/(:segment)', 'Pay::returned/$1/$2');
    $routes->post('webhooks/paystack', 'Webhooks::paystack');
    $routes->post('webhooks/payfast', 'Webhooks::payfast');
    $routes->post('webhooks/ozow', 'Webhooks::ozow');

    // ---- Admin panel
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
    $routes->get('logout', 'Auth::logout');

    $routes->group('', ['filter' => 'platform-auth'], static function ($routes) {
        $routes->get('/', 'Dashboard::index');

        $routes->get('packages', 'Packages::index');
        $routes->get('packages/new', 'Packages::form');
        $routes->get('packages/(:num)/edit', 'Packages::form/$1');
        $routes->get('packages/(:num)/copy', 'Packages::copy/$1');
        $routes->post('packages/save', 'Packages::save');
        $routes->post('packages/save/(:num)', 'Packages::save/$1');
        $routes->post('packages/(:num)/delete', 'Packages::delete/$1');

        $routes->get('tenants', 'Tenants::index');
        $routes->get('tenants/export', 'Tenants::export');
        $routes->get('tenants/new', 'Tenants::create');
        $routes->post('tenants', 'Tenants::store');
        $routes->get('tenants/(:num)', 'Tenants::show/$1');
        $routes->get('tenants/(:num)/edit', 'Tenants::edit/$1');
        $routes->post('tenants/(:num)', 'Tenants::update/$1');
        $routes->post('tenants/(:num)/action', 'Tenants::action/$1');
        $routes->post('tenants/(:num)/login-as', 'Tenants::loginAs/$1');
        $routes->get('tenants/(:num)/clients.csv', 'Clients::exportTenant/$1');

        $routes->get('invoices', 'Invoices::index');
        $routes->get('invoices/export', 'Invoices::export');
        $routes->post('tenants/(:num)/invoice', 'Invoices::create/$1');
        $routes->post('invoices/(:num)/paid', 'Invoices::markPaid/$1');
        $routes->post('invoices/(:num)/void', 'Invoices::void/$1');
        $routes->post('invoices/(:num)/resend', 'Invoices::resend/$1');
        $routes->post('invoices/(:num)/charge', 'Invoices::charge/$1');

        $routes->get('leads', 'Leads::index');
        $routes->get('leads/export', 'Leads::export');
        $routes->post('leads/(:num)/handled', 'Leads::handled/$1');

        $routes->get('clients', 'Clients::index');
        $routes->get('clients/export', 'Clients::export');

        $routes->get('settings', 'Settings::index');
        $routes->post('settings/test-email', 'Settings::testEmail');
        $routes->get('settings/(:segment)', 'Settings::index/$1');
        $routes->post('settings/(:segment)', 'Settings::save/$1');

        $routes->get('account', 'Admins::account');
        $routes->post('account', 'Admins::saveAccount');
        $routes->get('admins', 'Admins::index', ['filter' => 'platform-auth:owner']);
        $routes->post('admins', 'Admins::store', ['filter' => 'platform-auth:owner']);
        $routes->post('admins/(:num)/toggle', 'Admins::toggle/$1', ['filter' => 'platform-auth:owner']);
    });
});
