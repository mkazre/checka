<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * Checka roles.
 *
 * Two groups only:
 *  - admin    : manages templates, users, and every inspection.
 *  - mechanic : captures and edits inspections.
 */
class AuthGroups extends ShieldAuthGroups
{
    /**
     * The group a newly-created user is assigned to.
     */
    public string $defaultGroup = 'mechanic';

    /**
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Administrator',
            'description' => 'Full access: manage inspection templates, users and all inspections.',
        ],
        'mechanic' => [
            'title'       => 'Mechanic',
            'description' => 'Captures inspections, uploads photos, and generates reports.',
        ],
    ];

    /**
     * @var array<string, string>
     */
    public array $permissions = [
        'admin.access'        => 'Can access the admin area (templates, users)',
        'templates.manage'    => 'Can create, edit and delete inspection templates',
        'users.manage'        => 'Can create and manage user accounts',
        'inspections.manage'  => 'Can view, edit and delete any inspection',
        'inspections.capture' => 'Can create and edit inspections',
        'reports.view'        => 'Can view and download reports',
    ];

    /**
     * Which groups have which permissions.
     *
     * @var array<string, list<string>>
     */
    public array $matrix = [
        'admin' => [
            'admin.*',
            'templates.*',
            'users.*',
            'inspections.*',
            'reports.*',
        ],
        'mechanic' => [
            'inspections.capture',
            'reports.view',
        ],
    ];
}
