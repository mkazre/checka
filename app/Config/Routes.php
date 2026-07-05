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

// ---------------------------------------------------------------------
// Dashboard / app — requires a logged-in session
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function ($routes): void {
    $routes->get('dashboard', 'Dashboard::index');

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
    $routes->post('photos/(:num)/delete', 'Inspections::deletePhoto/$1');
    $routes->post('inspections/(:segment)/complete', 'Inspections::complete/$1');
    $routes->post('inspections/(:segment)/delete', 'Inspections::delete/$1');

    // Report view + PDF
    $routes->get('reports/(:segment)', 'Reports::view/$1');
    $routes->get('reports/(:segment)/pdf', 'Reports::pdf/$1');

    // Vehicles
    $routes->get('vehicles', 'Vehicles::index');
    $routes->get('vehicles/scan', 'Vehicles::scan');
    $routes->post('vehicles/parse-disc', 'Vehicles::parseDisc');
    $routes->post('vehicles', 'Vehicles::create');
    $routes->get('vehicles/(:num)/profile', 'Vehicles::profile/$1');
    $routes->post('vehicles/(:num)', 'Vehicles::update/$1');
    $routes->post('vehicles/(:num)/delete', 'Vehicles::delete/$1');

    // ---------------- Admin-only: template builder + users ----------------
    $routes->group('', ['filter' => 'group:admin'], static function ($routes): void {
        $routes->get('templates', 'Templates::index');
        $routes->get('templates/(:num)/builder', 'Templates::builder/$1');
        $routes->post('templates', 'Templates::create');
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
