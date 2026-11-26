<?php

/**
 * 投稿パスワードの送信先
 *
 * wp-login.php を Basic 認証の内側に置くと、コアのパスワードフォーム（action=postpass）が使えない。
 * そこでフォームの送信先をこの REST エンドポイントに差し替え、コアと同じ Cookie を発行する。
 */
class PubPla_Gatekeeper_Rest_Post_Password {

	/**
	 * REST APIエンドポイントのネームスペース
	 */
	const NAMESPACE = 'pubpla-gatekeeper/v1';

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
	 * パスワードを受け取り、投稿パスワードの Cookie を発行する
	 *
	 * JS（apiFetch）からのリクエストには JSON で、フォームの直接送信には 303 リダイレクトで応える。
	 * これで JS が読み込まれない・止まった環境でも、ブラウザーに JSON が表示されることはない。
	 *
	 * @see wp-login.php の action=postpass
	 * @param WP_REST_Request $request リクエストオブジェクト
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_post( $request ) {
		nocache_headers();
		$wants_json = $this->wants_json( $request );
		$post       = get_post( $request->get_param( 'post_id' ) );
		if ( ! $post || ! is_post_publicly_viewable( $post ) ) {
			// 下書き・非公開などもまとめて「見つからない」とし、存在する投稿 ID を漏らさない。
			return $wants_json
				? new WP_Error( 'rest_post_not_found', __( '該当するページは見つかりませんでした。', 'pubpla-gatekeeper' ), array( 'status' => 404 ) )
				: $this->redirect( home_url( '/' ) );
		}
		$redirect_to = get_permalink( $post );
		// REST のパラメーターは unslash 済みなので、ここで wp_unslash() してはいけない。
		$password = (string) $request->get_param( 'post_password' );
		if ( '' === $password ) {
			return $wants_json
				? new WP_Error( 'rest_post_no_password', __( 'パスワードが入力されていません。', 'pubpla-gatekeeper' ), array( 'status' => 400 ) )
				: $this->redirect( $redirect_to );
		}
		if ( empty( $post->post_password ) ) {
			// そもそもこの投稿にパスワードが指定されていない。
			return $this->success( $redirect_to, $wants_json );
		}
		if ( ! hash_equals( $post->post_password, $password ) ) {
			// 遷移すればフォームが再表示されるので、ここでエラーを返しても漏れる情報は増えない。
			return $wants_json
				? new WP_Error( 'rest_post_wrong_password', __( 'パスワードが違います。', 'pubpla-gatekeeper' ), array( 'status' => 403 ) )
				: $this->redirect( $redirect_to );
		}
		$this->set_cookie( $password );
		return $this->success( $redirect_to, $wants_json );
	}

	/**
	 * JS からのリクエストか（JSON の応答を期待しているか）
	 *
	 * apiFetch は Accept に application/json を付ける。ブラウザーのフォーム送信は付けない。
	 *
	 * @param WP_REST_Request $request リクエストオブジェクト
	 * @return bool
	 */
	private function wants_json( $request ) {
		return false !== strpos( (string) $request->get_header( 'accept' ), 'application/json' );
	}

	/**
	 * 成功時のレスポンス
	 *
	 * @param string $url        遷移先
	 * @param bool   $wants_json JSON で返すか
	 * @return WP_REST_Response
	 */
	private function success( $url, $wants_json ) {
		return $wants_json ? new WP_REST_Response( array( 'url' => $url ) ) : $this->redirect( $url );
	}

	/**
	 * リダイレクトのレスポンス（POST の後なので 303 See Other）
	 *
	 * @param string $url 遷移先
	 * @return WP_REST_Response
	 */
	private function redirect( $url ) {
		$response = new WP_REST_Response( array( 'url' => $url ), 303 );
		$response->header( 'Location', $url );
		return $response;
	}

	/**
	 * 投稿パスワードの Cookie の値を作る
	 *
	 * コアの wp-login.php（action=postpass）と同じ方式。post_password_required() が
	 * この値を検証するので、コアが方式を変えたらテストで検知する。
	 *
	 * @param string $password 平文のパスワード
	 * @return string
	 */
	public static function hash_password( $password ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );
		return $hasher->HashPassword( $password );
	}

	/**
	 * 投稿パスワードの Cookie を発行する
	 *
	 * @param string $password 平文のパスワード
	 * @return void
	 */
	private function set_cookie( $password ) {
		if ( headers_sent() ) {
			return;
		}
		/** This filter is documented in wp-login.php */
		$expire = apply_filters( 'post_password_expires', time() + 10 * DAY_IN_SECONDS );
		setcookie(
			'wp-postpass_' . COOKIEHASH,
			self::hash_password( $password ),
			array(
				'expires'  => $expire,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				// 同じサイトの中でしか使わないので Lax で足りる。
				'samesite' => 'Lax',
			)
		);
	}
}
