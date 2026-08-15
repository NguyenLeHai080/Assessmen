# Mini Assessment REST API

Base path: `/wp-json/assessment/v1`

## Authentication

GET endpoints are public with visibility restrictions. POST, PATCH, PUT and DELETE
use WordPress cookie authentication plus `X-WP-Nonce` and dedicated capabilities.
The production React shortcode injects this short-lived nonce at runtime for the
current WordPress session.

## Endpoints

| Method | Path | Access |
| --- | --- | --- |
| GET | `/assessments?page=1&per_page=10` | Public; published only |
| POST | `/assessments` | `edit_assessments` |
| GET | `/assessments/{id}` | Public when published |
| PUT/PATCH | `/assessments/{id}` | `edit_assessments` |
| DELETE | `/assessments/{id}` | `delete_assessments` |
| GET | `/assessments/{id}/questions` | Follows parent visibility |
| POST | `/questions` | `edit_assessments` |
| GET | `/questions/{id}/answers` | Follows parent visibility; public score hidden |
| POST | `/answers` | `edit_assessments` |

## Examples

### List assessments

```http
GET /wp-json/assessment/v1/assessments?page=1&per_page=10
```

```json
{
  "data": [],
  "meta": { "page": 1, "per_page": 10, "total": 0, "total_pages": 0 }
}
```

### Create an assessment

```http
POST /wp-json/assessment/v1/assessments
Content-Type: application/json
X-WP-Nonce: <nonce>

{
  "title": "WordPress Fundamentals",
  "description": "A junior-level knowledge check",
  "status": "draft"
}
```

Success: `201 Created`. Validation failures use `422 Unprocessable Entity`.

### Create a question

```json
{
  "assessment_id": 1,
  "content": "What does a WordPress action do?",
  "sort_order": 1,
  "status": "active"
}
```

### Create an answer

```json
{
  "question_id": 1,
  "content": "It executes registered callbacks at a hook.",
  "score": 1,
  "sort_order": 1
}
```

## Error format

```json
{
  "code": "assessment_validation_failed",
  "message": "The request data is invalid.",
  "data": {
    "status": 422,
    "fields": { "title": "Title is required." }
  }
}
```

Expected status groups: `401`, `403`, `404`, `422`, and `500`.
