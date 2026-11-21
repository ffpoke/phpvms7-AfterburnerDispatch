#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v3.0.3: Immutable Baked-In DropSchedule with Server-Signed UTC Auto-Unlock & Read-Only Admin Timetable"
git tag -f v3.0.3
git push -f -u origin main --tags
echo "Done! RouteRequest v3.0.3 is now live on ffpoke/phpvms7-RouteRequest!"
