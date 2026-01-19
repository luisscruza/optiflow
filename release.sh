#!/bin/bash

# Tag a new release adding a git tag
# Usage: ./release.sh major|minor|patch (default: patch)

set -euo pipefail

VERSION_BUMP=${1:-patch}

# Get latest v-* tag
CURRENT_TAG=$(git describe --tags --abbrev=0 --match 'v-*' 2>/dev/null || echo "v-0.0.0")

# Strip prefix v-
VERSION=${CURRENT_TAG#v-}

IFS='.' read -r MAJOR MINOR PATCH <<< "$VERSION"

case "$VERSION_BUMP" in
  major)
    NEW_VERSION="$((MAJOR + 1)).0.0"
    ;;
  minor)
    NEW_VERSION="$MAJOR.$((MINOR + 1)).0"
    ;;
  patch)
    NEW_VERSION="$MAJOR.$MINOR.$((PATCH + 1))"
    ;;
  *)
    echo "Invalid version bump type. Use 'major', 'minor', or 'patch'."
    exit 1
    ;;
esac

NEW_TAG="alpha-$NEW_VERSION"

echo "About to create and push the following tag:"
echo "  $NEW_TAG"
echo

read -r -p "Proceed? (y/N): " CONFIRM
[[ "$CONFIRM" =~ ^[Yy]$ ]] || {
  echo "Aborted"
  exit 1
}

git tag -a "$NEW_TAG" -m "Release $NEW_TAG"
git push origin "$NEW_TAG"

echo "Released new version: $NEW_TAG"