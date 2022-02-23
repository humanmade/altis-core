<?php
/**
 * Upgrade notice.
 *
 * @package altis/core
 */

namespace Altis\Upgrades;

use Altis;

/**
 * Bootstrap the actions.
 */
function bootstrap() {
	add_action( 'load-index.php', __NAMESPACE__ . '\\maybe_render_header', 0 );
}

/**
 * Conditionally render the upgrade notice.
 */
function maybe_render_header() {
	if ( ! current_user_can( 'edit_options' ) ) {
		return;
	}

	if ( ! is_version_supported() ) {
		add_action( 'admin_head-index.php', __NAMESPACE__ . '\\render_warning_styles' );
		add_action( 'in_admin_header', function () {
			global $pagenow;
			if ( $pagenow !== 'index.php' ) {
				return;
			}

			render_warning_widget();
		}, 0 );
	} elseif ( ! is_version_latest() ) {
		add_action( 'admin_head-index.php', __NAMESPACE__ . '\\render_upgrade_styles' );
		add_action( 'in_admin_header', function () {
			global $pagenow;
			if ( $pagenow !== 'index.php' ) {
				return;
			}

			render_upgrade_widget();
		}, 0 );
	}
}

/**
 * Get the supported version information from docs.altis-dxp.com.
 *
 * @param bool $skip_cache True to override the cache.
 * @return array|null Data from altis-dxp.com
 */
function get_supported_version_info( bool $skip_cache = false ) : ?array {
	$releases = wp_cache_get( 'releases', 'altis-core' );
	if ( ! empty( $releases ) && ! $skip_cache ) {
		return $releases;
	}

	// Fetch releases data.
	$response = wp_remote_get( 'https://docs.altis-dxp.com/releases.json' );

	// Handle any error response.
	if ( is_wp_error( $response ) ) {
		/**
		 * The wp_remote_get error response.
		 *
		 * @var WP_Error $response
		 */
		trigger_error( $response->get_error_message(), E_USER_WARNING );
		return null;
	}

	// Parse JSON.
	$releases = json_decode( wp_remote_retrieve_body( $response ), ARRAY_A );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		trigger_error( json_last_error_msg(), E_USER_WARNING );
		return null;
	}

	// Ensure releases are sorted by date.
	array_multisort(
		array_map( 'strtotime', array_column( $releases, 'date' ) ),
		SORT_DESC,
		$releases
	);

	wp_cache_set( 'releases', $releases, 'altis-core', WEEK_IN_SECONDS );

	return $releases;
}

/**
 * Get data for current version of Altis.
 *
 * @return array|null
 */
function get_current_version_info() : ?array {
	$releases = get_supported_version_info();
	if ( empty( $releases ) ) {
		return null;
	}

	$version = Altis\get_version();

	foreach ( $releases as $release ) {
		if ( $release['version'] === $version ) {
			return $release;
		}
	}

	return null;
}

/**
 * Is this site on the latest version of Altis?
 *
 * If we can't resolve the release data assume current version is latest.
 *
 * @return bool
 */
function is_version_latest() : bool {
	$version = Altis\get_version();
	$releases = get_supported_version_info();
	return $version === ( $releases[0]['version'] ?? $version );
}

/**
 * Is this site on a supported version of Altis?
 *
 * If version information cannot be resolved assume it is supported.
 *
 * @return bool
 */
function is_version_supported() : bool {
	$release = get_current_version_info();
	return $release['supported'] ?? true;
}

/**
 * Render stylesheet.
 */
function render_warning_styles() {
	?>
	<style>
		#altis-upgrade-warning {
			position: relative;
			margin: 0;
			padding: 8px 12px;
			border: 5px solid #df3232;
			border-radius: 1px;
			box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
			background: #fff;
			font-size: 13px;
			line-height: 1.7;
		}

		#altis-upgrade-warning h1 {
			background: #df3232;
			color: #fff;
			padding: 8px 12px;
			margin: -8px -12px 0;
			font-weight: bold;
		}

		#altis-upgrade-warning h1 .dashicons {
			color: #df3232;
			color: #fff;
			font-size: 23px;
			height: 23px;
			width: 23px;
			line-height: 1.3;
			vertical-align: baseline;
		}

		#altis-upgrade-warning p {
			font-size: 14px;
		}

		#altis-upgrade-warning p:last-of-type {
			margin-bottom: 4px;
		}

		#altis-upgrade-warning .button .dashicons-external {
			line-height: 43px;
		}
	</style>
	<?php
}

/**
 * Render the unsupported version widget.
 *
 * @return void
 */
