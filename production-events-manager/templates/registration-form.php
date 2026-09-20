<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_id = absint( $event_id );
$status   = isset( $status ) ? $status : '';
?>

<section
	class="pem-registration"
	aria-labelledby="pem-registration-title"
>
	<h2 id="pem-registration-title">
		<?php esc_html_e( 'Register for this event', 'production-events-manager' ); ?>
	</h2>

	<?php if ( 'upcoming' === $status ) : ?>

		<form
			class="pem-registration-form"
			data-event-id="<?php echo esc_attr( $event_id ); ?>"
			novalidate
		>
			<div class="pem-form-field">
				<label for="pem-registration-name">
					<?php esc_html_e( 'Name', 'production-events-manager' ); ?>
				</label>

				<input
					type="text"
					id="pem-registration-name"
					name="name"
					required
					autocomplete="name"
                    aria-describedby="pem-registration-name-error"
				/>

				<p
                    id="pem-registration-name-error"
					class="pem-field-error"
					data-field-error="name"
					aria-live="polite"
				></p>
			</div>

			<div class="pem-form-field">
				<label for="pem-registration-email">
					<?php esc_html_e( 'Email', 'production-events-manager' ); ?>
				</label>

				<input
					type="email"
					id="pem-registration-email"
					name="email"
					required
					autocomplete="email"
                    aria-describedby="pem-registration-email-error"
				/>

				<p
                    id="pem-registration-email-error"
					class="pem-field-error"
					data-field-error="email"
					aria-live="polite"
				></p>
			</div>

			<button type="submit">
				<?php esc_html_e( 'Register', 'production-events-manager' ); ?>
			</button>

			<p
                class="pem-registration-message"
                aria-live="polite"
                tabindex="-1"
            ></p>
		</form>

	<?php else : ?>

		<p class="pem-registration-unavailable">
			<?php
			esc_html_e(
				'Registration is not currently available for this event.',
				'production-events-manager'
			);
			?>
		</p>

	<?php endif; ?>
</section>