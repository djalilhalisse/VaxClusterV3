# VaxCluster (modernized)

Drop-in replacement for your `covid-prediction` project. Same database (`db sql/covid.sql`), same Python models, same URLs.

1. Copy the folder into `htdocs` and import `db sql/covid.sql` as before.
2. Open `configuration/app.php` and set `PYTHON_PATH` (Windows example is pre-filled).
3. Open `index.php`. Sample logins from the SQL file still work (plain text or hashed).

Layout: `assets/` (css, js), `includes/bootstrap.php` (shared layout + helpers), pages in the root, `predictions/` (models), `configuration/`.
Removed: the old Bootstrap/jQuery template (css, js, scss, fonts) and unused images. `models/` is an unused older copy of the prediction scripts and was left alone.

## Online deployment (Render + TiDB)
This folder already contains the Docker files (`Dockerfile`, `docker/`, `requirements.txt`). Follow `DEPLOY.md`. For TiDB use `db sql/covid_tidb.sql`; for XAMPP keep using `db sql/covid.sql`.
Database settings come from the `DB_*` environment variables on Render; without them the app uses XAMPP's local MySQL as before.
