@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1: 100% Verified Global Fleet Vault (155 Operators: Commercial, Cargo, BizJet & Worldwide Bush) + Admin Storefront & Timetable Polish"
git tag -f v4.1.1
git push -f -u origin main --tags
pause
