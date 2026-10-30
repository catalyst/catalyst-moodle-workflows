#!/usr/bin/env bash
# Usage: version-policy.sh <repo-dir> <base-ref>
# Prints "latest" when base-ref is the highest MOODLE_*_STABLE branch on origin, otherwise "frozen".
set -euo pipefail

repo_dir="${1:?repo dir required}"
base_ref="${2:?base ref required}"

highest=$(git -C "${repo_dir}" ls-remote --heads origin 'refs/heads/MOODLE_*_STABLE' \
  | sed -En 's#.*refs/heads/MOODLE_([0-9]+)_STABLE$#\1 MOODLE_\1_STABLE#p' \
  | sort -n | tail -n 1 | cut -d' ' -f2)

if [ -z "${highest}" ]; then
  echo "::error::No MOODLE_*_STABLE branches found in the repository." >&2
  exit 1
fi

if [ "${base_ref}" = "${highest}" ]; then
  policy=latest
else
  policy=frozen
fi

echo "Highest stable branch: ${highest}, PR base: ${base_ref}, policy: ${policy}" >&2
echo "${policy}"
