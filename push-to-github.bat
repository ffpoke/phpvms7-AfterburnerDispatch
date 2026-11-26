@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.0-beta.6: Verified Lufthansa Group Fleets (DLH, VLH, GEC, SWR, AUA — 112 Operators)"
git tag -f v4.1.0-beta.6
git push -f -u origin main --tags
pause
