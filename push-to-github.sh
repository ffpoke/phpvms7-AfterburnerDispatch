#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git
git add .
git commit -m "Pre-Release v3.2.0-beta.5: Targeted Performance Optimization & Code Cleanup"
git tag -f v3.2.0-beta.5
git push -f -u origin main --tags
