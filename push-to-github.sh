#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1-beta.3: Verified East & Southeast Asia Flagship Fleets (SIA, TGW, CPA, ANA, JAL — 120 Operators / 84 Verified)"
git tag -f v4.1.1-beta.3
git push -f -u origin main --tags
