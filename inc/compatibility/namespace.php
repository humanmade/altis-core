<?php
/**
 * Compatibility tweaks for optimal performance.
 *
 * @package altis/core
 */

namespace Altis\Compatibility;

/**
 * Bootstrap compatibility tweaks.
 */
function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\set_woocommerce_compatibility' );
}

/**
 * Set hooks for WooCommerce compatibility and optimization.
 *
 * Adjusts Action Scheduler to run optimially with Cavalcade, by avoiding
 * fallbacks and maximising the Cavalcade queue.
 *
 * The time limit and batch size hooks are set at priority 0 to allow sites to
 * easily override for their specific use case.
 */
function set_woocommerce_compatibility() {
	if ( ! class_exists( '\\ActionScheduler' ) ) {
		return;
	}

	// Disable Action Scheduler's async request fallback to ensure it runs
	// via Cavalcade.
	add_filter( 'action_scheduler_allow_async_request_runner', '__return_false', 1000 );

	// Allow Action Scheduler to run for 5 minutes (Cavalcade's limit is 60,
	// but most hosts stay around 1-2 minutes).
	add_filter( 'action_scheduler_queue_runner_time_limit', function () {
		return 5 * MINUTE_IN_SECONDS;
	}, 0 );

	// Increase queue batch size from 25 to 250: we give 12x the default time
	// (5 minutes instead of 30s), so should be able to run 8x the number of jobs comfortably.
	add_filter( 'action_scheduler_queue_runner_batch_size', function () {
		return 250;
	}, 0 );
}
