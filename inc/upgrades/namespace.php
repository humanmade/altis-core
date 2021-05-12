<?php

namespace Altis\Upgrades;

use Altis;

/**
 * Bootstrap the actions.
 */
function bootstrap() {
	add_action( 'wp_network_dashboard_setup', __NAMESPACE__ . '\\register_widget' );
	add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register_widget' );
}

/**
 * Register our dashboard widget.
 */
function register_widget() {
	if ( ! current_user_can( 'edit_options' ) ) {
		return;
	}

	if ( is_version_supported() ) {
		return;
	}

	add_action( 'admin_head-index.php', function () {
?>
<style>
#altis-upgrade-warning {
    position: relative;
    margin: 40px 0 10px;
    padding: 6px 10px;
    border: 2px solid #df3232;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    background: #fff;
    font-size: 13px;
    line-height: 1.7;
}

#altis-upgrade-warning h1 {
	background: #df3232;
	color: #f0f0f0;
	padding: 6px 10px;
	margin: -6px -10px 0;
}

#altis-upgrade-warning h1 .dashicons {
	color: #df3232;
	color: #f0f0f0;
	font-size: 23px;
	height: 23px;
	width: 23px;
	line-height: 1.3;
}

#altis-upgrade-warning p {
	font-size: 14px;
}

#altis-upgrade-warning .button .dashicons-external {
    line-height: 43px;
}
</style>
<?php
	} );

	add_action( 'all_admin_notices', function () {
		global $pagenow;
		if ( $pagenow !== 'index.php' ) {
			return;
		}

		render_widget();
	} );
}

/**
 * Get the supported version information from altis-dxp.com
 *
 * @param bool $skip_cache True to override the cache.
 * @return array Data from altis-dxp.com
 */
function get_supported_version_info() {
	return [
		'supported' => [
			4,
			5,
			6,
			7,
		],
		'latest' => [
			'version' => 7,
			'date' => '2021-05-04',
			'blog' => 'https://www.altis-dxp.com/foo/',
		],
	];
}

/**
 * Is this site on the latest version of Altis?
 *
 * @return bool
 */
function is_version_latest() : bool {
	$version = Altis\get_version();
	$support = get_supported_version_info();
	return $version === $support['latest']['version'];
}

/**
 * Is this site on a supported version of Altis?
 *
 * @return bool
 */
function is_version_supported() : bool {
	$version = Altis\get_version();
	$support = get_supported_version_info();
	return in_array( $version, $support['supported'], true );
}

/**
 * Render the unsupported version widget.
 *
 * @return void
 */
function render_widget() {
	$version = Altis\get_version();
	$info = get_supported_version_info();

	?>
	<div class="wrap">
	<div id="altis-upgrade-warning">
		<h1>
			<span aria-hidden="true" class="dashicons dashicons-warning"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Warning:', 'altis' ) ?></span>
			<?php esc_html_e( 'Altis Upgrade Required', 'altis' ) ?>
		</h1>
		<p><?php
		echo esc_html( sprintf(
			__( 'You are running an unsupported version of Altis (Altis v%d).', 'altis' ),
			$version
		) );
		?></p>

		<p><?php
		echo clean_html( sprintf(
			__( 'The latest version of Altis is <a href="%s">Altis v%d</a>, released on %s.', 'altis' ),
			$info['latest']['blog'],
			$info['latest']['version'],
			date_i18n( get_option( 'date_format' ), strtotime( $info['latest']['date'] ) )
		), 'a' );
		?></p>

		<p><?php esc_html_e( 'Newer versions of Altis are faster and come with new features included in your existing licensing cost.', 'altis' ) ?></p>

		<p class="button-container">
			<a
				class="button button-primary button-hero"
				href="https://docs.altis-dxp.com/guides/upgrading/"
				target="_blank"
				rel="noopener noreferrer"
			>
				<?php
					echo clean_html( sprintf(
						'%1$s <span class="screen-reader-text">%2$s</span>',
						__( 'Learn more about Altis upgrades' ),
						/* translators: Accessibility text. */
						__( '(opens in a new tab)' )
					), 'span' );
				?>
				<span aria-hidden="true" class="dashicons dashicons-external"></span>
			</a>
		</p>
	</div>
	</div>
	<?php
}
