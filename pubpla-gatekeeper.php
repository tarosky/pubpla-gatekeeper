<?php
/**
 * Plugin Name: PubPla Gatekeeper
 * Plugin URI: https://github.com/tarosky/pubpla-gatekeeper
 * Description: Keep privileged accounts accountable: tiered login protection, dormant account isolation, and breached-password checks for WordPress.
 * Version: 0.1.0
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
define( 'PUBPLA_GATEKEEPER_VERSION', '0.1.0' );
define( 'PUBPLA_GATEKEEPER_PATH', plugin_dir_path( __FILE__ ) );
define( 'PUBPLA_GATEKEEPER_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインのメインクラスを読み込み
 */
require_once PUBPLA_GATEKEEPER_PATH . 'includes/class-pubpla-gatekeeper.php';

/**
 * プラグインの初期化
 */
function pubpla_gatekeeper_init() {
	PubPla_Gatekeeper::get_instance();
}

// プラグインの初期化
add_action( 'plugins_loaded', 'pubpla_gatekeeper_init' );
