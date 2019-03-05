<?php
/**
 * Altis Core Loader.
 *
 * @package altis-core
 */

const ALTIS_CORE_DIR = __DIR__;

include ALTIS_CORE_DIR . '/inc/namespace.php';

// Define the env type as local if not set higher up the chain.
if ( ! defined( 'HM_ENV_TYPE' ) ) {
	define( 'HM_ENV_TYPE', 'local' );
}
