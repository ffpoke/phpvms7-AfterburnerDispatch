@echo off
echo ====================================================================
echo   RouteRequest v2.8.0 Afterburner Edition (PHP 8.3+) - Push to GitHub
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.8.0 Afterburner Edition (PHP 8.3+): Bulk Subfleet-to-Route Auto-Sync Engine with Dry-Run Preview & Custom Airline Restrictions"
git tag -f v2.8.0
git push -f -u origin main --tags

echo Done! RouteRequest v2.8.0 (PHP 8.3+) is now live on ffpoke/phpvms7-RouteRequest!
pause
