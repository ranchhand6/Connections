<?php
/**
 * REST API Entry Controller
 *
 * @author   Steven A. Zahm
 * @category API
 * @package  Connections/API
 * @since    8.5.26
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Entry Controller.
 *
 * @package Connections/API
 * @extends WP_REST_Controller
 */
class CN_REST_Entry_Controller extends WP_REST_Controller {

	/**
	 * @since 8.5.26
	 */
	const VERSION = '1';

	/**
	 * @since 8.5.26
	 * @var string
	 */
	protected $namespace;

	/**
	 * @since 8.5.26
	 * @var string
	 */
	protected $base = 'entry';

	/**
	 * @since 8.5.26
	 */
	public function __construct() {

		$this->namespace = 'cn-api/v' . self::VERSION;
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 8.5.26
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => FALSE,
							'description' => __( 'Required to be true, as resource does not support trashing.', 'connections' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read /entry.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( 'edit' === $request['context'] &&
		     ( ! current_user_can( 'connections_edit_entry' ) || ! current_user_can( 'connections_edit_entry_moderated' ) )
		) {

			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit these entries.', 'connections' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return TRUE;
	}

	/**
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function get_entries( $request ) {

		// Grab an instance of the Connections object.
		$instance = Connections_Directory();

		$atts = array(
			'limit'  => $request['per_page'],
			'offset' => $request['offset'],
		);

		$results = $instance->retrieve->entries( $atts );

		return $results;
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$results = $this->get_entries( $request );

		$entries = array();

		foreach ( $results as $result ) {

			$entry = new cnEntry( $result );

			$data = $this->prepare_item_for_response( $entry, $request );
			$entries[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $entries );

		return $response;
	}

	/**
	 * Prepare a single entry output for response.
	 *
	 * @param cnEntry         $entry   Post object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $entry, $request ) {

		$data = array();

		//$entry->directoryHome(
		//	array(
		//		'page_id'    => $homeID,
		//		'force_home' => $forceHome,
		//	)
		//);

		$data['id']   = $entry->getId();
		$data['type'] = $entry->getEntryType();
		$data['slug'] = $entry->getSlug();

		$data['name'] = array(
			'raw'      => $entry->getName( array(), 'raw' ),
			'rendered' => $entry->getName(),
		);

		$data['honorific_prefix'] = array(
			'raw'      => $entry->getHonorificPrefix( 'raw' ),
			'rendered' => $entry->getHonorificPrefix(),
		);

		$data['given_name'] = array(
			'raw'      => $entry->getFirstName( 'raw' ),
			'rendered' => $entry->getFirstName(),
		);

		$data['additional_name'] = array(
			'raw'      => $entry->getMiddleName( 'raw' ),
			'rendered' => $entry->getMiddleName(),
		);

		$data['family_name'] = array(
			'raw'      => $entry->getLastName( 'raw' ),
			'rendered' => $entry->getLastName(),
		);

		$data['honorific_suffix'] = array(
			'raw'      => $entry->getHonorificSuffix( 'raw' ),
			'rendered' => $entry->getHonorificSuffix(),
		);

		$data['job_title'] = array(
			'raw'      => $entry->getTitle( 'raw' ),
			'rendered' => $entry->getTitle(),
		);

		$data['org'] = array(
			'organization_name' => array(
				'raw'      => $entry->getDepartment( 'raw' ),
				'rendered' => $entry->getDepartment(),
			),
			'organization_unit' => array(
				'raw'      => $entry->getOrganization( 'raw' ),
				'rendered' => $entry->getOrganization(),
			),
		);

		$data['contact'] = array(
			'given_name' => array(
				'raw'      => $entry->getContactFirstName( 'raw' ),
				'rendered' => $entry->getContactFirstName(),
			),
			'family_name' => array(
				'raw'      => $entry->getContactLastName( 'raw' ),
				'rendered' => $entry->getContactLastName(),
			),
		);

		$data = $this->prepare_address_for_response( $entry, $request, $data );
		$data = $this->prepare_phone_for_response( $entry, $request, $data );
		$data = $this->prepare_emails_for_response( $entry, $request, $data );
		$data = $this->prepare_photo_for_response( $entry, $request, $data );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Prepare a single address output for response.
	 *
	 * @param cnEntry         $entry   Post object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $data
	 *
	 * @return array $data
	 */
	private function prepare_address_for_response( $entry, $request, $data ) {

		$data['addresses'] = array();
		$rendered_addresses = $entry->getAddresses();
		$raw_addresses      = $entry->getAddresses( array(), TRUE, FALSE, 'raw' );

		if ( empty( $rendered_addresses ) ) return $data;
		if ( count( $rendered_addresses ) != count( $raw_addresses ) ) {
			// error, log and bail
			return $data;
		}

		//foreach ( $rendered_addresses as $address ) {
		for ($i = 0; $i < count($rendered_addresses); ++$i) {

			$item = array(
				'preferred'   => array(
					'raw'      => $raw_addresses[$i]->preferred,
					'rendered' => $rendered_addresses[$i]->preferred,
				),
				'street_address'   => array(
					'raw'      => $raw_addresses[$i]->line_1,
					'rendered' => $rendered_addresses[$i]->line_1,
				),
				'extended_address' => array(
					'raw'      => $raw_addresses[$i]->line_2,
					'rendered' => $rendered_addresses[$i]->line_2,
				),
				'street_address_3' => array(
					'raw'      => $raw_addresses[$i]->line_3,
					'rendered' => $rendered_addresses[$i]->line_3,
				),
				'street_address_4' => array(
					'raw'      => $raw_addresses[$i]->line_4,
					'rendered' => $rendered_addresses[$i]->line_4,
				),
				'locality'         => array(
					'raw'      => $raw_addresses[$i]->city,
					'rendered' => $rendered_addresses[$i]->city,
				),
				'city'       => array(
					'raw'      => $raw_addresses[$i]->city,
					'rendered' => $rendered_addresses[$i]->city,
				),
				'region'     => array(
					'raw'      => $raw_addresses[$i]->state,
					'rendered' => $rendered_addresses[$i]->state,
				),
				'state'      => array(
					'raw'      => $raw_addresses[$i]->state,
					'rendered' => $rendered_addresses[$i]->state,
				),
				'district'   => array(
					'raw'      => $raw_addresses[$i]->county,
					'rendered' => $rendered_addresses[$i]->county,
				),
				'county'           => array(
					'raw'      => $raw_addresses[$i]->county,
					'rendered' => $rendered_addresses[$i]->county,
				),
				'postal_code'      => array(
					'raw'      => $raw_addresses[$i]->zipcode,
					'rendered' => $rendered_addresses[$i]->zipcode,
				),
				'country_name'     => array(
					'raw'      => $raw_addresses[$i]->country,
					'rendered' => $rendered_addresses[$i]->country,
				),
				'order'     => array(
					'raw'      => $raw_addresses[$i]->order,
					'rendered' => $rendered_addresses[$i]->order,
				),
			);

			$data['addresses'][] = $item;
		}

		return $data;
	}

	/**
	 * Prepare a single phone output for response.
	 *
	 * @param cnEntry         $entry   Post object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $data
	 *
	 * @return array $data
	 */
	private function prepare_phone_for_response( $entry, $request, $data ) {
		$data['phone_numbers'] = array();

		$phone_numbers = $entry->getPhoneNumbers();

		if ( empty( $phone_numbers ) ) return $data;

		foreach ( $phone_numbers as $phone_number )  {

			$item = array(
				'id'        => $phone_number->id,
				'order'     => $phone_number->order,
				'preferred' => $phone_number->preferred,
				'type'		  => $phone_number->type,
				'number'    => $phone_number->number,
				'name'      => $phone_number->name,
			);

			$data['phone_numbers'][] = $item;
		}

		return $data;
	}

	/**
	 * Prepare email addresses output for response.
	 *
	 * @param cnEntry         $entry   Post object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $data
	 *
	 * @return array $data
	 */
	private function prepare_emails_for_response( $entry, $request, $data ) {
		$data['email_addresses'] = array();

		$email_addresses = $entry->getEmailAddresses();

		if ( empty( $email_addresses ) ) return $data;

		foreach ( $email_addresses as $email_address )  {

			$item = array(
				'id'        => $email_address->id,
				'order'     => $email_address->order,
				'preferred' => $email_address->preferred,
				'type'		  => $email_address->type,
				'address'   => $email_address->address,
			);

			$data['email_addresses'][] = $item;
		}

		return $data;
	}

	/**
	 * Prepare entry photo output for response.
	 *
	 * @param cnEntry         $entry   Post object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $data
	 *
	 * @return array $data
	 */
	private function prepare_photo_for_response( $entry, $request, $data ) {
		$data['photo'] = array();

		$photo_url = $entry->getOriginalImageURL('photo');
		$photo_meta = $entry->getImageMeta(); // default original size

		if ( empty( $photo_url ) ) return $data;
		if ( empty( $photo_meta ) ) return $data;
		if ( $photo_meta instanceof WP_Error ) {
			$item = array (
				'errors' => $photo_meta->errors
			);
		} else {
			$item = array(
				'name'       => $photo_meta['name'],
				'url'        => $photo_meta['url'],
				'path'       => $photo_meta['path']
			);
		}

		$data['photo'][] = $item;

		return $data;
	}

	/**
	 * Get the entry's schema, conforming to JSON Schema.
	 *
	 * Schema based on JSON Schema examples and hCard microformat spec which itself is based off the vCard 4 spec.
	 * Uses underscores as spaces to match WP core naming.
	 *  - JSON Schema example @link http://json-schema.org/card
	 *  - hCard Spec @link http://microformats.org/wiki/h-card
	 *
	 * Resource links:
	 *
	 * JSON Schema Validator @link http://www.jsonschemavalidator.net/
	 *
	 * @since 8.5.26
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'description' => 'A representation of a person, company, organization, or place',
			'title'       => $this->base,
			'type'        => 'object',
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Unique identifier for the entry.', 'connections' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => TRUE,
				),
				'type' => array(
					'description' => __( 'Type of entry.', 'connections' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => TRUE,
				),
				'slug' => array(
					'description' => __( 'An alphanumeric identifier for the object unique to its type.', 'connections' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'fn' => array(
					'description' => __( 'The full formatted name of the entry.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Name of the entry, as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML name of the entry, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'honorific_prefix' => array(
					'description' => __( 'An honorific prefix preceding a person\'s name such as Dr/Mrs/Mr.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Honorific prefix as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML honorific prefix, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'given_name' => array(
					'description' => __( 'Given name. In the U.S., the first name of a person.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'First name as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML first name, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'additional_name' => array(
					'description' => __( 'An additional name for a person. The middle name of a person.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Middle name as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML middle name, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'family_name' => array(
					'description' => __( 'Family name. In the U.S., the last name of an person.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Last name as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML last name, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'honorific_suffix' => array(
					'description' => __( 'An honorific suffix preceding a person\'s name such as M.D. /PhD/MSCSW.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Honorific suffix as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML honorific suffix, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'job_title' => array(
					'description' => __( 'The job title of the person (for example, Financial Manager).', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Job title as it exists in the database.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML job title, transformed for display.', 'connections' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
				'org' => array(
					'description' => __( 'The organization object for the entry.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'organization_name' => array(
							'type'        => 'object',
							'properties'  => array(
								'raw'      => array(
									'description' => __( 'Organization name as it exists in the database.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'edit' ),
								),
								'rendered' => array(
									'description' => __( 'HTML organization name, transformed for display.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit', 'embed' ),
								),
							),
						),
						'organization_unit' => array(
							'type'        => 'object',
							'properties'  => array(
								'raw'      => array(
									'description' => __( 'Department (organization unit) as it exists in the database.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'edit' ),
								),
								'rendered' => array(
									'description' => __( 'HTML department (organization unit), transformed for display.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit', 'embed' ),
								),
							),
						),
					),
				),
				'contact' => array(
					'description' => __( 'The contact name object for the entry of type organization.', 'connections' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(
						'given_name' => array(
							'type'        => 'object',
							'properties'  => array(
								'raw'      => array(
									'description' => __( 'First name as it exists in the database.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'edit' ),
								),
								'rendered' => array(
									'description' => __( 'HTML first name, transformed for display.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit', 'embed' ),
								),
							),
						),
						'family_name' => array(
							'type'        => 'object',
							'properties'  => array(
								'raw'      => array(
									'description' => __( 'Last name as it exists in the database.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'edit' ),
								),
								'rendered' => array(
									'description' => __( 'HTML last name, transformed for display.', 'connections' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit', 'embed' ),
								),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 8.5.26
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$params['slug'] = array(
			'description'       => __( 'Limit result set to entries with a specific slug.', 'connections' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}
}
