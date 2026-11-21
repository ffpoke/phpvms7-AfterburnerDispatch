#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v3.0.4: Fix Old Route Time Estimator Fixer SQL (al.iata), Standalone/Flagship Storefront UI, Contrast & PHP 8.3.36+ Badge"
git tag -f v3.0.4
git push -f -u origin main --tags
echo "Done! RouteRequest v3.0.4 is now live on ffpoke/phpvms7-RouteRequest!"
