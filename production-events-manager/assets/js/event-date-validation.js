document.addEventListener('DOMContentLoaded', function () {

	const startField = document.getElementById('pem_start_datetime');
	const endField = document.getElementById('pem_end_datetime');
	const capacityInput = document.getElementById('pem_capacity');
	const capacityError = document.getElementById('pem-capacity-error');

    const registrationClosingField = document.getElementById(
        'pem_registration_closing_datetime'
    );

    const registrationClosingError = document.getElementById(
        'pem-registration-closing-error'
    );

	if (!startField || !endField) {
		return;
	}

	const lockName = 'pem-date-validation';

	/*
	 * Date validation
	 */
	function datesAreValid() {

		if (!startField.value || !endField.value) {
			return true;
		}

		const startDate = new Date(startField.value);
		const endDate = new Date(endField.value);

		return endDate > startDate;
	}

	/*
	 * Show date validation error
	 */
	function showDateError() {

		let notice = document.getElementById(
			'pem-date-validation-notice'
		);

		if (notice) {
			return;
		}

		notice = document.createElement('div');

		notice.id = 'pem-date-validation-notice';
		notice.className = 'notice notice-error';

		notice.innerHTML =
			'<p><strong>Invalid event dates.</strong> ' +
			'End Date &amp; Time must be after Start Date &amp; Time.</p>';

		endField.parentNode.insertBefore(
			notice,
			endField.nextSibling
		);
	}

	/*
	 * Hide date validation error
	 */
	function hideDateError() {

		const notice = document.getElementById(
			'pem-date-validation-notice'
		);

		if (notice) {
			notice.remove();
		}
	}

	/*
	 * Capacity validation
	 */
	function capacityIsValid() {

		// If Capacity field doesn't exist, don't block saving.
		if (!capacityInput) {
			return true;
		}

		const value = capacityInput.value.trim();

		// Empty value is invalid.
		if (value === '') {
			return false;
		}

		// Only whole numbers are allowed.
		if (!/^\d+$/.test(value)) {
			return false;
		}

		const capacity = Number(value);

		// Must be a non-negative integer.
		return Number.isInteger(capacity) && capacity >= 0;
	}

	/*
	 * Show capacity validation error
	 */
	function showCapacityError() {

		if (!capacityError) {
			return;
		}

		capacityError.textContent =
			'Invalid capacity. Please enter a whole number of 0 or greater.';

		capacityError.style.display = 'block';
	}

	/*
	 * Hide capacity validation error
	 */
	function hideCapacityError() {

		if (!capacityError) {
			return;
		}

		capacityError.textContent = '';
		capacityError.style.display = 'none';
	}

	/*
	 * Combined validation
	 *
	 * Both date and capacity validation are handled here.
	 * Gutenberg saving is locked if either one is invalid.
	 */
    function updateValidation() {

        const editor = wp.data.dispatch('core/editor');

        const datesValid = datesAreValid();
        const capacityValid = capacityIsValid();
        const registrationClosingValid =
            registrationClosingIsValid();

        /*
        * Date validation.
        */
        if (datesValid) {
            hideDateError();
        } else {
            showDateError();
        }

        /*
        * Capacity validation.
        */
        if (capacityValid) {
            hideCapacityError();
        } else {
            showCapacityError();
        }

        /*
        * Registration closing validation.
        */
        if (registrationClosingValid) {
            hideRegistrationClosingError();
        } else {
            showRegistrationClosingError();
        }

        /*
        * Save is allowed only when all
        * event metadata is valid.
        */
        if (
            datesValid &&
            capacityValid &&
            registrationClosingValid
        ) {

            editor.unlockPostSaving(lockName);

        } else {

            editor.lockPostSaving(lockName);
        }
    }

	/*
	 * Date field events
	 */
	startField.addEventListener('input', updateValidation);
	startField.addEventListener('change', updateValidation);

	endField.addEventListener('input', updateValidation);
	endField.addEventListener('change', updateValidation);

    if (registrationClosingField) {

        registrationClosingField.addEventListener(
            'input',
            updateValidation
        );

        registrationClosingField.addEventListener(
            'change',
            updateValidation
        );
    }

	/*
	 * Capacity field events
	 */
	if (capacityInput) {

		capacityInput.addEventListener(
			'input',
			updateValidation
		);

		capacityInput.addEventListener(
			'change',
			updateValidation
		);
	}

	/*
	 * Check initial values when editor loads.
	 */
	updateValidation();

        function registrationClosingIsValid() {

        if (!registrationClosingField) {
            return true;
        }

        const closingValue = registrationClosingField.value.trim();

        // Registration closing is optional.
        if (!closingValue) {
            return true;
        }

        // Start date is required to compare the closing date.
        if (!startField.value) {
            return false;
        }

        const closingDate = new Date(closingValue);
        const startDate = new Date(startField.value);

        if (Number.isNaN(closingDate.getTime())) {
            return false;
        }

        if (Number.isNaN(startDate.getTime())) {
            return false;
        }

        return closingDate <= startDate;
    }
    function showRegistrationClosingError() {

        if (!registrationClosingError) {
            return;
        }

        registrationClosingError.textContent =
            'Registration closing date and time must not be later than the event start.';

        registrationClosingError.style.display = 'block';
    }
    function hideRegistrationClosingError() {

        if (!registrationClosingError) {
            return;
        }

        registrationClosingError.textContent = '';
        registrationClosingError.style.display = 'none';
    }

});