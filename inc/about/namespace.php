<?php
/**
 * Altis About Page.
 *
 * @package altis/core
 */

namespace Altis\About;

use Altis;
use Altis\Module;
use WP_Admin_Bar;

const PAGE_SLUG = 'altis-about';

/**
 * Bootstrap About pages.
 */
function bootstrap() {
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\register_menu_item', 0 );
	add_action( 'load-about.php', __NAMESPACE__ . '\\render_about_page', 1000 );
	add_action( 'load-credits.php', __NAMESPACE__ . '\\render_credits_page', 1000 );
}

/**
 * Add the Altis logo menu.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar manager class.
 */
function register_menu_item( WP_Admin_Bar $wp_admin_bar ) {
	$logo_menu_args = [
		'id' => 'altis-about',
		'parent' => 'altis',
		'title' => 'About',
		'href' => admin_url( 'about.php' ),
	];

	$wp_admin_bar->add_menu( $logo_menu_args );
}

/**
 * Get versions for all modules.
 *
 * @return array Map of module slug => module data.
 */
function get_module_version_data() : array {
	$composer_data = Altis\get_composer_data();
	$modules = Module::get_all();

	$data = [];
	foreach ( $modules as $module ) {
		/** @var Module $module Altis module object. */
		$package = sprintf( 'altis/%s', basename( $module->get_directory() ) );
		$package_data = $composer_data[ $package ] ?? null;
		$data[ $module->get_slug() ] = (object) [
			'module' => $module,
			'version' => (object) [
				'human' => $package_data ? $package_data->version : 'Unknown',
				'sha' => $package_data ? $package_data->dist->reference : 'Unknown',
			],
		];
	}

	return $data;
}

/**
 * Get the license for a specific package.
 *
 * Returns a SPDX identifier for the given package.
 *
 * @link https://spdx.org/licenses/
 *
 * @param string $package Package name.
 * @return string|null Package license if known, or null otherwise.
 */
function get_package_license( string $package ) : ?string {
	// Manually except some packages which have inaccurate Composer
	// license data.
	$exceptions = [
		'humanmade/stream' => 'GPL-2.0-or-later',
		'humanmade/two-factor' => 'GPL-2.0-or-later',
		'humanmade/wp-redis-predis-client' => 'GPL-2.0-or-later',
		'humanmade/wp-redis' => 'GPL-2.0-or-later',
	];
	if ( isset( $exceptions[ $package ] ) ) {
		return $exceptions[ $package ];
	}

	$all_packages = Altis\get_composer_data();
	$package_data = $all_packages[ $package ] ?? null;
	if ( empty( $package_data ) || empty( $package_data->license ) ) {
		return null;
	}
	return implode( ',', $package_data->license );
}

/**
 * Render the About page.
 *
 * This is hooked in early on the about.php page, allowing us to hijack the
 * page and render our own About page instead.
 */
function render_about_page() {
	$GLOBALS['title'] = __( 'About', 'altis' );

	require ABSPATH . 'wp-admin/admin-header.php';

	?>
	<div class="wrap about-wrap full-width-layout">
		<h1><?php esc_html_e( 'Powered by Altis', 'altis' ) ?></h1>

		<p class="about-text"><?php esc_html_e( 'Welcome to Altis, the next-generation digital experience platform.', 'altis' ) ?></p>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="<?php echo esc_attr( admin_url( 'about.php' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'About', 'altis' ) ?></a>
			<a href="<?php echo esc_attr( admin_url( 'credits.php' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Credits', 'altis' ) ?></a>
		</h2>
	</div>

	<h2><?php esc_html_e( 'Current Module Versions', 'altis' ) ?></h2>

	<table class="widefat striped">
		<?php foreach ( get_module_version_data() as $module_data ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $module_data->module->get_title() ) ?></th>
				<td><?php echo esc_html( sprintf( '%s (%s)', $module_data->version->human, $module_data->version->sha ) ) ?></td>
			</tr>
		<?php endforeach ?>
	</table>
	<?php

	require ABSPATH . 'wp-admin/admin-footer.php';

	// Exit before we attempt to render WordPress' about page.
	exit;
}

/**
 * Render the Credits page.
 *
 * This is hooked in early on the credits.php page, allowing us to hijack the
 * page and render our own Credits page instead.
 */
function render_credits_page() {
	$GLOBALS['title'] = __( 'Credits', 'altis' );

	require ABSPATH . 'wp-admin/admin-header.php';
	require ABSPATH . 'wp-admin/includes/credits.php';

	?>
	<div class="wrap about-wrap full-width-layout">
		<h1><?php esc_html_e( 'Powered by Altis', 'altis' ) ?></h1>

		<p class="about-text"><?php esc_html_e( 'Welcome to Altis, the next-generation digital experience platform.', 'altis' ) ?></p>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="<?php echo esc_attr( admin_url( 'about.php' ) ); ?>" class="nav-tab"><?php esc_html_e( 'About', 'altis' ) ?></a>
			<a href="<?php echo esc_attr( admin_url( 'credits.php' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Credits', 'altis' ) ?></a>
		</h2>

		<p class="about-description">
			Altis is created by <a href="https://humanmade.com/">Human Made</a>, and is available under the terms of the GPL v3 licence.
			Altis is based on <a href="https://wordpress.org/">WordPress</a>, and contains code from many open source projects.
		</p>
	</div>

	<div class="wrap full-width-layout">
		<?php
		// Generate documentation for Composer dependencies.
		$packages = Altis\get_composer_data();
		?>
		<h2><?php esc_html_e( 'Packages', 'altis' ) ?></h2>
		<p>Altis includes code from the following projects, used under their respective licenses.</p>
		<table class="widefat striped">
			<?php foreach ( $packages as $package ) : ?>
				<tr>
					<th scope="row">
						<?php
						printf(
							'<a href="%s">%s</a> (%s)',
							esc_url( 'https://packagist.org/packages/' . $package->name ),
							esc_html( $package->name ),
							esc_html( $package->version )
						);
						?>
					</th>
					<td>
						<?php
						printf(
							'<a href="%s">%s</a>',
							esc_url( $package->homepage ?? 'https://packagist.org/packages/' . $package->name ),
							esc_html( get_package_license( $package->name ) )
						);
						?>
					</td>
				</tr>
			<?php endforeach ?>
		</table>

		<?php
		// Generate documentation for external libraries.
		$credits = wp_credits();
		$libraries = $credits['groups']['libraries'] ?? null;
		if ( ! empty( $libraries ) ) :
			?>

			<h2><?php esc_html_e( 'Other Libraries', 'altis' ) ?></h2>
			<p>Altis includes other libraries as part of WordPress.</p>
			<?php
			array_walk( $libraries['data'], '_wp_credits_build_object_link' );
			echo '<p class="wp-credits-list">' . wp_kses( implode( ', ', $libraries['data'] ), [ 'a' => [ 'href' => [] ] ] ) . ".</p>\n\n";
			?>

		<?php endif ?>

	</div>

	<?php

	require ABSPATH . 'wp-admin/admin-footer.php';

	// Exit before we attempt to render WordPress' credits page.
	exit;
}
