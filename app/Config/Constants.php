<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 | --------------------------------------------------------------------
 | Timing Constants
 | --------------------------------------------------------------------
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2592000);
defined('YEAR')   || define('YEAR', 31536000);
defined('DECADE') || define('DECADE', 315360000);

/*
 | --------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6);
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);

/*
 | --------------------------------------------------------------------
 | Checka — RAG rating system (single source of truth, mirrors the samples)
 | --------------------------------------------------------------------
 | key => [label, weight, colour, background]
 */
defined('CHECKA_RATINGS') || define('CHECKA_RATINGS', [
    'ok'     => ['label' => 'No Issues',     'weight' => 0,  'rank' => 0, 'colour' => '#15803d', 'bg' => '#e7f6ec'],
    'minor'  => ['label' => 'Minor Issues',  'weight' => 3,  'rank' => 1, 'colour' => '#1f4e79', 'bg' => '#e6eef6'],
    'lesser' => ['label' => 'Lesser Issues', 'weight' => 8,  'rank' => 2, 'colour' => '#b45309', 'bg' => '#fbeee0'],
    'major'  => ['label' => 'Major Issues',  'weight' => 25, 'rank' => 3, 'colour' => '#b42318', 'bg' => '#fbeae8'],
]);
