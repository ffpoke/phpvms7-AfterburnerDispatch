@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-AfterburnerDispatch.git
git add .
git commit -m "Release v4.1.3: Per-Admin GitHub Personal Access Token (Classic) for Private Companion Auto-Updates"
git tag -f v4.1.3
git push -f -u origin main --tags
pause
