<?php
// Moodle configuration generated from template during snapshot creation.
// Only {{DBTYPE}} is substituted at runtime (pgsql, mariadb, or mssql).
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = '{{DBTYPE}}' === 'mssql' ? 'sqlsrv' : '{{DBTYPE}}';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = '{{DBTYPE}}' === 'pgsql' ? 'postgres' : ('{{DBTYPE}}' === 'mssql' ? 'sa' : 'root');
// This credential is used only by the ephemeral, localhost-only SQL Server CI container.
$CFG->dbpass    = '{{DBTYPE}}' === 'mssql' ? 'MoodleMSSQL-CI-2026!' : '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist'   => 0,
    'dbport'      => '{{DBTYPE}}' === 'mssql' ? 1433 : '',
    'dbsocket'    => '',
    'dbcollation' => '{{DBTYPE}}' === 'mssql' ? 'Latin1_General_CI_AI' : 'utf8mb4_unicode_ci',
];
if ('{{DBTYPE}}' === 'mssql') {
    // The Microsoft container uses a self-signed certificate that is safe to trust in this isolated job.
    $CFG->dboptions['extrainfo'] = [
        'TrustServerCertificate' => true,
    ];
}

// config.php lives at moodle/config.php; dataroot is a sibling of moodle/.
$CFG->wwwroot  = 'http://localhost';
$CFG->dataroot = dirname(__DIR__) . '/moodledata';
$CFG->admin    = 'admin';
$CFG->directorypermissions = 02777;

$CFG->phpunit_prefix   = 'phpu_';
$CFG->phpunit_dataroot = dirname(__DIR__) . '/moodledata/phpunit';

$CFG->behat_prefix   = 'bht_';
$CFG->behat_dataroot = dirname(__DIR__) . '/moodledata/behat';
$CFG->behat_wwwroot  = 'http://localhost:8000';

// Selenium runs with --network host, so it reaches behat_wwwroot via localhost too.
$CFG->behat_profiles = [
    'chrome' => [
        'browser' => 'chrome',
        'wd_host' => 'http://localhost:4444/wd/hub',
        'capabilities' => [
            'extra_capabilities' => [
                'goog:chromeOptions' => [
                    'args' => ['--headless=new', '--disable-gpu', '--window-size=1024,768'],
                ],
            ],
        ],
    ],
];

// Support both classic layout (lib/setup.php) and public/ layout (public/lib/setup.php).
if (file_exists(__DIR__ . '/public/lib/setup.php')) {
    require_once(__DIR__ . '/public/lib/setup.php');
} else {
    require_once(__DIR__ . '/lib/setup.php');
}
