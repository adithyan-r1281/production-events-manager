<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation.
 */
class Production_Events_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate() {

		require_once plugin_dir_path( __FILE__ )
			. 'class-registration-repository.php';

		require_once plugin_dir_path( __FILE__ )
			. 'class-capabilities.php';

		/*
		 * Create/update the registrations table.
		 */
		$registration_repository =
			new Production_Events_Registration_Repository();

		$registration_repository->create_table();

		/*
		 * Add plugin-specific capabilities.
		 */
		Production_Events_Capabilities::add_capabilities();

		/*
		 * The CPT is normally registered during init.
		 *
		 * Schedule the rewrite flush for the next request so
		 * WordPress has already registered the CPT and taxonomy.
		 */
		update_option(
			'production_events_flush_rewrite_rules',
			1
		);
	}
}