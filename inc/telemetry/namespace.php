<?php
/**
 * Altis Product Telemetry.
 *
 * @package altis/core
 */

namespace Altis\Telemetry;

use Altis;
use Segment\Segment;
use WP_REST_Request;
use WP_REST_Server;
use WP_User;

const API_NAMESPACE = 'altis/v1';
const ACTION_OPT_IN = 'altis_tracking_opt_in';
const META_OPT_IN = 'altis_tracking_opt_in';
const SEGMENT_ID = 'GHqd7Vfs060yZBWOEGV4ajz3S3QHYKhk';

/**
 * Register hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'admin_head', __NAMESPACE__ . '\\load_segment_js' );
	add_action( 'admin_footer', __NAMESPACE__ . '\\render_identity_tag' );

	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_api_routes' );

	// Default event tracking.
	add_action( 'save_post', __NAMESPACE__ . '\\track_new_or_updated_content', 10, 3 );

	// Allow action hook for tracking, this makes it easy to track events in other code
	// without having a direct dependency on this module.
	add_action( 'altis.telemetry.track', __NAMESPACE__ . '\\track' );
}

/**
 * Initialize segment.io.
 */
function initialize() {
	static $initialized;
	if ( $initialized ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	// Connect.
	Segment::init( SEGMENT_ID );

	// Identify our user.
	$user = get_segmentio_user_details();
	if ( ! $user['opt_in'] ) {
		Segment::identify( [
			'anonymousId' => $user['id'],
		] );
	} else {
		Segment::identify( [
			'userId' => $user['id'],
			'traits' => $user['traits'],
		] );
	}

	// Set the group data.
	$environment = get_environment_details();
	if ( $environment['id'] ) {
		Segment::group( [
			'userId' => $user['id'],
			'groupId' => $environment['id'],
			'traits' => $environment['traits'],
		] );
	}

	$initialized = true;
}

/**
 * Track an event in Segment.io.
 *
 * @param array $message The event details.
 * @return void
 */
function track( array $message ) {
	initialize();

	// Add user ID if missing.
	if ( ! isset( $message['userId'] ) ) {
		$user = get_segmentio_user_details();
		$message['userId'] = $user['id'];
	}

	Segment::track( $message );
}

/**
 * Check user has opted in to tracking.
 *
 * @param WP_User|null $user Current user object if available.
 * @return boolean|null
 */
function is_user_opted_in( ?WP_User $user = null ) : ?bool {
	if ( ! $user ) {
		$user = wp_get_current_user();
	}
	if ( ! $user->exists() ) {
		return false;
	}

	return (bool) get_user_meta( $user->ID, META_OPT_IN, true );
}

/**
 * Get details for the current user.
 *
 * @return array
 */
function get_segmentio_user_details() : array {
	$current_user = wp_get_current_user();
	$auto_id = substr( bin2hex( $current_user->user_registered ), 0, 8 );
	$did_opt_in = is_user_opted_in( $current_user );

	if ( ! $did_opt_in ) {
		return [
			'id' => $auto_id,
			'opt_in' => false,
		];
	}

	$email = $current_user->user_email;
	$id = bin2hex( $current_user->user_email );
	if ( Altis\get_environment_type() === 'local' ) {
		// Create distinct_id (important for Mixpanel).
		$id = $auto_id;
		$email = 'no-reply+' . $auto_id . '@altis.dev';
	}

	$traits = [
		'first_name' => $current_user->first_name,
		'last_name' => $current_user->last_name,
		'email' => $email,
		'username' => $current_user->user_login,
		'created' => $current_user->user_registered,
		'avatar' => get_avatar_url( $current_user->ID ),
	];

	/**
	 * Filter user traits delivered to segment.io.
	 *
	 * @param array $traits User traits to send to Segment.io.
	 */
	$traits = (array) apply_filters( 'altis.telemetry.user_traits', $traits );

	return [
		'id' => $id,
		'opt_in' => true,
		'traits' => $traits,
	];
}

/**
 * Get a unique ID for the current (local) environment.
 *
 * Generates a persistent, unique ID for the current local environment.
 *
 * @return string|null Null for non-local environments, unique ID otherwise.
 */
function get_local_install_id() : ?string {
	if ( Altis\get_environment_type() !== 'local' ) {
		return null;
	}

	$id = get_network_option( null, 'altis_tracking_id', null );
	if ( ! empty( $id ) ) {
		return $id;
	}

	// Hash the registration date of the first site to get a unique ID.
	$site = get_blog_details( get_main_site_id(), false );
	$generated = 'local-' . substr( hash( 'sha1', $site->registered ), 0, 6 );
	update_network_option( null, 'altis_tracking_id', $generated );

	return $generated;
}

/**
 * Get current Altis environment details.
 *
 * @return array
 */
function get_environment_details() : array {
	$id = Altis\get_environment_name();
	$type = Altis\get_environment_type();
	if ( $type === 'local' ) {
		// Create distinct_id (important for Mixpanel).
		$id = get_local_install_id();
	}

	return [
		'id' => $id,
		'traits' => [
			'environment' => $type,
			'domain' => get_site_url(),
			'feature_tier' => Altis\get_feature_tier(),
			'environment_tier' => Altis\get_environment_tier(),
			'support_tier' => Altis\get_support_tier(),
		],
	];
}

/**
 * Load segment.io JS.
 *
 * @return void
 */
function load_segment_js() {
	?>
	<script>
		// Segment - Load
		!function(){var analytics=window.analytics=window.analytics||[];if(!analytics.initialize)if(analytics.invoked)window.console&&console.error&&console.error("Segment snippet included twice.");else{analytics.invoked=!0;analytics.methods=["trackSubmit","trackClick","trackLink","trackForm","pageview","identify","reset","group","track","ready","alias","debug","page","once","off","on","addSourceMiddleware","addIntegrationMiddleware","setAnonymousId","addDestinationMiddleware"];analytics.factory=function(e){return function(){var t=Array.prototype.slice.call(arguments);t.unshift(e);analytics.push(t);return analytics}};for(var e=0;e<analytics.methods.length;e++){var key=analytics.methods[e];analytics[key]=analytics.factory(key)}analytics.load=function(key,e){var t=document.createElement("script");t.type="text/javascript";t.async=!0;t.src="https://cdn.segment.com/analytics.js/v1/" + key + "/analytics.min.js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(t,n);analytics._loadOptions=e};analytics._writeKey="GHqd7Vfs060yZBWOEGV4ajz3S3QHYKhk";analytics.SNIPPET_VERSION="4.13.2";
			analytics.load( <?php echo wp_json_encode( SEGMENT_ID ) ?> );
			analytics.page();
		}}();
	</script>
	<?php
}

/**
 * Identify user to segment.io.
 *
 * @return void
 */
function render_identity_tag() {
	$user = get_segmentio_user_details();
	?>
	<script>
		(function () {
			var segment_user = <?php echo wp_json_encode( $user ) ?>;
			if ( ! segment_user.opt_in ) {
				analytics.setAnonymousId( segment_user.id );
			} else {
				analytics.identify( segment_user.id, segment_user.traits );
			}
		})();
	</script>
	<?php
}

/**
 * Register the welcome and tracking opt in API route.
 *
 * @return void
 */
function register_api_routes() {
	register_rest_route( API_NAMESPACE, '/telemetry', [
		'methods' => WP_REST_Server::EDITABLE,
		'permission_callback' => function () {
			return is_user_logged_in();
		},
		'callback' => __NAMESPACE__ . '\\handle_telemetry_endpoint',
		'args' => [
			'opt_in' => [
				'type' => 'boolean',
				'default' => false,
			],
		],
	] );
}

/**
 * Handle telemetry API request.
 *
 * @param WP_REST_Request $request The welcome APi reequest object.
 * @return array
 */
function handle_telemetry_endpoint( WP_REST_Request $request ) {
	$current_user = wp_get_current_user();
	$did_opt_in = $request['opt_in'];
	update_user_meta( $current_user->ID, META_OPT_IN, $did_opt_in );

	$data = [
		'id' => $current_user->ID,
		'tracking' => [
			'opt_in' => $did_opt_in,
		],
	];

	if ( $did_opt_in ) {
		$data['tracking_data'] = get_segmentio_user_details();
	}

	return $data;
}

/**
 * Push post save event to Segment.io.
 *
 * @param int $post_id The saved post ID.
 * @param WP_Post $post The post object.
 * @param bool $update True if this is an update and not an insert.
 * @return void
 */
function track_new_or_updated_content( $post_id, $post, $update ) {
	// Bail on auto-save.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( $update ) {
		$action = 'update';
	} else {
		$action = 'create';
	}

	track( [
		'event' => 'Content',
		'properties' => [
			'content_type' => $post->post_type,
			'content_action' => $action,
		],
	] );
}
