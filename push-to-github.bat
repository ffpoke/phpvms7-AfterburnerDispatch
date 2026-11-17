@echo off
echo ====================================================================
echo   RouteRequest v2.1.0 Afterburner Edition - Push to GitHub
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo   Made by Alexander Leard of Fantasy Air VA ^& Regal Artistic Avenue
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.1.0 Afterburner Edition: GitHub 1-Click Auto-Updater, Creator Attribution, and Cloud DRM Client"
git tag -f v2.1.0
git push -u origin main --tags

echo.
echo Done! RouteRequest v2.1.0 and tag v2.1.0 are now live on ffpoke/phpvms7-RouteRequest!
pause
