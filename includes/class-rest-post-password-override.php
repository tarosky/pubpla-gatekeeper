<?php

/**
 * パスワード保護機能改善
 *
 * wp-login.phpにBasic認証がかかっていると「パスワード保護」がうまく動かない。
 * なので、別のフィルターを使ってパスワードフォームを出力する。
 * また、パスワード保護エンドポイントの実装もコピーする。
 *
 * このAPIは、特定の条件下でパスワードをオーバーライドするために使用されます。
 * 例えば、特定のユーザーが特定のコンテンツにアクセスする際に、通常のパスワードチェックをスキップすることができます。
 */
class Taro_Rest_Post_Password_Override {

	/**
	 * REST APIエンドポイントのネームスペース
	 */
	const NAMESPACE = 'taro-bypass-password-protection/v1';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * REST APIルートの登録
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/post-password/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_post' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'args'                => $this->get_arguments(),
			)
		);
	}

	/**
	 * REST APIエンドポイントの引数定義
	 *
	 * @return array
	 */
	private function get_arguments() {
		return array(
			'post_id'       => array(
				'type'     => 'integer',
				'required' => true,
				'default'  => 0,
			),
			'post_password' => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}

	/**
	 * 誰でもリクエストできる
	 *
	 * @param WP_REST_Request $request リクエストオブジェクト
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return true;
	}

	/**
	 * 通常のREST APIエンドポイントと異なり、リダイレクト処理を行う
	 *
	 * @see wp-login.php
	 * @param WP_REST_Request $request リクエストオブジェクト
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_post( $request ) {
		nocache_headers();
		$redirect_to = home_url();
		$post        = get_post( $request->get_param( 'post_id' ) );
		if ( ! $post ) {
			return new WP_Error(
				'rest_post_not_found',
				__( '該当するページは見つかりませんでした。', 'taro-bypass-password-protection' ),
				array( 'status' => 404 )
			);
		}
		// 投稿自体は存在しているので、パーマリンクを取得。
		$redirect_to = get_permalink( $post );
		$password    = $request->get_param( 'post_password' );
		if ( empty( $password ) ) {
			// パスワードが空
			return new WP_Error(
				'rest_post_no_password',
				__( 'パスワードが設定されていません。', 'taro-bypass-password-protection' ),
				array( 'status' => 400 )
			);
		}
		if ( empty( $post->post_password ) ) {
			// そもそもこの投稿にパスワードが指定されていない
			return new WP_REST_Response( array(
				'url' => $redirect_to,
			) );
		}
		// ここからパスワード認証処理開始。
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );
		$expire = apply_filters( 'post_password_expires', time() + 10 * DAY_IN_SECONDS );
		$secure = is_ssl();
		
		// REST API経由でのクッキーパス問題を解決するため、サイトのホームURLからパスを取得
		$home_path = wp_parse_url( home_url(), PHP_URL_PATH );
		$cookie_path = $home_path ? rtrim( $home_path, '/' ) . '/' : '/';
		
		$args   = array(
			'expires' => $expire,
			'path'    => $cookie_path,
			'domain'  => COOKIE_DOMAIN,
			'secure'  => $secure,
		);
		if ( $secure ) {
			$args['samesite'] = 'None';
		}
		setcookie(
			'wp-postpass_' . COOKIEHASH,
			$hasher->HashPassword( wp_unslash( $password ) ),
			$args
		);
		return new WP_REST_Response( array(
			'url' => $redirect_to,
		) );
	}
}
