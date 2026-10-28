# mooninn.pl

Sklep wydawnictwa Mooninn na PrestaShop 9.2. Live: https://mooninn.pl (na razie w trybie przerwy technicznej).

## Co jest w repozytorium

- `theme/` - motyw `mooninn` (motyw potomny Hummingbird). Tu zmieniamy wygląd: `theme/assets/css/custom.css`, a szablony z Hummingbird kopiujemy do `theme/templates/...` tylko wtedy, gdy trzeba je zmienić.
- `tools/demo-catalog/` - skrypt i okładki demonstracyjnego katalogu (5 wymyślonych tomów).

Produkty, kategorie, zamówienia i strony CMS są w panelu PrestaShop, nie w repozytorium.

## Deploy

Push do `master` ze zmianą w `theme/` wysyła motyw do `public_html/themes/mooninn` przez GitHub Actions (`.github/workflows/deploy.yml`, rsync po SSH) i sprawdza, że na serwerze jest wersja z tego commita. Reszty sklepu deploy nie dotyka.

Sekret repozytorium: `DEPLOY_SSH_KEY` (prywatny klucz deploy, publiczny jest w `authorized_keys` na serwerze). Nic poufnego nie trafia do repozytorium.
