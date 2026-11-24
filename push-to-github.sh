#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.0.1: 104 Real-World Fleet & Cabin Presets Expansion"
git tag -f v4.0.1
git push -f -u origin main --tags
