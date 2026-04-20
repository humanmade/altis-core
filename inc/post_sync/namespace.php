<?php
/**
 * Altis Post Sync actions.
 *
 * Default tasks run after syncing an environment, such as pulling
 * a production database to staging or development.
 *
 * @package altis/core
 */

namespace Altis\Post_Sync;

use WP_CLI;

/**
 * Bootstrap post-sync hooks.
 *
 * @return void
 */
function bootstrap() : void {
	add_action( 'altis.post_sync', __NAMESPACE__ . '\\truncate_cavalcade_logs' );
}

/**
 * Truncate the Cavalcade logs table.
 *
 * Clears all cron job execution logs to reduce database size after sync.
 *
 * @return void
 */
function truncate_cavalcade_logs() : void {
	global $wpdb;

	$table = $wpdb->base_prefix . 'cavalcade_logs';

	// Check the table exists before attempting to truncate.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$table_exists = $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
	);

	if ( ! $table_exists ) {
		WP_CLI::log( 'Cavalcade logs table not found, skipping.' );
		return;
	}

	WP_CLI::log( 'Truncating Cavalcade logs...' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "TRUNCATE TABLE `{$table}`" );
	WP_CLI::success( 'Cavalcade logs truncated.' );
}
