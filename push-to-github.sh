#!/usr/bin/env bash
set -e
echo "===================================================================="
echo "  RouteRequest v2.1.0 Afterburner Edition - Push to GitHub"
echo "  Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git"
echo "  Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue"
echo "===================================================================="

if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.1.0 Afterburner Edition: GitHub 1-Click Auto-Updater, Creator Attribution, and Cloud DRM Client"
git tag -f v2.1.0
git push -u origin main --tags

echo "Done! RouteRequest v2.1.0 and tag v2.1.0 are now live on ffpoke/phpvms7-RouteRequest!"
