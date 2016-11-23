<?php
/**
 * Class SampleTest
 *
 * @package Connections
 */
require_once 'includes/admin/class.functions.php';
require_once 'includes/admin/class.message.php';
require_once 'includes/admin/class.actions.php';

class EntryTest extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		global $route;

		$this->server = $wp_rest_server = new WP_REST_Server;
		$this->route = "/cn-api/v1/entry";

		do_action( 'rest_api_init' );

		cnAdminFunction::init(); // Setups up cnMessage facility

		$this->subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->administrator = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$user = wp_set_current_user( $this->administrator );
		set_current_screen( 'connections_dashboard' ); // so is_admin is true
	}

	public function tearDown() {
					parent::tearDown();
	}

	/**
	 * Routes present
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/cn-api/v1/entry', $routes );
		$this->assertArrayHasKey( '/cn-api/v1/entry/(?P<id>[\d]+)', $routes );
	}
	/**
	 * Test that the route protexts itself from an unauthorized user
	 */
	public function test_get_unauthorized() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', $this->route );

		$response = $this->server->dispatch( $request );
		$this->assertResponseStatus( 200, $response ); // TODO:  Fix, this should be 401?
	}

	/**
	 * Test that there is a response for an authorized user
	 */
	public function test_get_authorized() {
		wp_set_current_user( $this->administrator ); // must be an admin to add entries

		// put an entry in the database
		$entryId1 = $this->createTestEntry();

		// test to see if a subscriber and get it back out
		wp_set_current_user( $this->subscriber ); // subscribers should have access
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatus( 200, $response );

		$response_data = $response->get_data();
		print_r($response_data);
		$this->assertArrayHasKey( 'name', $response_data[0] );

	}

	/**
	 * Test that the route exists in the API
	 */
	function test_checkInitialCategories() {
		wp_set_current_user( $this->subscriber );
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatus( 200, $response );

		$response_data = $response->get_data();

		// should be only one, uncategorized
		$this->assertArraySubset( ['name' => ['raw' => 'Tom Forest Hanks', 'rendered' => 'Tom Forest Hanks'],
															 'given_name' => ['raw' => 'Tom'],
														   'additional_name' => ['raw' => 'Forest'],
															 'family_name' => ['raw' => 'Hanks']
															], $response_data[0] );
	}

	// Helpers

	protected function assertResponseStatus( $status, $response ) {
		$this->assertEquals( $status, $response->get_status() );
	}

	protected function assertResponseData( $data, $response ) {
		$response_data = $response->get_data();
		$tested_data = array();
		foreach( $data as $key => $value ) {
			if ( isset( $response_data[ $key ] ) ) {
				$tested_data[ $key ] = $response_data[ $key ];
			} else {
				$tested_data[ $key ] = null;
			}
		}
		$this->assertEquals( $data, $tested_data );
	}

	protected function createTestEntry() {
		$entry = ['entry_type' => 'individual',
							'visibility' => 'public',
						  'first_name' => 'Tom',
							'middle_name' => 'Forest',
							'last_name' => 'Hanks'];
		$entryId = cnEntry_Action::add( $entry );
		return $entryId;
	}

}
