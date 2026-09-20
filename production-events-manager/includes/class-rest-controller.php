<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles REST API endpoints for event registrations.
 */
class Production_Events_REST_Controller {

	/**
	 * Registration service.
	 *
	 * @var Production_Events_Registration_Service
	 */
	private $registration_service;

	/**
	 * Constructor.
	 *
	 * @param Production_Events_Registration_Service $registration_service Registration service.
	 */
	public function __construct( $registration_service ) {
		$this->registration_service = $registration_service;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			'production-events/v1',
			'/events/(?P<event_id>\d+)/registrations',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_registration' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'event_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return absint( $value ) > 0;
						},
					),
					'name'     => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return is_string( $value ) && '' !== trim( $value );
						},
					),
					'email'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => function ( $value ) {
							return is_email( $value );
						},
					),
				),
			)
		);
	}

	/**
	 * Create an event registration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_registration( WP_REST_Request $request ) {

		$event_id = absint( $request->get_param( 'event_id' ) );
		$name     = $request->get_param( 'name' );
		$email    = $request->get_param( 'email' );

		$result = $this->registration_service->register(
			$event_id,
			$name,
			$email
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __(
                    'Registration successful.',
                    'production-events-manager'
                ),
            ),
            201
        );
	}
}