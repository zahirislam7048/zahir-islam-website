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

# Copies only the files tracked in git, never the folder itself, so the document root keeps its own
# permissions (it must stay 755 or the web server returns 404 for everything).
# Never overwrites local-seo-tool/config.php (it is not in the repo) and never deletes files on the server.
umask 022
git ls-files -z \
  | grep -z -v -E '^(\.cpanel\.yml|deploy\.sh|README\.md|\.gitignore)$' \
  | tar --null --no-recursion -T - -cf - \
  | tar --no-same-permissions -xf - -C "$DEST"

# Files 644, their folders 755 (cPanel's clone can be owner-only). The document root itself is not touched.
git ls-files | while IFS= read -r f; do
  if [ -f "$DEST/$f" ]; then chmod 644 "$DEST/$f"; fi
done
git ls-files | sed -n 's#/[^/]*$##p' | sort -u | while IFS= read -r d; do
  if [ -d "$DEST/$d" ]; then chmod 755 "$DEST/$d"; fi
done
echo "$(date '+%F %T') deployed $(git rev-parse --short HEAD)" >> "$HOME/deploy.log"
