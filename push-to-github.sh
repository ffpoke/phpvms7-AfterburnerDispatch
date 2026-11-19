#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.4.0 Afterburner Edition: PHP 8.3+ Strict Optimization & Security (Dropped PHP 8.1/8.2)"
git tag -f v2.4.0
git push -f -u origin main --tags
echo "Done! RouteRequest v2.4.0 (PHP 8.3+) and tag v2.4.0 are now live on ffpoke/phpvms7-RouteRequest!"
