<?php

// This is fixed only because the SQL Server service and Moodle config both need
// the same credential inside the same short-lived GitHub Actions job.
const MSSQL_PASSWORD = 'MoodleMSSQL-CI-2026!';

if ($argc !== 1) {
    fwrite(STDERR, "Usage: php create-mssql-moodle-database.php\n");
    exit(1);
}

if (!extension_loaded('sqlsrv')) {
    fwrite(STDERR, "The sqlsrv extension is not loaded.\n");
    exit(1);
}

$connection = false;
for ($attempt = 1; $attempt <= 30; $attempt++) {
    // Microsoft PHP drivers require certificate trust to be explicit for the
    // self-signed certificate used by the disposable SQL Server container.
    $connection = sqlsrv_connect('127.0.0.1,1433', [
        'UID' => 'sa',
        'PWD' => MSSQL_PASSWORD,
        'Database' => 'master',
        'TrustServerCertificate' => true,
    ]);
    if ($connection !== false) {
        break;
    }
    sleep(2);
}

if ($connection === false) {
    fwrite(STDERR, "Unable to connect to SQL Server after 60 seconds:\n" . print_r(sqlsrv_errors(), true));
    exit(1);
}

$statements = [
    "IF DB_ID('moodle') IS NOT NULL
       BEGIN
           ALTER DATABASE [moodle] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
           DROP DATABASE [moodle];
       END",
    "CREATE DATABASE [moodle] COLLATE Latin1_General_CI_AI",
    "ALTER DATABASE [moodle] SET READ_COMMITTED_SNAPSHOT ON",
];

foreach ($statements as $statement) {
    if (sqlsrv_query($connection, $statement) === false) {
        fwrite(STDERR, "SQL Server setup failed:\n" . print_r(sqlsrv_errors(), true));
        sqlsrv_close($connection);
        exit(1);
    }
}

sqlsrv_close($connection);
