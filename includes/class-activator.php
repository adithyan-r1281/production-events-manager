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

		$registration_repository =
			new Production_Events_Registration_Repository();

		$registration_repository->create_table();
	}
}