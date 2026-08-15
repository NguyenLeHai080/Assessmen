# Local WordPress test environment

This project provides a disposable WordPress environment using Docker Compose.

## Start

```bash
copy .env.example .env
docker compose up -d
```

Replace both placeholder database passwords in the root `.env` before starting a new
environment. The real `.env` is ignored by Git.

Open <http://localhost:8090> and complete the WordPress installation screen. Suggested
development values:

- Site title: `Mini Assessment Test`
- Username: choose a local administrator username
- Password: choose a local-only password
- Search engine visibility: disabled for this local environment

Then open **Plugins** and activate **Mini Assessment**. The local plugin directory is
mounted directly into WordPress, so source edits are immediately visible. Create a
page containing `[mini_assessment_app]`, or use the preconfigured local page at
<http://localhost:8090/assessment-app/>.

## Frontend

Create `frontend/.env` from `.env.example` and use:

```dotenv
VITE_API_BASE_URL=http://localhost:8090/wp-json/assessment/v1
VITE_WP_NONCE=
```

Public GET pages work without a nonce. For authenticated write requests, log in at
<http://localhost:8090/wp-admin/> and open the shortcode page in the same browser. The
plugin supplies the session REST nonce automatically. Do not place long-lived
credentials in the React bundle.

## Commands

```bash
docker compose ps
docker compose logs wordpress
docker compose logs database
docker compose stop
docker compose down
```

`docker compose down` stops and removes containers but keeps the named database volume.
Use `docker compose down --volumes` only when intentionally deleting the local test
database. The generated `wp/` directory and database storage are ignored by Git.
