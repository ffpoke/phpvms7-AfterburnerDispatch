@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1-beta.8: Verified Latin America Finale & Major Global Cargo Fleets (AZU, GLO, ARG, CLX, BCS, UPS, FDX — 122 Operators / 108 Verified)"
git tag -f v4.1.1-beta.8
git push -f -u origin main --tags
pause
