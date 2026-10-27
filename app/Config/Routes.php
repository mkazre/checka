<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------------------------
// Shield authentication routes (login, logout, register, magic-link…)
// ---------------------------------------------------------------------
service('auth')->routes($routes);

// ---------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('verify/(:segment)', 'Verify::show/$1');

// Public, read-only VIN Check share landing (QR code / emailed link) — feature: vinCheck
$routes->get('vin-check/(:segment)', 'VinChecks::publicView/$1');

// Public "Pay Now" pages linked from invoices/statements (feature: billing)
$routes->get('pay/account/(:segment)', 'Pay::account/$1');
$routes->get('pay/account/(:segment)/pdf', 'Pay::accountPdf/$1');
$routes->get('pay/account/(:segment)/card', 'Pay::accountCard/$1');
$routes->get('pay/account/(:segment)/done', 'Pay::accountDone/$1');
$routes->get('pay/(:segment)', 'Pay::invoice/$1');
$routes->get('pay/(:segment)/pdf', 'Pay::invoicePdf/$1');
$routes->get('pay/(:segment)/card', 'Pay::invoiceCard/$1');
$routes->get('pay/(:segment)/done', 'Pay::invoiceDone/$1');

// Public client approval portal (feature: clientPortal)
$routes->get('portal/(:segment)', 'Portal::show/$1');
$routes->post('portal/(:segment)', 'Portal::submit/$1');
$routes->get('portal/(:segment)/pay', 'Portal::pay/$1');
$routes->get('portal/(:segment)/paid', 'Portal::paid/$1');
$routes->get('portal/(:segment)/pdf', 'Portal::pdf/$1');

