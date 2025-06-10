(function ($) {
	Journal['stepperDecimals'] = +Journal['stepperDecimals'] || 0;

	function stepper(el) {
		const $this = $(el);

		const $input = $this.find('input[name^="quantity"]');
		const minimum = parseFloat($input.data('minimum')) || 1;

		function change(value, delta) {
			value = value || 0;
			delta = delta || 0;

			if (!Journal['stepperDecimals']) {
				delta = 1;
			}

			const val = Math.max((+$input.val() || 0) + value * delta, minimum);

			$input.val(val.toFixed(Journal['stepperDecimals']));

			$input.trigger('change');
		}

		$this.find('.fa-angle-up').on('click', function (e) {
			change(1, e.shiftKey ? 0.1 : 1);
		});

		$this.find('.fa-angle-down').on('click', function (e) {
			change(-1, e.shiftKey ? 0.1 : 1);
		});

		$input.on('keydown', function (e) {
			switch (e.key) {
				case 'ArrowUp':
					e.preventDefault();

					change(1, e.shiftKey ? 0.1 : 1);
					break;

				case 'ArrowDown':
					e.preventDefault();

					change(-1, e.shiftKey ? 0.1 : 1);
					break;
			}
		});

		$input.on('blur', function () {
			change();
		});
	}

	if (Journal['stepperStatus']) {
		Journal.lazy('stepper', '.stepper', {
			load: function (el) {
				stepper(el);
			}
		});
	}
})(jQuery);
