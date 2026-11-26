@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.1-beta.9: Verified Global Cargo Finale + ch-aviation Audit Sweep (GTI, CKS, CJT, SRR, BCS, BMA — 122 Operators / 112 Verified)"
git tag -f v4.1.1-beta.9
git push -f -u origin main --tags
pause
