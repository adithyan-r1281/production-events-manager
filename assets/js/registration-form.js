document.addEventListener('DOMContentLoaded', function () {
	const forms = document.querySelectorAll('.pem-registration-form');

	if (!forms.length) {
		return;
	}

	forms.forEach(function (form) {
		form.addEventListener('submit', handleRegistrationSubmit);
	});
});

async function handleRegistrationSubmit(event) {
	event.preventDefault();

	const form = event.currentTarget;
	const eventId = form.dataset.eventId;
	const nameInput = form.querySelector('[name="name"]');
	const emailInput = form.querySelector('[name="email"]');
	const submitButton = form.querySelector('[type="submit"]');
	const message = form.querySelector('.pem-registration-message');

	const nameError = form.querySelector('[data-field-error="name"]');
	const emailError = form.querySelector('[data-field-error="email"]');

	clearErrors(form);

	const name = nameInput.value.trim();
	const email = emailInput.value.trim();

	let hasErrors = false;

	if (!name) {
		showFieldError(
			nameInput,
			nameError,
			'Please provide your name.'
		);

		hasErrors = true;
	}

	if (!email) {
		showFieldError(
			emailInput,
			emailError,
			'Please provide your email address.'
		);

		hasErrors = true;
	} else if (!isValidEmail(email)) {
		showFieldError(
			emailInput,
			emailError,
			'Please provide a valid email address.'
		);

		hasErrors = true;
	}

	if (hasErrors) {
		const firstInvalidInput = form.querySelector(
		'input[aria-invalid="true"]'
        );

        if (firstInvalidInput) {
            firstInvalidInput.focus();
        }

        return;
	}

	submitButton.disabled = true;
	message.textContent = 'Submitting registration...';

	try {
		const response = await fetch(
			'/wp-json/production-events/v1/events/' +
				encodeURIComponent(eventId) +
				'/registrations',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					name: name,
					email: email
				})
			}
		);

		const data = await response.json();

		if (response.ok) {
            form.reset();

            clearErrors(form);

            message.textContent =
                data.message || 'Registration successful.';

            message.classList.add('is-success');
            message.focus();

            return;
        }
		handleRegistrationError(
			response.status,
			data,
			message,
			form
		);
	} catch (error) {
        message.classList.remove('is-success');
        message.classList.add('is-error');

        message.textContent =
            'Unable to complete registration. Please try again.';

        message.focus();
    } finally {
		submitButton.disabled = false;
	}
}

function clearErrors(form) {
	const errors = form.querySelectorAll('.pem-field-error');

	errors.forEach(function (error) {
		error.textContent = '';
	});

	const inputs = form.querySelectorAll('input');

	inputs.forEach(function (input) {
		input.removeAttribute('aria-invalid');
	});

	const message = form.querySelector('.pem-registration-message');

	if (message) {
		message.textContent = '';
		message.classList.remove(
			'is-success',
			'is-error'
		);
	}
}

function showFieldError(input, errorElement, text) {
	errorElement.textContent = text;
	input.setAttribute('aria-invalid', 'true');
}

function isValidEmail(email) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function handleRegistrationError(
	status,
	data,
	message,
	form
) {
	message.classList.remove('is-success');
	message.classList.add('is-error');

	const code = data && data.code ? data.code : '';

	if (status === 409 || code === 'duplicate_registration') {
		message.textContent =
			'You are already registered for this event.';
	} else if (status === 403) {
		message.textContent =
			data.message ||
			'Registration is not currently available for this event.';
	} else if (status === 400) {
		message.textContent =
			data.message ||
			'Please check your information and try again.';
	} else if (status === 404) {
		message.textContent =
			'This event could not be found.';
	} else {
		message.textContent =
			'Unable to complete registration. Please try again.';
	}

	message.focus();
}