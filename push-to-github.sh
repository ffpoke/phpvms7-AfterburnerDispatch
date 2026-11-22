#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.2: Dual-Layer ZIP Header Edition/Downgrade Guard, Strict DRM/Standalone Separation, Move Request Discord Alerts & Live DisposableBasic Rate Readout"
git tag -f v3.1.0-beta.2
git push -f -u origin main --tags
echo "Done! Upload the smaller DRM-Client ZIP manually to GitHub Releases and check 'Set as a pre-release'."
