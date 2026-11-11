# Reusable Workflows

Thanks to the new GitHub Actions feature called "Reusable Workflows" we can now reference an existing workflow with a single line of configuration rather than copying and pasting from one workflow to another.

This massively reduces the amount of boilerplate setup in each plugin to the bare minimum and also means that we can maintain and revise our definition of best practice in one place and have all the Moodle plugins inherit improvements in lock step. Mostly these shared actions in turn wrap the Moodle plugin CI scripts:

https://moodlehq.github.io/moodle-plugin-ci/

## :rocket: Quick start

For most plugins, you'll want to go through this checklist:

1. Ensure branch section in `README.md` describes correct versions supported
2. Supported range in `version.php` has been configured correctly
3. Workflow file has been added `.github/workflows/ci.yml` and configured
4. CI badge has been properly added in the `README.md`

Some examples of usage: [tool_mfa](https://github.com/catalyst/moodle-tool_mfa#branches), [tool_dataflows](https://github.com/catalyst/moodle-tool_dataflows/#branches)


### Configure support range

Each plugin should have a measurable range of versions supported. It's recommended and ensures a predictable test range.

1. Open `version.php` in your plugin repository
2. Set the `$plugin->supported`* as the range the plugin supports, which then determines the versions the workflow tests for.

```php
# version.php
$plugin->supported = [35, 402];
```
This example will run a matrix of tests from `MOODLE_35_STABLE` to `MOODLE_402_STABLE` - [see full test matrix here](.github/actions/matrix/matrix_includes.yml).

Any number _greater_ than the [latest available stable branch](https://github.com/moodle/moodle/branches/active) will automatically include the [main branch](https://github.com/moodle/moodle/tree/main) for testing

\* For more info on the `$plugin->supported` field, please see https://docs.moodle.org/dev/version.php


### Add the workflow

For most cases, this following demonstrates how your workflow file would typically look.

Create `.github/workflows/ci.yml` with the below template in your plugin
repository.
```yaml
# .github/workflows/ci.yml
name: ci

on: [push, pull_request]

jobs:
  ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/ci.yml@main
    # Required if you plan to publish (uncomment the below)
    # secrets:
      # MOODLE_MARKETPLACE_TOKEN: ${{ secrets.MOODLE_MARKETPLACE_TOKEN }}
    with:
      # Any further options in this section
```
For how to set up the secret, please see the [_How does this automate releases_](#how-does-this-automate-releases) section below.

You can add extra options to disable checks that you might not want, or to add additional dependencies under the `with` field. For example:
```yaml
    with:
      disable_behat: true
      disable_grunt: true
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'
```


#### `with` options

Below lists the available inputs which are _all optional_:

| Inputs                   | Description                                                                                                                                                                              |
|--------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| codechecker_max_warnings | To fail on warnings, set this to 0                                                                                                                                                       |
| extra_plugin_runners     | Command to install more dependencies                                                                                                                                                     |
| disable_behat            | Set `true` to disable behat tests.                                                                                                                                                       |
| disable_phpdoc           | Set `true` to disable phpdoc tests.                                                                                                                                                      |
| disable_phpcs            | Set `true` to disable phpcs (codechecker) tests.                                                                                                                                         |
| disable_phplint          | Set `true` to disable phplint tests.                                                                                                                                                     |
| disable_phpunit          | Set `true` to disable phpunit tests.                                                                                                                                                     |
| disable_grunt            | Set `true` to disable grunt.                                                                                                                                                             |
| disable_mustache         | Set `true` to disable mustache.                                                                                                                                                          |
| disable_master           | If `true`, this will skip testing against moodle/master branch                                                                                                                           |
| disable_release          | If `true`, this will skip the release job                                                                                                                                                     |
| disable_ci_validate      | If `true`, this will skip moodle-plugin-ci validate checks                                                                                                                               |
| subplugin_version_policy | `bump_parent` (default) also requires a parent version bump when a subplugin changes. `individual` checks each plugin on its own. |
| release_version_policy | `none` or `match_version` (default), which requires `$plugin->release` to equal `$plugin->version` in each checked `version.php`. |
| disable_version_bump_check | If `true`, this will skip pull-request version bump enforcement checks                                                                                                                   |
| moodle_plugin_ci_repo    | Git repository to install moodle-plugin-ci from (default: moodlehq/moodle-plugin-ci)                                                                                                                   |
| moodle_plugin_ci_ref     | Branch or tag of moodle-plugin-ci to install (default: main)                                                                                                                        |
| enable_phpmd             | If `true`, to enable phpmd                                                                                                                                                               |
| release_branches         | Name of the non-standardly named branch which should run the release job                                                                                                                 |
| moodle_branches          | Specify the MOODLE_XX_STABLE branch you specifically want to test against. This is _not_ recommended, and instead you should configuring a supported range.                              |
| min_php                  | The minimum php version to test. Set this to support the minimum php version supported by the plugin. Defaults to '7.4', however more recent Moodle branches only test higher versions.  |
| ignore_paths             | Specify custom paths for CI to ignore. Third party libraries are ignored by default. |

### Running with dependencies
If you'd require to run your workflow against specific versions of a plugin you depend on, then you can specify a branch you'd like to run each job against. Like following:   

```yaml
jobs:
  moodle41:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/ci.yml@main
    with:
      disable_phpunit: true
      moodle_branches: MOODLE_401_STABLE
      extra_plugin_runners:
        moodle-plugin-ci add-plugin danmarsden/moodle-mod_attendance --branch MOODLE_401_STABLE

  moodle42:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/ci.yml@main
    with:
      disable_phpunit: true
      moodle_branches: MOODLE_402_STABLE
      extra_plugin_runners:
        moodle-plugin-ci add-plugin danmarsden/moodle-mod_attendance --branch MOODLE_402_STABLE
```

An example can be found here - https://github.com/danmarsden/moodle-block_attendance/blob/main/.github/workflows/ci.yml

### Add CI badge

With badges, we will be able to see at a glance from the plugin's `README.md` whether or not the plugin is in a good state for usage.

```
[![ci](https://github.com/[user]/[plugin]/actions/workflows/ci.yml/badge.svg?branch=[branch])](https://github.com/[user]/[plugin]/actions/workflows/ci.yml?branch=[branch])
```

Please update `[USER]`, `[PLUGIN]` and `[BRANCH]` in the example above. This goes under the plugin title. Here is an example:

```
[![ci](https://github.com/catalyst/moodle-tool_excimer/actions/workflows/ci.yml/badge.svg?branch=MOODLE_35_STABLE)](https://github.com/catalyst/moodle-tool_excimer/actions/workflows/ci.yml?branch=MOODLE_35_STABLE)
</a>
```

which renders as:

[![ci](https://github.com/catalyst/moodle-tool_excimer/actions/workflows/ci.yml/badge.svg?branch=MOODLE_35_STABLE)](https://github.com/catalyst/moodle-tool_excimer/actions/workflows/ci.yml?branch=MOODLE_35_STABLE)

## How does this automate tests?
When you call the reusable ci, it will:
1. a check to see what versions of moodle should run, based on the `version.php` file included in the plugin repository.
2. This will then build out the combination of tests to run, performing the tests based on the __MOODLE_XX_STABLE__ version affected and will handle any version specific caveats and run a more optimally configured test suite for you.

To view or modify the full matrix, please see it here: [.github/actions/matrix/matrix_includes.yml](.github/actions/matrix/matrix_includes.yml)

## How does this automate releases?

Whenever a change is made to version.php, the workflow is triggered on a release branch (e.g. __main__ / __MOODLE_XX_STABLE__), and tests pass, a `v<version>` git tag is created and the release is published to the Moodle Marketplace via `moodlehq/moodle-plugin-release`.

The `MOODLE_MARKETPLACE_TOKEN` secret must be set, otherwise no release is made.

The calling workflow must grant `permissions: contents: write` so the tag can be pushed. If the tag already exists on a different commit (version not bumped), the tag job fails and the release is skipped.

To configure the secret, create a repository secret named `MOODLE_MARKETPLACE_TOKEN` in your plugin's **Settings > Secrets** section, holding your Moodle Marketplace access token.
* For the latest branch/stable releases on the Marketplace we **MUST** bump plugin version (e.g. by date).
* For the __older stables__ with closed groups, the version **MUST** be a <ins>**micro bump**</ins>.

## How does the version bump check work?
On pull requests targeting a `MOODLE_XX_STABLE` branch, the workflow runs `moodle-plugin-ci version` against the PR base and head code:
1. The policy is `latest` when the PR base is the highest `MOODLE_XX_STABLE` branch in the repository, otherwise `frozen`. Versions are compared by major (first digit) then minor (remaining digits), so `MOODLE_401_STABLE` is 4.1 and `MOODLE_311_STABLE` is 3.11.
2. The plugin is checked at the PR base and head commits, and the version bump is validated against the base.
3. Subplugins are detected automatically from `db/subplugins.json`: each subdirectory of a listed subplugin type path with a `version.php` is checked the same way. With `subplugin_version_policy: bump_parent` (default), a changed or removed subplugin also requires the parent plugin to be bumped. With `individual`, each plugin is checked on its own and removing a subplugin does not require a parent bump.
4. With `release_version_policy: match_version`, the `$plugin->release` value in each checked `version.php` must equal `$plugin->version`. The default is `match_version`.

## Common concerns / issues

### Core patches
If you need to apply core patches to the moodle code to allow the plugin to work on older versions missing API support, this can be achieved by outputting
an appliable diff to a file, in the `patch` directory in the top level of the repo. The version for a particular branch should be named `MOODLE_XX_STABLE.diff` in line with the naming of the branch. To generate a diff, these patches can be manually applied to the target branch, and a diff from the remote generated by doing `git format-patch MOODLE_XX_STABLE --stdout > my/plugin/path/patch/MOODLE_XX_STABLE.diff`.

### amd / grunt bundling issues

Depending on your supported range, you might encounter an issue which outputs something along the lines of `File is stale and needs to be rebuilt`. The brief reason for this, is that along the way, Moodle has updated the way it bundles JS/CSS files, which results in different outputs across Moodle versions.

To fix this, you'll need to rebundle the relevant files, on the <ins>highest supported version of Moodle</ins> for your plugin's support range. For example, if the plugin supports up to __Moodle 4.0__, you'll need to bundle the changes, while on the `MOODLE_400_STABLE` branch of Moodle, and then commit those changes.

__NOTE:__ This may involve having a clean copy of Moodle and installing the plugin code to run the necessary commands to rebuild the _stale_ files.

Grunt docs: https://docs.moodle.org/dev/Grunt#Running_grunt

### Testing changes
Changes can be tested by using another plugin repository that uses these workflows.

Simply change the repository/branch it pulls from:

```yml
uses: catalyst/catalyst-moodle-workflows/.github/workflows/ci.yml@my-custom-branch
```

and specify the same branch as a `with` parameter:

```yml
with:
  internal_workflow_branch: 'my-custom-branch'
```
