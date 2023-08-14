#!/usr/bin/env bash

set -e

php8.1 ./vendor/bin/php-scoper add --working-dir=./third-party-src --output-dir=../third-party --force --stop-on-failure -vvv

sleep 1

composer --working-dir=./third-party dump

sleep 1

php8.1 ./vendor/bin/pint --config ./third-party-src/pint.json third-party/vendor/illuminate/collections third-party/vendor/illuminate/support third-party/vendor/illuminate/view

sleep 1

rm -r ./._*