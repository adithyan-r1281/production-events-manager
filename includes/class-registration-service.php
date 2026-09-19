<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event registration business logic.
 */
class Production_Events_Registration_Service {

	/**
	 * Event repository.
	 *
	 * @var Production_Events_Event_Repository
	 */
	private $event_repository;

	/**
	 * Event lifecycle service.
	 *
	 * @var Production_Events_Event_Lifecycle
	 */
	private $event_lifecycle;

	/**
	 * Registration repository.
	 *
	 * @var Production_Events_Registration_Repository
	 */
	private $registration_repository;

	/**
	 * Constructor.
	 *
	 * @param Production_Events_Event_Repository       $event_repository       Event repository.
	 * @param Production_Events_Event_Lifecycle        $event_lifecycle        Event lifecycle service.
	 * @param Production_Events_Registration_Repository $registration_repository Registration repository.
	 */
	public function __construct(
		$event_repository,
		$event_lifecycle,
		$registration_repository
	) {
		$this->event_repository       = $event_repository;
		$this->event_lifecycle        = $event_lifecycle;
		$this->registration_repository = $registration_repository;
	}

	/**
	 * Register a person for an event.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $name     Registrant name.
	 * @param string $email    Registrant email.
	 * @return int|WP_Error Registration ID on success, WP_Error on failure.
	 */
	public function register( $event_id, $name, $email ) {

		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return new WP_Error(
				'invalid_event',
				__( 'Invalid event.', 'production-events-manager' ),
				array( 'status' => 404 )
			);
		}

		$event = $this->event_repository->get_event( $event_id );

		if ( ! $event || 'pem_event' !== $event->post_type ) {
			return new WP_Error(
				'event_not_found',
				__( 'Event not found.', 'production-events-manager' ),
				array( 'status' => 404 )
			);
		}

		if ( ! $this->event_repository->is_published( $event_id ) ) {
			return new WP_Error(
				'event_not_available',
				__( 'This event is not available for registration.', 'production-events-manager' ),
				array( 'status' => 403 )
			);
		}

		$status = $this->event_lifecycle->get_status( $event_id );

		if ( 'cancelled' === $status ) {
			return new WP_Error(
				'event_cancelled',
				__( 'Registration is unavailable because this event has been cancelled.', 'production-events-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( 'expired' === $status ) {
			return new WP_Error(
				'event_expired',
				__( 'Registration is unavailable because this event has ended.', 'production-events-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( 'registration_closed' === $status ) {
			return new WP_Error(
				'registration_closed',
				__( 'Registration for this event is closed.', 'production-events-manager' ),
				array( 'status' => 403 )
			);
		}

		if ( 'started' === $status ) {
			return new WP_Error(
				'event_started',
				__( 'Registration is unavailable because this event has already started.', 'production-events-manager' ),
				array( 'status' => 403 )
			);
		}

		$name = sanitize_text_field( wp_unslash( $name ) );
		$email = sanitize_email( wp_unslash( $email ) );

		if ( '' === $name ) {
			return new WP_Error(
				'invalid_name',
				__( 'Please provide your name.', 'production-events-manager' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Please provide a valid email address.', 'production-events-manager' ),
				array( 'status' => 400 )
			);
		}

		$email = strtolower( trim( $email ) );

		if ( $this->registration_repository->registration_exists( $event_id, $email ) ) {
			return new WP_Error(
				'duplicate_registration',
				__( 'You are already registered for this event.', 'production-events-manager' ),
				array( 'status' => 409 )
			);
		}

		$capacity = $this->event_repository->get_capacity( $event_id );

        $registered_at = current_time( 'mysql' );

        if ( $capacity > 0 ) {
            $registration_id =
                $this->registration_repository->insert_registration_with_capacity(
                    $event_id,
                    $name,
                    $email,
                    $registered_at,
                    $capacity
                );
        } else {
            $registration_id =
                $this->registration_repository->insert_registration(
                    $event_id,
                    $name,
                    $email,
                    $registered_at
                );
        }

        if ( is_wp_error( $registration_id ) ) {
            return $registration_id;
        }

        if ( false === $registration_id ) {
            return new WP_Error(
                'registration_failed',
                __(
                    'Unable to complete registration. Please try again.',
                    'production-events-manager'
                ),
                array(
                    'status' => 500,
                )
            );
        }

        return $registration_id;		

		$registered_at = current_time( 'mysql' );

		$registration_id =
			$this->registration_repository->insert_registration(
				$event_id,
				$name,
				$email,
				$registered_at
			);

		if ( false === $registration_id ) {
			return new WP_Error(
				'registration_failed',
				__( 'Unable to complete registration. Please try again.', 'production-events-manager' ),
				array( 'status' => 500 )
			);
		}

		return $registration_id;
	}
}