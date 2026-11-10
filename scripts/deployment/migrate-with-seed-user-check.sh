#!/usr/bin/env sh

set -eu

# The deployment environment (and any Laravel config cache) must already
# contain the complete SEED_* variable inventory. Neither command echoes it.
php artisan seed-users:validate-config
php artisan migrate --seed --force
