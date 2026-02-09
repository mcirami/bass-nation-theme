<?php
/**
 * Plugin Name: PMPro CLI – Fix Missing Membership Levels
 * Description: WP-CLI command to find users with active PMPro subscriptions but missing membership levels.
 */

if ( ! defined('WP_CLI') || ! WP_CLI ) {
	return;
}

WP_CLI::add_command('pmpro fix-missing-levels', function($args, $assoc_args) {
	global $wpdb;

	if (!function_exists('pmpro_changeMembershipLevel')) {
		WP_CLI::error('Paid Memberships Pro is not active.');
	}

	$fix     = isset($assoc_args['fix']);
	$dry_run = isset($assoc_args['dry-run']);
	$format  = $assoc_args['format'] ?? 'table';

	$subs_table = $wpdb->prefix . 'pmpro_subscriptions';
	$mu_table   = $wpdb->prefix . 'pmpro_memberships_users';
	$now        = gmdate('Y-m-d H:i:s');

	// Detect membership level column (PMPro version safe)
	$level_column = null;
	foreach (['membership_level_id', 'membership_id', 'level_id'] as $col) {
		if ($wpdb->get_var("SHOW COLUMNS FROM {$subs_table} LIKE '{$col}'")) {
			$level_column = $col;
			break;
		}
	}

	if (!$level_column) {
		WP_CLI::error('Could not determine membership level column.');
	}

	$sql = "
		SELECT
		s.id AS subscription_id,
		s.user_id,
		s.{$level_column} AS level_id,
		s.status AS subscription_status,
		s.next_payment_date,
		COUNT(mu.user_id) AS has_membership
	FROM {$subs_table} s
	LEFT JOIN {$mu_table} mu
		ON mu.user_id = s.user_id
		AND mu.membership_id = s.{$level_column}
		AND (
			mu.status = 'active'
			OR mu.status IS NULL
			OR mu.status = ''
		)
		AND (
			mu.enddate IS NULL
			OR mu.enddate = '0000-00-00 00:00:00'
			OR mu.enddate > %s
		)
	WHERE
		s.status IN ('active','trial','trialing')
		AND s.next_payment_date IS NOT NULL
		AND s.next_payment_date != '0000-00-00 00:00:00'
		AND s.next_payment_date > %s
	GROUP BY s.id
	HAVING has_membership = 0
	ORDER BY s.user_id
	";

	$rows = $wpdb->get_results($wpdb->prepare($sql, $now, $now), ARRAY_A);

	if (!empty($assoc_args['debug'])) {
		$limit = min(5, count($rows));
		for ($i = 0; $i < $limit; $i++) {
			$r = $rows[$i];
			WP_CLI::log("---- DEBUG user_id={$r['user_id']} level_id={$r['level_id']} sub_id={$r['subscription_id']} ----");

			$mu_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, membership_id, status, startdate, enddate
				 FROM {$mu_table}
				 WHERE user_id = %d
				 ORDER BY startdate DESC
				 LIMIT 10",
					(int)$r['user_id']
				),
				ARRAY_A
			);

			WP_CLI\Utils\format_items('table', $mu_rows, ['user_id','membership_id','status','startdate','enddate']);
		}
	}

	if (!$rows) {
		WP_CLI::success('No broken subscriptions found.');
		return;
	}

	// Add email + level name
	$levels = function_exists('pmpro_getAllLevels') ? pmpro_getAllLevels(true, true) : [];
	$level_map = [];
	foreach ((array)$levels as $lvl) {
		$level_map[$lvl->id] = $lvl->name;
	}

	foreach ($rows as &$row) {
		$user = get_user_by('id', $row['user_id']);
		$row['email'] = $user ? $user->user_email : '';
		$row['level_name'] = $level_map[$row['level_id']] ?? '';
	}
	unset($row);

	WP_CLI\Utils\format_items(
		$format,
		$rows,
		['user_id','email','subscription_id','level_id','level_name','subscription_status','next_payment_date']
	);

	if (!$fix) {
		WP_CLI::log('Run with --fix to repair memberships.');
		return;
	}

	foreach ($rows as $row) {
		$msg = "User {$row['user_id']} → Level {$row['level_id']} ({$row['level_name']})";

		if ($dry_run) {
			WP_CLI::log("[DRY RUN] {$msg}");
			continue;
		}

		if (pmpro_changeMembershipLevel((int)$row['level_id'], (int)$row['user_id'])) {
			WP_CLI::success("Fixed: {$msg}");
		} else {
			WP_CLI::warning("FAILED: {$msg}");
		}
	}
});
