#!/usr/bin/env bash
#
# Local development: replace the GitHub copies of the antlion modules in
# vendor/ with symlinks to your own checkouts, so edits there show up in the
# site immediately. composer.json and composer.lock keep pointing at GitHub,
# so the project still installs anywhere.
#
# Runs automatically after `composer install` / `composer update` (see
# "scripts" in composer.json). Does nothing when the checkouts folder doesn't
# exist (CI, servers, other machines).
#
#   ANTLION_MODULES_DIR  where the checkouts live
#                        (default: ../antlion-modules next to this project)

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
modules_dir="${ANTLION_MODULES_DIR:-$(dirname "$root")/antlion-modules}"

if [ ! -d "$modules_dir" ]; then
    echo "link-local-modules: $modules_dir not found, using the GitHub copies"
    exit 0
fi

for vendor_path in "$root"/vendor/antlion/*; do
    [ -e "$vendor_path" ] || continue
    name="$(basename "$vendor_path")"
    checkout="$modules_dir/$name"

    if [ ! -f "$checkout/composer.json" ]; then
        continue
    fi
    if [ -L "$vendor_path" ] && [ "$(readlink -f "$vendor_path")" = "$(readlink -f "$checkout")" ]; then
        continue
    fi

    rm -rf "$vendor_path"
    ln -s "$checkout" "$vendor_path"
    echo "link-local-modules: vendor/antlion/$name -> $checkout"
done
