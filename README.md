# Zahir Islam website

Hosted on Namecheap cPanel. cPanel **Git Version Control** keeps a copy of this repo at
`~/repositories/zahir-islam-website`, and a cron job runs `deploy.sh` every 5 minutes:
when `main` has new commits it pulls them and copies the site into `~/public_html`.

- Manual deploy: cPanel → Git Version Control → Manage → Pull or Deploy → Update from Remote → Deploy HEAD Commit.
- Log: `~/deploy.log`
- `local-seo-tool/config.php` is never stored here; it lives only on the server.
