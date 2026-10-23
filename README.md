# Zahir Islam website

Every push to `main` uploads the changed files to cPanel automatically (`.github/workflows/deploy.yml`).

## One-time setup (Settings → Secrets and variables → Actions)
Secrets:
- `FTP_SERVER` – e.g. `ftp.yourdomain.com`
- `FTP_USERNAME` – cPanel FTP account
- `FTP_PASSWORD` – its password

Optional variable `FTP_SERVER_DIR` – folder on the server, default `public_html/`.

`local-seo-tool/config.php` is never stored here; it lives only on the server.
