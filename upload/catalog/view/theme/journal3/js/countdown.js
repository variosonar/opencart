(function ($) {
	function countdown(el) {
		const $this = $(el);

		$this.countdown({
			date: $this.data('date'),
			render: function (data) {
				return $(this.el).html(
					'<div>' + this.leadingZeros(data.days, 2) + ' <span>' + Journal['countdownDay'] + '</span></div>' +
					'<div>' + this.leadingZeros(data.hours, 2) + ' <span>' + Journal['countdownHour'] + '</span></div>' +
					'<div>' + this.leadingZeros(data.min, 2) + ' <span>' + Journal['countdownMin'] + '</span></div>' +
					'<div>' + this.leadingZeros(data.sec, 2) + ' <span>' + Journal['countdownSec'] + '</span></div>');
			}
		});
	}

	Journal.lazy('countdown', '.countdown', {
		load: function (el) {
			Journal.load(Journal['assets']['countdown'], 'countdown', function () {
				countdown(el);
			});
		}
	});
})(jQuery);
