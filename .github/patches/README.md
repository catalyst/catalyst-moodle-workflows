# Shared Core Patches

Patch files in this directory are applied to the Moodle source tree during CI setup, before
`moodle-plugin-ci install` runs. They are applied in alphabetical filename order using `git am`.

## Naming convention

```
{min}-{max}-{description}.patch
```

| Part | Description |
|---|---|
| `min` | Lowest Moodle branch number this patch applies to (e.g. `500` for `MOODLE_500_STABLE`) |
| `max` | Highest Moodle branch number this patch applies to (e.g. `999` for `main`) |
| `description` | Freeform identifier (hyphens recommended) |

The branch number is the `XXX` from `MOODLE_XXX_STABLE`. The `main` branch is treated as `999`.

A patch is applied when `min <= current_branch_number <= max`. Patch files whose names do not
match the `NNN-NNN-` prefix are skipped with a warning.

## Examples

| Filename | Applies to |
|---|---|
| `500-999-phpunit-restore.patch` | Moodle 5.0 → main |
| `401-405-phpunit-restore.patch` | Moodle 4.1 → 4.5 |

## Authoring patches

Patches must be in `git format-patch` / `git am` format (i.e. include a commit header). To create
one:

```bash
# Make your changes inside a Moodle checkout, then:
git add <changed files>
git commit -m "Brief description of the patch"
git format-patch HEAD~1 --stdout > NNN-NNN-description.patch
```

The patch is applied with `git am --whitespace=nowarn`, so trailing-whitespace issues are ignored.
If `git am` fails the CI job fails immediately (fail-fast per matrix entry).
