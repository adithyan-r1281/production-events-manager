<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Production_Events_Activator {

	public static function activate() {
		require_once plugin_dir_path( __FILE__ )
			. 'class-registration-repository.php';

		require_once plugin_dir_path( __FILE__ )
			. 'class-capabilities.php';

		$registration_repository =
			new Production_Events_Registration_Repository();

		$registration_repository->create_table();

		Production_Events_Capabilities::add_capabilities();
	}
}