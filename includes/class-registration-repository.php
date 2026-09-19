<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event registration data persistence.
 */
class Production_Events_Registration_Repository {

	/**
	 * Get the registration table name.
	 *
	 * @return string
	 */
	private function get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'production_event_registrations';
	}

	/**
	 * Create the registrations table.
	 *
	 * This method is intended to be called during plugin activation.
	 *
	 * @return void
	 */
	public function create_table() {

		global $wpdb;

		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			email varchar(320) NOT NULL,
			registered_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_email (event_id,email),
			KEY event_id (event_id),
			KEY email (email),
			KEY registered_at (registered_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Insert a registration.
	 *
	 * @param int    $event_id      Event ID.
	 * @param string $name          Registrant name.
	 * @param string $email         Normalized email address.
	 * @param string $registered_at Registration datetime.
	 * @return int|false Inserted registration ID or false on failure.
	 */
	public function insert_registration(
		$event_id,
		$name,
		$email,
		$registered_at
	) {

		global $wpdb;

		$table_name = $this->get_table_name();

		$result = $wpdb->insert(
			$table_name,
			array(
				'event_id'      => absint( $event_id ),
				'name'          => $name,
				'email'         => $email,
				'registered_at' => $registered_at,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Insert a registration while enforcing event capacity atomically.
	 *
	 * The event post row is locked for the duration of the transaction.
	 * This prevents concurrent registrations from exceeding capacity.
	 *
	 * @param int    $event_id      Event ID.
	 * @param string $name          Registrant name.
	 * @param string $email         Normalized email address.
	 * @param string $registered_at Registration datetime.
	 * @param int    $capacity      Event capacity. Zero means unlimited.
	 * @return int|WP_Error|false Registration ID, WP_Error for a known
	 *                           registration conflict, or false on DB failure.
	 */
	public function insert_registration_with_capacity(
		$event_id,
		$name,
		$email,
		$registered_at,
		$capacity
	) {

		global $wpdb;

		$event_id = absint( $event_id );
		$capacity = absint( $capacity );

		if ( ! $event_id ) {
			return false;
		}

		$registrations_table = $this->get_table_name();
		$posts_table         = $wpdb->posts;

		$transaction_started = $wpdb->query( 'START TRANSACTION' );

		if ( false === $transaction_started ) {
			return false;
		}

		/*
		 * Lock the event row so concurrent registrations for the same
		 * event are serialized while capacity is checked and updated.
		 */
		$event_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID
				FROM {$posts_table}
				WHERE ID = %d
				AND post_type = %s
				FOR UPDATE",
				$event_id,
				'pem_event'
			)
		);

		if ( ! $event_exists ) {
			$wpdb->query( 'ROLLBACK' );

			return false;
		}

		/*
		 * Check the duplicate while the event row is locked.
		 *
		 * The unique database constraint remains the final safeguard.
		 */
		$existing_registration = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
				FROM {$registrations_table}
				WHERE event_id = %d
				AND email = %s
				LIMIT 1",
				$event_id,
				$email
			)
		);

		if ( null !== $existing_registration ) {
			$wpdb->query( 'ROLLBACK' );

			return new WP_Error(
				'duplicate_registration',
				__(
					'You are already registered for this event.',
					'production-events-manager'
				),
				array(
					'status' => 409,
				)
			);
		}

		/*
		 * Capacity is checked only for limited events.
		 * A capacity of zero means unlimited.
		 */
		if ( $capacity > 0 ) {
			$registration_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$registrations_table}
					WHERE event_id = %d",
					$event_id
				)
			);

			if ( $registration_count >= $capacity ) {
				$wpdb->query( 'ROLLBACK' );

				return new WP_Error(
					'capacity_reached',
					__(
						'Registration is unavailable because this event has reached capacity.',
						'production-events-manager'
					),
					array(
						'status' => 403,
					)
				);
			}
		}

		$result = $wpdb->insert(
			$registrations_table,
			array(
				'event_id'      => $event_id,
				'name'          => $name,
				'email'         => $email,
				'registered_at' => $registered_at,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );

			/*
			 * The unique constraint is still the final duplicate
			 * protection in case another database-level conflict occurs.
			 */
			if ( $this->registration_exists( $event_id, $email ) ) {
				return new WP_Error(
					'duplicate_registration',
					__(
						'You are already registered for this event.',
						'production-events-manager'
					),
					array(
						'status' => 409,
					)
				);
			}

			return false;
		}

		$registration_id = (int) $wpdb->insert_id;

		$committed = $wpdb->query( 'COMMIT' );

		if ( false === $committed ) {
			$wpdb->query( 'ROLLBACK' );

			return false;
		}

		return $registration_id;
	}

	/**
	 * Check whether an email is already registered for an event.
	 *
	 * Email should already be normalized before calling this method.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $email    Normalized email address.
	 * @return bool
	 */
	public function registration_exists( $event_id, $email ) {

		global $wpdb;

		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare(
			"SELECT id
			FROM {$table_name}
			WHERE event_id = %d
			AND email = %s
			LIMIT 1",
			absint( $event_id ),
			$email
		);

		return null !== $wpdb->get_var( $sql );
	}

	/**
	 * Get registrations.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_registrations( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
			'event_id' => 0,
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$page     = max( 1, absint( $args['page'] ) );
		$per_page = max( 1, absint( $args['per_page'] ) );
		$offset   = ( $page - 1 ) * $per_page;
		$event_id = absint( $args['event_id'] );
		$search   = sanitize_text_field( $args['search'] );

		$table_name = $this->get_table_name();

		$where  = '1=1';
		$values = array();

		if ( $event_id > 0 ) {
			$where   .= ' AND event_id = %d';
			$values[] = $event_id;
		}

		if ( '' !== $search ) {
			$where .= ' AND (name LIKE %s OR email LIKE %s)';

			$search_like = '%' . $wpdb->esc_like( $search ) . '%';

			$values[] = $search_like;
			$values[] = $search_like;
		}

		$values[] = $per_page;
		$values[] = $offset;

		$sql = "SELECT
			id,
			event_id,
			name,
			email,
			registered_at
		FROM {$table_name}
		WHERE {$where}
		ORDER BY registered_at DESC
		LIMIT %d OFFSET %d";

		$sql = $wpdb->prepare( $sql, $values );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Count registrations.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $search   Search term.
	 * @return int
	 */
	public function count_registrations(
		$event_id = 0,
		$search = ''
	) {

		global $wpdb;

		$table_name = $this->get_table_name();

		$event_id = absint( $event_id );
		$search   = sanitize_text_field( $search );

		$where  = '1=1';
		$values = array();

		if ( $event_id > 0 ) {
			$where   .= ' AND event_id = %d';
			$values[] = $event_id;
		}

		if ( '' !== $search ) {
			$where .= ' AND (name LIKE %s OR email LIKE %s)';

			$search_like = '%' . $wpdb->esc_like( $search ) . '%';

			$values[] = $search_like;
			$values[] = $search_like;
		}

		$sql = "SELECT COUNT(*)
			FROM {$table_name}
			WHERE {$where}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return (int) $wpdb->get_var( $sql );
	}
}