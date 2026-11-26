#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.0: Multi-Sim Scenery Engine, SkyAdventures Bridge, 30/90/180d Analytics & Complete European Fleet Database (119 Operators)"
git tag -f v4.1.0
git push -f -u origin main --tags
