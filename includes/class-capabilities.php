<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Production_Events_Capabilities {

	public static function add_capabilities() {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		$role->add_cap( 'manage_event_registrations' );
	}

	public static function remove_capabilities() {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		$role->remove_cap( 'manage_event_registrations' );
	}
}