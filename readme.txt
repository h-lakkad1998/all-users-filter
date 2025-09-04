=== All Users Filter ===
Contributors: hlakkad1998, akshaykungiri, visualsbyridhi
Tags: filter-users, users-filter, wp-users-filter, users-export, export-user
Donate link:
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 7.4
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 1.0
Version: 1.0

Filter, sort, and export WordPress users to CSV using powerful UI-driven meta queries (roles, dates, numeric ranges, regex, and more).

== Description ==
Plugin for filtering, sorting, and exporting users.

This plugin allows you to filter, sort, and export users in CSV format. You can filter users by multiple parameters, such as date, role, meta key-value, and registration date.

Developed by Hardik Patel (also known as Hardik Lakkad).
More info: https://www.linkedin.com/in/hardik-patel-lakkad-097b12147/

== Installation ==
1. Go to the Plugins page.
2. Click the Add New button.
3. Search for the plugin name "All Users Filter".
4. Click the Install Now button.
5. Click the Activate button.

The plugin is now installed. Note: Initially only administrators can use this plugin.

== Usage ==
Use the Users → All Users Filter screen to compose conditions. Choose a meta key, operator, type, value, and (optionally) group relation to build complex queries without code. Export matched users to CSV.

=== Admin UI → WP_Meta_Query Mapping ===
* meta_key → `key`
* operator → `compare`
* Type → `type`
* meta_value → `value`
* Group relation (if available) → `relation: AND | OR`

=== Meta keys used in tests (wp_usermeta.meta_key) ===
* `job_description` (string)
* `job_designation` (string; single value OR a pipe/comma-separated list)
* `age_in_years` (integer-like string)
* `joining_date` (stored as `YYYY-MM-DD` or full `YYYY-MM-DD HH:MM:SS`)
* `monthly_salary` (numeric, e.g., `9000`)

If your dataset stores dates in a different format (e.g., `DD/MM/YYYY`) or salaries with commas (e.g., `9,000`), see **Edge Cases** below.

== Test Cases ==
The following UI test cases validate common scenarios and guardrails.

=== 1) Single-condition functional tests ===

1A. Exact match on text  
Intent: Users with `job_description` exactly “Lorem ipsum test”.  
UI:
- meta_key: `job_description`
- operator: `=`
- Type: `CHAR`
- meta_value: `Lorem ipsum test`  
Expected: Exact match; typically case-insensitive under default collations; respects spaces.

1B. Substring match (LIKE)  
Intent: `job_description` contains “ipsum”.  
UI:
- meta_key: `job_description`
- operator: `LIKE`
- Type: `CHAR`
- meta_value: `ipsum`  
Expected: Returns rows containing “ipsum”. `%ipsum%` also works but not required.

1C. Case-sensitive regex match  
Intent: `job_description` starts with `Lorem` (case-sensitive).  
UI:
- meta_key: `job_description`
- operator: `REGEXP`
- Type: `BINARY`
- meta_value: `^Lorem`  
Expected: Matches only values beginning with uppercase `Lorem`.

1D. Negative regex  
Intent: `job_description` does NOT mention `ipsum` as a whole word.  
UI:
- meta_key: `job_description`
- operator: `NOT REGEXP`
- Type: `CHAR`
- meta_value: `(^|[^A-Za-z])ipsum([^A-Za-z]|$)`  
Expected: Excludes any row containing `ipsum` as a separate word.

=== 2) Multi-value (roles) tests for `job_designation` ===
Assume values include: UI/UX Designer | QA Engineer | DevOps Engineer | Project Manager | Business Analyst | HR Manager | Data Analyst | Software Engineer | System Admin | Marketing Specialist

2A. Membership via IN  
Intent: QA Engineer OR DevOps Engineer.  
UI:
- meta_key: `job_designation`
- operator: `IN`
- Type: `CHAR`
- meta_value: `QA Engineer, DevOps Engineer`  (comma-separated → parsed to array)  
Expected: Exact membership match.

