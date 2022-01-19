<?php
/**
 * Altis Global Content Repository.
 *
 * @package altis/core
 */

namespace Altis\Global_Content;

use Exception;
use WP_Admin_Bar;
use WP_CLI;
use WP_Http_Cookie;
use WP_Site;

/**
 * Setup global media hooks.
 *
 * @return void
 */
function bootstrap() : void {
	// Create the site on upgrade.
	add_action( 'altis.migrate', __NAMESPACE__ . '\\maybe_create_site' );

	// Bootstrap media site if it exists.
	add_action( 'muplugins_loaded', __NAMESPACE__ . '\\bootstrap_site', 1 );
}

/**
 * Add global content site hooks.
 *
 * @return void
 */
function bootstrap_site() : void {
	if ( defined( 'WP_INITIAL_INSTALL' ) && WP_INITIAL_INSTALL ) {
		return;
	}

	if ( empty( get_site_id() ) ) {
		return;
	}

	// Redirect global site URLs.
	add_action( 'admin_init', __NAMESPACE__ . '\\redirect_admin_pages' );
	add_action( 'template_redirect', __NAMESPACE__ . '\\redirect_frontend' );

	// Handle clean up operations.
	add_action( 'wp_uninitialize_site', __NAMESPACE__ . '\\uninitialize_media_site' );

	// Handle global content repo admin customisations.
	add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu', 1000 );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\admin_bar_menu', 1000 );

	// Handle global site URL changes.
	add_action( 'updated_option_siteurl', __NAMESPACE__ . '\\handle_siteurl_update', 10, 2 );
	add_action( 'updated_option_home', __NAMESPACE__ . '\\handle_siteurl_update', 10, 2 );
	add_action( 'wp_update_site', __NAMESPACE__ . '\\handle_site_update' );

	// Do not allow global site deletion.
	add_filter( 'map_meta_cap', __NAMESPACE__ . '\\prevent_site_deletion', 10, 4 );

	// Handle network admin sites list.
	add_filter( 'manage_sites_action_links', __NAMESPACE__ . '\\sites_list_row_actions', 10, 2 );
	add_filter( 'display_site_states', __NAMESPACE__ . '\\add_global_site_state', 10, 2 );

	// Handles passing current user cookies to global content requests to allow authenticated requests.
	add_filter( 'http_request_args', __NAMESPACE__ . '\\filter_global_content_requests_for_auth', 10, 2 );
}

/**
 * Get the global content repository site ID.
 *
 * @return integer|null
 */
function get_site_id() : ?int {
	return get_site_option( 'global_content_site_id', null );
}

/**
 * Get the global content repository site URL.
 *
 * @return string|null
 */
function get_site_url() : ?string {
	return get_site_option( 'global_content_site_url', null );
}

/**
 * Returns true if the current site is the global media site.
 *
 * @param int|null $site_id An optional site ID to check. Defaults to the current site.
 * @return boolean
 */
function is_global_site( ?int $site_id = null ) : bool {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return false;
	}

	return ! empty( get_site_meta( $site_id ?? get_current_blog_id(), 'is_global_site' ) );
}

/**
 * Create the Global Content site.
 *
 * @throws Exception If site cannot be created.
 */
function maybe_create_site() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( 'Creating Global Content Repository site...' );
	}

	// Check if site exists.
	if ( ! empty( get_site_id() ) ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::line( 'Global Content Repository site exists, skipping.' );
		}
		return;
	}

	/**
	 * Filters the args used to create the global content site.
	 *
	 * @param array $global_site_args The arguments array for creating the global content site.
	 */
	$global_site_args = apply_filters( 'altis.core.global_content_site_args', [
		'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
		'path' => '/repo/',
		'public' => 0,
		'title' => __( 'Global Content Repository', 'altis' ),
	] );

	// Ensure user and global site meta is set.
	$global_site_args = wp_parse_args( $global_site_args, [
		'user_id' => get_user_by( 'login', get_super_admins()[0] )->ID,
		'meta' => [],
	] );

	$global_site_args['meta']['is_global_site'] = true;

	// Create the site.
	$site_id = wp_insert_site( $global_site_args );

	if ( is_wp_error( $site_id ) ) {
		/**
		 * The error response.
		 *
		 * @var \WP_Error $site_id
		 */
		throw new Exception( sprintf( 'Global media site could not be created. %s', $site_id->get_error_message() ) );
	}

	// Store the site URL and ID.
	$site = get_site( $site_id );
	$site_url = set_url_scheme( sprintf( 'https://%s%s', $site->domain, $site->path ) );
	update_site_option( 'global_content_site_url', rtrim( $site_url, '/' ) );
	update_site_option( 'global_content_site_id', $site_id );

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( WP_CLI::colorize( '%GGlobal Content Repository site created!%n' ) );
	}
}

/**
 * Handle deletion of media site.
 *
 * @return void
 */
function uninitialize_media_site() {
	if ( ! is_global_site() ) {
		return;
	}

	delete_site_option( 'global_content_site_url' );
	delete_site_option( 'global_content_site_id' );
}

/**
 * Get the allowed admin menu pages for the global site.
 *
 * @return array
 */
function get_allowed_admin_pages() : array {
	/**
	 * Filters the admin menu pages allowed on the Global Media Site admin menu.
	 *
	 * @param array $allowed_menu_pages The page slugs allowed in the global media site admin menu.
	 */
	$allowed_menu_pages = (array) apply_filters( 'altis.core.global_content_site_menu_pages', [] );

	// Always allow users.php.
	$allowed_menu_pages[] = 'users.php';
	$allowed_menu_pages[] = 'profile.php';
	$allowed_menu_pages[] = 'user-new.php';
	$allowed_menu_pages[] = 'user-edit.php';

	return $allowed_menu_pages;
}

