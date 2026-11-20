#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.6.0 Afterburner Edition (PHP 8.3+): Mark Completed No More Routes, Two-Layer Duplicate Checker + Bypass, PaxStudio Compatibility Floor & Auto-Updater"
git tag -f v2.6.0
git push -f -u origin main --tags
echo "Done! RouteRequest v2.6.0 (PHP 8.3+) is now live on ffpoke/phpvms7-RouteRequest!"
