#!/usr/bin/env bash
set -e
echo "===================================================================="
echo "  RouteRequest v2.2.0 Afterburner Edition - Push to GitHub"
echo "  Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git"
echo "  Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue"
echo "===================================================================="

if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.2.0 Afterburner Edition: 3-Tier DRM (Basic, Executive, Flagship + PaxStudio Bridge), 12/15 Per-Request & 28/Week Caps, Pre-Release Updater"
git tag -f v2.2.0
git push -u origin main --tags

echo "Done! RouteRequest v2.2.0 and tag v2.2.0 are now live on ffpoke/phpvms7-RouteRequest!"
