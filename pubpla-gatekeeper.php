<?php
/**
 * Plugin Name: PubPla Gatekeeper
 * Plugin URI: https://github.com/tarosky/pubpla-gatekeeper
 * Description: Keep privileged accounts accountable: tiered login protection, dormant account isolation, and breached-password checks for WordPress.
 * Version: nightly
 * Author: Tarosky INC.
 * Author URI: https://tarosky.co.jp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pubpla-gatekeeper
 * Domain Path: /languages
 * Requires at least: 6.6
 * Tested up to: 7.1
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

// プラグインの定数定義
// バージョンはデプロイ時にリリースタグからヘッダーへ書き込まれる（nightly 方式）。
define( 'PUBPLA_GATEKEEPER_VERSION', get_file_data( __FILE__, array( 'version' => 'Version' ) )['version'] );
define( 'PUBPLA_GATEKEEPER_PATH', plugin_dir_path( __FILE__ ) );
define( 'PUBPLA_GATEKEEPER_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインのメインクラスを読み込み
 */
require_once PUBPLA_GATEKEEPER_PATH . 'includes/class-pubpla-gatekeeper.php';

/**
 * Register assets from wp-dependencies.json.
 */
function pubpla_gatekeeper_register_assets() {
	$json = __DIR__ . '/wp-dependencies.json';
	if ( ! file_exists( $json ) ) {
		return;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$dependencies = json_decode( file_get_contents( $json ), true );
	if ( empty( $dependencies ) ) {
		return;
	}
	$base = trailingslashit( plugin_dir_url( __FILE__ ) );
	foreach ( $dependencies as $dep ) {
		if ( empty( $dep['path'] ) ) {
			continue;
		}
		$url = $base . $dep['path'];
		switch ( $dep['ext'] ) {
			case 'css':
				wp_register_style( $dep['handle'], $url, $dep['deps'], $dep['hash'], $dep['media'] );
				break;
			case 'js':
				$footer = array( 'in_footer' => $dep['footer'] );
				if ( in_array( $dep['strategy'], array( 'defer', 'async' ), true ) ) {
					$footer['strategy'] = $dep['strategy'];
				}
				wp_register_script( $dep['handle'], $url, $dep['deps'], $dep['hash'], $footer );
				if ( in_array( 'wp-i18n', $dep['deps'], true ) ) {
					wp_set_script_translations( $dep['handle'], 'pubpla-gatekeeper' );
				}
				break;
		}
	}
}
add_action( 'init', 'pubpla_gatekeeper_register_assets' );

/**
 * プラグインの初期化
 */
function pubpla_gatekeeper_init() {
	PubPla_Gatekeeper::get_instance();
}

// プラグインの初期化
add_action( 'plugins_loaded', 'pubpla_gatekeeper_init' );
