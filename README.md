# Mini Assessment — Headless WordPress + React SPA

This repository contains one installable WordPress plugin bundling the REST API and
production React SPA, plus the React source and supporting delivery documentation.

## Repository structure

```text
plugin-assessment/   WordPress plugin source
frontend/            React SPA source
docs/api-specs/      REST API contract
docs/architecture/   System design notes
docs/business/       Detailed Vietnamese requirement analysis
```

## Requirements

- WordPress 6.4 or newer and PHP 8.1 or newer.
- MySQL 5.7+/MariaDB equivalent; InnoDB is recommended.
- Node.js 20 or newer and npm.

## Install the WordPress plugin

1. Copy `plugin-assessment` to `wp-content/plugins/`, or ZIP that directory and upload
   it from **Plugins → Add New → Upload Plugin**.
2. Activate **Mini Assessment**.
3. Activation creates three prefixed custom tables and assigns assessment
   capabilities to the administrator role.
4. Use pretty permalinks so the REST routes are available normally.

Deactivation retains business data. Uninstall deletion is disabled by default. To
explicitly delete plugin data on uninstall, set the WordPress option
`mini_assessment_delete_data_on_uninstall` to `1` before uninstalling.

For a clean local environment, copy the root `.env.example` to `.env`, replace its
development passwords, run `docker compose up -d`, and open
<http://localhost:8090>, complete the WordPress installer and activate **Mini
Assessment**. See [LOCAL_WORDPRESS.md](docs/architecture/LOCAL_WORDPRESS.md).

## Run the React SPA

### Recommended: run inside WordPress

Create a WordPress page containing this shortcode:

```text
[mini_assessment_app]
```

The plugin loads the production React bundle and supplies the current user's REST
nonce at runtime. This is the recommended mode for authenticated create/update/delete
requests because no credential or long-lived secret is stored in JavaScript. The local
Docker environment already provides the page at <http://localhost:8090/assessment-app/>.

Authenticated administrators can create, edit and delete Assessments from the SPA,
add Questions to an Assessment, and add scored Answers to each Question. Management
controls are not rendered for anonymous visitors; server-side capabilities remain the
authoritative security boundary.

### Standalone Vite development

```bash
cd frontend
copy .env.example .env
npm install
npm run dev
```

Configure `.env` for the WordPress installation:

```dotenv
VITE_API_BASE_URL=http://localhost/wordpress/wp-json/assessment/v1
VITE_WP_NONCE=
```

GET pages work anonymously for published data. For authenticated write testing, prefer
the WordPress shortcode page. `VITE_WP_NONCE` is only a short-lived development
override; never commit it or embed an Application Password in the browser bundle.

Quality commands:

```bash
npm run lint
npm run test
npm run build
```

## Database and migration

Physical table names use `$wpdb->prefix`:

- `{prefix}assessment`
- `{prefix}assessment_questions`
- `{prefix}assessment_answers`

The plugin stores `mini_assessment_db_version` and checks it during bootstrap, so an
update does not require deactivate/reactivate. Physical foreign keys are not used for
WordPress hosting compatibility; parent existence and cascade deletion are enforced
in the application layer. Cascade deletion runs in a transaction and assumes a
transactional storage engine such as InnoDB.

## REST API

Base path: `/wp-json/assessment/v1`.

| Method | Route | Access |
| --- | --- | --- |
| GET | `/assessments` | Public, published records |
| POST | `/assessments` | Authenticated + capability |
| GET | `/assessments/{id}` | Public when published |
| PUT/PATCH | `/assessments/{id}` | Authenticated + capability |
| DELETE | `/assessments/{id}` | Authenticated + capability |
| GET | `/assessments/{id}/questions` | Follows parent visibility |
| POST | `/questions` | Authenticated + capability |
| GET | `/questions/{id}/answers` | Follows parent visibility |
| POST | `/answers` | Authenticated + capability |

See [REST_API.md](docs/api-specs/REST_API.md) for payload and error examples.

## Security decisions

- Anonymous users see only published assessments and active questions.
- Answer scores are hidden from public responses.
- Write routes distinguish unauthenticated `401` from unauthorized `403`.
- Dedicated capabilities are assigned to administrators on activation.
- WordPress validates and sanitizes every request; React validation is UX only.
- SQL uses `$wpdb` helpers and prepared statements.
- Logs omit credentials, cookies, nonces and raw request bodies.
- React renders API content as text and does not use `dangerouslySetInnerHTML`.

## Confirmed requirements and design decisions

The technical lead confirmed that the implementation must use the described
Assessment, Question and Answer tables/API, public users may view published lists,
creation requires a WordPress login, and the complete React + API solution must be
delivered in one plugin ZIP. The current package follows those decisions.

The supplied contract still does not define attempt/response tables or a separate
submit endpoint, so Answer data is stored in the required Answer table and no extra
submission schema is invented.

Other current assumptions:

- The production React SPA is compiled and embedded in the WordPress plugin; Vite
  standalone mode remains available only for development.
- Same-origin WordPress cookie authentication with REST nonce protects writes.
- `PUT` and `PATCH` both perform documented partial updates for this small API.
- Assessment deletion cascades through questions and answers.
- `score` accepts values from 0 to 100 and is stored with two decimal places.
- Data remains on deactivation and uninstall unless explicitly configured.

## Testing checklist

1. Install the plugin ZIP on a clean WordPress site.
2. Activate twice without losing data.
3. Confirm anonymous GET returns only published data.
4. Confirm unauthenticated writes return 401 and missing capability returns 403.
5. Confirm invalid payloads return 400/422 without partial writes.
6. Confirm missing parents return 404.
7. Confirm pagination and stable `sort_order, id` sorting.
8. Confirm Assessment deletion leaves no orphan records.
9. Run React lint, tests and production build.
10. Run the WordPress integration smoke test:

```bash
php wp-content/plugins/plugin-assessment/tests/integration-smoke.php
```

## Documentation

- [Detailed requirement analysis](docs/business/ASSESSMENT_TEST_ANALYSIS.md)
- [System architecture](docs/architecture/SYSTEM_ARCHITECTURE.md)
- [Local WordPress environment](docs/architecture/LOCAL_WORDPRESS.md)
- [REST API specification](docs/api-specs/REST_API.md)
- [Git workflow](docs/GIT_WORKFLOW.md)

The existing branch and Conventional Commit rules in `GIT_WORKFLOW.md` remain the
source of truth; this implementation does not reconfigure that workflow.

## AI usage disclosure

**Tool:** OpenAI Codex.

**Used for:** requirement analysis, initial implementation scaffolding, security/code
review, test-case suggestions and documentation structure.

**Example prompt:**

> Analyze the required WordPress REST routes for permissions, validation, SQL safety
> and application-level referential integrity.

**Manually reviewed and adjusted:** the candidate compared the implementation with
the confirmed API/database contract; reviewed capability checks, sanitization,
prepared SQL, public visibility and cascade deletion; corrected the Git policy for
GitHub squash references; and manually ran PHP lint, integration smoke tests, ESLint,
Vitest and the production build on WordPress/PHP 8.3 before delivery. AI output was
not accepted as the final result without review and verification.