/**
 * Trim down the global site's admin menu.
 *
 * @return void
 */
function admin_menu() : void {
	global $menu;

	if ( ! is_global_site() ) {
		return;
	}

	$allowed_menu_pages = get_allowed_admin_pages();

	foreach ( $menu as $position => $item ) {
		if ( ! in_array( $item[2], $allowed_menu_pages, true ) ) {
			unset( $menu[ $position ] );
		}
	}
}

/**
 * Modify the admin bar for the media site.
 *
 * @param WP_Admin_Bar $wp_admin_bar The menu bar control.
 * @return void
 */
function admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) : void {

	$wp_admin_bar->remove_node( sprintf( 'blog-%d-n', get_site_id() ) );
	$wp_admin_bar->remove_node( sprintf( 'blog-%d-c', get_site_id() ) );
	$wp_admin_bar->remove_node( sprintf( 'blog-%d-v', get_site_id() ) );

	if ( ! is_global_site() ) {
		return;
	}

	// Remove content and front end related menus.
	$wp_admin_bar->remove_menu( 'new-content' );
	$wp_admin_bar->remove_menu( 'comments' );
	$wp_admin_bar->remove_node( 'view-site' );

	// Add the site title in as static text.
	$wp_admin_bar->add_menu( [
		'id' => 'site-name',
		'title' => get_option( 'blogname' ),
		'href' => false,
	] );
}

/**
 * Filter the site row actions in network admin to prevent deletion.
 *
 * @param array $actions The action links array.
 * @param integer $site_id The site ID.
 * @return array
 */
function sites_list_row_actions( array $actions, int $site_id ) : array {
	if ( ! is_global_site( $site_id ) ) {
		return $actions;
	}

	unset( $actions['deactivate'] );
	unset( $actions['archive'] );
	unset( $actions['delete'] );
	unset( $actions['visit'] );

	return $actions;
}

/**
 * Filters the default site display states for items in the Sites list table.
 *
 * @param array $site_states An array of site states.
 * @param WP_Site $site The current site object.
 * @return array An array of site states.
 */
function add_global_site_state( array $site_states, WP_Site $site ) : array {
	if ( is_global_site( $site->id ) ) {
		$site_states[] = __( 'Global Content Repository', 'altis' );
	}

	return $site_states;
}

/**
 * Redirect to the first allowed menu page for the global site.
 *
 * @return void
 */
function redirect_admin_pages() : void {
	global $pagenow;

	if ( ! is_global_site() ) {
		return;
	}

	if ( $pagenow !== 'index.php' ) {
		return;
	}

	if ( in_array( 'index.php', get_allowed_admin_pages(), true ) ) {
		return;
	}

	wp_safe_redirect( admin_url( get_allowed_admin_pages()[0] ) );
	exit;
}

/**
 * Redirect to the media library from the media site frontend.
 *
 * @return void
 */
function redirect_frontend() : void {
	if ( ! is_global_site() ) {
		return;
	}

	if ( is_admin() ) {
		return;
	}

	wp_safe_redirect( admin_url( get_allowed_admin_pages()[0] ) );
	exit;
}

/**
 * Update network option on site URL changes.
 *
 * @param string $old_value The old option value.
 * @param string $value The new option value.
 * @return void
 */
function handle_siteurl_update( $old_value, $value ) : void {
	update_site_option( 'global_content_site_url', untrailingslashit( $value ) );
}

/**
 * Handle updating the global media site URL when edited from the network settings.
 *
 * @param WP_Site $site The site object.
 * @return void
 */
function handle_site_update( WP_Site $site ) : void {
	if ( ! is_global_site( $site->id ) ) {
		return;
	}

	// Build the new site URL as the new value isn't cached yet for get_site_url().
	$new_site_url = set_url_scheme( sprintf( 'https://%s%s', $site->domain, $site->path ) );

	update_site_option( 'global_content_site_url', untrailingslashit( $new_site_url ) );
}

/**
 * Prevent users deleting the global media site.
 *
 * @param string[] $caps Primitive capabilities required of the user.
 * @param string $cap Capability being checked.
 * @param int $user_id The user ID.
 * @param array $args Adds context to the capability check, typically starting with an object ID.
 * @return string[] Primitive capabilities required of the user.
 */
function prevent_site_deletion( array $caps, string $cap, int $user_id, array $args ) : array {
	if ( $cap !== 'delete_site' ) {
		return $caps;
	}

	if ( ! isset( $args[0] ) || intval( $args[0] ) !== (int) get_site_option( 'global_content_site_id' ) ) {
		return $caps;
	}

	return [ 'do_not_allow' ];
}

/**
 * Filter all requests to the global content repo to pass-through current user cookies
 *
 * @param array $parsed_args Parsed request arguments.
 * @param string $url Request URL.
 *
 * @filters http_request_args
 *
 * @return array Parsed request arguments including current session cookies.
 */
function filter_global_content_requests_for_auth( array $parsed_args, string $url ) : array {
	$global_site_url = get_site_url();

	if ( strpos( $url, $global_site_url ) !== 0 ) {
		return $parsed_args;
	}

	$parsed_args['cookies'] = array_map( function ( $value, $name ) {
		return new WP_Http_Cookie( [
			'name' => $name,
			'value' => $value,
		] );
	}, $_COOKIE, array_keys( $_COOKIE ) );

	return $parsed_args;
}
