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