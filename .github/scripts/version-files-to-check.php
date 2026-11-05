<?php
// Usage: php version-files-to-check.php <repo-dir> [<base-dir>]
// With a base dir, lists changed plugin dirs, plus the root plugin if a subplugin changed or was removed.

$repo = rtrim($argv[1], '/');
$baseRepo = isset($argv[2]) ? rtrim($argv[2], '/') : null;

$pluginDirs = findPluginDirs($repo);
if ($pluginDirs === []) {
    fail('No version.php found in the plugin or its subplugins.');
}

if ($baseRepo !== null) {
    $headDirs = $pluginDirs;
    $pluginDirs = array_values(array_filter(
        $headDirs,
        fn(string $dir): bool => hasChanged($repo, $baseRepo, $dir, $headDirs)
    ));
    $pluginDirs = addRootForSubplugins($repo, $baseRepo, $headDirs, $pluginDirs);
}

if ($pluginDirs !== []) {
    echo implode("\n", $pluginDirs), "\n";
}

/** The root plugin ('.') and its subplugin dirs, relative to $repo, sorted and unique. */
function findPluginDirs(string $repo): array
{
    assertNotSymlink("$repo/version.php");
    assertNotSymlink("$repo/db/subplugins.json");
    $dirs = is_file("$repo/version.php") ? ['.'] : [];
    if (is_file("$repo/db/subplugins.json")) {
        $dirs = array_merge($dirs, subpluginDirs($repo));
    }
    $dirs = array_values(array_unique($dirs));
    sort($dirs);
    return $dirs;
}

/** Plugin dirs listed under db/subplugins.json, relative to $repo. */
function subpluginDirs(string $repo): array
{
    $json = json_decode((string) file_get_contents("$repo/db/subplugins.json"), true);
    if (!is_array($json)) {
        fail('db/subplugins.json is not valid JSON.');
    }
    $headGitlinks = gitlinks($repo);
    $pluginName = pluginName($repo);

    // subplugintypes paths are relative to the plugin, plugintypes paths to the Moodle root.
    $typeDirs = array_values($json['subplugintypes'] ?? []);
    foreach ($json['plugintypes'] ?? [] as $moodlePath) {
        $typeDirs[] = pluginRelativePath((string) $moodlePath, $pluginName);
    }

    $dirs = [];
    foreach ($typeDirs as $typeDir) {
        $relTypeDir = trim($typeDir, '/');
        $absTypeDir = "$repo/$relTypeDir";
        if (!is_dir($absTypeDir)) {
            continue;
        }
        if (!resolvesInside($repo, $absTypeDir)) {
            fail("Subplugin path \"$typeDir\" resolves outside the plugin.");
        }
        foreach (scandir($absTypeDir) as $name) {
            if ($name[0] === '.') {
                continue;
            }
            $relDir = "$relTypeDir/$name";
            assertNotSymlink("$absTypeDir/$name");
            assertNotSymlink("$absTypeDir/$name/version.php");
            // Unpopulated submodules are empty dirs, so a tracked gitlink also marks a plugin dir.
            if (is_file("$absTypeDir/$name/version.php") || isset($headGitlinks[$relDir])) {
                $dirs[] = $relDir;
            }
        }
    }
    return $dirs;
}

/** Whether a plugin dir is new in the base checkout or differs from it in files or submodule SHAs. */
function hasChanged(string $repo, string $baseRepo, string $dir, array $pluginDirs): bool
{
    $baseVersion = $dir === '.' ? "$baseRepo/version.php" : "$baseRepo/$dir/version.php";
    // An unpopulated base submodule has no version.php, so its gitlink is what marks it as existing.
    if (!is_file($baseVersion) && !isset(gitlinks($baseRepo)[$dir])) {
        return true;
    }
    // Nested plugin dirs are compared on their own, so they are left out of this dir's fingerprint.
    $nestedDirs = array_filter($pluginDirs, fn(string $other): bool => $other !== $dir && isInside($other, $dir));
    return fingerprint($repo, $dir, $nestedDirs) !== fingerprint($baseRepo, $dir, $nestedDirs);
}

/** Adds the root plugin ('.') when a subplugin changed or was removed, since it must bump too. */
function addRootForSubplugins(string $repo, string $baseRepo, array $headDirs, array $changedDirs): array
{
    $subplugin = fn(string $dir): bool => $dir !== '.';
    $changedSubplugin = array_filter($changedDirs, $subplugin) !== [];
    $removedSubplugin = array_diff(array_filter(findPluginDirs($baseRepo), $subplugin), $headDirs) !== [];
    if (!$changedSubplugin && !$removedSubplugin) {
        return $changedDirs;
    }
    if (in_array('.', $changedDirs, true) || !is_file("$repo/version.php")) {
        return $changedDirs;
    }
    $reason = $changedSubplugin ? 'changed' : 'removed';
    fwrite(STDERR, "Subplugin $reason, adding main plugin to list\n");
    $changedDirs[] = '.';
    sort($changedDirs);
    return $changedDirs;
}

