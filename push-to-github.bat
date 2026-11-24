@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Pre-Release v4.0.0-beta.1: Afterburner Dispatch Suite Rebrand (Paired with AfterburnerServer v3.0.0)"
git tag -f v4.0.0-beta.1
git push -f -u origin main --tags
pause
