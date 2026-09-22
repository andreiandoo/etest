# User layer

M5 turns completed test attempts into a persistent learning profile without paywalling test content.

## Private routes

All user-layer routes require authentication and are covered by the NoIndex middleware:

    /dashboard
    /history
    /leaderboard
    /attempts/{attempt}/results

They emit:

    X-Robots-Tag: noindex, nofollow

## Source of truth

Attempt and answer data remain the source of truth for:

- test history;
- average and best scores;
- vertical progress;
- weak areas;
- time spent in submitted tests.

The application does not copy these aggregates into a large secondary progress table.

Small gamification/privacy state is persisted separately.

## user_stats

One row per user:

- xp
- completed_attempts
- current_streak
- longest_streak
- last_activity_date
- leaderboard_opt_in
- leaderboard_display_name

completed_attempts in user_stats means reward-eligible submitted attempts, not automatically expired attempts.

## XP ledger

Every rewarded completion creates one user_xp_events row with:

    reason = attempt_completed

The database uniqueness rule on test_attempt_id + reason, together with row locking during attempt finalization, prevents duplicate rewards.

Expired attempts do not award XP or streak progress.

### XP formula

For a submitted attempt:

    20 base XP
    + round(percentage / 2)
    + 15 XP if an explicit passing threshold exists and was reached
    + 25 XP for a 100% score

Examples:

- 40% without threshold: 40 XP
- 80% with a passed threshold: 75 XP
- 100% with a passed threshold: 110 XP

The formula is deliberately simple and can later become configuration-driven without changing the ledger schema.

## Levels

Levels use increasing quadratic thresholds.

Given XP:

    level = floor(sqrt(xp / 100)) + 1

Threshold examples:

- level 1: 0 XP
- level 2: 100 XP
- level 3: 400 XP
- level 4: 900 XP
- level 5: 1600 XP

The dashboard shows progress from the current threshold to the next threshold.

## Streaks

A streak advances when the user finishes at least one reward-eligible test on consecutive local calendar days.

The user's configured timezone is used; the default remains Europe/Bucharest.

Rules:

- first qualifying day -> streak 1;
- another completion on the same local day -> streak unchanged;
- next local calendar day -> +1;
- any larger gap -> reset to 1;
- expired attempts -> no streak change.

The longest streak is retained.

## Achievements

Achievements are persisted idempotently by user_id + achievement_key.

Current catalog:

- first_test — first completed test;
- five_tests — 5 completed tests;
- twenty_tests — 20 completed tests;
- perfect_score — 100% on a test;
- streak_3 — 3 consecutive days;
- streak_7 — 7 consecutive days;
- xp_100 — at least 100 XP;
- explorer_3 — completed tests in at least 3 verticals.

Achievement definitions are application code, while unlock state is persistent.

## Progress

The dashboard exposes:

- reward-eligible completed test count;
- average percentage;
- best percentage;
- total duration in submitted tests;
- recent submitted attempts;
- XP and level;
- current and longest streak;
- progress grouped by vertical;
- weak areas;
- favorites;
- achievements.

Expired attempts remain visible in history but are excluded from headline progress metrics.

## Weak areas

Weak areas are derived from answer-level performance.

For each answer, the taxonomy node is chosen from:

1. the source question taxonomy node;
2. otherwise the test taxonomy node.

A taxonomy node appears as a weak-area candidate only after at least 2 answered questions.

Candidates are sorted by ascending answer accuracy, then by answered volume.

Expired attempts may still contribute answered questions to weak-area analysis because those answers can be useful learning evidence even though the attempt earns no gamification reward.

## History

/history lists the authenticated user's completed attempts and supports filtering by vertical.

Expired attempts are labeled explicitly.

Attempt detail pages remain ownership protected.

## Favorites

Authenticated users can save or remove a published test from its public presentation page.

favorite_tests has a unique constraint on:

    user_id + test_id

The dashboard shows the most recently saved tests and links back to their canonical public URLs.

## Leaderboard privacy

The leaderboard is explicitly opt-in.

Default:

    leaderboard_opt_in = false

A user does not appear until they save an explicit opt-in preference.

When opting in, they can choose a public leaderboard display name that is independent from the account name.

The leaderboard exposes only:

- public display name;
- XP;
- level;
- completed reward-eligible attempts;
- current streak.

Email address and other account fields are never exposed.

Ranking currently sorts by:

1. XP descending;
2. completed attempts descending;
3. user ID for deterministic ties.

The UI returns the first 50 opted-in users.

## Concurrency

AttemptEngine locks the test_attempt row with SELECT ... FOR UPDATE before marking it complete.

This guarantees that two concurrent finish requests cannot independently transition the same in-progress attempt and issue duplicate rewards.

The XP ledger provides a second idempotency boundary.
