#!/usr/bin/env sh

set -eo pipefail

if [ "$DRONE_BUILD_EVENT" != "tag" ]; then
  echo "This script is only meant to be run on tag events"
  exit 1
fi

TAG_VERSION=$(echo "$DRONE_TAG" | grep -o -E -e "(\d+\.\d+\.\d+)(-(alpha|beta|rc)\.\d+)?$")
echo "Tag version is: $TAG_VERSION"
if [ -z "$TAG_VERSION" ]; then
    echo "Tag version is invalid"
    exit 2
fi

PRERELEASE=$(echo "$TAG_VERSION" | grep -E -e "-(alpha|beta|rc)\.\d+" || echo "")
if [ -n "$PRERELEASE" ]; then
  echo "This is a pre-release: $PRERELEASE"
fi

# The root directory of the project is one up
cd "$(dirname "$0")/.."

PLUGIN_VERSION=$(grep "^Version:" src/einsatzverwaltung.php | cut -d " " -f2)
if [ "$PLUGIN_VERSION" != "$TAG_VERSION" ]; then
  echo "Plugin version does not match git tag ($PLUGIN_VERSION)"
  exit 2
fi

CORE_CONST=$(grep -E -e "^\s+const VERSION = '" src/Core.php | cut -d "'" -f2)
if [ "$CORE_CONST" != "$TAG_VERSION" ]; then
  echo "Const in Core.php does not match git tag ($CORE_CONST)"
  exit 3
fi

STABLE_TAG=$(grep "^Stable tag:" src/readme.txt | cut -d " " -f3)
if [ -n "$PRERELEASE" ]; then
      echo "Not checking stable tag, as this is a pre-release"
elif [ "$STABLE_TAG" != "$TAG_VERSION" ]; then
  echo "Stable tag does not match git tag ($STABLE_TAG)"
  exit 4
fi
