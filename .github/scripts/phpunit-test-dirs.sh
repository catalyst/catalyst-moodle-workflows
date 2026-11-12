#!/usr/bin/env bash
# Usage: phpunit-test-dirs.sh <plugin-dir>
# Prints the tests dirs of the plugin and its subplugins; Moodle's generated phpunit.xml only covers the main plugin's testsuite.
set -euo pipefail

find "$1" -mindepth 1 -name version.php -not -path '*/vendor/*' -not -path '*/node_modules/*' -not -path '*/tests/*' -printf '%h/tests\n' | sort | while read -r dir; do
  if [ -d "$dir" ]; then echo "$dir"; fi
done
