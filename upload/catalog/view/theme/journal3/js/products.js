(function ($) {
	// Grid / List toggle
	$(document).on('click', '.grid-list .view-btn', function () {
		const $this = $(this);
		const $products = $('.main-products');
		const view = $this.data('view');
		const current = $products.hasClass('product-grid') ? 'grid' : 'list';

		$this.tooltip('hide');

		if (view !== current) {
			$products.addClass('no-transitions').removeClass('product-' + current).addClass('product-' + view);

			setTimeout(function () {
				$products.removeClass('no-transitions');
			}, 1);

			const d = new Date;
			d.setTime(d.getTime() + 24 * 60 * 60 * 1000 * 365);

			if (view === 'list') {
				document.cookie = 'view=list;path=/;expires=' + d.toGMTString();
			} else {
				document.cookie = 'view=grid;path=/;expires=' + d.toGMTString();
			}
		}

		$('.grid-list .view-btn').removeClass('active');
		$this.addClass('active');
	});

	// Sort / Limit handler
	$(document).on('change', '.main-products-wrapper .select-group select', function () {
		if (!window['journal_filter']) {
			window.location = $(this).val();
		}
	});

	// Infinite Scroll
	$(function () {
		if (Journal['infiniteScrollStatus'] && $('.main-products').length) {
			Journal['infiniteScrollInstance'] = $.ias({
				container: '.main-products',
				item: '.product-layout',
				pagination: '.pagination-results',
				next: '.pagination a.next'
			});

			Journal['infiniteScrollInstance'].extension(new IASTriggerExtension({
				offset: parseInt(Journal['infiniteScrollOffset'], 10) || Infinity,
				text: Journal['infiniteScrollLoadNext'],
				textPrev: Journal['infiniteScrollLoadPrev'],
				htmlPrev: '<div class="ias-trigger ias-trigger-prev"><a class="btn">{text}</a></div>',
				html: '<div class="ias-trigger ias-trigger-next"><a class="btn">{text}</a></div>'
			}));

			Journal['infiniteScrollInstance'].extension(new IASSpinnerExtension({
				html: '<div class="ias-spinner"><em class="fa fa-spinner fa-spin"></em></div>'
			}));

			Journal['infiniteScrollInstance'].extension(new IASNoneLeftExtension({
				text: Journal['infiniteScrollNoneLeft']
			}));

			Journal['infiniteScrollInstance'].extension(new IASPagingExtension());

			Journal['infiniteScrollInstance'].extension(new IASHistoryExtension({
				prev: '.pagination a.prev'
			}));

			Journal['infiniteScrollInstance'].on('load', function (event) {
				try {
					var u = new URL(event.url);

					u.host = window.location.host;
					u.hostname = window.location.hostname;
					u.protocol = window.location.protocol;

					event.url = u.toString();
				} catch (e) {
				}
			});

			Journal['infiniteScrollInstance'].on('loaded', function (data) {
				$('.pagination-results').html($(data).find('.pagination-results'));
			});

			Journal['infiniteScrollInstance'].on('rendered', function (data) {
				Journal.lazy();
			});
		}
	});

})(jQuery);
