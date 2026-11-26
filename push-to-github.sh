#!/usr/bin/env bash
set -e
[ ! -d .git ] && git init -b main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.0-beta.7: Verified Brussels, ITA & Remaining IAG Group Fleets (BEL, ITY, IBE, IBS, ANE, EIN, EAI, VLG — 115 Operators)"
git tag -f v4.1.0-beta.7
git push -f -u origin main --tags
