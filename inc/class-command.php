<?php
/**
 * Altis WP CLI Command.
 *
 * @package altis/core
 */

namespace Altis;

use WP_CLI;
use WP_CLI_Command;

/**
 * Altis WP CLI Command class.
 */
class Command extends WP_CLI_Command {

	/**
	 * Run migration scripts.
	 *
	 * This command should be called after upgrading Altis to set up Altis features.
	 * Custom code can hook into this command using the `altis.migrate` hook.
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function migrate( array $args, array $assoc_args ) {
		WP_CLI::log( 'Running Altis migration scripts...' );

		/**
		 * Triggered by the `wp altis migrate` command. Attach any custom
		 * behaviour you need to run post deployment or upgrade.
		 *
		 * @param array $args Any plain args passed to the command e.g. `wp altis migrate myarg`.
		 * @param array $assoc_args Any named arguments passed to the command e.g. `wp altis migrate --url=example.org`.
		 */
		do_action( 'altis.migrate', $args, $assoc_args );
	}

}
