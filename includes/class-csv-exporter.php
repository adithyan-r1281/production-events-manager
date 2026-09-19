<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles CSV export for event registrations.
 */
class Production_Events_CSV_Exporter {

	/**
	 * Registration repository.
	 *
	 * @var Production_Events_Registration_Repository
	 */
	private $registration_repository;

	/**
	 * Constructor.
	 *
	 * @param Production_Events_Registration_Repository $registration_repository Registration repository.
	 */
	public function __construct( $registration_repository ) {

		$this->registration_repository = $registration_repository;
	}

	/**
	 * Register export handler.
	 *
	 * @return void
	 */
	public function register() {

		add_action(
			'admin_init',
			array( $this, 'handle_export' )
		);
	}

	/**
	 * Handle CSV export request.
	 *
	 * @return void
	 */
	public function handle_export() {

		if ( ! isset( $_GET['pem_export_registrations'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_event_registrations' ) ) {
			wp_die(
				esc_html__(
					'You do not have permission to export registrations.',
					'production-events-manager'
				)
			);
		}

		if (
			! isset( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field(
					wp_unslash( $_GET['_wpnonce'] )
				),
				'pem_export_registrations'
			)
		) {
			wp_die(
				esc_html__(
					'Security check failed.',
					'production-events-manager'
				)
			);
		}

		$selected_event = isset( $_GET['event_id'] )
			? absint( $_GET['event_id'] )
			: 0;

		$search = isset( $_GET['s'] )
			? sanitize_text_field(
				wp_unslash( $_GET['s'] )
			)
			: '';

		/*
		 * Get all filtered registrations.
		 *
		 * CSV export intentionally does not use the admin page
		 * pagination. The export should contain all matching records.
		 */
		$registrations = $this->registration_repository->get_registrations(
			array(
				'page'     => 1,
				'per_page' => 100000,
				'event_id' => $selected_event,
				'search'   => $search,
			)
		);

		/*
		 * Prevent any previously buffered output from corrupting CSV.
		 */
		if ( ob_get_length() ) {
			ob_end_clean();
		}

		nocache_headers();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header(
			'Content-Disposition: attachment; filename=event-registrations.csv'
		);

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die(
				esc_html__(
					'Unable to create CSV export.',
					'production-events-manager'
				)
			);
		}

		/*
		 * CSV header row.
		 */
		fputcsv(
			$output,
			array(
				'Event',
				'Name',
				'Email',
				'Registered At',
			)
		);

		foreach ( $registrations as $registration ) {

			$event_title = get_the_title(
				$registration->event_id
			);

			fputcsv(
				$output,
				array(
					$this->sanitize_csv_value( $event_title ),
					$this->sanitize_csv_value( $registration->name ),
					$this->sanitize_csv_value( $registration->email ),
					$this->sanitize_csv_value( $registration->registered_at ),
				)
			);
		}

		fclose( $output );

		exit;
	}

	/**
	 * Protect a CSV value from spreadsheet formula injection.
	 *
	 * Values beginning with spreadsheet formula characters are
	 * prefixed with an apostrophe.
	 *
	 * @param string $value Value to sanitize.
	 * @return string
	 */
	private function sanitize_csv_value( $value ) {

		$value = (string) $value;

		if ( '' === $value ) {
			return '';
		}

		$first_character = substr( $value, 0, 1 );

		if (
			in_array(
				$first_character,
				array( '=', '+', '-', '@' ),
				true
			)
		) {
			return "'" . $value;
		}

		return $value;
	}
}