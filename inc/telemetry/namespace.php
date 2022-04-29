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

const ACTION_OPT_IN = 'altis_tracking_opt_in';
const META_OPT_IN = 'altis_tracking_opt_in';
const SEGMENT_ID = 'GHqd7Vfs060yZBWOEGV4ajz3S3QHYKhk';

/**
 * Register hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'admin_init', __NAMESPACE__ . '\\handle_opt_in_form' );
	add_action( 'admin_head', __NAMESPACE__ . '\\load_segment_js' );
	add_action( 'admin_footer', __NAMESPACE__ . '\\render_identity_tag' );
	add_action( 'in_admin_header', __NAMESPACE__ . '\\render_opt_in_form' );
	add_action( 'profile_personal_options', __NAMESPACE__ . '\\user_profile_options' );
	add_action( 'personal_options_update', __NAMESPACE__ . '\\handle_profile_form' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_api_routes' );

	// Default event tracking.
	add_action( 'save_post', __NAMESPACE__ . '\\track_new_or_updated_content', 10, 3 );

	// Allow action hook for tracking, this makes it easy to track events in other code
	// without having a direct dependency on this module.
	add_action( 'altis.telemetry.track', __NAMESPACE__ . '\\track' );
}

/**
 * Get the segment ID for tracking.
 *
 * @return string
 */
function get_segment_id() : string {
	if ( defined( 'ALTIS_SEGMENT_ID' ) ) {
		return ALTIS_SEGMENT_ID;
	}
	return SEGMENT_ID;
}

/**
 * Initialize segment.io.
 *
 * @return bool True if Segment has initialised successfully.
 */
function initialize() : bool {
	static $initialized;
	if ( is_bool( $initialized ) ) {
		return $initialized;
	}

	if ( ! is_user_logged_in() ) {
		$initialized = false;
		return $initialized;
	}

	// Connect.
	Segment::init( get_segment_id() );

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
	return $initialized;
}

/**
 * Track an event in Segment.io.
 *
 * @param array $message The event details.
 * @return void
 */
function track( array $message ) {
	if ( ! initialize() ) {
		return;
	}

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
		return null;
	}

	$did_opt_in = get_user_meta( $user->ID, META_OPT_IN, true );

	if ( $did_opt_in === '' ) {
		return null;
	}

	return intval( $did_opt_in ) === 1;
}

/**
 * Return an automatic, pseudo-anonymous ID for the current user.
 *
 * @return string
 */
function get_anonymous_id() : string {
	$current_user = wp_get_current_user();
	return substr( sha1( $current_user->ID . $current_user->user_registered ), 0, 8 );
}

/**
 * Return a known ID for the current user.
 *
 * @return string
 */
function get_id() : string {
	$current_user = wp_get_current_user();
	return sha1( $current_user->user_email );
}

/**
 * Get details for the current user.
 *
 * @return array
 */
