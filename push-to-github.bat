@echo off
echo ====================================================================
echo   RouteRequest v3.0.7 Great-Circle Map Cache Fix (PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v3.0.7: Fix Great-Circle Map & Admin Auto-Est Cache Coordinate Lookup (getAirportById)"
git tag -f v3.0.7
git push -f -u origin main --tags

echo Done! RouteRequest v3.0.7 is now live on ffpoke/phpvms7-RouteRequest!
pause
