(function ($) {
	// sync image carousels
	const $thumbs = $('.additional-images .additional-image');
	const $main = $('.main-image');

	$thumbs.on('click', function () {
		if ($main.data('swiper')?.params.loop) {
			$main.data('swiper')?.slideToLoop($(this).data('index'), 0);
		} else {
			$main.data('swiper')?.slideTo($(this).data('index'), 0);
		}

		if (Journal[Journal['isPopup'] ? 'quickviewPageStyleOpenThumbInGallery' : 'productPageStyleOpenThumbInGallery']) {
			$('.main-image [data-index="' + $(this).data('index') + '"]').trigger('click');
		}
	});

	$main.on('jcarousel:init', function (e) {
		const swiper = $('.additional-images').data('swiper');

		e.swiper.on('slideChange', function () {
			if (swiper) {
				swiper.slideTo($main.data('swiper').realIndex);
			} else {
				$thumbs.removeClass('swiper-slide-active');
				$thumbs.eq($main.data('swiper').realIndex).addClass('swiper-slide-active');
			}

			const currentImg = this.slides[this.activeIndex].querySelector('img');

			if (currentImg && currentImg.classList.contains('lazyload')) {
				window['__journal_lazy']['image'].triggerLoad(currentImg);
			}
		});

		if (!swiper) {
			$thumbs.eq(0).addClass('swiper-slide-active');
		}
	});

	// image zoom
	if (Journal['isDesktop'] && Journal[Journal['isPopup'] ? 'quickviewPageStyleCloudZoomStatus' : 'productPageStyleCloudZoomStatus']) {
		$(document).one('mousemove', function () {
			Journal.load(Journal['assets']['imagezoom'], 'imagezoom', function () {
				$('.main-image img').each(function () {
					const $this = $(this);

					$this.ImageZoom({
						type: Journal[Journal['isPopup'] ? 'quickviewPageStyleCloudZoomPosition' : 'productPageStyleCloudZoomPosition'],
						showDescription: false,
						offset: [0, 0],
						zoomSize: [$this.width(), $this.height()],
						bigImageSrc: $this.data('largeimg'),
						onShow: function (target) {
							target.$viewer.css('opacity', 1);
						},
						onHide: function (target) {
							target.$viewer.css('opacity', 0);
						}
					});
				})
			});
		});
	}

	// Select first option
	if ((Journal['isPopup'] ? Journal['quickviewPageStyleOptionsSelect'] : Journal['productPageStyleOptionsSelect']) === 'all') {
		$('.product-options .form-group .radio:first-child input, .product-options .form-group .checkbox:first-child input').prop('checked', true);
		$('.product-options .form-group select').each(function () {
			$(this).find('option').eq(1).prop('selected', true);
		});
	}

	if ((Journal['isPopup'] ? Journal['quickviewPageStyleOptionsSelect'] : Journal['productPageStyleOptionsSelect']) === 'required') {
		$('.product-options .form-group.required .radio:first-child input, .product-options .form-group.required .checkbox:first-child input').prop('checked', true);
		$('.product-options .form-group.required select').each(function () {
			$(this).find('option').eq(1).prop('selected', true);
		});
	}

	// Auto Update Price
	if (Journal['isPopup'] ? Journal['quickviewPageStylePriceUpdate'] : Journal['productPageStylePriceUpdate']) {
		function autoUpdatePrice() {
			$.ajax({
				url: 'index.php?route=journal3/price&popup=' + (Journal['isPopup'] ? 1 : 0) + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : ''),
				type: 'post',
				data: $('#product-id, #product-quantity, #product input[type="radio"]:checked, #product input[type="checkbox"]:checked, #product select'),
				dataType: 'json',
				beforeSend: function () {
					// $('#button-cart').jbutton('loading');
				},
				complete: function () {
					// $('#button-cart').jbutton('reset');
				},
				success: function (json) {
					if (json['response']['status'] === 'error') {
						show_message({
							message: json.response.message
						});
					} else {
						if (Journal['isPopup'] ? Journal['quickviewPageStyleProductStockUpdate'] : Journal['productPageStyleProductStockUpdate']) {
							if (json['response']['stock']) {
								$('.product-stock span').html(json['response']['stock']);
							}

							if (json['response']['in_stock']) {
								$('.product-stock').removeClass('out-of-stock').addClass('in-stock');
							} else {
								$('.product-stock').removeClass('in-stock').addClass('out-of-stock');
							}
						}

						if (json['response']['tax']) {
							$('.product-tax').html(json['response']['tax']);
						}

						if (json['response']['price']) {
							if (json['response']['special']) {
								$('.product-price-group .product-price-old').html(json['response']['price']);
								$('.product-price-group .product-price-new').html(json['response']['special']);
							} else {
								$('.product-price-group .product-price').html(json['response']['price']);
							}
						}

						if (json['response']['discounts']) {
							$('.product-discount').each(function (index) {
								$(this).html(json['response']['discounts'][index]);
							});
						}

						if (json['response']['points']) {
							$('.product-points').html(json['response']['points']);
						}

						if (json['response']['weight']) {
							$('.product-stats .product-weight span').html(json['response']['weight']);
						}
					}
				}
			});
		}

		$('.product-options input[type="radio"], .product-options input[type="checkbox"], .product-options select, #product-quantity').on('change', autoUpdatePrice);

		autoUpdatePrice();
	}
})(jQuery);
