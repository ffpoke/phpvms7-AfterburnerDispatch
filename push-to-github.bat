@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.0.2: Cloud DRM Encrypted Fleet Vault & Verified US Big-3 Fleets"
git tag -f v4.0.2
git push -f -u origin main --tags
pause
