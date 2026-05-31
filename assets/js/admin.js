/* Cardology Reports — admin scripts.
 *
 * The appearance swatches hide their native radio, so the visual "selected"
 * state is driven by an .is-active class. PHP sets it on load; this moves it
 * as soon as the admin clicks a different swatch (and opens the custom palette
 * panel when "Custom" is chosen) so selection feels responsive before saving.
 */
(function () {
	'use strict';

	var form = document.querySelector('.crwp-appearance-form');
	if (!form) {
		return;
	}

	var swatches = form.querySelectorAll('.crwp-swatch');
	var details = form.querySelector('.crwp-custom-tokens');

	form.addEventListener('change', function (event) {
		var input = event.target;
		if (!input || input.type !== 'radio' || !/\[theme\]$/.test(input.name || '')) {
			return;
		}

		swatches.forEach(function (swatch) {
			var radio = swatch.querySelector('input[type="radio"]');
			swatch.classList.toggle('is-active', !!radio && radio.checked);
		});

		if (details) {
			details.open = input.value === 'custom';
		}
	});
})();
