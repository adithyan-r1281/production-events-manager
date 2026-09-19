<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$event_repository = new Production_Events_Event_Repository();
$event_lifecycle  = new Production_Events_Event_Lifecycle();

$event_id = get_the_ID();
$event    = $event_repository->get_event( $event_id );

if ( ! $event ) {
	status_header( 404 );
	nocache_headers();
	?>

	<main class="pem-event-single">
		<h1>
			<?php esc_html_e( 'Event not found', 'production-events-manager' ); ?>
		</h1>

		<p>
			<?php
			esc_html_e(
				'The requested event could not be found.',
				'production-events-manager'
			);
			?>
		</p>
	</main>

	<?php
	get_footer();
	return;
}

$start = $event_lifecycle->get_event_start( $event_id );
$end   = $event_lifecycle->get_event_end( $event_id );

$registration_closing = $event_lifecycle->get_registration_closing(
	$event_id
);

$venues = $event_repository->get_venues( $event_id );
$status = $event_lifecycle->get_status( $event_id );
$capacity = $event_repository->get_capacity( $event_id );
?>

<main class="pem-event-single">

	<article
		id="post-<?php echo esc_attr( $event_id ); ?>"
		<?php post_class( 'pem-event' ); ?>
	>

		<header class="pem-event-header">

			<h1 class="pem-event-title">
				<?php echo esc_html( get_the_title( $event_id ) ); ?>
			</h1>

			<p class="pem-event-status">
				<strong>
					<?php esc_html_e( 'Status:', 'production-events-manager' ); ?>
				</strong>

				<?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?>
			</p>

		</header>

		<?php if ( has_post_thumbnail( $event_id ) ) : ?>

			<div class="pem-event-featured-image">

				<?php
				echo get_the_post_thumbnail(
					$event_id,
					'large',
					array(
						'alt' => the_title_attribute(
							array(
								'post' => $event_id,
								'echo' => false,
							)
						),
					)
				);
				?>

			</div>

		<?php endif; ?>

		<div class="pem-event-details">

			<?php if ( $start ) : ?>

				<p class="pem-event-start">

					<strong>
						<?php esc_html_e( 'Starts:', 'production-events-manager' ); ?>
					</strong>

					<?php
					echo esc_html(
						wp_date(
							'F j, Y g:i A',
							$start->getTimestamp(),
							wp_timezone()
						)
					);
					?>

				</p>

			<?php endif; ?>

			<?php if ( $end ) : ?>

				<p class="pem-event-end">

					<strong>
						<?php esc_html_e( 'Ends:', 'production-events-manager' ); ?>
					</strong>

					<?php
					echo esc_html(
						wp_date(
							'F j, Y g:i A',
							$end->getTimestamp(),
							wp_timezone()
						)
					);
					?>

				</p>

			<?php endif; ?>

			<?php if ( ! empty( $venues ) ) : ?>

				<p class="pem-event-venue">

					<strong>
						<?php esc_html_e( 'Venue:', 'production-events-manager' ); ?>
					</strong>

					<?php
					$venue_names = wp_list_pluck(
						$venues,
						'name'
					);

					echo esc_html(
						implode( ', ', $venue_names )
					);
					?>

				</p>

			<?php endif; ?>

			<p class="pem-event-capacity">

				<strong>
					<?php esc_html_e( 'Capacity:', 'production-events-manager' ); ?>
				</strong>

				<?php if ( 0 === $capacity ) : ?>

					<?php esc_html_e( 'Unlimited', 'production-events-manager' ); ?>

				<?php else : ?>

					<?php echo esc_html( number_format_i18n( $capacity ) ); ?>

				<?php endif; ?>

			</p>

			<?php if ( $registration_closing ) : ?>

				<p class="pem-event-registration-closing">

					<strong>
						<?php
						esc_html_e(
							'Registration closes:',
							'production-events-manager'
						);
						?>
					</strong>

					<?php
					echo esc_html(
						wp_date(
							'F j, Y g:i A',
							$registration_closing->getTimestamp(),
							wp_timezone()
						)
					);
					?>

				</p>

			<?php endif; ?>

		</div>

		<div class="pem-event-description">

			<?php
			echo wp_kses_post(
				apply_filters(
					'the_content',
					$event->post_content
				)
			);
			?>

		</div>

	</article>

</main>

<?php
    $registration_form = plugin_dir_path( dirname( __FILE__ ) )
        . 'templates/registration-form.php';

    if ( file_exists( $registration_form ) ) {
        include $registration_form;
    }
?>

<?php get_footer(); ?>