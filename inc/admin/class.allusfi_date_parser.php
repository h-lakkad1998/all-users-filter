<?php

/**
 * ALLUSFI_Date_Parser
 *
 * Converts semantic relative-date expressions into absolute date-range arrays
 * compatible with WP_User_Query meta_query clauses.
 *
 * Supported types:
 *  - DATE     → 'YYYY-MM-DD'  strings
 *  - DATETIME → 'YYYY-MM-DD HH:MM:SS'  strings
 *  - TIME     → 'HH:MM:SS'  strings
 *  - NUMERIC  → YYYYMMDD  integers (ACF Date Picker compact format)
 *
 * Supported semantic expressions (case-insensitive):
 *  today, yesterday, this week, last week, N weeks ago,
 *  this month, last month, N months ago,
 *  this year, last year,
 *  last N days, last N hours,
 *  N mins ago / N minutes ago,
 *  YYYY-MM-DD,YYYY-MM-DD  (explicit range passthrough),
 *  YYYY-MM-DD             (single date passthrough)
 *
 * @package All_Users_Filter
 * @since   1.7
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('ALLUSFI_Date_Parser')) {

	class ALLUSFI_Date_Parser
	{

		/**
		 * Build a complete meta_query clause array from a semantic value string.
		 *
		 * @param string $meta_key  The user-meta key to filter on.
		 * @param string $value     The semantic or explicit date string.
		 * @param string $type      One of DATE | DATETIME | TIME | NUMERIC.
		 * @return array|null       Ready-to-merge meta_query clause, or null on failure.
		 */
		public static function parse($meta_key, $value, $type)
		{
			$meta_key = sanitize_key($meta_key);
			$type     = strtoupper(sanitize_text_field($type));
			$value    = sanitize_text_field($value);

			if (empty($meta_key) || empty($value)) {
				return null;
			}

			$allowed_types = array('DATE', 'DATETIME', 'TIME', 'NUMERIC');
			if (! in_array($type, $allowed_types, true)) {
				$type = 'DATE';
			}

			// Resolve the semantic string to an absolute {from, to} pair.
			$range = self::resolve_relative($value);

			if (empty($range) || empty($range['from']) || empty($range['to'])) {
				return null;
			}

			// Format both dates according to the requested type.
			$from_formatted = self::format_for_type($range['from'], $type);
			$to_formatted   = self::format_for_type($range['to'], $type);

			if (null === $from_formatted || null === $to_formatted) {
				return null;
			}

			return array(
				'key'     => $meta_key,
				'value'   => array($from_formatted, $to_formatted),
				'compare' => 'BETWEEN',
				'type'    => $type,
			);
		}

		/**
		 * Resolve a semantic expression into a normalised { from, to } array of
		 * 'YYYY-MM-DD' strings (or 'YYYY-MM-DD HH:MM:SS' for minute-precision tokens).
		 *
		 * Uses wp_date() when available (WP 5.3+) to respect the site timezone.
		 *
		 * @param string $value Raw semantic or explicit date string.
		 * @return array{from: string, to: string}|null
		 */
		public static function resolve_relative($value)
		{
			$value = strtolower(trim($value));

			// ---------------------------------------------------------------
			// 1. Explicit range: 'YYYY-MM-DD,YYYY-MM-DD'
			// ---------------------------------------------------------------
			if (strpos($value, ',') !== false) {
				$parts = array_map('trim', explode(',', $value, 2));
				if (self::is_valid_date($parts[0]) && self::is_valid_date($parts[1])) {
					return array(
						'from' => $parts[0],
						'to'   => $parts[1],
					);
				}
			}

			// ---------------------------------------------------------------
			// 2. Single explicit date: 'YYYY-MM-DD'
			// ---------------------------------------------------------------
			if (self::is_valid_date($value)) {
				return array(
					'from' => $value,
					'to'   => $value,
				);
			}

			// ---------------------------------------------------------------
			// 3. Semantic tokens — resolve against "now" in site timezone.
			// ---------------------------------------------------------------
			$now       = self::now_ts();
			$today_str = self::ts_to_date($now);

			// --- today ---
			if ('today' === $value) {
				return array('from' => $today_str, 'to' => $today_str);
			}

			// --- yesterday ---
			if ('yesterday' === $value) {
				$d = self::ts_to_date(strtotime('-1 day', $now));
				return array('from' => $d, 'to' => $d);
			}

			// --- this week (Mon–Sun, ISO) ---
			if ('this week' === $value) {
				$dow   = (int) self::wp_date_str('N', $now); // 1=Mon … 7=Sun
				$start = strtotime('-' . ($dow - 1) . ' days', $now);
				$end   = strtotime('+' . (7 - $dow) . ' days', $now);
				return array(
					'from' => self::ts_to_date($start),
					'to'   => self::ts_to_date($end),
				);
			}

			// --- last week ---
			if ('last week' === $value) {
				$dow        = (int) self::wp_date_str('N', $now);
				$this_start = strtotime('-' . ($dow - 1) . ' days', $now);
				$start      = strtotime('-7 days', $this_start);
				$end        = strtotime('-1 day', $this_start);
				return array(
					'from' => self::ts_to_date($start),
					'to'   => self::ts_to_date($end),
				);
			}

			// --- N weeks ago ---
			if (preg_match('/^(\d+)\s+(?:weeks?|wks?)\s+ago$/', $value, $m)) {
				$n          = (int) $m[1];
				$dow        = (int) self::wp_date_str('N', $now);
				$this_start = strtotime('-' . ($dow - 1) . ' days', $now);
				$start      = strtotime('-' . ($n * 7) . ' days', $this_start);
				$end        = strtotime('+6 days', $start);
				return array(
					'from' => self::ts_to_date($start),
					'to'   => self::ts_to_date($end),
				);
			}

			// --- this month ---
			if ('this month' === $value) {
				$y = (int) self::wp_date_str('Y', $now);
				$m = (int) self::wp_date_str('m', $now);
				return array(
					'from' => self::ymd_str($y, $m, 1),
					'to'   => self::ymd_str($y, $m, (int) self::wp_date_str('t', $now)),
				);
			}

			// --- last month ---
			if ('last month' === $value) {
				$y      = (int) self::wp_date_str('Y', $now);
				$m      = (int) self::wp_date_str('m', $now);
				$prev_m = $m - 1;
				$prev_y = $y;
				if ($prev_m < 1) {
					$prev_m = 12;
					--$prev_y;
				}
				$days_in = (int) gmdate('t', mktime(0, 0, 0, $prev_m, 1, $prev_y));
				return array(
					'from' => self::ymd_str($prev_y, $prev_m, 1),
					'to'   => self::ymd_str($prev_y, $prev_m, $days_in),
				);
			}

			// --- N months ago ---
			if (preg_match('/^(\d+)\s+(?:months?|mos?)\s+ago$/', $value, $m)) {
				$n      = (int) $m[1];
				$y      = (int) self::wp_date_str('Y', $now);
				$mo     = (int) self::wp_date_str('m', $now);
				$target = $mo - $n;
				$target_y = $y;
				while ($target < 1) {
					$target += 12;
					--$target_y;
				}
				$days_in = (int) gmdate('t', mktime(0, 0, 0, $target, 1, $target_y));
				return array(
					'from' => self::ymd_str($target_y, $target, 1),
					'to'   => self::ymd_str($target_y, $target, $days_in),
				);
			}

			// --- this year ---
			if ('this year' === $value) {
				$y = (int) self::wp_date_str('Y', $now);
				return array(
					'from' => self::ymd_str($y, 1, 1),
					'to'   => self::ymd_str($y, 12, 31),
				);
			}

			// --- last year ---
			if ('last year' === $value) {
				$y = (int) self::wp_date_str('Y', $now) - 1;
				return array(
					'from' => self::ymd_str($y, 1, 1),
					'to'   => self::ymd_str($y, 12, 31),
				);
			}

			// --- last N days ---
			if (preg_match('/^last\s+(\d+)\s+days?$/', $value, $m)) {
				$n   = (int) $m[1];
				$end = strtotime('-1 day', $now);
				$start = strtotime('-' . $n . ' days', $now);
				return array(
					'from' => self::ts_to_date($start),
					'to'   => self::ts_to_date($end),
				);
			}

			// --- last N hours ---
			if (preg_match('/^last\s+(\d+)\s+(?:hours?|hrs?)$/', $value, $m)) {
				$n     = (int) $m[1];
				$start = $now - ($n * HOUR_IN_SECONDS);
				return array(
					'from' => self::ts_to_datetime($start),
					'to'   => self::ts_to_datetime($now),
				);
			}

			// --- N mins ago / N minutes ago ---
			if (preg_match('/^(\d+)\s+min(?:s|utes?)?\s+ago$/', $value, $m)) {
				$n     = (int) $m[1];
				$start = $now - ($n * MINUTE_IN_SECONDS);
				return array(
					'from' => self::ts_to_datetime($start),
					'to'   => self::ts_to_datetime($now),
				);
			}

			// --- N hours ago ---
			if (preg_match('/^(\d+)\s+(?:hours?|hrs?)\s+ago$/', $value, $m)) {
				$n     = (int) $m[1];
				$start = $now - ($n * HOUR_IN_SECONDS);
				return array(
					'from' => self::ts_to_datetime($start),
					'to'   => self::ts_to_datetime($now),
				);
			}

			// --- N days ago (single day) ---
			if (preg_match('/^(\d+)\s+days?\s+ago$/', $value, $m)) {
				$n = (int) $m[1];
				$d = self::ts_to_date(strtotime('-' . $n . ' days', $now));
				return array('from' => $d, 'to' => $d);
			}

			return null;
		}

		/**
		 * Format a resolved date string for the requested meta type.
		 *
		 * @param string $date  'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'.
		 * @param string $type  DATE | DATETIME | TIME | NUMERIC.
		 * @return string|int|null
		 */
		public static function format_for_type($date, $type)
		{
			$ts = strtotime($date);
			if (false === $ts) {
				return null;
			}

			switch ($type) {
				case 'NUMERIC':
					// ACF Date Picker stores as YYYYMMDD integer.
					return (int) gmdate('Ymd', $ts);

				case 'DATE':
					return gmdate('Y-m-d', $ts);

				case 'DATETIME':
					// If the input had no time component, default time to 00:00:00 / 23:59:59
					// is handled by the caller; here we just preserve whatever time is present.
					if (preg_match('/\d{2}:\d{2}:\d{2}/', $date)) {
						return gmdate('Y-m-d H:i:s', $ts);
					}
					return gmdate('Y-m-d H:i:s', $ts);

				case 'TIME':
					return gmdate('H:i:s', $ts);

				default:
					return gmdate('Y-m-d', $ts);
			}
		}

		// ---------------------------------------------------------------
		// Private helpers
		// ---------------------------------------------------------------

		/**
		 * Return current timestamp using the site timezone.
		 *
		 * @return int
		 */
		private static function now_ts()
		{
			return current_time('timestamp'); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		}

		/**
		 * Convert a timestamp to 'YYYY-MM-DD' in the site timezone.
		 *
		 * @param int $ts Unix timestamp.
		 * @return string
		 */
		private static function ts_to_date($ts)
		{
			return self::wp_date_str('Y-m-d', $ts);
		}

		/**
		 * Convert a timestamp to 'YYYY-MM-DD HH:MM:SS' in the site timezone.
		 *
		 * @param int $ts Unix timestamp.
		 * @return string
		 */
		private static function ts_to_datetime($ts)
		{
			return self::wp_date_str('Y-m-d H:i:s', $ts);
		}

		/**
		 * Wrapper for wp_date() with a gmdate() fallback for WP < 5.3.
		 *
		 * @param string $format PHP date format.
		 * @param int    $ts     Unix timestamp.
		 * @return string
		 */
		private static function wp_date_str($format, $ts)
		{
			if (function_exists('wp_date')) {
				return (string) wp_date($format, $ts);
			}
			return gmdate($format, $ts);
		}

		/**
		 * Build 'YYYY-MM-DD' from integer parts.
		 *
		 * @param int $y Year.
		 * @param int $m Month.
		 * @param int $d Day.
		 * @return string
		 */
		private static function ymd_str($y, $m, $d)
		{
			return sprintf('%04d-%02d-%02d', $y, $m, $d);
		}

		/**
		 * Validate that a string looks like 'YYYY-MM-DD'.
		 *
		 * @param string $str
		 * @return bool
		 */
		private static function is_valid_date($str)
		{
			if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
				return false;
			}
			list($y, $m, $d) = array_map('intval', explode('-', $str));
			return checkdate($m, $d, $y);
		}
	}
}
