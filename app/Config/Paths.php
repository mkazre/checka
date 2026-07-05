<?php

namespace Config;

/**
 * Holds the paths that are used by the system to
 * locate the main directories, app, system, etc.
 * Modifying these allows you to restructure your application,
 * share a system folder between multiple applications, and more.
 */
class Paths
{
    /**
     * The path to the application directory.
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * The path to the system directory (pulled in by Composer).
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * The path to the writable directory.
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * The path to the tests directory.
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * The path to the views directory.
     */
    public string $viewDirectory = __DIR__ . '/../Views';
}
