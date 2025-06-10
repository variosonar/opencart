(function ($) {
	function catalog(el) {
		$(el).find('.subitem').hover(function () {
			const $this = $(this);
			const $img = $this.closest('.item-content').find('.catalog-image img');

			if ($img.length) {
				$img[0]._src = $img.attr('src');
				$img[0]._srcSet = $img.attr('srcset');

				$img.attr('src', $this.data('image'));
				$img.attr('srcset', $this.data('image2x'));
			}
		}, function () {
			const $this = $(this);
			const $img = $this.closest('.item-content').find('.catalog-image img');

			if ($img.length) {
				$img.attr('src', $img[0]._src);
				$img.attr('srcset', $img[0]._srcSet);
			}
		});
	}

	Journal.lazy('catalog', '.module-catalog.image-on-hover', {
		load: function (el) {
			catalog(el);
		}
	});

	Journal.lazy('catalog_blocks', '.module-catalog_blocks.image-on-hover', {
		load: function (el) {
			catalog(el);
		}
	});

	Journal.lazy('catalog_blocks_tab', '.module-catalog_blocks .tab-container.tab-on-hover', {
		load: function (el) {
			$('> ul > li', el).hoverIntent(function () {
				$(this).find('a[data-toggle="tab"]').tab('show');
			}, function () {
			});
		}
	});
})(jQuery);
