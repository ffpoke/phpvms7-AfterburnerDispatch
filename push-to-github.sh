#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v3.1.0: Full Stable Release — Unified Split Chips, SimBrief & VATSIM Bridges, Guided Travel & Admin Move Queue, Companion Installer, Queue Filters, Auto-Expiring Charters, ZIP Guards & Proprietary California/US Legal Suite"
git tag -f v3.1.0
git push -f -u origin main --tags
echo "Done! Upload RouteRequest-phpvms7-module-v3.1.0-php83-DRM-Client.zip to GitHub Releases as a FULL RELEASE (leave 'pre-release' UNCHECKED)."
