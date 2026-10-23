# mooninn.pl

Strona Mooninn. Live: https://mooninn.pl

## Deploy

Każdy push do `master` wysyła zawartość `public/` na hosting Namecheap (`public_html`) przez GitHub Actions (`.github/workflows/deploy.yml`, rsync po SSH).

Sekret repozytorium: `DEPLOY_SSH_KEY` (prywatny klucz deploy, publiczny jest w `authorized_keys` na serwerze). Nic poufnego nie trafia do repozytorium.

Pliki hostingu, których deploy nie rusza: `.well-known/` (certyfikat Let's Encrypt), `cgi-bin/`, `nc_assets/`, `parking-page.shtml`.
