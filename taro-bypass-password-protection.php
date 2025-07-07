<?php
/**
 * Plugin Name: Taro Bypass Password Protection
 * Plugin URI: https://github.com/tarosky/taro-bypass-password-protection
 * Description: WordPressのパスワード保護機能をBasic認証下でも動作させるプラグイン
 * Version: 1.0.0
 * Author: Tarosky INC.
 * Author URI: https://tarosky.co.jp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: taro-bypass-password-protection
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

// プラグインの定数定義
define( 'TARO_BYPASS_PASSWORD_PROTECTION_VERSION', '1.0.0' );
define( 'TARO_BYPASS_PASSWORD_PROTECTION_PATH', plugin_dir_path( __FILE__ ) );
define( 'TARO_BYPASS_PASSWORD_PROTECTION_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインのメインクラスを読み込み
 */
require_once TARO_BYPASS_PASSWORD_PROTECTION_PATH . 'includes/class-taro-bypass-password-protection.php';

/**
 * プラグインの初期化
 */
function taro_bypass_password_protection_init() {
	Taro_Bypass_Password_Protection::get_instance();
}

// プラグインの初期化
add_action( 'plugins_loaded', 'taro_bypass_password_protection_init' );
