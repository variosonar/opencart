(function ($) {
	function gallery(el) {
		const $this = $(el);
		const $gallery = $($this.data('gallery'));
		const index = parseInt($this.data('index'), 10) || 0;

		let gallery = $gallery.data('__j_gallery');

		if (!gallery) {
			gallery = lightGallery($gallery[0], $.extend({
				licenseKey: ' ',
				plugins: [lgAutoplay, lgThumbnail, lgFullscreen, lgVideo, lgZoom],
				dynamic: true,
				dynamicEl: $gallery.data('images'),
				download: true,
				loadYoutubeThumbnail: false,
				loadVimeoThumbnail: false,
				showZoomInOutIcons: false,
				allowMediaOverlap: true,
				actualSize: true,
				fullScreen: true,
				slideEndAnimation: false,
				scale: .5,
				thumbWidth: 80,
				thumbHeight: '75px',
				toggleThumb: true,
				thumbMargin: 0,
				showThumbByDefault: false,
				appendSubHtmlTo: '.lg-outer',
				mobileSettings: {
					controls: true,
					showCloseIcon: true,
					download: true,
				}
			}, $gallery.data('options')));

			$gallery.on('lgAfterOpen', function () {
				$('.lg-inner').each(function () {
					const $this = $(this);
					$this.append('<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>');
				});
				$('.lg-backdrop').addClass(gallery.settings.addClass);
				$('.lg-toggle-thumb').prependTo($('.lg-thumb-outer'));
				$('.lg-container')[0].style.setProperty('--lg-components-height', $('#lg-components-1 .lg-group').height() + 'px');
			});

			$gallery.data('__j_gallery', gallery);
		}

		gallery.openGallery(index);
	}

	$(document).on('click', '[data-gallery]', function (e) {
		Journal.load(Journal['assets']['lightgallery'], 'lightgallery', function () {
			gallery(e.currentTarget);
		});

		return false;
	});
})(jQuery);
