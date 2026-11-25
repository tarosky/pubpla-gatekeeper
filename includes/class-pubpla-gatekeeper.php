<?php
/**
 * プラグインのメインクラス
 *
 * @package PubPla_Gatekeeper
 */

defined( 'ABSPATH' ) || exit;

/**
 * プラグインのメインクラス
 */
class PubPla_Gatekeeper {

	/**
	 * シングルトンインスタンス
	 *
	 * @var PubPla_Gatekeeper
	 */
	private static $instance = null;

	/**
	 * REST APIクラスのインスタンス
	 *
	 * @var PubPla_Gatekeeper_Rest_Post_Password
	 */
	private $rest_api;

	/**
	 * シングルトンパターンでインスタンスを取得
	 *
	 * @return PubPla_Gatekeeper
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * プラグインの初期化
	 */
	public function init() {
		// 依存ファイルの読み込み
		$this->load_dependencies();

		// REST APIの初期化
		$this->rest_api = new PubPla_Gatekeeper_Rest_Post_Password();

		// フック登録
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'the_password_form', array( $this, 'override_password_form' ), 10, 2 );
	}

	/**
	 * 依存ファイルの読み込み
	 */
	private function load_dependencies() {
		require_once PUBPLA_GATEKEEPER_PATH . 'includes/class-rest-post-password-override.php';
	}

	/**
	 * スクリプトの読み込み
	 */
	public function enqueue_scripts() {
		wp_register_script(
			'pubpla-gatekeeper-post-password',
			PUBPLA_GATEKEEPER_URL . 'assets/js/post-password.js',
			array( 'jquery', 'wp-api-fetch' ),
			PUBPLA_GATEKEEPER_VERSION,
			true
		);
	}

	/**
	 * パスワードフォームを変更する
	 *
	 * @param string   $output パスワードフォームのHTML出力
	 * @param \WP_Post $post   投稿オブジェクト
	 *
	 * @return string
	 */
	public function override_password_form( $output, $post ) {
		$rest_endpoint = rest_url( 'pubpla-gatekeeper/v1/post-password/' . $post->ID );
		wp_enqueue_script( 'pubpla-gatekeeper-post-password' );
		return preg_replace( '/action="[^"]+"/u', sprintf( 'action="%s"', esc_url( $rest_endpoint ) ), $output );
	}
}
