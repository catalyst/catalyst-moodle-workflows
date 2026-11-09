<?php
// Usage: php version-policy.php <repo-dir> <base-ref> [--branch-pattern=<PCRE>]
// Prints "latest" if base-ref is the highest branch in its group, "frozen" if not, or "skip" if it does not match.
// The pattern needs a (?<version>...) group: first digit is the major, the rest the minor (401 = 4.1, 310 = 3.10).
// An optional (?<group>...) group limits the comparison to branches with the same group.

$pattern = '/^MOODLE_(?<version>[0-9]+)_STABLE$/';
$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--branch-pattern=')) {
        $pattern = substr($arg, strlen('--branch-pattern='));
    } elseif (str_starts_with($arg, '--')) {
        fail("Unknown option: $arg");
    } else {
        $args[] = $arg;
    }
}
if (count($args) !== 2) {
    fail('Usage: php version-policy.php <repo-dir> <base-ref> [--branch-pattern=<PCRE>]');
}
[$repoDir, $baseRef] = $args;

if (@preg_match($pattern, '') === false) {
    fail("Invalid --branch-pattern: $pattern");
}
if (!preg_match('/\(\?P?<version>/', $pattern)) {
    fail('--branch-pattern must contain a (?<version>...) group.');
}

$base = branchKey($baseRef, $pattern);
if ($base === null) {
    fwrite(STDERR, "Base $baseRef does not match the branch pattern, skipping version policy.\n");
    echo "skip\n";
    exit(0);
}

exec(sprintf('git -C %s ls-remote --heads origin', escapeshellarg($repoDir)), $lines, $code);
if ($code !== 0) {
    fail("Could not list remote branches in $repoDir.");
}

$highest = null;
foreach ($lines as $line) {
    // Lines look like "<sha>\trefs/heads/<branch>".
    if (!preg_match('#\trefs/heads/(.+)$#', $line, $m)) {
        continue;
    }
    $key = branchKey($m[1], $pattern);
    if ($key === null || $key['group'] !== $base['group']) {
        continue;
    }
    if ($highest === null || compareKeys($key, $highest) > 0) {
        $highest = $key;
    }
}
if ($highest === null) {
    fail('No remote branches match the branch pattern.');
}

$policy = $base['branch'] === $highest['branch'] ? 'latest' : 'frozen';
fwrite(STDERR, "Highest branch: {$highest['branch']}, PR base: $baseRef, policy: $policy\n");
echo "$policy\n";

/** Parses a branch name into its group and major/minor version, or null if it does not match. */
function branchKey(string $branch, string $pattern): ?array
{
    if (!preg_match($pattern, $branch, $m) || ($m['version'] ?? '') === '') {
        return null;
    }
    $digits = $m['version'];
    return [
        'branch' => $branch,
        'group' => $m['group'] ?? '',
        'major' => (int) $digits[0],
        'minor' => (int) substr($digits, 1),
    ];
}

/** Orders two parsed branches by major then minor. */
function compareKeys(array $a, array $b): int
{
    return [$a['major'], $a['minor']] <=> [$b['major'], $b['minor']];
}

/** Writes an error message to STDERR and exits with status 1. */
function fail(string $message): void
{
    fwrite(STDERR, "$message\n");
    exit(1);
}
