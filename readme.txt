=== All Users Filter ===
Contributors: hlakkad1998, akshaykungiri, visualsbyridhi
Tags: filter-users, users-filter, wp-users-filter, users-export, export-user
Donate link:
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 7.4
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 1.1
Version: 1.1

Filter, sort, and export WordPress users to CSV using powerful UI-driven meta queries (roles, dates, numeric ranges, regex, and more).

== Description ==
Plugin for filtering, sorting, and exporting users.

This plugin allows you to filter, sort, and export users in CSV format. You can filter users by multiple parameters, such as date, role, meta key-value, and registration date.

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
Intent: Users with `job_description` exactly "Lorem ipsum test".  
UI:
- meta_key: `job_description`
- operator: `=`
- Type: `CHAR`
- meta_value: `Lorem ipsum test`  
Expected: Exact match; typically case-insensitive under default collations; respects spaces.

1B. Case-sensitive regex match  
Intent: `job_description` starts with `Lorem` (case-sensitive).  
UI:
- meta_key: `job_description`
- operator: `REGEXP`
- Type: `BINARY`
- meta_value: `^Lorem`  
Expected: Matches only values beginning with uppercase `Lorem`.

1C. Negative regex  
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
- meta_value: `QA Engineer, DevOps Engineer`  (comma-separated → parsed to array) Must Include at least one array  
Expected: Exact membership match.

2B. Pipe-separated field using REGEXP  
Intent: Field stores multiple roles separated by `|`; match Software Engineer or Data Analyst.  
UI:
- meta_key: `job_designation`
- operator: `REGEXP`
- Type: `CHAR`
- meta_value: `(^|\s*\|\s*)(Software Engineer|Data Analyst)(\s*\|\s*|$)`  
Expected: Token-aware match; avoids partials like "Engineer" inside longer tokens.

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

=== 4) Date tests for `joining_date` on Meta Filters===
Reference cut-off date: 2023-08-25 (two years ago from baseline).

4A. On or before cut-off (joined ≥ 2 years ago)  
UI:
- meta_key: `joining_date`
- operator: `<=`
- Type: `DATE`
- meta_value: `2023-08-25`  
Expected: On or before 2023-08-25. If stored as DATETIME, see 4A'.

4B'. DATETIME variant  
UI:
- meta_key: `joining_date`
- operator: `<=`
- Type: `DATETIME`
- meta_value: `2023-08-25 23:59:59`  

=== 5) Salary tests for `monthly_salary` (business rule: exactly 9000) ===

5A. Exact numeric  
UI:
- meta_key: `monthly_salary`
- operator: `=`
- Type: `NUMERIC`
- meta_value: `9000`  

5B. Degenerate range ⇒ equality  
UI:
- operator: `BETWEEN`
- Type: `NUMERIC`
- meta_value: `9000,9000`  

=== 10) Extending scope for multiple users ===
Allow non-admins (specific user ID) to use the plugin by adding this to your theme's functions.php:
`
<?php
// Allow a specific user to access All Users Filter UI
function yr_theme_custom_allusfi_filter( $allowed ) {
    return ( 64901 === get_current_user_id() ) ? true : $allowed;
}
add_filter( 'allusfi_allowed_user_to_filter', 'yr_theme_custom_allusfi_filter' );`

== Frequently Asked Questions ==
= How can non-admins access the plugin? =
Use the `allusfi_allowed_user_to_filter` filter (see "Extending scope for multiple users").

= Why doesn't a numeric filter work with Type: CHAR? =
String comparisons are lexicographic. Use `NUMERIC` (or `SIGNED`/`UNSIGNED`) for number ranges and equality.

= What is SIGNED and UNSIGNED type? =
"SIGNED" Treated as a signed integer, can represent negative, zero, and positive numbers. e.g. -2, -1, 0, 1, 2
"UNSIGNED" Only allows 0 and positive numbers (no negatives). e.g. 0, 1, 2

== Changelog ==
= 1.0 =
* Initial Release
= 1.1 =
* Minor changes in the main file added German Support

== Upgrade Notice ==
= 1.0 =
Initial Release.
= 1.1 =
* Minor changes in the main file added German Support