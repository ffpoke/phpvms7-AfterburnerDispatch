@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1-beta.2: Verified Middle East & Africa Fleets + Wet-Lease Cleanup (FDB, RXI, ELY, MSR, RAM, RXP, ETH — 120 Operators / 79 Verified)"
git tag -f v4.1.1-beta.2
git push -f -u origin main --tags
pause
