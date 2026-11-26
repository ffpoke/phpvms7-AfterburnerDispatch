@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1-beta.4: Verified Korea & Taiwan Flagship Fleets (KAL, EVA, CAL, SJX — 120 Operators / 88 Verified)"
git tag -f v4.1.1-beta.4
git push -f -u origin main --tags
pause
