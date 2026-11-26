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
		parent::tear_down();
	}

	/**
	 * Dispatch a POST request to the endpoint.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $password Password.
	 * @return WP_REST_Response
	 */
	private function request( $post_id, $password ) {
		$request = new WP_REST_Request( 'POST', '/pubpla-gatekeeper/v1/post-password/' . $post_id );
		$request->set_param( 'post_password', $password );
		return $this->server->dispatch( $request );
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
	 * Empty password returns 400.
	 */
	public function test_empty_password() {
		$post_id  = self::factory()->post->create( array( 'post_password' => 'secret' ) );
		$response = $this->request( $post_id, '' );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_post_no_password', $response->get_data()['code'] );
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
	 * Password form action points to the REST endpoint when the script is available.
	 */
	public function test_password_form_action() {
		if ( ! wp_script_is( 'pubpla-gatekeeper-post-password', 'registered' ) ) {
			// Assets are not built in CI. Register a stub to simulate a built package.
			wp_register_script( 'pubpla-gatekeeper-post-password', 'https://example.com/post-password.js', array(), '1.0', true );
		}
		$post_id = self::factory()->post->create( array( 'post_password' => 'secret' ) );
		$form    = get_the_password_form( $post_id );
		$this->assertStringContainsString( rest_url( 'pubpla-gatekeeper/v1/post-password/' . $post_id ), $form );
	}

	/**
	 * Password form is left untouched when the script is not built.
	 */
	public function test_password_form_without_script() {
		wp_deregister_script( 'pubpla-gatekeeper-post-password' );
		$post_id = self::factory()->post->create( array( 'post_password' => 'secret' ) );
		$form    = get_the_password_form( $post_id );
		$this->assertStringNotContainsString( 'pubpla-gatekeeper/v1/post-password', $form );
		$this->assertStringContainsString( 'wp-login.php?action=postpass', $form );
	}
}
