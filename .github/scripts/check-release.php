<?php
// Usage: php check-release.php <plugin-dir> [--release-policy=none|match_version]
// Enforces the Catalyst $plugin->release policy on a plugin's version.php.

$releasePolicy = 'none';
$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--release-policy=')) {
        $releasePolicy = substr($arg, strlen('--release-policy='));
    } elseif (str_starts_with($arg, '--')) {
        fail("Unknown option: $arg");
    } else {
        $args[] = $arg;
    }
}
if (!in_array($releasePolicy, ['none', 'match_version'], true)) {
    fail("Invalid --release-policy \"$releasePolicy\", expected none or match_version.");
}
if (!isset($args[0])) {
    fail('Usage: php check-release.php <plugin-dir> [--release-policy=none|match_version]');
}
if ($releasePolicy === 'none') {
    exit(0);
}

$versionFile = rtrim($args[0], '/') . '/version.php';
if (is_link($versionFile) || !is_file($versionFile)) {
    fail("$versionFile not found.");
}
// Parsed rather than included so PR code is never executed.
$contents = (string) file_get_contents($versionFile);
$version = assignedValue($contents, 'version');
$release = assignedValue($contents, 'release');

if ($version === null) {
    fail("$versionFile: \$plugin->version not found.");
}
if ($release === null) {
    fail("$versionFile: \$plugin->release is missing or not a literal value.");
}
// Loose comparison so numerically equal values such as '2024010100' and 2024010100.00 match.
if ($release != $version) {
    fail("$versionFile: \$plugin->release ($release) must match \$plugin->version ($version).");
}
echo "$versionFile: release matches version ($version).\n";

/** The literal value assigned to $plugin->$property (or $module->$property), or null if not found. */
function assignedValue(string $contents, string $property): ?string
{
    $contents = implode('', array_map(
        static fn($token): string => is_array($token)
            ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1])
            : $token,
        token_get_all($contents)
    ));
    $pattern = '/\$(?:plugin|module)->' . $property . '\s*=\s*(?:\'([^\']*)\'|"([^"]*)"|([0-9.]+))\s*;/';
    if (!preg_match($pattern, $contents, $m, PREG_UNMATCHED_AS_NULL)) {
        return null;
    }
    return $m[3] ?? $m[2] ?? $m[1];
}

/** Writes an error message to STDERR and exits with status 1. */
function fail(string $message): void
{
    fwrite(STDERR, "$message\n");
    exit(1);
}
