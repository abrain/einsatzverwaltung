#!/usr/bin/env bash

# Set the environment variable GIT_BRANCH for the Code Climate test-reporter, so it does not report the coverage for the
# target branch of pull requests but for the source branch
if [ "$DRONE_BUILD_EVENT" == "pull_request" ]; then
    export GIT_BRANCH="$DRONE_SOURCE_BRANCH"
else
    export GIT_BRANCH="$DRONE_COMMIT_BRANCH"
fi
