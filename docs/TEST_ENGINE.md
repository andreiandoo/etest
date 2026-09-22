# Test engine contracts

This document defines the question snapshot and answer_config conventions used by M2.

## Snapshot invariants

When an attempt starts, each selected source question is copied into attempt_questions.question_snapshot.

The snapshot freezes:

- question type
- prompt
- explanation
- difficulty
- source metadata
- answer_config
- answer options
- correct-option flags
- randomized option order

The attempt seed is persisted. Question and option ordering are derived deterministically from that seed, so a resumed attempt is stable.

## Canonical answer JSON

### single_choice

```json
{"selected":[20]}
```

### multiple_choice

```json
{"selected":[1,2]}
```

### true_false

```json
{"value":true}
```

### numeric

```json
{"value":9.81}
```

### short_text

```json
{"value":"București"}
```

### matching

```json
{"pairs":{"a":"1","b":"2"}}
```

### ordering

```json
{"items":["a","b","c"]}
```

## answer_config

### Single choice

Correctness is defined by answer_options.is_correct.

No extra config is required.

### Multiple choice

Correctness is defined by answer_options.is_correct.

Optional:

```json
{
  "partial_credit": true,
  "penalty_per_wrong": 0.5
}
```

If the selected set exactly equals the correct set, full points are awarded.

When partial_credit is enabled, the correct-selection fraction is multiplied by the question points. penalty_per_wrong is then subtracted once for each selected incorrect option. The result is clamped between zero and the question maximum.

### True / false

```json
{
  "correct_boolean": true
}
```

### Numeric

```json
{
  "correct_value": 9.81,
  "tolerance": 0.02
}
```

The numeric answer is accepted when absolute(actual - expected) <= tolerance.

### Short text

```json
{
  "accepted_answers": ["București", "Bucuresti"],
  "case_sensitive": false
}
```

Whitespace is normalized and surrounding whitespace is ignored.

### Matching

```json
{
  "left_items": [
    {"id":"a","label":"Element A"},
    {"id":"b","label":"Element B"}
  ],
  "right_items": [
    {"id":"1","label":"Răspuns 1"},
    {"id":"2","label":"Răspuns 2"}
  ],
  "correct_pairs": {
    "a":"1",
    "b":"2"
  },
  "partial_credit": true
}
```

With partial_credit enabled, points are proportional to the number of correctly matched pairs.

### Ordering

```json
{
  "items": [
    {"id":"a","label":"Primul element"},
    {"id":"b","label":"Al doilea element"},
    {"id":"c","label":"Al treilea element"}
  ],
  "correct_order":["a","b","c"],
  "partial_credit": true
}
```

With partial_credit enabled, points are proportional to items placed in the correct position.

## Attempt lifecycle

1. User opens /{vertical}/{test}/start.
2. Authentication is required.
3. AttemptBuilder resumes the newest in-progress attempt for that user and test or creates a new one.
4. The builder creates immutable attempt-question snapshots.
5. Each submitted answer is normalized, scored and upserted.
6. Aggregate attempt score and percentage are refreshed after every answer.
7. A timed attempt is completed automatically after expires_at.
8. Manual completion stores completion_reason=submitted.
9. Expiry stores completion_reason=expired.
10. The results page is visible only to the attempt owner.

## Analytics

QuestionStatistic is derived data and can be rebuilt from attempt_answers + attempt_questions.

Metrics currently maintained:

- distinct attempts
- answered count
- correct count
- correct rate
- total awarded points
- total possible points
- average answer duration
- last answered timestamp

Because statistics are rebuilt rather than incremented blindly, editing/re-submitting the same attempt answer does not double-count it.

## Quality reports

Authenticated users can report the current question with one of:

- incorrect
- outdated
- ambiguous
- typo
- other

Each report stores the source question, attempt-question snapshot reference, user, reason, optional message and contextual attempt/test identifiers.
