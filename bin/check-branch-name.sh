#!/usr/bin/env sh

if [ "$DRONE_BUILD_EVENT" == "pull_request" ]; then
    case "$DRONE_SOURCE_BRANCH" in
        develop|master|main) echo "!!! Please make your changes in a branch other than develop, master, or main !!!";
                             exit 1;
        ;;
        *) echo "Branch name OK"
        ;;
    esac
else
  echo "This script is only meant to be run on a Pull Requests"
  exit 2
fi
