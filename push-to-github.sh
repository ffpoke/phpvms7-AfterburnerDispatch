#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.8.0 Afterburner Edition (PHP 8.3+): Bulk Subfleet-to-Route Auto-Sync Engine with Dry-Run Preview & Custom Airline Restrictions"
git tag -f v2.8.0
git push -f -u origin main --tags
echo "Done! RouteRequest v2.8.0 (PHP 8.3+) is now live on ffpoke/phpvms7-RouteRequest!"
