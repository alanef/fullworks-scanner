# Tests Passed: 1.4.1

**Date:** 2026-10-23T08:04:50Z
**Commit:** ab0c7e57d7429eda5470beb3f0528be4e2a83b1a
**Tracked changes at run time:** 0

| Gate | Result |
|---|---|
| composer install | pass |
| composer validate --strict | pass |
| composer run check | pass |
| version consistency | pass |
| build (composer run build) | pass |
| Plugin Check | not run locally (no plugin-check script); CI runs it on the built zip |
| PHPUnit (npm test) | pass |

PHPUnit summaries:

    OK (68 tests, 200 assertions)

Full output: tests.log (not committed).
