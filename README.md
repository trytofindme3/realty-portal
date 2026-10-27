# RealtyPortal

Jednoduchý portál realitních inzerátů v čistém PHP 8 + PostgreSQL (bez frameworku, PDO).

## Funkce
- výpis inzerátů s filtrem (město, prodej/pronájem, max. cena)
- detail inzerátu
- přihlášení / odhlášení (sessions, `password_hash`)
- přidání inzerátu s nahráním fotky (jen pro přihlášené)

## Bezpečnost
- PDO + prepared statements (ochrana proti SQL injection)
- escapování výstupu `htmlspecialchars` (XSS)
- CSRF token ve formulářích
- kontrola MIME typu a velikosti nahraných souborů, náhodné názvy souborů

## Spuštění
1. `createdb -U postgres realty` a `psql -U postgres -d realty -f sql/schema.sql` (nebo Query Tool v pgAdminu)
   – v `php.ini` musí být zapnuté rozšíření `pdo_pgsql`
2. zkopírovat `src/config.example.php` → `src/config.php` a doplnit přístupy
3. `php -S localhost:8000 -t public`
4. otevřít http://localhost:8000 — demo účet `demo@example.cz` / `demo1234`

## Struktura
```
public/   – stránky dostupné z prohlížeče (index, listing, login, create…)
src/      – připojení k DB, pomocné funkce, layout (mimo webroot)
sql/      – schéma databáze a testovací data
```
