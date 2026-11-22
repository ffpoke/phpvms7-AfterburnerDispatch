@echo off
echo ====================================================================
echo   RouteRequest v3.1.0-beta.3 (Flagship Pre-Release - PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.3: Restore v3.0.4 Native Theme Frontend Look & Font Inheritance while keeping v3.0.7 Map Fix & Full v3.1.0 Beta Stack"
git tag -f v3.1.0-beta.3
git push -f -u origin main --tags

echo Done! Upload the smaller DRM-Client ZIP manually to GitHub Releases and check 'Set as a pre-release'.
pause
