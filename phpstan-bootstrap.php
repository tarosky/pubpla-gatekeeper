<?php
/**
 * PHPStan bootstrap file.
 *
 * Defines constants that are set in the main plugin file but not available
 * during static analysis when includes/ files are analyzed in isolation.
 */

if ( ! defined( 'PUBPLA_GATEKEEPER_VERSION' ) ) {
	define( 'PUBPLA_GATEKEEPER_VERSION', '0.0.0' );
}

if ( ! defined( 'PUBPLA_GATEKEEPER_PATH' ) ) {
	define( 'PUBPLA_GATEKEEPER_PATH', __DIR__ . '/' );
}

if ( ! defined( 'PUBPLA_GATEKEEPER_URL' ) ) {
	define( 'PUBPLA_GATEKEEPER_URL', 'https://example.com/wp-content/plugins/pubpla-gatekeeper/' );
}

// WordPress runtime constants not covered by the stubs.
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', md5( 'https://example.com' ) );
}
