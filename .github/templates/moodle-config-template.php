<?php
// Moodle configuration generated from template during snapshot creation.
// Only {{DBTYPE}} is substituted at runtime (pgsql or mariadb).
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = '{{DBTYPE}}';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = '{{DBTYPE}}' === 'pgsql' ? 'postgres' : 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist'   => 0,
    'dbport'      => '',
    'dbsocket'    => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

// config.php lives at moodle/config.php; dataroot is a sibling of moodle/.
$CFG->wwwroot  = 'http://localhost';
$CFG->dataroot = dirname(__DIR__) . '/moodledata';
$CFG->admin    = 'admin';
$CFG->directorypermissions = 02777;

$CFG->phpunit_prefix   = 'phpu_';
$CFG->phpunit_dataroot = dirname(__DIR__) . '/moodledata/phpunit';

// Support both classic layout (lib/setup.php) and public/ layout (public/lib/setup.php).
if (file_exists(__DIR__ . '/public/lib/setup.php')) {
    require_once(__DIR__ . '/public/lib/setup.php');
} else {
    require_once(__DIR__ . '/lib/setup.php');
}
