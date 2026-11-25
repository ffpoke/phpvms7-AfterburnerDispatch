#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.0.5: Seat Map Revision System, Update Banner & seatmaps.com Fleet Refinements"
git tag -f v4.0.5
git push -f -u origin main --tags
