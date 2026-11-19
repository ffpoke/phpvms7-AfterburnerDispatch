@echo off
echo ====================================================================
echo   RouteRequest v2.4.0 Afterburner Edition (PHP 8.3+) - Push to GitHub
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo   Made by Alexander Leard of Fantasy Air VA ^& Regal Artistic Avenue
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.4.0 Afterburner Edition: PHP 8.3+ Strict Optimization & Security (Dropped PHP 8.1/8.2)"
git tag -f v2.4.0
git push -f -u origin main --tags

echo.
echo Done! RouteRequest v2.4.0 (PHP 8.3+) and tag v2.4.0 are now live on ffpoke/phpvms7-RouteRequest!
pause
