<?php
// Usage: php subplugin-test-dirs.php <plugin-dir>
// Prints the tests dir (relative to <plugin-dir>) of each subplugin listed in db/subplugins.json that contains *_test.php files.
// Moodle's generated phpunit.xml only covers the main plugin, so subplugin tests must be run separately.

if (!isset($argv[1])) {
    fwrite(STDERR, "Usage: php subplugin-test-dirs.php <plugin-dir>\n");
    exit(1);
}
$plugin = rtrim($argv[1], '/');
if (!is_file("$plugin/db/subplugins.json")) {
    exit(0);
}
$json = json_decode((string) file_get_contents("$plugin/db/subplugins.json"), true);
if (!is_array($json)) {
    fwrite(STDERR, "db/subplugins.json is not valid JSON.\n");
    exit(1);
}

// subplugintypes paths are relative to the plugin.
$typeDirs = is_array($json['subplugintypes'] ?? null) ? array_values($json['subplugintypes']) : [];
$testDirs = [];
foreach ($typeDirs as $typeDir) {
    foreach (glob("$plugin/" . trim((string) $typeDir, '/') . '/*', GLOB_ONLYDIR) ?: [] as $subDir) {
        $testsDir = "$subDir/tests";
        if (is_file("$subDir/version.php") && hasTestFiles($testsDir)) {
            $testDirs[] = substr($testsDir, strlen($plugin) + 1);
        }
    }
}

sort($testDirs);
foreach ($testDirs as $testDir) {
    echo $testDir, "\n";
}

/** Whether a dir contains any *_test.php file, searched recursively. */
function hasTestFiles(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (substr($file->getFilename(), -strlen('_test.php')) === '_test.php') {
            return true;
        }
    }
    return false;
}
