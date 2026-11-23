#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.2.0-beta.2: Synchronized Flagship Beta (Paired with Encrypted Standalone .rrpkg/.rrx Milestone & Server v2.2.0)"
git tag -f v3.2.0-beta.2
git push -f -u origin main --tags
echo "Done! Upload RouteRequest-phpvms7-module-v3.2.0-beta.2-php83-DRM-Client.zip manually to GitHub Releases and check 'Set as a pre-release'."
