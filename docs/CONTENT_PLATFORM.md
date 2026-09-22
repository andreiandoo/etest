# Content platform

## Admin access

The admin interface lives under /admin and requires both authentication and the access-admin Gate.

The first administrator is granted from CLI:

    php artisan admin:grant admin@example.com

The is_admin property is cast to boolean but intentionally excluded from User::$fillable.

## Admin modules

- /admin — dashboard and content-health counts
- /admin/verticals — vertical management and SEO metadata
- /admin/taxonomy — hierarchical domain/exam/certification/subject/chapter/topic nodes
- /admin/tests — test settings and question assignment
- /admin/questions — question bank and question authoring
- /admin/imports — bulk imports and import history
- /admin/review — editorial review queue
- /admin/quality — source verification and user-reported issues

## Editorial workflow

PublicationStatus is used for questions and tests.

Normal flow:

    draft -> review -> published

An administrator can also return content from review to draft.

Publishing records:

- reviewed_by
- reviewed_at
- published_by

Tests additionally receive published_at.

A test's public query only returns published content whose published_at is not in the future.

## Source quality

Questions support:

- source_label
- source_url
- source_checked_at

The quality screen exposes:

- open user reports;
- sources never checked or older than six months;
- questions without a source.

Marking a source as checked updates source_checked_at to the current date.

## Bulk imports

Supported formats:

- CSV
- JSON
- XLS
- XLSX

Uploads are stored on Laravel's local/private disk, a ContentImport row is created, and ProcessContentImport is dispatched to the queue.

Production therefore requires a running Laravel queue worker.

Imports are processed row-by-row. Invalid rows are isolated and recorded; valid rows in the same file continue processing.

At most the first 100 row errors are persisted on the import record.

### Columns / object properties

Supported fields:

| Field | Required | Notes |
| --- | --- | --- |
| source_key | no | Stable external ID inside a vertical. Enables updates instead of duplicates. |
| taxonomy_slug | no | Must exist in the selected vertical. |
| type | yes | single_choice, multiple_choice, true_false, numeric, short_text, matching, ordering |
| prompt | yes | Question text |
| explanation | no | Explanation shown according to test settings |
| difficulty | no | Integer 1-5, default 3 |
| source_label | no | Human-readable source |
| source_url | no | Source link |
| source_checked_at | no | Date accepted by Laravel's date cast |
| answer_config | no | JSON in CSV/XLS/XLSX; native object/array in JSON |
| options | no | JSON in CSV/XLS/XLSX; native array in JSON |

Imported questions are always saved as draft. Import files cannot publish content directly.

### CSV example

    source_key,type,prompt,difficulty,answer_config,options
    auto-b-001,single_choice,"Care este răspunsul corect?",2,"{}","[{""content"":""A"",""is_correct"":true},{""content"":""B"",""is_correct"":false}]"

### JSON example

    [
      {
        "source_key": "auto-b-001",
        "taxonomy_slug": "categoria-b",
        "type": "single_choice",
        "prompt": "Care este răspunsul corect?",
        "difficulty": 2,
        "answer_config": {},
        "options": [
          {"content": "A", "is_correct": true},
          {"content": "B", "is_correct": false}
        ]
      }
    ]

A JSON document may also wrap rows under a top-level questions property.

## Stable updates

When source_key is provided, QuestionImporter searches by:

    vertical_id + source_key

A matching question is updated in place. Its status is reset to draft and prior review/publication metadata is cleared.

If options are supplied, existing options are replaced by the imported set.

Without source_key, every imported row creates a new question.
