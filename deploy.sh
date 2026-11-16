#!/bin/bash
# Copies the website from this repository into the zahirislam.com document root.
#   Cron (every 5 minutes):  /bin/bash $HOME/repositories/zahir-islam-website/deploy.sh
#   cPanel "Deploy HEAD Commit" runs it with --copy-only (via .cpanel.yml).
set -euo pipefail
REPO="$(cd "$(dirname "$0")" && pwd)"
DEST="${DEPLOY_DIR:-$HOME/zahirislam.com}"   # document root of zahirislam.com (cPanel > Domains)
cd "$REPO"

if [ "${1:-}" != "--copy-only" ]; then
  git fetch -q origin main
  if [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ]; then exit 0; fi   # nothing new
  git reset -q --hard origin/main
fi

# Never overwrites local-seo-tool/config.php (it is not in the repo) and never deletes files on the server.
umask 022
tar -cf - \
  --exclude='./.git' --exclude='./.cpanel.yml' --exclude='./deploy.sh' --exclude='./README.md' --exclude='./.gitignore' \
  --exclude='./local-seo-tool/config.php' \
  . | tar --no-same-permissions -xf - -C "$DEST"

# The web server must be able to read what was copied. cPanel's clone can be owner-only (600/700),
# which makes every page return 404, so set normal web permissions on exactly the files from the repo.
git ls-files | while IFS= read -r f; do
  if [ -f "$DEST/$f" ]; then chmod 644 "$DEST/$f"; fi
done
git ls-files | sed -n 's#/[^/]*$##p' | sort -u | while IFS= read -r d; do
  if [ -d "$DEST/$d" ]; then chmod 755 "$DEST/$d"; fi
done
echo "$(date '+%F %T') deployed $(git rev-parse --short HEAD)" >> "$HOME/deploy.log"