function get_segmentio_user_details() : array {
	$current_user = wp_get_current_user();
	$auto_id = get_anonymous_id();
	$did_opt_in = is_user_opted_in( $current_user );

	if ( ! $did_opt_in ) {
		return [
			'id' => $auto_id,
			'opt_in' => false,
		];
	}

	$email = $current_user->user_email;
	$id = get_id();
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
		'roles' => (array) $current_user->roles,
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

	if ( is_multisite() ) {
		// Hash the registration date of the first site to get a unique ID.
		$site = get_blog_details( get_main_site_id(), false );
		$generated = 'local-' . substr( hash( 'sha1', $site->registered ), 0, 6 );
	} else {
		// Get the oldest piece of content.
		global $wpdb;
		$first = $wpdb->get_col( "SELECT post_date FROM $wpdb->posts ORDER BY post_date ASC LIMIT 1" );
		$generated = 'local-' . substr( hash( 'sha1', $first[0] ), 0, 6 );
	}
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

	$traits = [
		'site_name' => get_site_option( 'site_name', get_option( 'blogname', '' ) ),
		'environment' => $type,
		'domain' => get_site_url(),
		'multisite' => is_multisite(),
		'feature_tier' => Altis\get_feature_tier(),
		'environment_tier' => Altis\get_environment_tier(),
		'support_tier' => Altis\get_support_tier(),
		'version' => Altis\get_version(),
	];

	/**
	 * Filter environment traits delivered to segment.io.
	 *
	 * @param array $traits Environment traits to send to Segment.io.
	 */
	$traits = (array) apply_filters( 'altis.telemetry.env_traits', $traits );

	return [
		'id' => $id,
		'traits' => $traits,
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
		!function(){var analytics=window.analytics=window.analytics||[];if(!analytics.initialize)if(analytics.invoked)window.console&&console.error&&console.error("Segment snippet included twice.");else{analytics.invoked=!0;analytics.methods=["trackSubmit","trackClick","trackLink","trackForm","pageview","identify","reset","group","track","ready","alias","debug","page","once","off","on","addSourceMiddleware","addIntegrationMiddleware","setAnonymousId","addDestinationMiddleware"];analytics.factory=function(e){return function(){var t=Array.prototype.slice.call(arguments);t.unshift(e);analytics.push(t);return analytics}};for(var e=0;e<analytics.methods.length;e++){var key=analytics.methods[e];analytics[key]=analytics.factory(key)}analytics.load=function(key,e){var t=document.createElement("script");t.type="text/javascript";t.async=!0;t.src="https://cdn.segment.com/analytics.js/v1/" + key + "/analytics.min.js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(t,n);analytics._loadOptions=e};analytics._writeKey="<?php echo esc_js( get_segment_id() ); ?>";analytics.SNIPPET_VERSION="4.13.2";
			analytics.load( <?php echo wp_json_encode( get_segment_id() ) ?> );
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
 * Opt in or out of tracking.
 *
 * @param boolean $did_opt_in If true will opt the user in.
 * @param WP_User|null $user Optional user object, defaults to the current user.
 * @return void
 */
function opt_in( bool $did_opt_in, ?WP_User $user = null ) {
	$user = $user ?? wp_get_current_user();
	update_user_meta( $user->ID, META_OPT_IN, $did_opt_in ? 1 : 0 );

	// Alias previously unknown user if they opted in.
	if ( $did_opt_in ) {
		if ( ! initialize() ) {
			return;
		}
		Segment::alias( [
			'userId' => get_id(),
			'previousId' => get_anonymous_id(),
		] );
	}
}

/**
 * Enqueue CSS for opt in UI.
 *
 * @return void
 */
function enqueue_scripts() {
	if ( ! is_null( is_user_opted_in() ) ) {
		return;
	}

	wp_enqueue_style(
		'altis-telemetry',
		plugins_url( 'assets/opt-in.css', dirname( __FILE__, 2 ) )
	);
}

/**
 * Display the UI for opting in / out of telemetry.
 *
 * @return void
 */
function render_opt_in_form() {
	$user = wp_get_current_user();

	// Don't render if they've made a decision.
	if ( ! is_null( is_user_opted_in( $user ) ) ) {
		return;
	}

	?>
	<div class="welcome altis-telemetry-opt-in">
		<h2><?php echo esc_html( sprintf( __( 'Hi %s!', 'altis' ), $user->display_name ) ); ?></h2>
		<p><?php esc_html_e( 'To help us develop Altis, we would like to collect data on your usage of the software. This will help us build a better product for you.', 'altis' ); ?></p>
		<form method="post" action="">
			<?php wp_nonce_field( 'altis_telemetry_opt_in' ); ?>
			<input type="submit" name="altis_telemetry_opt_in" class="button button-primary" value="<?php esc_attr_e( 'Sounds good to me!', 'altis' ); ?>" />
			<input type="submit" name="altis_telemetry_opt_out" class="button button-secondary" value="<?php esc_attr_e( 'No thanks', 'altis' ); ?>" />
		</form>
		<p><?php esc_html_e( 'You can change this setting at any time on your profile page.' ); ?></p>
	</div>
	<?php
}

/**
 * Fires after the 'About the User' settings table on the 'Edit User' screen.
 *
 * @param WP_User $user The current WP_User object.
 */
function user_profile_options( WP_User $user ) : void {
	?>
	<table class="form-table">
		<tr>
			<th>
				<label for="altis_telemetry_opt_in"><?php esc_html_e( 'Altis Telemetry', 'altis' ); ?></label>
			</th>
			<td>
				<input type="checkbox" name="altis_telemetry_opt_in_toggle" id="altis_telemetry_opt_in" <?php checked( is_user_opted_in( $user ) ) ?> value="1" />
				<label for="altis_telemetry_opt_in"><?php esc_html_e( 'Opt in to Altis Telemetry', 'altis' ); ?></label>
				<p class="description"><?php esc_html_e( 'To help us develop Altis, we would like to collect data on your usage of the software. This will help us build a better product for you.', 'altis' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Handle opt in form submission.
 *
 * @return void
 */
function handle_opt_in_form() {
	if ( isset( $_POST['altis_telemetry_opt_in'] ) && check_admin_referer( 'altis_telemetry_opt_in' ) ) {
		opt_in( true );
	}
	if ( isset( $_POST['altis_telemetry_opt_out'] ) && check_admin_referer( 'altis_telemetry_opt_in' ) ) {
		opt_in( false );
	}
}

/**
 * Handle user profile opt in field.
 *
 * @param int $user_id The ID of the current user.
 * @return void
 */
function handle_profile_form( int $user_id ) {
	if ( ! check_admin_referer( 'update-user_' . $user_id ) ) {
		return;
	}

	if ( isset( $_POST['altis_telemetry_opt_in_toggle'] ) ) {
		opt_in( true );
	} else {
		opt_in( false );
	}
}

/**
 * Register the welcome and tracking opt in API route.
 *
 * @return void
 */
function register_api_routes() {
	register_rest_route( Altis\API_NAMESPACE, '/telemetry', [
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
	opt_in( $did_opt_in );

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
