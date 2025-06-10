(function ($) {
	function countup(el) {
		el.querySelectorAll('.info-block-counter').forEach(function (el) {
			const counter = new countUp.CountUp(el, +el.textContent.replace(/\D/g, ''));
			counter.start();
		});
	}

	Journal.lazy('countup', '.module-info_blocks.has-countup', {
		load: function (el) {
			Journal.load(Journal['assets']['countup'], 'countup', function () {
				$(function () {
					countup(el);
				});
			});
		}
	});
})(jQuery);
