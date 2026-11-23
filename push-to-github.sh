#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.4: Unified Split Flight Chips + Collapsible Multi-Dest (Theme Font), SimBrief & VATSIM Bridges, Admin Queue Filters & DropSchedule 2026.11.21-v3.1.0"
git tag -f v3.1.0-beta.4
git push -f -u origin main --tags
echo "Done! Upload the smaller DRM-Client ZIP manually to GitHub Releases and check 'Set as a pre-release'."
