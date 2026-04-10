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
	add_action( 'altis.post_sync', __NAMESPACE__ . '\\flush_object_cache' );
	add_action( 'altis.post_sync', __NAMESPACE__ . '\\reindex_elasticsearch' );
	add_action( 'altis.post_sync', __NAMESPACE__ . '\\truncate_cavalcade_logs' );
}

/**
 * Flush the object cache.
 *
 * @return void
 */
function flush_object_cache() : void {
	WP_CLI::log( 'Flushing object cache...' );
	wp_cache_flush();
	WP_CLI::success( 'Object cache flushed.' );
}

/**
 * Reindex Elasticsearch if available.
 *
 * Skips reindexing if Elasticsearch is not configured for this environment.
 *
 * @return void
 */
function reindex_elasticsearch() : void {
	if ( ! defined( 'ELASTICSEARCH_HOST' ) || ! ELASTICSEARCH_HOST ) {
		WP_CLI::log( 'Elasticsearch not available, skipping reindex.' );
		return;
	}

	WP_CLI::log( 'Reindexing Elasticsearch...' );
	WP_CLI::runcommand( 'elasticpress sync --setup --network-wide --yes' );
	WP_CLI::success( 'Elasticsearch reindex complete.' );
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
