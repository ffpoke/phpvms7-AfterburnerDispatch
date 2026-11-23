@echo off
echo ====================================================================
echo   RouteRequest v3.1.0-beta.5 (Flagship Pre-Release - PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.5: Proprietary Source-Available License, SaaS Terms & Fail-Open Install/Activation Telemetry"
git tag -f v3.1.0-beta.5
git push -f -u origin main --tags

echo Done! Upload the smaller DRM-Client ZIP manually to GitHub Releases and check 'Set as a pre-release'.
pause
