<?php

/**
 * Routes for the platform host (app.<rootDomain>) — the SaaS admin panel.
 * Loaded by app/Config/Routes.php instead of the workspace app's routes.
 *
 * @var CodeIgniter\Router\RouteCollection $routes
 */
$routes->setDefaultNamespace('Platform\Controllers');
$routes->setDefaultController('Dashboard');

$routes->group('', ['namespace' => 'Platform\Controllers'], static function ($routes) {
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
