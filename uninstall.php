<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Data deletion is opt-in.
 *
 * By default, uninstalling the plugin preserves all plugin data.
 */
if (
	! defined( 'PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL' )
	|| true !== PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL
) {
	return;
}

/*
 * Remove plugin-specific capability.
 */
$role = get_role( 'administrator' );

if ( $role ) {
	$role->remove_cap( 'manage_event_registrations' );
}

/*
 * Delete Event posts.
 */
$event_ids = get_posts(
	array(
		'post_type'      => 'pem_event',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $event_ids as $event_id ) {
	wp_delete_post( $event_id, true );
}

/*
 * Delete Venue terms.
 */
$venue_terms = get_terms(
	array(
		'taxonomy'   => 'pem_venue',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $venue_terms ) ) {
	foreach ( $venue_terms as $term_id ) {
		wp_delete_term( $term_id, 'pem_venue' );
	}
}

/*
 * Drop registrations table.
 */
global $wpdb;

$table_name = $wpdb->prefix . 'production_event_registrations';

$wpdb->query(
	'DROP TABLE IF EXISTS `' . esc_sql( $table_name ) . '`'
);

/*
 * Remove plugin options.
 */
delete_option( 'production_events_flush_rewrite_rules' );