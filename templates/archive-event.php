<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$event_repository = new Production_Events_Event_Repository();
$event_lifecycle  = new Production_Events_Event_Lifecycle();

$current_page = max(
	1,
	get_query_var( 'paged' ),
	get_query_var( 'page' )
);

$events = $event_repository->get_upcoming_events(
	array(
		'posts_per_page' => 10,
		'paged'          => $current_page,
	)
);
?>

<main class="pem-events-archive">

	<header class="pem-events-header">
		<h1><?php esc_html_e( 'Upcoming Events', 'production-events-manager' ); ?></h1>
	</header>

	<?php if ( $events->have_posts() ) : ?>

		<div class="pem-events-list">

			<?php while ( $events->have_posts() ) : ?>

				<?php
				$events->the_post();

				$event_id = get_the_ID();

				$start = $event_lifecycle->get_event_start(
					$event_id
				);

				$end = $event_lifecycle->get_event_end(
					$event_id
				);

				$venues = $event_repository->get_venues(
					$event_id
				);
				?>

				<article
					id="post-<?php the_ID(); ?>"
					<?php post_class( 'pem-event-card' ); ?>
				>

					<?php if ( has_post_thumbnail() ) : ?>

						<div class="pem-event-card-image">

							<a href="<?php the_permalink(); ?>">

								<?php
								the_post_thumbnail(
									'medium_large',
									array(
										'alt' => the_title_attribute(
											array(
												'echo' => false,
											)
										),
									)
								);
								?>

							</a>

						</div>

					<?php endif; ?>

					<div class="pem-event-card-content">

						<h2 class="pem-event-card-title">

							<a href="<?php the_permalink(); ?>">
								<?php the_title(); ?>
							</a>

						</h2>

						<?php if ( $start ) : ?>

							<p class="pem-event-card-date">

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

							<p class="pem-event-card-date">

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

							<p class="pem-event-card-venue">

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

						<div class="pem-event-card-excerpt">
							<?php the_excerpt(); ?>
						</div>

						<p class="pem-event-card-link">

							<a href="<?php the_permalink(); ?>">
								<?php esc_html_e( 'View Event', 'production-events-manager' ); ?>
							</a>

						</p>

					</div>

				</article>

			<?php endwhile; ?>

		</div>

		<?php
		$pagination = paginate_links(
			array(
				'total'   => $events->max_num_pages,
				'current' => $current_page,
				'type'    => 'list',
			)
		);
		?>

		<?php if ( $pagination ) : ?>

			<nav
				class="pem-events-pagination"
				aria-label="<?php esc_attr_e( 'Events pagination', 'production-events-manager' ); ?>"
			>
				<?php echo wp_kses_post( $pagination ); ?>
			</nav>

		<?php endif; ?>

	<?php else : ?>

		<p class="pem-no-events">
			<?php esc_html_e( 'There are no upcoming events at this time.', 'production-events-manager' ); ?>
		</p>

	<?php endif; ?>

</main>

<?php
wp_reset_postdata();

get_footer();