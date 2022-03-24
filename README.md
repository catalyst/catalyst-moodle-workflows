# Reusable Workflows

Thanks to the new GitHub Actions feature called "Reusable Workflows" we can now reference an existing workflow with a single line of configuration rather than copying and pasting from one workflow to another.

This massively reduces the amount of boilerplate setup in each plugin to the bare minimum and also means that we can maintain and revise our definition of best practice in one place and have all the Moodle plugins inherit improvements in lock step. Mostly these shared actions in turn wrap the Moodle plugin CI scripts:

https://moodlehq.github.io/moodle-plugin-ci/


## Grouping actions

We have 2 types of group actions. lets call these the 'closed' groups and 'open' groups. A closed group is where we know the maximum supported stable and so we can express the group and a range of the earliest and latest supported stable. An open group is one which we do not yet know the upper bound and support may continue for some time, but could also break at any future stable.

### Open groups

| Moodle version    | CI group              |  Plugin release group       |
|-------------------|-----------------------|-----------------------------|
| Moodle 3.3+       | group-33-plus-ci.yml  | group-33-plus-release.yml   |
| Moodle 3.4+       | group-34-plus-ci.yml  | group-34-plus-release.yml   |
| Moodle 3.5+       | group-35-plus-ci.yml  | group-35-plus-release.yml   |
| Moodle 3.6+       | group-36-plus-ci.yml  | group-36-plus-release.yml   |
| Moodle 3.7+       | group-37-plus-ci.yml  | group-37-plus-release.yml   |
| Moodle 3.8+       | group-34-plus-ci.yml  | group-38-plus-release.yml   |
| Moodle 3.9+       | group-39-plus-ci.yml  | group-39-plus-release.yml   |
| Moodle 3.10+      | group-310-plus-ci.yml | group-310-plus-release.yml  |
| Moodle 3.11+      | group-311-plus-ci.yml | group-311-plus-release.yml  |
| Moodle 4.0+       | group-40-plus-ci.yml  | group-40-plus-release.yml   |

### Closed groups

| Moodle version     | CI group                   |
|--------------------|----------------------------|
| Moodle 2.7 - 3.2   | group-27-to-32-release.yml |
| Moodle 3.3 - 3.8   | group-33-to-38-ci.yml      |
| Moodle 3.3 - 3.9   | group-33-to-39-ci.yml      |
| Moodle 3.4 - 3.8   | group-34-to-38-ci.yml      |
| Moodle 3.4 - 3.9   | group-34-to-39-ci.yml      |
| Moodle 3.5 - 3.9   | group-35-to-39-ci.yml      |
| Moodle 3.5 - 3.10  | group-35-to-310-ci.yml     |
| Moodle 3.5 - 3.11  | group-35-to-311-ci.yml     |

## Using a Reusable Workflow
Now that we have our reusable workflow ready, it is time to use it in another workflow.

To do so, just add it directly in a `job` of your workflow with this syntax:

```yaml
 job_name:
    uses: USER_OR_ORG_NAME/REPO_NAME/.github/workflows/REUSABLE_WORKFLOW_FILE.yml@TAG_OR_BRANCH

```

Let's analyse this:
- You create a `job` with no steps
- You don't need to add a "runs-on" clause, because it is contained in the reusable workflow
- You reference it as "uses" passing:
- the name of the user or organization that owns the repo where the reusable workflow is stored
- the repo name
- the base folder
- the name of the reusable workflow yaml file
- and the tag or the branch where the file is store (if you haven't created a tag/version for it)

In real example above, this is how I'd reference it in a job called group-35-plus-ci.yml:

```yaml
test:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-35-plus-ci.yml@main
```

Now of course we have to pass the parameters. Let's start with the inputs:

```yaml
with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'
```

As you can see, we just use the "with" clause, and we specify the name of the inputs.

## How to call reusable workflow for CI in plugin?

Create `.github/workflows/ci.yml` with the below template in your plugin
repository. Change the targetted CI group file (here it is using
`group-310-plus-ci.yml`) based on your plugin support.


```yaml
# .github/workflows/ci.yml
name: ci

on: [push, pull_request]

jobs:
  test:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-310-plus-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

```

Please note the `extra_plugin_runners` parameter is not required in our case.

If your plugin want more than one plugin to be installed as a dependency, then you can add another plugin command by using "|" (which represents new line in YAML) as a separator. Eg:

```yaml
test:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-310-plus-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws | moodle-plugin-ci add-plugin catalyst/moodle-mod_attendance'
```
Here is an another full example which doesn't need extra plugins.

```yaml
# .github/workflows/ci.yml
name: ci

on: [push, pull_request]

jobs:
  test:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-35-plus-ci.yml@main

```
Below is the list of available inputs (all optional) for CI:

| Inputs                  | Description                                |
|-------------------------|--------------------------------------------|
| extra_plugin_runners    | Command to install additional dependencies |
| disable_behat           | Set `true` to disable behat tests.         |
| disable_phplint         | Set `true` to disable phplint tests.       |
| disable_phpunit         | Set `true` to disable phpunit tests.       |
| disable_grunt           | Set `true` to disable grunt.               |


## How to call reusable workflow for plugin moodle release?

Create `.github/workflows/moodle-release.yml` using the template below in your plugin repository. Change the targetted moodle release group file based on your plugin
support - in this example, `group-35-plus-release.yml` is chosen. This workflow
is used to automatically generate and publish releases to the plugins directory.

```yaml
# .github/workflows/moodle-release.yml
name: Releasing in the Plugins directory

on:
  push:
    branches:
      - master
    paths:
      - 'version.php'

jobs:
  release:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-35-plus-release.yml@main
    with:
      plugin_name: auth_enrolkey
    secrets:
      moodle_org_token: ${{ secrets.MOODLE_ORG_TOKEN }}
```
Whenever version.php is changed, add the latest version to the Moodle Plugins directory at https://moodle.org/plugins.

You **MUST** update below items for each plugin
* `branches` - Add branches you wish to publish into Moodle Plugins directory. In this example, release workflow will trigger on a **push** event to the master branch.
* `plugin_name` - Update this to the **frankenstyle** plugin name (located in `version.php` of the plugin folder)

Also please note:
* Please check `MOODLE_ORG_TOKEN` is available in your plugin's **Settings > Secrets** section. If not, please create using below steps:
  * Log in to the Moodle Plugins directory at https://moodle.org/plugins/
  * Locate the **Navigation block > Plugins > API access**.
    * Use that page to generate your personal token for the `plugins_maintenance` service.
  * Go back to your plugin repository at Github. Locate your plugin's **Settings > Secrets** section. Use the 'New repository secret' button to define a new repository secret to hold your access token. Use name `MOODLE_ORG_TOKEN` and set the value to the one you generated in previous step.
* To release in plugin directory we **MUST** bump plugin version. For the older stables with closed groups, please ensure the version is only a <ins>**micro bump**</ins>.

## My Repo is complicated!
### Core patches
If you need to apply core patches to the moodle code to allow the plugin to work on older versions missing API support, this can be achieved by outputting
an appliable diff to a file, in the `patch` directory in the top level of the repo. The version for a particular branch should be named `MOODLE_XX_STABLE.diff` in line with the naming of the branch. To generate a diff, these patches can be manually applied to the target branch, and a diff from the remote generated by doing `git format-patch MOODLE_XX_STABLE --stdout > my/plugin/path/patch/MOODLE_XX_STABLE.diff`.