// ---------------------------------------------------------------------
// Dashboard / app — requires a logged-in session
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function ($routes): void {
    $routes->get('dashboard', 'Dashboard::index');

    // Fresh CSRF token for the offline sync queue (feature: offline).
    $routes->get('csrf', static function () {
        return service('response')->setJSON(['name' => csrf_header(), 'hash' => csrf_hash()]);
    });

    // Native push: register this device's FCM token.
    $routes->post('push/register', 'Push::register');

    // Serve uploaded media (auth-guarded)
    $routes->get('media/(:num)', 'Media::serve/$1');
    $routes->get('media/(:num)/thumb', 'Media::thumb/$1');

    // Inspections (capture + CRUD)
    $routes->get('inspections', 'Inspections::index');
    $routes->get('inspections/new', 'Inspections::new');
    $routes->post('inspections', 'Inspections::create');
    $routes->get('inspections/(:segment)/capture', 'Inspections::capture/$1');
    $routes->post('inspections/(:segment)', 'Inspections::update/$1');
    $routes->post('inspections/(:segment)/photos', 'Inspections::uploadPhoto/$1');
    $routes->post('inspections/(:segment)/ai-conclusion', 'Inspections::aiConclusion/$1');
    $routes->post('photos/(:num)/caption', 'Inspections::captionPhoto/$1');
    $routes->post('photos/(:num)/ai-analyze', 'Inspections::aiAnalyzePhoto/$1');
    $routes->post('photos/(:num)/annotate', 'Inspections::annotatePhoto/$1');
    $routes->post('photos/(:num)/delete', 'Inspections::deletePhoto/$1');
    $routes->post('inspections/(:segment)/complete', 'Inspections::complete/$1');
    $routes->post('inspections/(:segment)/client', 'Inspections::setClient/$1');
    $routes->post('inspections/(:segment)/delete', 'Inspections::delete/$1');

    // Report view + PDF
    $routes->get('reports/(:segment)', 'Reports::view/$1');
    $routes->get('reports/(:segment)/pdf', 'Reports::pdf/$1');
    $routes->post('reports/(:segment)/send', 'Reports::send/$1');
    $routes->post('reports/(:segment)/ai-insights', 'Reports::aiInsights/$1');

    // Clients
    $routes->get('clients', 'Clients::index');
    $routes->get('clients/new', 'Clients::new');
    $routes->post('clients', 'Clients::create');
    $routes->get('clients/(:segment)', 'Clients::show/$1');
    $routes->get('clients/(:segment)/edit', 'Clients::edit/$1');
    $routes->post('clients/(:segment)', 'Clients::update/$1');
    $routes->get('clients/(:segment)/statement', 'Clients::statementPdf/$1');
    $routes->post('clients/(:segment)/statement', 'Clients::statementSend/$1');

    // Invoices & payments (feature: billing). Fixed paths before the (:segment) catch-alls.
    $routes->get('invoices/settings', 'Invoices::settings', ['filter' => 'group:admin']);
    $routes->post('invoices/settings', 'Invoices::saveSettings', ['filter' => 'group:admin']);
    $routes->post('invoices/statements/run', 'Invoices::runStatements', ['filter' => 'group:admin']);
    $routes->get('invoices', 'Invoices::index');
    $routes->get('invoices/new', 'Invoices::new');
    $routes->post('invoices', 'Invoices::create');
    $routes->get('invoices/payments', 'Invoices::payments');
    $routes->post('invoices/payments', 'Invoices::recordPayment');
    $routes->post('invoices/from-inspection/(:segment)', 'Invoices::fromInspection/$1');
    $routes->get('invoices/(:segment)', 'Invoices::show/$1');
    $routes->get('invoices/(:segment)/edit', 'Invoices::edit/$1');
    $routes->post('invoices/(:segment)', 'Invoices::update/$1');
    $routes->get('invoices/(:segment)/pdf', 'Invoices::pdf/$1');
    $routes->post('invoices/(:segment)/send', 'Invoices::send/$1');
    $routes->post('invoices/(:segment)/issue', 'Invoices::issue/$1');
    $routes->post('invoices/(:segment)/void', 'Invoices::void/$1');

    // Vendors & purchase orders (feature: purchasing). Fixed paths before (:segment).
    $routes->get('vendors', 'Vendors::index');
    $routes->get('vendors/new', 'Vendors::new');
    $routes->post('vendors', 'Vendors::create');
    $routes->get('vendors/(:segment)', 'Vendors::show/$1');
    $routes->get('vendors/(:segment)/edit', 'Vendors::edit/$1');
    $routes->post('vendors/(:segment)', 'Vendors::update/$1');
    $routes->get('items', 'CatalogItems::index');
    $routes->get('items/suggest', 'CatalogItems::suggest');
    $routes->get('items/new', 'CatalogItems::new');
    $routes->post('items', 'CatalogItems::create');
    $routes->get('items/(:num)/edit', 'CatalogItems::edit/$1');
    $routes->post('items/(:num)', 'CatalogItems::update/$1');
    $routes->post('items/(:num)/delete', 'CatalogItems::delete/$1');
    $routes->get('purchase-orders', 'PurchaseOrders::index');
    $routes->get('purchase-orders/new', 'PurchaseOrders::new');
    $routes->get('purchase-orders/summary', 'PurchaseOrders::summary');
    $routes->post('purchase-orders', 'PurchaseOrders::create');
    $routes->get('purchase-orders/(:segment)', 'PurchaseOrders::show/$1');
    $routes->get('purchase-orders/(:segment)/edit', 'PurchaseOrders::edit/$1');
    $routes->post('purchase-orders/(:segment)', 'PurchaseOrders::update/$1');
    $routes->get('purchase-orders/(:segment)/pdf', 'PurchaseOrders::pdf/$1');
    $routes->post('purchase-orders/(:segment)/send', 'PurchaseOrders::send/$1');
    $routes->post('purchase-orders/(:segment)/status', 'PurchaseOrders::status/$1');
    $routes->post('purchase-orders/(:segment)/clear-prices', 'PurchaseOrders::clearPrices/$1');
    $routes->post('purchase-orders/(:segment)/toggle/(:segment)', 'PurchaseOrders::toggle/$1/$2');

    // VIN History Checks (feature: vinCheck)
    $routes->get('vin-checks', 'VinChecks::index');
    $routes->get('vin-checks/new', 'VinChecks::new');
    $routes->post('vin-checks', 'VinChecks::create');
    $routes->get('vin-checks/(:segment)', 'VinChecks::view/$1');
    $routes->get('vin-checks/(:segment)/pdf', 'VinChecks::pdf/$1');
    $routes->post('vin-checks/(:segment)/send', 'VinChecks::send/$1');
    $routes->post('vin-checks/(:segment)/delete', 'VinChecks::delete/$1');

    // Vehicles
    $routes->get('vehicles', 'Vehicles::index');
    $routes->get('vehicles/scan', 'Vehicles::scan');
    $routes->post('vehicles/parse-disc', 'Vehicles::parseDisc');
    $routes->post('vehicles', 'Vehicles::create');
    $routes->get('vehicles/(:num)/profile', 'Vehicles::profile/$1');
    $routes->post('vehicles/(:num)/ai-fill', 'Vehicles::aiFill/$1');
    $routes->post('vehicles/(:num)/vin-decode', 'Vehicles::vinDecode/$1');
    $routes->post('vehicles/(:num)/image', 'Vehicles::setImage/$1');
    $routes->post('vehicles/(:num)', 'Vehicles::update/$1');
    $routes->post('vehicles/(:num)/delete', 'Vehicles::delete/$1');

    // ---------------- Admin-only: template builder + users ----------------
    $routes->group('', ['filter' => 'group:admin'], static function ($routes): void {
        $routes->get('templates', 'Templates::index');
        $routes->get('templates/(:num)/builder', 'Templates::builder/$1');
        $routes->post('templates', 'Templates::create');
        $routes->post('templates/(:num)/duplicate', 'Templates::duplicate/$1');
        $routes->post('templates/(:num)', 'Templates::update/$1');
        $routes->post('templates/(:num)/delete', 'Templates::delete/$1');
        $routes->post('templates/(:num)/sections', 'Templates::addSection/$1');
        $routes->post('sections/(:num)', 'Templates::updateSection/$1');
        $routes->post('sections/(:num)/delete', 'Templates::deleteSection/$1');
        $routes->post('sections/(:num)/fields', 'Templates::addField/$1');
        $routes->post('fields/(:num)', 'Templates::updateField/$1');
        $routes->post('fields/(:num)/delete', 'Templates::deleteField/$1');
        $routes->post('templates/reorder', 'Templates::reorder');

        $routes->get('users', 'Users::index');
        $routes->post('users', 'Users::create');
        $routes->post('users/(:num)/role', 'Users::setRole/$1');
        $routes->post('users/(:num)/delete', 'Users::delete/$1');

        $routes->get('settings', 'Settings::index');
        $routes->post('settings', 'Settings::save');
        $routes->post('settings/test-email', 'Settings::testEmail');

        $routes->get('analytics', 'Analytics::index');

        $routes->post('clients/(:segment)/delete', 'Clients::delete/$1');
        $routes->post('invoices/(:segment)/delete', 'Invoices::delete/$1');
        $routes->post('payments/(:num)/delete', 'Invoices::deletePayment/$1');
        $routes->post('vendors/(:segment)/delete', 'Vendors::delete/$1');
        $routes->post('purchase-orders/(:segment)/delete', 'PurchaseOrders::delete/$1');
    });
});

// ---------------------------------------------------------------------
// REST API v1 — token auth (future React / Flutter client)
// ---------------------------------------------------------------------
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'tokens'], static function ($routes): void {
    $routes->get('me', 'Auth::me');
    $routes->resource('vehicles', ['controller' => 'Vehicles']);
    $routes->get('templates', 'Templates::index');
    $routes->get('templates/(:num)', 'Templates::show/$1');
    $routes->resource('inspections', ['controller' => 'Inspections', 'except' => 'new,edit']);
    $routes->get('inspections/(:segment)/report', 'Inspections::report/$1');
});

// Public API token issue (login with email/password → access token)
$routes->post('api/v1/auth/token', 'Api\V1\Auth::token', ['filter' => 'auth-rates']);
