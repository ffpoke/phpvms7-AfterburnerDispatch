@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.0.4: Verified Canadian Fleet & Sun Country Expansion (SCX, ACA, JZA, ROU, WJA, WEN, POE, TSC)"
git tag -f v4.0.4
git push -f -u origin main --tags
pause
