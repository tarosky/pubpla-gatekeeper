<?php
/**
 * Tests for the post password REST endpoint.
 *
 * @package PubPla_Gatekeeper
 */

/**
 * Test post password override endpoint.
 */
class TestPostPasswordRest extends WP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Set up REST server.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $this->server );
	}

	/**
	 * Tear down REST server.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		parent::tear_down();
	}

	/**
	 * Dispatch a POST request to the endpoint.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $password Password.
	 * @param bool   $json     Send as apiFetch does (Accept: application/json).
	 * @return WP_REST_Response
	 */
	private function request( $post_id, $password, $json = true ) {
		$request = new WP_REST_Request( 'POST', '/pubpla-gatekeeper/v1/post-password/' . $post_id );
		$request->set_header( 'accept', $json ? 'application/json, */*;q=0.1' : 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' );
		$request->set_param( 'post_password', $password );
		return $this->server->dispatch( $request );
	}

	/**
	 * Create a published post with a password.
	 *
	 * @param string $password Password.
	 * @param array  $args     Extra post args.
	 * @return int
	 */
	private function protected_post( $password = 'secret', $args = array() ) {
		return self::factory()->post->create( array_merge( array( 'post_password' => $password ), $args ) );
	}

	/**
	 * Assert the response is a 303 redirect to the URL.
	 *
	 * @param string           $url      Expected URL.
	 * @param WP_REST_Response $response Response.
	 */
	private function assertRedirect( $url, $response ) {
		$this->assertSame( 303, $response->get_status() );
		$this->assertSame( $url, $response->get_headers()['Location'] );
	}

	/**
	 * The route is registered.
	 */
	public function test_route_registered() {
		$this->assertArrayHasKey( '/pubpla-gatekeeper/v1/post-password/(?P<post_id>\d+)', $this->server->get_routes() );
	}

	/**
	 * Non-existent post returns 404.
	 */
	public function test_missing_post() {
		$response = $this->request( 999999, 'secret' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_post_not_found', $response->get_data()['code'] );
	}

	/**
	 * Posts that are not publicly viewable look the same as missing posts.
	 *
	 * @dataProvider provider_non_public_status
	 * @param string $status Post status.
	 */
	public function test_non_public_post( $status ) {
		$post_id  = $this->protected_post( 'secret', array( 'post_status' => $status ) );
		$response = $this->request( $post_id, 'secret' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_post_not_found', $response->get_data()['code'] );
	}

	/**
	 * Non-public statuses.
	 *
	 * @return array
	 */
	public function provider_non_public_status() {
		return array(
			array( 'draft' ),
			array( 'private' ),
			array( 'pending' ),
		);
	}

	/**
	 * Empty password returns 400.
	 */
	public function test_empty_password() {
		$response = $this->request( $this->protected_post(), '' );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_post_no_password', $response->get_data()['code'] );
	}

	/**
	 * Wrong password returns 403.
	 */
	public function test_wrong_password() {
		$response = $this->request( $this->protected_post(), 'wrong' );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_post_wrong_password', $response->get_data()['code'] );
	}

	/**
	 * Correct password returns the permalink.
	 */
	public function test_correct_password() {
		$post_id  = $this->protected_post();
		$response = $this->request( $post_id, 'secret' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( get_permalink( $post_id ), $response->get_data()['url'] );
	}

	/**
	 * Passwords with backslashes are compared as-is (REST params are already unslashed).
	 */
	public function test_password_with_backslash() {
		// wp_insert_post() expects slashed data, as the editor sends it.
		$post_id = $this->protected_post( wp_slash( 'a\\b' ) );
		$this->assertSame( 'a\\b', get_post( $post_id )->post_password );
		$response = $this->request( $post_id, 'a\\b' );
		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Post without password returns its permalink.
	 */
	public function test_post_without_password() {
		$post_id  = self::factory()->post->create();
		$response = $this->request( $post_id, 'anything' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( get_permalink( $post_id ), $response->get_data()['url'] );
	}

	/**
	 * Form submissions without JS are redirected instead of receiving JSON.
	 */
	public function test_no_js_redirects() {
		$post_id   = $this->protected_post();
		$permalink = get_permalink( $post_id );
		$this->assertRedirect( $permalink, $this->request( $post_id, 'secret', false ) );
		$this->assertRedirect( $permalink, $this->request( $post_id, 'wrong', false ) );
		$this->assertRedirect( $permalink, $this->request( $post_id, '', false ) );
		// Missing and non-public posts go to the home page, revealing nothing.
		$this->assertRedirect( home_url( '/' ), $this->request( 999999, 'secret', false ) );
		$draft_id = $this->protected_post( 'secret', array( 'post_status' => 'draft' ) );
		$this->assertRedirect( home_url( '/' ), $this->request( $draft_id, 'secret', false ) );
	}

	/**
	 * The cookie value is accepted by core's post_password_required().
	 *
	 * Fails when core changes how post password cookies are hashed.
	 */
	public function test_cookie_compatible_with_core() {
		$post_id = $this->protected_post( 'secret' );
		$this->assertTrue( post_password_required( $post_id ) );

		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = wp_slash( PubPla_Gatekeeper_Rest_Post_Password::hash_password( 'secret' ) );
		$this->assertFalse( post_password_required( $post_id ) );

		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = wp_slash( PubPla_Gatekeeper_Rest_Post_Password::hash_password( 'wrong' ) );
		$this->assertTrue( post_password_required( $post_id ) );
	}

	/**
	 * Password form action always points to the REST endpoint.
	 */
	public function test_password_form_action() {
		$post_id = $this->protected_post();
		$form    = get_the_password_form( $post_id );
		$this->assertStringContainsString( rest_url( 'pubpla-gatekeeper/v1/post-password/' . $post_id ), $form );
	}
}
