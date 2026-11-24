@echo off
if not exist .git ( git init -b main )
git remote remove origin 2>nul
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git
git add .
git commit -m "Pre-Release v3.2.0-beta.4: Duplicate Route Guard, Dispatcher Canned Notes, Native phpVMS Cron Force-Update & Staggered DropSchedule"
git tag -f v3.2.0-beta.4
git push -f -u origin main --tags
pause