function render_warning_widget() {
	$version = Altis\get_version();
	$releases = get_supported_version_info();
	if ( empty( $releases ) ) {
		return;
	}

	$latest = $releases[0];

	?>
		<div id="altis-upgrade-warning">
			<h1>
				<span aria-hidden="true" class="dashicons dashicons-warning"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Warning:', 'altis' ) ?></span>
				<?php esc_html_e( 'Altis Upgrade Required', 'altis' ) ?>
			</h1>
			<p>
			<?php
				echo esc_html( sprintf(
					__( 'You are running an unsupported version of Altis (Altis v%d).', 'altis' ),
					$version
				) );
			?>
			</p>

			<p>
			<?php
				// phpcs:ignore
				echo clean_html( sprintf(
					__( 'The latest version of Altis is <a href="%s">Altis v%d</a>, released on %s.', 'altis' ),
					$latest['blog'] ?: "https://github.com/humanmade/altis/releases/tag/{$latest['tag']}",
					$latest['version'],
					date_i18n( get_option( 'date_format' ), strtotime( $latest['date'] ) )
				), 'a' );
			?>
			</p>

			<p><?php esc_html_e( 'Newer versions of Altis are faster and come with new features included in your existing licensing cost.', 'altis' ) ?></p>

			<p class="button-container">
				<a
					class="button button-primary button-hero"
					href="https://docs.altis-dxp.com/guides/upgrading/"
					target="_blank"
					rel="noopener noreferrer"
				>
					<?php
						// phpcs:ignore
						echo clean_html(
							sprintf(
								'%1$s <span class="screen-reader-text">%2$s</span>',
								__( 'Learn more about Altis upgrades' ),
								/* translators: Accessibility text. */
								__( '(opens in a new tab)' )
							),
							[
								'span' => [
									'class' => true,
								],
							]
						);
					?>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			</p>
		</div>
	<?php
}

/**
 * Render stylesheet.
 */
function render_upgrade_styles() {
	?>
	<style>
		#altis-upgrade-info {
			position: relative;
			margin: 0;
			padding: 8px 5%;
			border-radius: 1px;
			box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
			border-bottom: 2px solid hsla(0,0%,100%,.2);
			color: #fff;
			background: #152a4e;
			font-size: 13px;
			line-height: 1.7;
			display: flex;
			justify-content: center;
			align-items: center;
		}

		#altis-upgrade-info-cols {
			display: grid;
			grid-template-columns: min-content auto min-content;
			align-items: center;
			column-gap: 3em;
			width: 100%;
		}

		#altis-upgrade-info img {
			width: 300px;
			height: auto;
		}

		#altis-upgrade-info h1 {
			color: #fff;
			padding: 8px 12px;
			margin: -8px -12px 0;
			font-weight: bold;
		}

		#altis-upgrade-info h1 .dashicons {
			color: #df3232;
			color: #fff;
			font-size: 23px;
			height: 23px;
			width: 23px;
			line-height: 1.3;
			vertical-align: baseline;
		}

		#altis-upgrade-info p {
			font-size: 14px;
			margin: 0;
		}

		#altis-upgrade-info .button .dashicons-external {
			line-height: 43px;
		}

		#altis-upgrade-info .button-container {
			display: flex;
			flex-direction: column;
			row-gap: 1em;
			align-items: flex-end;
		}
	</style>
	<?php
}

/**
 * Render the unsupported version widget.
 *
 * @return void
 */
function render_upgrade_widget() {
	$releases = get_supported_version_info();
	if ( empty( $releases ) ) {
		return;
	}

	$latest = $releases[0];
	$link = $latest['blog'] ?: "https://github.com/humanmade/altis/releases/tag/{$latest['tag']}";
	$img = $latest['prompt_image'] ?? 'https://www.altis-dxp.com/tachyon/2021/08/Marketer-Experience-1.png';
	$message = $latest['prompt'] ?? sprintf(
		__( 'Altis v%d is now available! Newer versions of Altis are faster and come with new features included in your existing licensing cost.', 'altis' ),
		$latest['version']
	);

	?>
		<div id="altis-upgrade-info">
			<div id="altis-upgrade-info-cols">
				<div>
					<?php if ( $img ) : ?>
						<img
							alt=""
							src="<?php echo esc_url( $img ) ?>"
						/>
					<?php endif ?>
				</div>
				<div>
					<h1>
						<?php
							echo esc_html( sprintf(
								__( 'Altis v%d now available', 'altis' ),
								$latest['version']
							) );
						?>
					</h1>

					<p>
					<?php
						// phpcs:ignore
						echo clean_html( $message, [ 'a' ] );
					?>
					</p>
				</div>

				<p class="button-container">
					<a
						class="button button-primary button-hero"
						href="<?php echo esc_url( $link ) ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php
							// phpcs:ignore
							echo clean_html(
								sprintf(
									'%1$s <span class="screen-reader-text">%2$s</span>',
									__( 'Learn more' ),
									/* translators: Accessibility text. */
									__( '(opens in a new tab)' )
								),
								[
									'span' => [
										'class' => true,
									],
								]
							);
						?>
						<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
					<a
						href="https://docs.altis-dxp.com/guides/upgrading/"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php
							// phpcs:ignore
							echo clean_html(
								sprintf(
									'%1$s <span class="screen-reader-text">%2$s</span>',
									__( 'View upgrade guide' ),
									/* translators: Accessibility text. */
									__( '(opens in a new tab)' )
								),
								[
									'span' => [
										'class' => true,
									],
								]
							);
						?>
						<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
				</p>
			</div>
		</div>
	<?php
}
