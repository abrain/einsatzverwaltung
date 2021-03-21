#!/usr/bin/env bash

# The root directory of the project is one up
cd "$(dirname "$0")/.."

# Set the environment variable GIT_BRANCH for the Code Climate test-reporter, so it does not report the coverage for the
# target branch of pull requests but for the source branch
if [ "$DRONE_BUILD_EVENT" == "pull_request" ]; then
    export GIT_BRANCH="$DRONE_SOURCE_BRANCH"
else
    export GIT_BRANCH="$DRONE_COMMIT_BRANCH"
fi

./cc-test-reporter after-build --debug --coverage-input-type clover