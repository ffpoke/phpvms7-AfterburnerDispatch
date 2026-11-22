#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.1: PaxStudio Guided Travel & Admin Move Queue, Companion Module Installer (DisposableBasic & ManualPirepControl), Revert to Stable & Standalone .zip Updater"
git tag -f v3.1.0-beta.1
git push -f -u origin main --tags
echo "Done! Remember to upload the smaller ZIP manually on GitHub Releases and check 'Set as a pre-release'!"
