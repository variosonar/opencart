(function ($) {
	function carousel_swiper(el) {
		const $content = $('#content');
		const $column_left = $('#column-left');
		const $column_right = $('#column-right');
		const $this = $(el);

		let c = 'c0';

		if ($content.has($this).length) {
			c = 'c' + window['Journal']['columnsCount'];
		} else if ($column_left.has($this).length || $column_right.has($this).length) {
			c = 'sc';
		}

		const itemsPerRow = $this.data('items-per-row') ? $this.data('items-per-row')[c] : { 0: { items: 1, spacing: 0 } };
		const breakpoints = {};

		$.each(itemsPerRow, function (v, k) {
			breakpoints[v] = {
				slidesPerView: parseInt(k.items, 10),
				slidesPerGroup: parseInt(k.items, 10),
				spaceBetween: parseInt(k.spacing, 10)
			}
		});

		const options = $.extend({
			init: false,
			slidesPerView: parseInt(itemsPerRow[0].items, 10),
			slidesPerGroup: parseInt(itemsPerRow[0].items, 10),
			spaceBetween: parseInt(itemsPerRow[0].spacing, 10),
			breakpoints: breakpoints,
			observer: true,
			observeParents: true,
			paginationClickable: true,
			preventClicks: false,
			preventClicksPropagation: false,
			simulateTouch: true,
			watchSlidesProgress: true,
			watchSlidesVisibility: true,
			navigation: {
				nextEl: $this.find('.swiper-button-next'),
				prevEl: $this.find('.swiper-button-prev')
			},
			pagination: {
				el: $this.find('.swiper-pagination'),
				type: 'bullets',
				clickable: true
			},
			scrollbar: $this.find('.swiper-scrollbar'),
			scrollbarHide: false,
			scrollbarDraggable: true
		}, $this.data('options'));

		if (options.loop && ($(el).find('.swiper-slide').length < 2)) {
			options.loop = false;
		}

		if (!Journal.isDesktop) {
			options.a11y = false;
		}

		const swiper = new Swiper($('.swiper-container', el), options);

		function checkPages() {
			if (swiper.isBeginning && swiper.isEnd) {
				$this.removeClass('swiper-has-pages');
			} else {
				$this.addClass('swiper-has-pages');
			}
		}

		swiper.on('init', checkPages);

		swiper.on('resize', checkPages);

		swiper.init();

		if (options.autoplay) {
			// pause on hover
			if (options.pauseOnHover) {
				$('.swiper-container', el).hover(function () {
					swiper.autoplay.stop();
				}, function () {
					swiper.autoplay.start();
				});
			}

			// stop autoplay for elements not in viewport
			swiper.on('observerUpdate', function () {
				const visible = $(swiper.$el).is(':visible');
				const running = swiper.autoplay.running;

				if (visible && !running) {
					swiper.autoplay.start();
				}

				if (!visible && running) {
					swiper.autoplay.stop();
				}
			});
		}

		$this.data('swiper', swiper);

		$this.trigger($.Event('jcarousel:init', { swiper: swiper }));
	}

	Journal.lazy('carousel_swiper', '.swiper', {
		load: function (el) {
			if (el.querySelectorAll('.swiper-slide').length > 1) {
				Journal.load(Journal['assets']['swiper'], 'swiper', function () {
					carousel_swiper(el);
				});
			}
		}
	});

	//Journal Carousel
	function carousel_grid(el) {
		const gridItemsEl = el.querySelector('.auto-grid-items');
		const progressScrollBar = el.querySelector('.auto-carousel-bar');
		const progressScrollEl = el.querySelector('.auto-carousel-thumb');
		const progressFillEl = el.querySelector('.auto-carousel-fill');

		function onScroll() {
			const p = Math.round(gridItemsEl.scrollLeft / (gridItemsEl.scrollWidth - gridItemsEl.clientWidth) * 1000);
			const x = Math.round((progressScrollBar.clientWidth - progressScrollEl.clientWidth) * p / 100);

			if (Math.abs(p) <= 0) {
				gridItemsEl.parentElement.classList.add('no-scroll-prev');
			} else {
				gridItemsEl.parentElement.classList.remove('no-scroll-prev');
			}

			if (Math.abs(p) >= 1000) {
				gridItemsEl.parentElement.classList.add('no-scroll-next');
			} else {
				gridItemsEl.parentElement.classList.remove('no-scroll-next');
			}

			progressScrollEl.style.transform = 'translate3d(' + x / 10 + 'px, -50%, 0)';
			progressScrollEl.style.width = Math.round(progressScrollBar.clientWidth / gridItemsEl.scrollWidth * gridItemsEl.clientWidth) + 'px';
			progressFillEl.style.transform = 'translate3d(0, -50%, 0) scaleX(calc(' + p / 1000 + ' * var(--progressDirection, 1)))';
		}

		function onResize() {
			if (gridItemsEl.scrollWidth > gridItemsEl.clientWidth) {
				gridItemsEl.parentElement.classList.remove('no-scroll');
			} else {
				gridItemsEl.parentElement.classList.add('no-scroll');
			}
		}

		function slideByLeft(delta) {
			const scrollBy = Math.max(parseInt(getComputedStyle(gridItemsEl).getPropertyValue('--scroll-by'), 10), 1);
			const gap = Math.max(parseInt(getComputedStyle(gridItemsEl).getPropertyValue('gap'), 10), 0);

			gridItemsEl.classList.remove('is-dragging');

			gridItemsEl.scrollBy({
				behavior: 'smooth',
				left: (Journal['isRTL'] ? -1 : 1) * scrollBy * delta * gridItemsEl.firstElementChild.clientWidth + (gap * scrollBy)
			});
		}

		function slideByRight(delta) {
			const scrollBy = Math.max(parseInt(getComputedStyle(gridItemsEl).getPropertyValue('--scroll-by'), 10), 1);
			const gap = Math.max(parseInt(getComputedStyle(gridItemsEl).getPropertyValue('gap'), 10), 0);

			gridItemsEl.classList.remove('is-dragging');

			gridItemsEl.scrollBy({
				behavior: 'smooth',
				left: (Journal['isRTL'] ? -1 : 1) * scrollBy * delta * gridItemsEl.firstElementChild.clientWidth - (gap * scrollBy)
			});
		}

		const prev = el.querySelector('.auto-carousel-prev');

		if (prev) {
			prev.addEventListener('click', function () {
				slideByRight(-1);
			});
		}

		const next = el.querySelector('.auto-carousel-next');

		if (next) {
			next.addEventListener('click', function () {
				slideByLeft(1);
			});
		}

		if (gridItemsEl) {
			gridItemsEl.addEventListener('scroll', onScroll);
			new ResizeObserver(function () {
				onResize();
				onScroll();
			}).observe(gridItemsEl);
		}

		if (gridItemsEl && Journal['isDesktop']) {
			mouseDrag();
		}

		//Mouse drag
		function mouseDrag() {
			let isDown = false;
			let startX;
			let scrollLeft;

			gridItemsEl.addEventListener('mousedown', (e) => {
				if (e.button !== 0) {
					return;
				}

				isDown = true;
				startX = e.pageX - gridItemsEl.offsetLeft;
				scrollLeft = gridItemsEl.scrollLeft;
				cancelMomentumTracking();
			});

			gridItemsEl.addEventListener('mousemove', function (e) {
				if (!isDown) return;

				gridItemsEl.classList.add('is-dragging');
				gridItemsEl.classList.add('is-gliding');
				e.preventDefault();
				const x = e.pageX - gridItemsEl.offsetLeft;
				const walk = (x - startX);
				const prevScrollLeft = gridItemsEl.scrollLeft;

				gridItemsEl.scrollLeft = scrollLeft - walk;
				velX = gridItemsEl.scrollLeft - prevScrollLeft;
			});

			gridItemsEl.addEventListener('mouseup', function (e) {
				if (e.button !== 0) {
					return;
				}

				isDown = false;
				beginMomentumTracking();
			});

			// Momentum
			let velX = 0;
			let momentumID;

			gridItemsEl.addEventListener('wheel', function () {
				cancelMomentumTracking();
				gridItemsEl.classList.remove('is-dragging');
			}, { passive: true });

			function beginMomentumTracking() {
				cancelMomentumTracking();
				momentumID = requestAnimationFrame(momentumLoop);
				gridItemsEl.classList.add('is-gliding');
			}

			function cancelMomentumTracking() {
				cancelAnimationFrame(momentumID);
			}

			function momentumLoop() {
				gridItemsEl.scrollLeft += velX;
				gridItemsEl.scrollBehavior = 'smooth';
				velX *= .9;
				if (Math.abs(velX) > .45) {
					momentumID = requestAnimationFrame(momentumLoop);
				} else {
					gridItemsEl.classList.remove('is-gliding', 'is-dragging');
				}
			}
		}


		//Thumb drag
		const $thumb = el.querySelector('.auto-carousel-thumb');
		const $bar = el.querySelector('.auto-carousel-bar');

		if ($thumb && $bar) {
			let startX;
			let rectBar;
			let max;

			function thumbDrag(e) {
				let currentX = e.type === 'touchmove' ? e.touches[0].pageX : e.pageX;

				const p = Math.min(1, Math.max(0, (currentX - rectBar.x - startX) / rectBar.width));

				gridItemsEl.scrollLeft = Math.round(p * max);
			}

			function thumbStopDrag(e) {
				e.preventDefault();

				document.body.style.userSelect = null;
				gridItemsEl.classList.remove('is-dragging');

				document.removeEventListener('touchmove', thumbDrag);
				document.removeEventListener('mousemove', thumbDrag);

				document.removeEventListener('touchend', thumbStopDrag);
				document.removeEventListener('mouseup', thumbStopDrag);
			}

			function thumbStartDrag(e) {
				if (e.button !== 0) {
					return;
				}

				e.preventDefault();

				startX = e.type === 'touchstart' ? e.touches[0].pageX : e.pageX;
				startX -= e.target.getBoundingClientRect().x;

				rectBar = $bar.getBoundingClientRect();
				max = gridItemsEl.scrollWidth - gridItemsEl.clientWidth;

				document.body.style.userSelect = 'none';
				gridItemsEl.classList.add('is-dragging');

				document.addEventListener('touchmove', thumbDrag);
				document.addEventListener('mousemove', thumbDrag);

				document.addEventListener('touchend', thumbStopDrag, { once: true });
				document.addEventListener('mouseup', thumbStopDrag, { once: true });
			}

			$thumb.addEventListener('touchstart', thumbStartDrag, { passive: true });
			$thumb.addEventListener('mousedown', thumbStartDrag);
		}
	}

	Journal.lazy('carousel_grid', '.auto-grid', {
		load: function (el) {
			carousel_grid(el);
		}
	});
})(jQuery);
