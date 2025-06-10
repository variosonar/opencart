(function ($) {
	function slider(el) {
		const $this = $(el);
		const $parent = $this.parent();
		const $img = $('>img', $parent);
		const options = $.extend(true, {
			loop: true,
			mobileBGVideo: true,
			grabCursor: false,
			instantStartLayers: true
			// startOnAppear: true
		}, $this.data('options'));

		$parent.css({
			width: $parent.width(),
			height: $parent.height()
		});

		$img.remove();
		$parent.find('.journal-loading').remove();

		const $slider = $this.masterslider(options);

		$slider.masterslider('slider').api.addEventListener(MSSliderEvent.CHANGE_START, function () {
			$this.find('video').each(function () {
				$(this)[0].pause();
			});
		});

		setTimeout(function () {
			$parent.attr('style', '');
			$parent.css('background-image', 'none');
			$parent.addClass('master-loaded');
			$parent.find('.journal-loading').remove();
			$slider.find('iframe, video').each(function () {
				$(this).attr('src', $(this).data('src'));
			});
		}, 1000);

		if ($this.data('parallax')) {
			MSScrollParallax.setup($slider.masterslider('slider'), 0, $this.data('parallax'), false);
		}
	}

	Journal.lazy('master_slider', '.module-master_slider', {
		load: function (el) {
			Journal.load(Journal['assets']['masterslider'], 'masterslider', function () {
				$(function () {
					slider(el.querySelector('.master-slider'));
				});
			});
		}
	});
})(jQuery);
