#!/usr/bin/env bash
set -e
if [ ! -d .git ]; then
    git init -b main
fi
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/ffpoke/phpvms7-RouteRequest.git

git add .
git commit -m "Release v2.3.0 Afterburner Edition: In-Admin License Storefront, 1-Click Free Key Claim, Square & PayPal Checkout"
git tag -f v2.3.0
git push -f -u origin main --tags
echo "Done! RouteRequest v2.3.0 and tag v2.3.0 are now live on ffpoke/phpvms7-RouteRequest!"
