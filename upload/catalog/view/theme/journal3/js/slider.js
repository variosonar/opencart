(function ($) {
	function animate({ timing, draw, duration }) {
		let start = performance.now();

		requestAnimationFrame(function animate(time) {
			// timeFraction goes from 0 to 1
			let timeFraction = (time - start) / duration;
			if (timeFraction > 1) timeFraction = 1;

			// calculate the current animation state
			let progress = timing(timeFraction)

			draw(progress); // draw it

			if (timeFraction < 1) {
				requestAnimationFrame(animate);
			}
		});
	}

	function slider_sync() {
		$('.module-slider,.module-background_slider').find('[data-sync-with]').each(function () {
			const swiper = $($(this).data('sync-with')).find('.slider-wrapper .swiper-container')[0];
			if (this.swiper && swiper.swiper) {
				this.swiper.controller.control = this.swiper.controller.control || [];
				this.swiper.controller.control.push(swiper.swiper);

				swiper.swiper.controller.control = swiper.swiper.controller.control || [];
				swiper.swiper.controller.control.push(this.swiper);
			}
		});
	}

	function slider(el) {
		const sliderEl = el.querySelector('.slider-wrapper');
		const thumbsEl = el.querySelector('.slider-thumbs');

		if (sliderEl.querySelector('.swiper-container').swiper) {
			return;
		}

		// main
		const options = $.extend(true, {
			init: false,
			watchOverflow: true,
			watchSlidesProgress: true,
			slideToClickedSlide: true,
			parallax: {
				enabled: true
			},
			fadeEffect: {
				crossFade: true
			},
			coverflowEffect: {
				rotate: 50,
				stretch: 0,
				depth: 100,
				modifier: 1,
				slideShadows: false,
			},
			keyboard: {
				enabled: true,
				onlyInViewport: true,
				pageUpDown: false
			},
			cardsEffect: {
				slideShadows: false
			},
			flipEffect: {
				slideShadows: false,
			},
			// mousewheel: {
			// 	forceToAxis: true
			// },
			pagination: {
				el: sliderEl.querySelector('.swiper-pagination'),
				clickable: true,
				renderBullet(index, className) {
					let html = '';

					html += '<span class="' + className + '" tabindex="' + index + '" role="button" aria-label="Go to slide ' + (index + 1) +'">';

					if (options.autoplay) {
						if (options.bulletsType === 'dots') {
							html += '<div class="swiper-timeline-dots"> <svg viewBox="0 0 40 40"> <circle fill="none" stroke-linecap="round" cx="20" cy="20" r="15.915494309"/></svg></div>';
						} else {
							html += '<div class="swiper-timeline"></div>';
						}
					}

					html += '</span>';

					return html;
				}
			},
			navigation: {
				nextEl: sliderEl.querySelector('.swiper-button-next'),
				prevEl: sliderEl.querySelector('.swiper-button-prev')
			},
			scrollbar: {
				el: sliderEl.querySelector('.swiper-scrollbar'),
				draggable: true,
			},
			on: {}
		}, $(sliderEl.querySelector('.swiper-wrapper')).data('options'));

		if (options.slidesPerView === 'auto') {
			options.effect = 'slide';
			options.on.reachEnd = function () {
				this.snapGrid = [...this.slidesGrid];
				setTimeout(() => {
					const $next = document.querySelector('.swiper-button-next');

					if ($next) {
						$next.dispatchEvent(new MouseEvent('click', {
							bubbles: false
						}));
					}
				}, 1);
			}
		}

		// thumbs
		if (thumbsEl) {
			const thumbsOptions = $.extend(true, {
				slidesPerView: 'auto',
				slideToClickedSlide: true,
				observer: true,
				resizeObserver: true,
				freeMode: {
					enabled: options.thumbsFreeMode,
					sticky: false,
				},
			}, $(thumbsEl).data('options'));

			const thumbs = new SwiperLatest(thumbsEl.querySelector('.swiper-container'), thumbsOptions);

			options.thumbs = {
				swiper: thumbs,
				slideThumbActiveClass: 'swiper-slide-active'
			}
		}

		// timeline
		if (options.autoplay && options.autoplay.delay) {
			let $timeline = null;

			function onProgress() {
				if ($timeline === null) {
					$timeline = el.querySelectorAll('.swiper-timeline, .swiper-timeline-dots');
				}

				if (!$timeline.length) {
					return;
				}

				return animate({
					duration: options.autoplay.delay,
					timing: function (x) {
						return x;
					},
					draw: function (progress) {
						$timeline.forEach(function ($el) {
							$el.style.setProperty('--timeline-progress', progress);
							$el.style.setProperty('--timeline-progress-circle', 100 - Math.round(progress * 100));
							// $el.style.transform = 'scaleX(' + progress + ')';
						});
					}
				})
			}

			options.on.init = onProgress;
			options.on.slideChange = onProgress;

			if (options.autoplay && options.autoplay.delay && options.pauseOnMouseEnter) {
				options.on.autoplayStart = function (swiper) {
					// el.classList.remove('swiper-autoplay-paused')

					onProgress();
				};

				options.on.autoplayStop = function (swiper) {
					// el.classList.add('swiper-autoplay-paused');
				}
			}
		}

		const swiper = new SwiperLatest(sliderEl.querySelector('.swiper-container'), options);

		if (thumbsEl && !options.thumbsFreeMode) {
			swiper.on('snapIndexChange', function() {
				options.thumbs.swiper.slideTo(swiper.activeIndex);
			});

			options.thumbs.swiper.on('slideChangeTransitionStart', function() {
				swiper.slideTo(options.thumbs.swiper.activeIndex);
			});
		}

		if (options.autoplay && options.autoplay.delay && options.pauseOnMouseEnter) {
			$(el).on('mouseenter', function () {
				swiper.autoplay.stop();
			});

			$(el).on('mouseleave', function () {
				swiper.autoplay.start();
			});
		}

		// video
		const videos = sliderEl.querySelectorAll('video');

		if (videos.length) {
			function videoStarted(event) {
				swiper.slides[swiper.activeIndex].setAttribute('data-swiper-autoplay', Math.round(event.target.duration * 1000));

				if (swiper.params.autoplay.enabled && swiper.autoplay.running) {
					swiper.autoplay.stop();
					swiper.autoplay.start();
				}
			}

			function videoEnded() {
				// console.log('ended', swiper.autoplay);
				//
				// if (swiper.params.autoplay.enabled && !swiper.autoplay.running) {
				// 	swiper.slideNext();
				// 	swiper.autoplay.start();
				// }
			}

			swiper.on('init slideChange', function (swiper) {
				// pause previous video, if any
				if (swiper.previousIndex !== undefined) {
					const video = swiper.slides[swiper.previousIndex].querySelector('video');

					if (video) {
						video.removeEventListener('playing', videoStarted);
						video.removeEventListener('ended', videoEnded);

						video.pause();
						video.currentTime = 0;
					}
				}

				// play current video, if any
				const video = swiper.slides[swiper.activeIndex].querySelector('video');

				if (video) {
					video.addEventListener('playing', videoStarted);
					video.addEventListener('ended', videoEnded);

					if (!video.src && video.dataset.src) {
						video.src = video.dataset.src;
					} else {
						video.play();
					}
				}
			});
		}

		swiper.init();

		return swiper;
	}

	function background_slider(el) {
		const sliderEl = el.querySelector('.slider-wrapper');
		const thumbsEl = el.querySelector('.slider-thumbs');

		if (sliderEl.querySelector('.swiper-container').swiper) {
			return;
		}
		const options = $.extend(true, {
			slidesPerView: 1,
			fadeEffect: {
				crossFade: true
			}
		}, $(sliderEl.querySelector('.swiper-wrapper')).data('options'));

		return new SwiperLatest(sliderEl.querySelector('.swiper-container'), options);
	}

	Journal.lazy('slider', '.module-slider', {
		load: function (el) {
			Journal.load(Journal['assets']['swiper-latest'], 'swiper-latest', function () {
				slider(el);
				slider_sync();
			});
		}
	});

	Journal.lazy('background_slider', '.module-background_slider', {
		load: function (el) {
			Journal.load(Journal['assets']['swiper-latest'], 'swiper-latest', function () {
				background_slider(el);
				slider_sync();
			});
		}
	});
})(jQuery);
