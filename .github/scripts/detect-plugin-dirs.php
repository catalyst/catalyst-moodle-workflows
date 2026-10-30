<?php
// Usage: php detect-plugin-dirs.php <repo-dir> [<base-dir>]
// With a base dir, only plugin dirs whose files or submodule SHAs changed (or are new) are listed.
$root = rtrim($argv[1], '/');
$baseRoot = isset($argv[2]) ? rtrim($argv[2], '/') : null;
$dirs = is_file("$root/version.php") ? ['.'] : [];
$subpluginsFile = "$root/db/subplugins.json";

if (is_file($subpluginsFile)) {
    $json = json_decode((string) file_get_contents($subpluginsFile), true);
    if (!is_array($json)) {
        fwrite(STDERR, "db/subplugins.json is not valid JSON.\n");
        exit(1);
    }
    $headGitlinks = gitlinks($root);
    $pluginName = pluginName($root);
    // subplugintypes paths are relative to the plugin, plugintypes paths to the Moodle root.
    $typeDirs = array_values($json['subplugintypes'] ?? []);
    foreach ($json['plugintypes'] ?? [] as $path) {
        $typeDirs[] = pluginRelativePath((string) $path, $pluginName);
    }

    foreach ($typeDirs as $path) {
        $relPath = trim($path, '/');
        $typeDir = "$root/$relPath";
        if (!is_dir($typeDir)) {
            continue;
        }
        foreach (scandir($typeDir) as $name) {
            // Unpopulated submodules are empty dirs, so a tracked gitlink also marks a plugin dir.
            $child = "$relPath/$name";
            if ($name[0] !== '.' && (is_file("$typeDir/$name/version.php") || isset($headGitlinks[$child]))) {
                $dirs[] = $child;
            }
        }
    }
}

$dirs = array_values(array_unique($dirs));
sort($dirs);

if ($dirs === []) {
    fwrite(STDERR, "No version.php found in the plugin or its subplugins.\n");
    exit(1);
}

if ($baseRoot !== null) {
    $dirs = array_values(array_filter($dirs, function (string $rel) use ($dirs, $root, $baseRoot): bool {
        $baseVersion = $rel === '.' ? "$baseRoot/version.php" : "$baseRoot/$rel/version.php";
        // An unpopulated base submodule has no version.php, so its gitlink is what marks it as existing.
        if (!is_file($baseVersion) && !isset(gitlinks($baseRoot)[$rel])) {
            return true;
        }
        // The root plugin excludes its subplugin dirs, which are checked separately.
        $nested = array_filter($dirs, fn(string $other): bool => $other !== $rel && ($rel === '.' || str_starts_with($other, "$rel/")));
        return fileHashes($root, $rel, $nested) !== fileHashes($baseRoot, $rel, $nested);
    }));
}

if ($dirs !== []) {
    echo implode("\n", $dirs), "\n";
}

/** Map of repo-relative path to md5 (files) or commit SHA (submodules) for a plugin dir, skipping .git and nested plugin dirs. */
function fileHashes(string $repo, string $rel, array $nested): array
{
    $start = $rel === '.' ? $repo : "$repo/$rel";
    $prefix = $rel === '.' ? '' : "$rel/";
    $hashes = [];
    if (is_dir($start)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($start, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $prefix . substr($file->getPathname(), strlen($start) + 1);
            if (!$file->isFile() || $path === '.git' || str_starts_with($path, '.git/') || isNested($path, $nested)) {
                continue;
            }
            $hashes[$path] = md5_file($file->getPathname());
        }
    }
    // Submodules are empty dirs in an unpopulated checkout, so their tracked SHA is the only signal.
    foreach (gitlinks($repo) as $path => $sha) {
        if (($rel === '.' || $path === $rel || str_starts_with($path, $prefix)) && !isNested($path, $nested)) {
            $hashes[$path] = $sha;
        }
    }
    ksort($hashes);
    return $hashes;
}

/** Whether a repo-relative path is one of the nested plugin dirs or inside one. */
function isNested(string $path, array $nested): bool
{
    foreach ($nested as $other) {
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
            fwrite(STDERR, "Could not list submodules in $repo.\n");
            exit(1);
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
function pluginName(string $root): ?string
{
    $versionFile = "$root/version.php";
    if (!is_file($versionFile) || !preg_match('/\$(?:plugin|module)->component\s*=\s*[\'"]([a-z0-9_]+)[\'"]/', (string) file_get_contents($versionFile), $m)) {
        return null;
    }
    $pos = strpos($m[1], '_');
    return $pos === false ? null : substr($m[1], $pos + 1);
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
    fwrite(STDERR, "Cannot map plugintypes path \"$moodlePath\" to this plugin.\n");
    exit(1);
}
