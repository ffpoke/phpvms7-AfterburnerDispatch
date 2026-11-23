@echo off
echo ====================================================================
echo   RouteRequest v3.1.0 FULL STABLE RELEASE (PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v3.1.0: Full Stable Release — Unified Split Chips, SimBrief & VATSIM Bridges, Guided Travel & Admin Move Queue, Companion Installer, Queue Filters, Auto-Expiring Charters, ZIP Guards & Proprietary California/US Legal Suite"
git tag -f v3.1.0
git push -f -u origin main --tags

echo Done! Upload RouteRequest-phpvms7-module-v3.1.0-php83-DRM-Client.zip to GitHub Releases as a FULL RELEASE (leave 'pre-release' UNCHECKED).
pause
