#!/usr/bin/env bash

set -eou pipefail

if ! command -v mkcert &> /dev/null; then
  curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
  chmod +x mkcert-v*-linux-amd64
  sudo cp mkcert-v*-linux-amd64 /usr/local/bin/mkcert
fi

if [ -z "$(git config user.email)" ]; then
  git config --global user.email "actions@github.com"
  git config --global user.name "GitHub Actons"
fi

# install the ISLE stack using isle-site-template
git clone https://github.com/Islandora-Devops/isle-site-template
pushd isle-site-template
./tests/init-template-starter.sh

# make sure a db update and config export doesn't add any new configs
pushd ist-test
docker compose --profile dev exec drupal-dev drush updb -y
docker compose --profile dev exec drupal-dev drush cex -y

# core.extension.yml is expected to have a change (uninstall sqlite and pgsql modules)
# so make sure it's just a two line diff
git diff \
  --numstat drupal/rootfs/var/www/drupal/config/sync/core.extension.yml \
  | awk '$1==0 && $2==2' | grep core.extension.yml && echo "Only 2 missing lines" \
  || (echo "core.extension.yml not as expected" && exit 1)

git checkout -- drupal/rootfs/var/www/drupal/config/sync/core.extension.yml

# now make sure no other config differences
if [ -n "$(git status drupal/rootfs/var/www/drupal/config/sync --porcelain)" ]; then
    echo "Error: Working directory is not clean" >&2
    git status --short >&2
    exit 1
fi
