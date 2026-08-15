# System Architecture

## Overview

The solution uses WordPress as a headless application backend and bundles the compiled
React SPA in the same installable plugin. The React source can still run through Vite
during development.

```text
Browser / React SPA
        |
        | JSON over WordPress REST API
        | Cookie + REST nonce for write requests
        v
MA_REST_Controller
        |
        | validation, permission, response mapping
        v
MA_Repository
        |
        | $wpdb helpers and prepared queries
        v
Assessment custom tables
```

## Component responsibilities

| Component | Responsibility |
| --- | --- |
| `MA_Plugin` | Plugin bootstrap and hook registration |
| `MA_Migrator` | Schema lifecycle, version option and capabilities |
| `MA_REST_Controller` | Routes, validation, authorization and HTTP semantics |
| `MA_Repository` | Database reads/writes and cascade transaction |
| `MA_Logger` | Safe, minimal operational logging |
| React API client | Base URL, nonce, credentials and error normalization |
| `MA_Frontend` | Shortcode, compiled SPA assets and per-session REST nonce |
| React pages/components | Presentation and UI states |

## Security boundaries

- Anonymous callers can only observe published assessments and active questions.
- Answer scores are excluded from public responses.
- Write operations require authentication and a dedicated capability.
- Database values use `$wpdb` CRUD helpers or prepared statements.
- Dynamic identifiers are internal fixed values, never direct request input.
- React validation is for UX only; WordPress validates every request again.

## SPA delivery and authentication

The production SPA is built into `plugin-assessment/assets/app` and rendered using the
`[mini_assessment_app]` shortcode. WordPress generates a short-lived `wp_rest` nonce
for the current session and writes it into a small runtime settings object. The React
API client reads that object and sends `X-WP-Nonce` with same-origin cookies. Anonymous
visitors receive an empty nonce and retain read-only access to published content.

## Known scope boundary

The supplied schema and API contract do not define assessment attempts or user
submissions. This implementation therefore manages assessment definitions only.
