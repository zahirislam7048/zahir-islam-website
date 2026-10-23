#!/bin/bash
# Copies the website from this repository into public_html.
#   Cron (every 5 minutes):  /bin/bash $HOME/repositories/zahir-islam-website/deploy.sh
#   cPanel "Deploy HEAD Commit" runs it with --copy-only (via .cpanel.yml).
set -euo pipefail
REPO="$(cd "$(dirname "$0")" && pwd)"
DEST="${DEPLOY_DIR:-$HOME/public_html}"
cd "$REPO"

if [ "${1:-}" != "--copy-only" ]; then
  git fetch -q origin main
  if [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ]; then exit 0; fi   # nothing new
  git reset -q --hard origin/main
fi

# Never overwrites local-seo-tool/config.php (it is not in the repo) and never deletes files on the server.
rsync -a \
  --exclude '.git' --exclude '.cpanel.yml' --exclude 'deploy.sh' --exclude 'README.md' --exclude '.gitignore' \
  --exclude 'local-seo-tool/config.php' \
  ./ "$DEST/"
echo "$(date '+%F %T') deployed $(git rev-parse --short HEAD)" >> "$HOME/deploy.log"
