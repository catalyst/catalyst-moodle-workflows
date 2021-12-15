# Reusable Workflows

Thanks to the new GitHub Actions feature called "Reusable Workflows" we can now reference an existing workflow with a single line of configuration rather than copying and pasting from one workflow to another.

## Using a Reusable Workflow
Now that we have our reusable workflow ready, it is time to use it in another workflow.

To do so, just add it directly in a job of your workflow with this syntax:

```
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

In real example above, this is how I'd reference it in a job called workflow_311_ci:

```
workflow_311_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/311-ci.yml@main
```

Now of course we have to pass the parameters. Let's start with the inputs:

```
with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'
```

As you can see, we just use the "with" clause, and we specify the name of the inputs.

And this is it. So the complete example would look like this for ci

```
name: Run all tests

on: [push, pull_request]

jobs:
  workflow_34_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/34-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_35_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/35-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_36_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/36-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_37_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/37-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_38_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/38-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_39_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/39-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_310_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/310-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

  workflow_311_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/311-ci.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws'

```

Please note the "extra_plugin_runners" parameter is not required in our case.

If your plugin want more than one plugin to be installed as a dependency, then you can add another plugin command by using "|" (which represents new line) as a separation. Eg:

```
workflow_36:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/36.yml@main
    with:
      extra_plugin_runners: 'moodle-plugin-ci add-plugin catalyst/moodle-local_aws | moodle-plugin-ci add-plugin catalyst/moodle-mod_attendance'
```
Here is an another full example which doesn't need extra plugins.

```
name: Run all tests

on: [push, pull_request]

jobs:
  workflow_35_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/35-ci.yml@main

  workflow_36_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/36-ci.yml@main

  workflow_37_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/37-ci.yml@main

  workflow_38_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/38-ci.yml@main

  workflow_39_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/39-ci.yml@main

  workflow_310_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/310-ci.yml@main

  workflow_311_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/311-ci.yml@main

  workflow_master_ci:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/master-ci.yml@main
```

Here is an example for master-release reusable workflow which uses to releasing in the plugins directory

```
name: Releasing in the Plugins directory

on:
  push:
    paths:
      - 'version.php'

jobs:
  workflow_master_release:
    uses: catalyst/catalyst-moodle-workflows/.github/workflows/master-release.yml@main
    with:
      plugin_name: auth_enrolkey
      plugin_branch: master
      plugin_repository_url: https://github.com/catalyst/moodle-auth_enrolkey
```
Where plugin_name, plugin_branch and plugin_repository_url are required parameters here.