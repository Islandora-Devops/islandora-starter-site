#!/usr/bin/env bash

set -eou pipefail

if [ -z "${GITHUB_ACTIONS:-}" ]; then
  echo "This should only be ran in GitHub Actions"
  exit 0
fi

git add . && git commit -m "testing" || echo "no changes"

docker compose exec drupal drush updb -y
docker compose exec drupal drush cex -y
rm -rf drupal/rootfs/var/www/drupal/config
docker compose cp drupal:/var/www/drupal/config  drupal/rootfs/var/www/drupal/config

# core.extension.yml is expected to have a change (uninstall sqlite and pgsql modules)
# so make sure it's just a two line diff
git diff \
  --numstat drupal/rootfs/var/www/drupal/config/sync/core.extension.yml \
  | awk '$1==0 && $2==2' | grep core.extension.yml && echo "Only 2 missing lines" \
  || (echo "core.extension.yml not as expected" && git diff drupal/rootfs/var/www/drupal/config/sync/core.extension.yml && exit 1)

git checkout -- drupal/rootfs/var/www/drupal/config/sync/core.extension.yml

# now make sure no other config differences
if [ -n "$(git status drupal/rootfs/var/www/drupal/config/sync --porcelain)" ]; then
    echo "Error: Working directory is not clean" >&2
    git status --short >&2
    exit 1
fi