/** Whether a repo-relative path is inside the plugin dir $dir; '.' contains every path. */
function isInside(string $path, string $dir): bool
{
    return $dir === '.' || str_starts_with($path, "$dir/");
}

/**
 * Map of repo-relative path to md5 (files) or commit SHA (submodules) within a plugin dir,
 * skipping .git and nested plugin dirs.
 */
function fingerprint(string $repo, string $dir, array $nestedDirs): array
{
    $start = $dir === '.' ? $repo : "$repo/$dir";
    $prefix = $dir === '.' ? '' : "$dir/";
    $hashes = [];

    if (is_dir($start)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($start, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $prefix . substr($file->getPathname(), strlen($start) + 1);
            if ($path === '.git' || str_starts_with($path, '.git/') || isNested($path, $nestedDirs)) {
                continue;
            }
            if ($file->isLink()) {
                // Record link targets without following them.
                $hashes[$path] = 'symlink:' . readlink($file->getPathname());
            } elseif ($file->isFile()) {
                $hashes[$path] = md5_file($file->getPathname());
            }
        }
    }

    // Submodules are empty dirs in an unpopulated checkout, so their tracked SHA is the only signal.
    foreach (gitlinks($repo) as $path => $sha) {
        $inDir = $dir === '.' || $path === $dir || str_starts_with($path, $prefix);
        if ($inDir && !isNested($path, $nestedDirs)) {
            $hashes[$path] = $sha;
        }
    }

    ksort($hashes);
    return $hashes;
}

/** Whether a repo-relative path is one of the nested plugin dirs or inside one. */
function isNested(string $path, array $nestedDirs): bool
{
    foreach ($nestedDirs as $other) {
        if ($path === $other || str_starts_with($path, "$other/")) {
            return true;
        }
    }
    return false;
}

/** Map of submodule path to its tracked commit SHA at HEAD. */
function gitlinks(string $repo): array
{
    static $cache = [];
    if (!isset($cache[$repo])) {
        exec(sprintf('git -C %s ls-tree -r HEAD', escapeshellarg($repo)), $lines, $code);
        if ($code !== 0) {
            fail("Could not list submodules in $repo.");
        }
        $cache[$repo] = [];
        foreach ($lines as $line) {
            if (preg_match('/^160000 commit ([0-9a-f]+)\t(.+)$/', $line, $m)) {
                $cache[$repo][$m[2]] = $m[1];
            }
        }
    }
    return $cache[$repo];
}

/** The plugin name from $plugin->component in version.php, or null if not declared. */
function pluginName(string $repo): ?string
{
    $versionFile = "$repo/version.php";
    $pattern = '/\$(?:plugin|module)->component\s*=\s*[\'"]([a-z0-9_]+)[\'"]/';
    if (!is_file($versionFile) || !preg_match($pattern, (string) file_get_contents($versionFile), $m)) {
        return null;
    }
    $underscore = strpos($m[1], '_');
    return $underscore === false ? null : substr($m[1], $underscore + 1);
}

/** Maps a plugintypes path (relative to the Moodle root) to the matching dir inside this plugin. */
function pluginRelativePath(string $moodlePath, ?string $pluginName): string
{
    $segments = explode('/', trim($moodlePath, '/'));
    if ($pluginName !== null) {
        // The plugin's own Moodle install path ends in its name, so everything after it is in this repo.
        for ($i = count($segments) - 2; $i >= 0; $i--) {
            if ($segments[$i] === $pluginName) {
                return implode('/', array_slice($segments, $i + 1));
            }
        }
    }
    fail("Cannot map plugintypes path \"$moodlePath\" to this plugin.");
}

/** Fails if a path is a symlink, since it could point outside the checkout. */
function assertNotSymlink(string $path): void
{
    if (is_link($path)) {
        fail("$path must not be a symlink.");
    }
}

/** Whether a path resolves, following symlinks, to a location inside $repo. */
function resolvesInside(string $repo, string $path): bool
{
    $realRepo = realpath($repo);
    $realPath = realpath($path);
    return $realRepo !== false && $realPath !== false
        && ($realPath === $realRepo || str_starts_with($realPath, $realRepo . '/'));
}

/** Writes an error message to STDERR and exits with status 1. */
function fail(string $message): void
{
    fwrite(STDERR, "$message\n");
    exit(1);
}
