#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.0-beta.4: Multi-Sim Scenery & Reviews, SkyAdventures Bridge, 30/90/180d Analytics & Host-Locked Transfers"
git tag -f v4.1.0-beta.4
git push -f -u origin main --tags
