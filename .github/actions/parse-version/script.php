<?php

require __DIR__ . '../../../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

/**
 * Outputs a value and binds it to a name provided.
 *
 * Note that arrays/objects should be JSON encoded, and read via the fromJson
 * method as described here:
 * https://docs.github.com/en/actions/learn-github-actions/expressions#fromjson
 *
 * @param     string $name name of the output
 * @param     string $value value of the output - preferably JSON encoded if its an object/array
 * @author    Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright Catalyst IT, 2022
 */
function output(string $name, string $value) {
    echo PHP_EOL;
    $outputfile = getenv('GITHUB_OUTPUT');
    if (false !== $outputfile) {
        file_put_contents($outputfile, "{$name}={$value}\n", FILE_APPEND);
        echo PHP_EOL;
    }
    echo "Setting output.. $name = $value";
    echo PHP_EOL;
}

// Greet the user.
echo "Hello from PHP!";
$workspace = $_SERVER['GITHUB_WORKSPACE'] ?? '';

// This check is only possible with the version.php file, so end early if it doesn't exist.
$versionFilePath = "$workspace/plugin/version.php";
if (!file_exists($versionFilePath)) {
    echo "version.php does not exist";
    exit(1);
}

// Some moodle constants to prevent errors when trying to require the version.php file.
define('MOODLE_INTERNAL', 1);
/** Software maturity level - internals can be tested using white box techniques. */
define('MATURITY_ALPHA',    50);
/** Software maturity level - feature complete, ready for preview and testing. */
define('MATURITY_BETA',     100);
/** Software maturity level - tested, will be released unless there are fatal bugs. */
define('MATURITY_RC',       150);
/** Software maturity level - ready for production deployment. */
define('MATURITY_STABLE',   200);
/** Any version - special value that can be used in $plugin->dependencies in version.php files. */
define('ANY_VERSION', 'any');

$plugin = new \stdClass();
require_once($versionFilePath);

// All supported matrix includes:
$matrixYaml = file_get_contents("$workspace/ci/.github/actions/matrix/matrix_includes.yml");
$matrix = Yaml::parse($matrixYaml);

// Version breakpoints are sourced from:
// https://download.moodle.org/api/1.3/updates.php?format=json&version=0.0&branch=$lowestSupportedBranch
$updates = json_decode(file_get_contents('https://download.moodle.org/api/1.3/updates.php?format=json&version=0.0&branch=3.8'), true);
$updates = $updates['updates']['core'] ?? [];

$preparedMatrix = array_filter($matrix['include'], function($entry) use($plugin, $updates, $matrix) {

    if (!isset($entry)) {
        return false;
    }

    // Regex and replacement templates - Partially generated from https://regex101.com/
    $re = '/MOODLE_(.*)_STABLE/m';
    $subst = '$1';
    $coreVersion = preg_replace(
        $re,
        $subst,
        $entry['moodle-branch'],
    );
    $disable_main = !empty($_SERVER['disable_master']) || !empty($_SERVER['disable_main']);

    // Check that the php version for the workflow is higher than the minimum set by the option.
    if (isset($_SERVER['min_php']) && $_SERVER['min_php'] > $entry['php']) {
        return false;
    }

    // Use the 'moodle_branches' supplied in the 'with:' options to specify the branches of Moodle that would be included in the test.
    // Example: MOODLE_35_STABLE MOODLE_36_STABLE
    // Note: They are space separated.
    if (!empty($_SERVER['filter'])) {
        $filter = $_SERVER['filter'];
        $filter = preg_split('/\s+/', $filter);
        // Otherwise if not defined, it should check if the 'filter' variable has been provided, and use the matching versions there instead.
        if (in_array($entry['moodle-branch'], $filter)) {
            return true;
        }

        // If they are NOT specified, do NOT run those branches in tests.
        return false;
    }

    // Determine whether or not to include the main/dev branch.
    if ($entry['moodle-branch'] === 'main' && $disable_main) {
        return false;
    }

    // If a fixed support range is set, use this. This dynamically includes the necessary branches based on the range provided.
    if (!empty($plugin->supported)) {
        [$lower, $upper] = $plugin->supported;
        // If within permitted ranges, e.g. 36 is between [35, 39], include it.
        if ($lower <= $coreVersion && $coreVersion <= $upper) {
            return true;
        }

        // If this iteration is on main, check if the upper supported value implies main should be tested or not.
        // e.g. for [35, 500], 500 doesn't exist as a included matrix entry, so treat it like 'main'.
        if ($entry['moodle-branch'] === 'main') {
            // If the upper range does NOT exist in the matrix, then assume the user wants the main to be tested.
            $exists = false;
            foreach ($matrix['include'] as $row) {
                if (!isset($row)) continue; // Skip nulls.
                $currentVersion = preg_replace(
                    $re,
                    $subst,
                    $row['moodle-branch'],
                ); // e.g. 39, 310, 311, 400, etc.
                if ($currentVersion == $upper) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return true;
            }
        }

        // For everything else, exclude it, since $plugin->supported implies a closed range.
        return false;
    }

    // If that hasn't been provided either, it should fallback and do a
    // requirement check on the version itself. Noting that this is probably the
    // worst option as it may need to go all the way down to version 3.3 to
    // support Totara and is probably wrong, as it also assumes the range is
    // open e.g. 35+
    if (!empty($plugin->requires)) {
        // Notes:
        // - $plugin->requires = lowest version supported
        // - Use $plugin->requires to ensure the version checked (matching based
        // on regex) is higher than the lowest supported, if so, include it in
        // the matrix.
        foreach ($updates as $apiVersion) {
            $branchValue = str_replace('.', '', $apiVersion['branch']);
            $branch = "MOODLE_{$branchValue}_STABLE";
            if ($entry['moodle-branch'] === $branch && $plugin->requires <= $apiVersion['version']) {
                return true;
            }
        }
    }

    return false;
});

$jsonMatrix = json_encode(['include' => array_values($preparedMatrix)], JSON_UNESCAPED_SLASHES);
output('matrix', $jsonMatrix);

// Output the component / plugin name (which would be useful e.g. for a release)
output('component', $plugin->component);

// Output the highest available moodle branch in this set, which will be used to
// determine whether or not various tests/tasks will run, such as grunt.
output('highest_moodle_branch', reset($preparedMatrix)['moodle-branch'] ?? '');
