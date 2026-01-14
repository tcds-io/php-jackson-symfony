#!/bin/bash

WORKDIR=tests/blog

echo "Deleting current blog installation..."
rm -rf $WORKDIR
echo "Done!"

composer create-project symfony/skeleton:"8.0.*" $WORKDIR
composer require --dev symfony/test-pack --working-dir=$WORKDIR
composer config minimum-stability dev --working-dir=$WORKDIR

cp -r tests/fixtures/. $WORKDIR

composer config \
  repositories.php-jackson-symfony '{"type": "path", "url": "./../../", "options": {"symlink": true}}' \
  --working-dir=$WORKDIR

composer require tcds-io/php-jackson \
    tcds-io/php-jackson-symfony:* \
    --working-dir=$WORKDIR
