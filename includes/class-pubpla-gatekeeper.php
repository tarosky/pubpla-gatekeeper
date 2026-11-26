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
		new PubPla_Gatekeeper_Rest_Post_Password();

		// フック登録（スクリプトは wp-dependencies.json から init で登録済み）
		add_filter( 'the_password_form', array( $this, 'override_password_form' ), 10, 2 );
	}

	/**
	 * 依存ファイルの読み込み
	 */
	private function load_dependencies() {
		require_once PUBPLA_GATEKEEPER_PATH . 'includes/class-rest-post-password-override.php';
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
		// エンドポイントは JS なしのフォーム送信にもリダイレクトで応えるので、送信先は常に書き換える。
		// JS はエラーをフォーム上に表示するための上乗せで、未ビルドなら登録されず何もしない。
		$rest_endpoint = rest_url( 'pubpla-gatekeeper/v1/post-password/' . $post->ID );
		wp_enqueue_script( 'pubpla-gatekeeper-post-password' );
		return preg_replace( '/action="[^"]+"/u', sprintf( 'action="%s"', esc_url( $rest_endpoint ) ), $output );
	}
}
