# Reusable Workflows

Thanks to the new GitHub Actions feature called "Reusable Workflows" we can now reference an existing workflow with a single line of configuration rather than copying and pasting from one workflow to another.

This massively reduces the amount of boilerplate setup in each plugin to the bare minimum and also means that we can maintain and revise our definition of best practice in one place and have all the Moodle plugins inherit improvements in lock step. Mostly these shared actions in turn wrap the Moodle plugin CI scripts:

https://moodlehq.github.io/moodle-plugin-ci/

## :rocket: Quick start

For most plugins, you'll want to go through this checklist:

1. Ensure branch section in `README.md` describes correct versions supported
2. Supported range in `version.php` has been configured correctly
3. Workflow file has been added `.github/workflows/ci.yml` and configured
4. CI badge has been properly referenced in the `README.md`

Some examples of usage: [tool_mfa](https://github.com/catalyst/moodle-tool_mfa#branches), [tool_dataflows](https://github.com/catalyst/moodle-tool_dataflows/#branches)


### Configure support range

Each plugin should have a measurable range of versions supported. It's recommended and ensures a predictable test range.

1. Open `version.php` in your plugin repository
2. Set the `$plugin->supported` as the range the plugin supports, which then determines the versions the workflow tests for.

```php
# version.php
$plugin->supported = [35, 401];
```
This example will run a matrix of tests from `MOODLE_35_STABLE` to `MOODLE_401_STABLE` - [see full test matrix here](.github/actions/matrix/matrix_includes.yml)


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
      # moodle_org_token: ${{ secrets.MOODLE_ORG_TOKEN }}
    with:
      # Any further options in this section
```

You can add extra options to disable checks that you might not want, or to add additional dependencies under the `with` field. For example:
```yaml
    with:
      disable_behat: true
      disable_grunt: true
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'
```


#### `with` options

Below lists the available inputs which are _all optional_:

| Inputs               | Description                                |
|----------------------|--------------------------------------------|
| extra_plugin_runners | Command to install additional dependencies |
| disable_behat        | Set `true` to disable behat tests.         |
| disable_phplint      | Set `true` to disable phplint tests.       |
| disable_phpunit      | Set `true` to disable phpunit tests.       |
| disable_grunt        | Set `true` to disable grunt.               |
| disable_master       | If `true`, this will skip testing against moodle/master branch |
| disable_release      | If `true`, this will skip the release job |
| release_branches     | Name of the non-standardly named branch which should run the release job |
| moodle_branches      | Specify the MOODLE_XX_STABLE branch you specifically want to test against. This is _not_ recommended, and instead you should configuring a supported range. |

### Add CI badge

With badges, we will be able to see at a glance from the plugin's `README.md` whether or not the plugin is in a good state for usage.

```
![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/catalyst/:plugin/ci/:branch?label=ci)
```
Please update `:plugin` and `:branch` in the example above. This goes under the plugin title. Here is an example from [tool_excimer](https://github.com/catalyst/moodle-tool_excimer/blob/MOODLE_35_STABLE/README.md?plain=1) ![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/catalyst/moodle-tool_excimer/ci/MOODLE_35_STABLE?label=ci)

## How does this automate tests?
When you call the reusable ci, it will:
1. a check to see what versions of moodle should run, based on the `version.php` file included in the plugin repository.
2. This will then build out the combination of tests to run, performing the tests based on the __MOODLE_XX_STABLE__ version affected and will handle any version specific caveats and run optimisations for you.

To view or modify the full matrix, please see it here: [.github/actions/matrix/matrix_includes.yml](.github/actions/matrix/matrix_includes.yml)

## How does this automate releases?

Whenever a change is made to version.php, the workflow is triggered on a release branch (e.g. __main__ / __MOODLE_XX_STABLE__), and tests pass, will it attempt to run the plugin/release action `.github/plugin/release/action.yml`. Doing so will automatically publish a release to the Moodle plugin directory at https://moodle.org/plugins.

In the prepare step of the CI, it will have resolved the component name for you so you don't need to enter one manually, and the `MOODLE_ORG_TOKEN` secret should be set otherwise the plugin won't be published.

To configure the secret:
* Please check `MOODLE_ORG_TOKEN` is available in your plugin's **Settings > Secrets** section. If not, please create using below steps:
  * Log in to the Moodle Plugins directory at https://moodle.org/plugins/
  * Locate the **Navigation block > Plugins > API access**.
    * Use that page to generate your personal token for the `plugins_maintenance` service.
  * Go back to your plugin repository at Github. Locate your plugin's **Settings > Secrets** section. Use the 'New repository secret' button to define a new repository secret to hold your access token. Use name `MOODLE_ORG_TOKEN` and set the value to the one you generated in previous step.
* For the latest branch/stable releases in plugin directory we **MUST** bump plugin version (e.g. by date).
* For the __older stables__ with closed groups, the version **MUST** be a <ins>**micro bump**</ins>.

## My Repo is complicated!
### Core patches
If you need to apply core patches to the moodle code to allow the plugin to work on older versions missing API support, this can be achieved by outputting
an appliable diff to a file, in the `patch` directory in the top level of the repo. The version for a particular branch should be named `MOODLE_XX_STABLE.diff` in line with the naming of the branch. To generate a diff, these patches can be manually applied to the target branch, and a diff from the remote generated by doing `git format-patch MOODLE_XX_STABLE --stdout > my/plugin/path/patch/MOODLE_XX_STABLE.diff`.
