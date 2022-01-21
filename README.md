# Reusable Workflows

Thanks to the new GitHub Actions feature called "Reusable Workflows" we can now reference an existing workflow with a single line of configuration rather than copying and pasting from one workflow to another.

This massively reduces the amount of boilerplate setup in each plugin to the bare minimum and also means that we can maintain and revise our definition of best practice in one place and have all the Moodle plugins inherit improvements in lock step. Mostly these shared actions in turn wrap the Moodle plugin CI scripts:

https://moodlehq.github.io/moodle-plugin-ci/


## Grouping actions

We have 2 types of group actions. lets call these the 'closed' groups and 'open' groups. A closed group is where we know the maximum supported stable and so we can express the group and a range of the earliest and latest supported stable. An open group is one which we do not yet know the upper bound and support may continue for some time, but could also break at any future stable.

### Open groups

| Moodle version    | CI group              |  Plugin release group       |
| ----------------- | -------------         | --------------------------- |
| Moodle 33+        | group-33-plus-ci.yml  | group-33-plus-release.yml   |
| Moodle 34+        | group-34-plus-ci.yml  | group-34-plus-release.yml   |
| Moodle 35+        | group-35-plus-ci.yml  | group-35-plus-release.yml   |
| Moodle 36+        | group-36-plus-ci.yml  | group-36-plus-release.yml   |
| Moodle 37+        | group-37-plus-ci.yml  | group-37-plus-release.yml   |
| Moodle 38+        | group-34-plus-ci.yml  | group-38-plus-release.yml   |
| Moodle 39+        | group-39-plus-ci.yml  | group-39-plus-release.yml   |
| Moodle 310+       | group-310-plus-ci.yml | group-310-plus-release.yml  |
| Moodle 311+       | group-311-plus-ci.yml | group-311-plus-release.yml  |

### Closed groups

| Moodle version     | CI group               |
| ----------------- | -------------           |
| Moodle 34 to 38   | group-34-to-38-ci.yml   |
| Moodle 34 to 39   | group-34-to-39-ci.yml   |

## Using a Reusable Workflow
Now that we have our reusable workflow ready, it is time to use it in another workflow.

To do so, just add it directly in a job of your workflow with this syntax:

```yaml
 job_name:
    uses: USER_OR_ORG_NAME/REPO_NAME/.github/workflows/REUSABLE_WORKFLOW_FILE.yml@TAG_OR_BRANCH

```

Let's analyse this:
<ul>
<li>
    You create a job with no steps
</li>
<li>
    You don't add a "runs-on" clause, because it is contained in the reusable workflow
</li>
<li>
    You reference it as "uses" passing:
</li>
<li>
    the name of the user or organization that owns the repo where the reusable workflow is stored
</li>
<li>
    the repo name
</li>
<li>
    the base folder
</li>
<li>
    the name of the reusable workflow yaml file
</li>
<li>
    and the tag or the branch where the file is store (if you haven't created a tag/version for it)
</li>
</ul>

In real example above, this is how I'd reference it in a job called group-35-plus-ci.yml:

```yaml
workflow_group_35_plus_ci:
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
name: Run all tests

on: [push, pull_request]

jobs:
  workflow_group_310_plus_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-310-plus-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

```

Please note the "extra_plugin_runners" parameter is not required in our case.

If your plugin want more than one plugin to be installed as a dependency, then you can add another plugin command by using "|" (which represents new line) as a separation. Eg:

```yaml
workflow_group_310_plus_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-310-plus-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws | moodle-plugin-ci add-plugin catalyst/moodle-mod_attendance'
```
Here is an another full example which doesn't need extra plugins.

```yaml
# .github/workflows/ci.yml
name: Run all tests

on: [push, pull_request]

jobs:
  workflow_group_35_plus_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-35-plus-ci.yml@main

```
Here is the list of available inputs (all are optional) for CI:

| Available inputs for CI     | Used for                                                  | Required? |
| --------------------------- | --------------------------------------------------------- | --------- |
| extra_plugin_runners        | Command to install dependencies                           | No        |
| disable_behat               | Option to disable behat tests. It will disable if true.   | No        |
| disable_phplint             | Option to disable phplint tests. It will disable if true. | No        |
| disable_phpunit             | Option to disable phpunit tests. It will disable if true. | No        |
| disable_grunt               | Option to disable grunt. It will disable if true.         | No        |


## How to call reusable workflow for plugin moodle release?

Create `.github/workflows/moodle-release.yml` using the template below in your
plugin repository. Change the targetted moodle release group file based on your plugin
support - in this example, `group-35-plus-release.yml` is chosen. This workflow
is used to automatically generate releases in the plugins directory.

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
  workflow_group_35_plus_release:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/group-35-plus-release.yml@main
    with:
      plugin_name: auth_enrolkey
    secrets:
      moodle_org_token: ${{ secrets.MOODLE_ORG_TOKEN }}
```
Whenever version.php is changed, add the latest version to the Moodle Plugins directory at https://moodle.org/plugins.

You MUST update below items for each plugin
* `branches` - branches you wish to publish into Moodle Plugins directory. So release workflow will trigger on push or pull request, and for the example above, only for the master branch.
* `plugin_name` - the frankenstyle plugin name (also known as the component name)

Also please note:
* Please check MOODLE_ORG_TOKEN is available in your plugin's Settings > Secrets section. Currently The we have set the token at the org level and shared across all repos.
* To release in plugin directory we should bump plugin version. For the older stables with closed groups, make sure version is only a micro bump.

## My Repo is complicated!
### Core patches
If you need to apply core patches to the moodle code to allow the plugin to work on older versions missing API support, this can be achieved by outputting
an appliable diff to a file, in the `patch` directory in the top level of the repo. The version for a particular branch should be named `MOODLE_XX_STABLE.diff` in line with the naming of the branch. To generate a diff, these patches can be manually applied to the target branch, and a diff from the remote generated by doing `git format-patch MOODLE_XX_STABLE --stdout > my/plugin/path/patch/MOODLE_XX_STABLE.diff`.