2B. Pipe-separated field using REGEXP  
Intent: Field stores multiple roles separated by `|`; match Software Engineer or Data Analyst.  
UI:
- meta_key: `job_designation`
- operator: `REGEXP`
- Type: `CHAR`
- meta_value: `(^|\s*\|\s*)(Software Engineer|Data Analyst)(\s*\|\s*|$)`  
Expected: Token-aware match; avoids partials like “Engineer” inside longer tokens.

2C. Excluding a role with NOT REGEXP  
Intent: Exclude HR Manager.  
UI:
- meta_key: `job_designation`
- operator: `NOT REGEXP`
- Type: `CHAR`
- meta_value: `(^|\s*\|\s*)HR Manager(\s*\|\s*|$)`  
Expected: Excludes any token equal to HR Manager.

=== 3) Numeric range tests for `age_in_years` ===

3A. Inclusive range (happy path)  
Intent: 18–45 inclusive.  
UI:
- meta_key: `age_in_years`
- operator: `BETWEEN`
- Type: `NUMERIC` (or `UNSIGNED`)
- meta_value: `18,45`  
Expected: Ages 18 through 45 inclusive.

3B. Boundary checks  
Repeat 3A with `18,18` and `45,45` to ensure inclusivity.

3C. Incorrect type safety  
Intent: Show why `CHAR` is wrong for numeric.  
UI:
- meta_key: `age_in_years`
- operator: `>`
- Type: `CHAR`
- meta_value: `9`  
Expected: Lexicographic misbehavior (e.g., `'100'` < `'9'`). Use `NUMERIC`/`UNSIGNED`.

3D. Negative test (below range)  
Intent: Ensure users below 18 are excluded.  
UI:
- meta_key: `age_in_years`
- operator: `<`
- Type: `NUMERIC`
- meta_value: `18`  
Expected: No minors when combined with AND group in 6A.

=== 4) Date tests for `joining_date` ===
Reference cut-off date: 2023-08-25 (two years ago from baseline).

4A. On or before cut-off (joined ≥ 2 years ago)  
UI:
- meta_key: `joining_date`
- operator: `<=`
- Type: `DATE`
- meta_value: `2023-08-25`  
Expected: On or before 2023-08-25. If stored as DATETIME, see 4A'.

4A'. DATETIME variant  
UI:
- meta_key: `joining_date`
- operator: `<=`
- Type: `DATETIME`
- meta_value: `2023-08-25 23:59:59`  
Expected: Includes whole day.

4B. Window: three to two years ago  
UI:
- meta_key: `joining_date`
- operator: `BETWEEN`
- Type: `DATE`
- meta_value: `2022-08-25,2023-08-25`  
Expected: Inclusive of endpoints.

4C. Future-join exclusion  
UI:
- meta_key: `joining_date`
- operator: `>`
- Type: `DATE`
- meta_value: `2025-08-25`  
Expected: No results (sanity check).

=== 5) Salary tests for `monthly_salary` (business rule: exactly 9000) ===

5A. Exact numeric  
UI:
- meta_key: `monthly_salary`
- operator: `=`
- Type: `NUMERIC`
- meta_value: `9000`  
Expected: Only users with numeric 9000.

5B. Degenerate range ⇒ equality  
UI:
- operator: `BETWEEN`
- Type: `NUMERIC`
- meta_value: `9000,9000`  
Expected: Same as 5A.

5C. Wrong type guard  
UI:
- operator: `>`
- Type: `CHAR`
- meta_value: `8000`  
Expected: Demonstrates incorrect lexicographic behavior; should be `NUMERIC`.

5D. Not-set detection  
UI:
- operator: `NOT EXISTS`
- meta_value: (empty)  
Expected: Users missing the `monthly_salary` key.

=== 6) Multi-condition (grouped) tests ===

6A. Master AND (full preference set)  
Group relation: `AND`  
Conditions:
1) `job_description` = `Lorem ipsum test` (CHAR)  
2) `job_designation` IN (UI/UX Designer, QA Engineer, DevOps Engineer, Project Manager, Business Analyst, HR Manager, Data Analyst, Software Engineer, System Admin, Marketing Specialist) (CHAR)  
3) `age_in_years` BETWEEN `18,45` (NUMERIC)  
4) `joining_date` <= `2023-08-25` (DATE or DATETIME with `... 23:59:59`)  
5) `monthly_salary` = `9000` (NUMERIC)  
Expected: Must satisfy all.

