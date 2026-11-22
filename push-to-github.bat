@echo off
echo ====================================================================
echo   RouteRequest v3.1.0-beta.1 (Flagship Pre-Release - PHP 8.3.36+)
echo   Repository: https://github.com/ffpoke/phpvms7-RouteRequest.git
echo ====================================================================

if not exist .git (
    git init -b main
)
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Pre-Release v3.1.0-beta.1: PaxStudio Guided Travel & Admin Move Queue, Companion Module Installer (DisposableBasic & ManualPirepControl), Revert to Stable & Standalone .zip Updater"
git tag -f v3.1.0-beta.1
git push -f -u origin main --tags

echo Done! Remember to upload the smaller ZIP manually on GitHub Releases and check 'Set as a pre-release'!
pause
