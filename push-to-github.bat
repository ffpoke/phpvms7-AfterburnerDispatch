@echo off
echo ====================================================================
echo   RouteRequest v3.2.0-beta.1 (Flagship Pre-Release - PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.2.0-beta.1: Ed25519-Signed Encrypted DropSchedule, Subfleet Range & Runway Dispatch Guard, Portal Telemetry Insights (Server v2.2.0)"
git tag -f v3.2.0-beta.1
git push -f -u origin main --tags

echo Done! Upload RouteRequest-phpvms7-module-v3.2.0-beta.1-php83-DRM-Client.zip manually to GitHub Releases and check 'Set as a pre-release'.
pause