6B. AND with nested OR (role flexibility)  
Top relation: `AND`  
Group 1 (OR): `job_designation` IN (QA Engineer, DevOps Engineer) OR `job_designation` = Software Engineer  
Group 2: `age_in_years` BETWEEN 18,45  
Group 3: `joining_date` <= 2023-08-25  
Group 4: `monthly_salary` = 9000  
Expected: Meets Groups 2–4 and any branch of Group 1.

6C. Role tokenization safety (delimited list)  
Top relation: `AND`  
Conditions:
- `job_designation REGEXP '(^|\\s*\\|\\s*)(Project Manager)(\\s*\\|\\s*|$)'`
- `monthly_salary = 9000` (NUMERIC)  
Expected: Matches the `Project Manager` token only (not “Assistant Project Manager” unless intended).

6D. Exclude a designation while matching another  
Top relation: `AND`  
Conditions:
- `job_designation REGEXP '(^|\\s*\\|\\s*)(Data Analyst)(\\s*\\|\\s*|$)'`
- `job_designation NOT REGEXP '(^|\\s*\\|\\s*)(Marketing Specialist)(\\s*\\|\\s*|$)'`  
Expected: Data Analysts that are not also Marketing Specialists (in multi-tag scenarios).

=== 7) Key existence tests (data hygiene) ===

7A. Missing `joining_date`  
UI:
- meta_key: `joining_date`
- operator: `NOT EXISTS`  
Expected: Returns users lacking a joining date.

7B. Missing `age_in_years` or non-numeric  
UI:
- meta_key: `age_in_years`
- operator: `NOT EXISTS`  
Add-on check: Compare with a query using `Type: NUMERIC` and `BETWEEN 18,45`; counts should differ only by correctly typed rows.

=== 10) Extending scope for multiple users ===
Allow non-admins (specific user ID) to use the plugin by adding this to your theme's functions.php:

    // Allow a specific user to access All Users Filter UI
   function yr_theme_custom_allusfi_filter( $allowed ) {
        return ( 64901 === get_current_user_id() ) ? true : $allowed;
    }
    add_filter( 'allusfi_allowed_user_to_filter', 'yr_theme_custom_allusfi_filter' );

== Edge Cases ==
* **Date format other than `YYYY-MM-DD`:** If stored as `DD/MM/YYYY`, direct `DATE` comparisons won't work. Normalize your data (recommended) or use REGEXP to pre-filter tokens; ideally migrate to ISO format.
* **Salaries with commas (e.g., `9,000`):** Store a numeric-only meta value for reliable `NUMERIC` comparisons. String comparisons (`CHAR`) are error-prone for numbers.
* **Case sensitivity for text/regex:** Use `Type: BINARY` with `REGEXP` when you need strict case-sensitive matches; otherwise collation may be case-insensitive.
* **Delimited lists for roles:** Prefer one role per row. If you must store delimited values, use the token-aware REGEXP patterns above to avoid partial matches.
* **NUMERIC vs CHAR:** Always choose `NUMERIC`/`SIGNED`/`UNSIGNED` for number logic. `CHAR` compares lexicographically.

== Quick UI Test Case Template ==
Use this compact template to jot down UI scenarios:

* meta_key → `monthly_salary`
* operator → `=`
* Type → `NUMERIC`
* meta_value → `9999`
* Group relation (if available) → `relation: AND | OR`

== Frequently Asked Questions ==
= How can non-admins access the plugin? =
Use the `allusfi_allowed_user_to_filter` filter (see “Extending scope for multiple users”).

= Why doesn't a numeric filter work with Type: CHAR? =
String comparisons are lexicographic. Use `NUMERIC` (or `SIGNED`/`UNSIGNED`) for number ranges and equality.

= What is SIGNED and UNSIGNED type? =
"SIGNED" Treated as a signed integer, can represent negative, zero, and positive numbers. e.g. -2, -1, 0, 1, 2
"UNSIGNED" Only allows 0 and positive numbers (no negatives). e.g. 0, 1, 2

== Changelog ==
= 1.0 =
* Initial Release

== Upgrade Notice ==
= 1.0 =
Initial Release.
