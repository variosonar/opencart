(function ($) {
	const $html = $('html');

	// lazyload images
	Journal.lazy('image', '.lazyload', {
		loaded: function (el) {
			el.classList.add('lazyloaded');
		}
	});

	// tooltips
	if (Journal['isDesktop']) {
		Journal.lazy('tooltip', '[data-toggle="tooltip-hover"]', {
			load: function (el) {
				const $el = $(el);

				$el.tooltip({ container: 'body' });
			}
		});
	}

	// tooltip class
	$(document).on('show.bs.tooltip', function (e) {
		const $el = $(e.target);

		$el.attr('title', '');
		$el.data('tooltipClass') && $el.data('bs.tooltip').$tip.addClass($el.data('tooltipClass'));
	});

	// dropdowns
	Journal.lazy('dropdown', '[data-toggle="dropdown"]', {
		load: function (el) {
			const $el = $(el);

			$el.attr('data-toggle', 'dropdown-hover');
		}
	});

	// dropdowns clear
	function clear_dropdowns() {
		const $trigger = $('> .dropdown-toggle', this);
		const $dropdown = $(this);
		const relatedTarget = { relatedTarget: $trigger[0] };

		$dropdown.trigger(e = $.Event('hide.bs.dropdown', relatedTarget));

		if (e.isDefaultPrevented()) {
			return;
		}

		$dropdown.removeClass('open');
		$trigger.attr('aria-expanded', 'true');

		$dropdown.trigger(e = $.Event('hidden.bs.dropdown', relatedTarget));
	}

	// dropdown toggle
	$(document).on('click', '[data-toggle="dropdown-hover"]', function (e) {
		const $trigger = $(this);
		const $dropdown = $(this).closest('.dropdown');
		const relatedTarget = { relatedTarget: $trigger[0] };
		const isOpen = $dropdown.hasClass('open');
		const isLink = $trigger.attr('href') && !$trigger.attr('href').startsWith('javascript:;');

		if (isOpen && isLink) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		if (!isOpen) {
			$dropdown.trigger(e = $.Event('show.bs.dropdown', relatedTarget));

			if (e.isDefaultPrevented()) {
				return;
			}

			$('.dropdown.open').not($dropdown).not($dropdown.parents('.dropdown')).each(clear_dropdowns);

			$dropdown.addClass('open');
			$trigger.attr('aria-expanded', 'false');

			$dropdown.trigger(e = $.Event('shown.bs.dropdown', relatedTarget));
		} else {
			$dropdown.trigger(e = $.Event('hide.bs.dropdown', relatedTarget));

			if (e.isDefaultPrevented()) {
				return;
			}

			$dropdown.removeClass('open');
			$trigger.attr('aria-expanded', 'true');

			$dropdown.trigger(e = $.Event('hidden.bs.dropdown', relatedTarget));
		}
	});

	// dropdown clear
	$(document).on('click', function (e) {
		$('.dropdown.open').not($(e.target).parents('.dropdown')).each(clear_dropdowns);
	});

	// dropdown on hover
	if (Journal['isDesktop']) {
		function dropdownOver(e) {
			if (Journal['isTouch']) {
				return;
			}

			const $trigger = $('> .dropdown-toggle', this);
			const $dropdown = $(this);
			const relatedTarget = { relatedTarget: $trigger[0] };

			$dropdown.trigger(e = $.Event('show.bs.dropdown', relatedTarget));

			if (e.isDefaultPrevented()) {
				return;
			}

			$dropdown.addClass('open');
			$trigger.attr('aria-expanded', 'false');

			$dropdown.trigger(e = $.Event('shown.bs.dropdown', relatedTarget));
		}

		function dropdownOut(e) {
			if (Journal['isTouch']) {
				return;
			}

			const $trigger = $('> .dropdown-toggle', this);
			const $dropdown = $(this);
			const relatedTarget = { relatedTarget: $trigger[0] };

			$dropdown.trigger(e = $.Event('hide.bs.dropdown', relatedTarget));

			if (e.isDefaultPrevented()) {
				return;
			}

			$dropdown.removeClass('open');
			$trigger.attr('aria-expanded', 'true');

			$dropdown.trigger(e = $.Event('hidden.bs.dropdown', relatedTarget));
		}

		$(document).hoverIntent({
			over: dropdownOver,
			out: dropdownOut,
			selector: '.dropdown:not([data-overlay]):not(.search-dropdown-page):not(.dropdown .dropdown)'
		});

		$(document).on('mouseenter', '.dropdown .dropdown', dropdownOver);
		$(document).on('mouseleave', '.dropdown .dropdown', dropdownOut);
	}

	// show animation
	$(document).on('shown.bs.dropdown', function (e) {
		const $dropdown = $(e.target);

		$dropdown.outerWidth();
		$dropdown.addClass('animating');
	});

	$(document).on('hidden.bs.dropdown', function (e) {
		const $dropdown = $(e.target);

		$dropdown.removeClass('animating');
	});

	// dropdown templates + color scheme
	$(document).on('show.bs.dropdown', function (e) {
		const el = $(e.target).find('template')[0] || $(' > template', e.target)[0];

		if (Journal['mobile_header_active']) {
			if (Journal['header_mobile_dropdown_color_scheme']) {
				const menu = $(e.target).find('.dropdown-menu')[0] || $(' > .dropdown-menu', e.target)[0];
				if (!$(menu).is('[class*="color-scheme-"]')) {
					menu.classList.add(Journal['header_mobile_dropdown_color_scheme']);
				}
			}
		} else {
			if (Journal['header_dropdown_color_scheme']) {
				const menu = $(e.target).find('.dropdown-menu')[0] || $(' > .dropdown-menu', e.target)[0];
				if (!$(menu).is('[class*="color-scheme-"]')) {
					menu.classList.add(Journal['header_dropdown_color_scheme']);
				}
			}
		}

		if (el) {
			Journal.template(el);
			Journal.lazy();
		}
	});

	$(document).on('shown.bs.dropdown', function (e) {
		const $el = $(e.target);
		if ($el.data('open-color-scheme')) {
			$el.addClass($el.data('open-color-scheme'));
		}
	});

	$(document).on('hidden.bs.dropdown', function (e) {
		const $el = $(e.target);
		if ($el.data('open-color-scheme')) {
			$el.removeClass($el.data('open-color-scheme'));
		}
	});

	// popover class
	$(document).on('show.bs.popover', function (e) {
		const $el = $(e.target);

		$el.data('popoverClass') && $el.data('bs.popover').$tip.addClass($el.data('popoverClass'));
	});

	// datepicker class
	$(document).on('dp.show', function (e) {
		const $el = $(e.target);

		$el.data('pickerClass') && $el.data('DateTimePicker').widget.addClass($el.data('pickerClass'));
	});

	// panel-active class
	$(document).on('show.bs.collapse', function (e) {
		$(e.target).parent().addClass('panel-active');
	});

	$(document).on('hide.bs.collapse', function (e) {
		$(e.target).parent().removeClass('panel-active');
	});

	// panel templates
	$(document).on('show.bs.collapse', function (e) {
		const el = $(e.target).find('template')[0];

		if (el) {
			Journal.template(el);
			Journal.lazy();
		}
	});

	// tabs templates
	$(document).on('show.bs.tab', function (e) {
		const href = $(e.target).attr('href');
		const el = $(href).filter('template')[0];

		if (el) {
			Journal.template(el);
			Journal.lazy();
		}
	});

	// off canvas
	$(document).on('click', '[data-off-canvas]', function (e) {
		e.preventDefault();

		const $this = $(this);
		const $container = $('.' + $this.data('off-canvas'));

		if (Journal['header_offcanvas_color_scheme']) {
			$container[0].classList.add('has-color-scheme', Journal['header_offcanvas_color_scheme']);
		}

		const el = $container.find('template')[0];

		if (el) {
			Journal.template(el);
			Journal.lazy();
		}

		$html.addClass('mobile-overlay');
		$html.addClass($this.data('off-canvas') + '-open');

		$container.outerWidth();
		$container.addClass('animating');
		//$('.desktop-mobile-main-menu-container .main-menu-item-1 .collapse').addClass('in');
		//$('.desktop-mobile-main-menu-wrapper .main-menu .open-menu').attr('aria-expanded', true)

		if ($this.data('off-canvas') === 'mobile-main-menu-container') {
			Journal.mobileOffCanvasDropdownOffset && Journal.mobileOffCanvasDropdownOffset();
		}
	});

	$(document).on('click', '.mobile-overlay .site-wrapper, .x', function () {
		const $this = $(this);

		$('.mobile-container.animating').removeClass('animating');

		$html.removeClass('mobile-overlay');

		setTimeout(function () {
			$('[data-off-canvas]').each(function () {
				const $this = $(this);
				$html.removeClass($this.data('off-canvas') + '-open');
			});
		}, 300);
	});

	// accordion menus
	$(document).on('click', '.accordion-menu span[data-toggle="collapse"]', function (e) {
		return false;
	});

	// accordion menus first tap open
	$(document).on('click', '.accordion-menu a', function (e) {
		const $this = $(this);
		const $trigger = $('> span[data-toggle="collapse"]', this);
		const isLink = $this.attr('href') && !$this.attr('href').startsWith('javascript:');

		if (Journal['isDesktop'] && isLink) {
			return;
		}

		if ($trigger.length && (!isLink || $trigger.attr('aria-expanded') !== 'true')) {
			e.preventDefault();

			$trigger.trigger('click');
		}
	});

	// grid dimensions
	if (Journal['isDesktop'] && Journal['isAdmin']) {
		Journal.lazy('dimensions', '.grid-col > .grid-items > .grid-item', {
			load: function (el) {
				$(el).one('mouseover', function () {
					const $this = $(this);
					$this.attr('data-dimensions', $this.width() + ' x ' + $this.height());
				});
			}
		});
	}

	// admin edit
	if (Journal['isDesktop'] && Journal['isAdmin']) {
		$(document).on('click', '[data-edit]', function () {
			window['__j_edited'] = false;
			const src = Journal.admin_url + '#/' + $(this).data('edit');

			$('.admin-edit-popup').remove();

			$html.addClass('is-editor-open');

			$('body').append('' +
				'<div class="admin-edit-popup">' +
				'	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>' +
				'	<button class="admin-close-edit" type="button">&times;</button>' +
				'	<iframe src="' + src + '" onload="$(\'.admin-edit-popup .journal-loading\').remove()" />' +
				'</div>');
		});

		$(document).on('click', '.admin-close-edit', function () {
			if (window['__j_edited']) {
				window.location.reload();
			} else {
				$html.removeClass('is-editor-open');
				$('.admin-edit-popup').remove();
			}
		});

		$(document).on('click', '.admin-close-bar', function () {
			$(this).tooltip('hide');
			$html.toggleClass('admin-bar-visible');
			window.localStorage.setItem('j-editor', $html.hasClass('admin-bar-visible') ? 'visible' : 'hidden');
		});
	}

	// load deferred inline scripts
	if (Journal['performanceJSDefer']) {
		$('script[type="text/javascript/defer"]').each(function () {
			$(this).after($('<script type="text/javascript"/>').text($(this).clone().text())).remove();
		});
	}

	// open popup links in new tab
	if (Journal['isLoginPopup'] || Journal['isRegisterPopup'] || Journal['isQuickviewPopup'] || Journal['isOptionsPopup']) {
		$('a[href]').each(function () {
			const $this = $(this);

			if (!$this.attr('target')) {
				$this.attr('target', '_blank');
			}

			if (!Journal['isDesktop']) {
				$this.removeClass('agree');
			}
		});
	}

	// smoothscroll
	if (!('scrollBehavior' in document.documentElement.style)) {
		Journal.load(Journal['assets']['smoothscroll'], 'smoothscroll');
	}

	// anchors scroll
	$(document).on('click', '[data-scroll-to]', function (e) {
		e.preventDefault();

		$($(this).data('scroll-to'))[0].scrollIntoView({ behavior: 'smooth' });
	});

	// scroll top
	if (Journal['scrollTop']) {
		let scrollTopTimeout;

		$(window).on('scroll', function () {
			const scroll = $(this)[0].scrollY;

			clearTimeout(scrollTopTimeout);

			if (scroll > 500) {
				$('.scroll-top').addClass('scroll-top-active');

				scrollTopTimeout = setTimeout(function () {
					$('.scroll-top').removeClass('scroll-top-active');
				}, 3000);
			} else {
				$('.scroll-top').removeClass('scroll-top-active');
			}
		});

		$('.scroll-top').on('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

	// Module Blocks
	$(document).on('click', '.block-expand', function () {
		$(this).closest('.expand-block').find('.expand-content').toggleClass('block-expanded');
	});

	$('.block-map iframe').on('load', function () {
		$('.block-map .journal-loading').hide();
	});

	// ripple effect
	if (Journal['rippleStatus'] && Journal['rippleSelectors']) {
		Journal.lazy('ripple', Journal['rippleSelectors'], {
			load: function (el) {
				const ripple = document.createElement('small');

				el.classList.add('ripple');
				el.appendChild(ripple);
				el.addEventListener('click', function (e) {
					e = e.touches ? e.touches[0] : e;
					const r = el.getBoundingClientRect(), d = Math.sqrt(Math.pow(r.width, 2) + Math.pow(r.height, 2)) * 2;
					el.style.cssText = `--s: 0; --o: 1;`;
					el.offsetTop;
					el.style.cssText = `--t: 1; --o: 0; --d: ${d}; --x:${e.clientX - r.left}; --y:${e.clientY - r.top};`
				});
			}
		});
	}

	// fix Opencart name attribute html5 validator issue (replace name with data-name)
	$(function () {
		$('#form-currency .currency-select').off('click').on('click', function (e) {
			e.preventDefault();

			$('#form-currency input[name=\'code\']').val($(this).data('name'));

			$('#form-currency').submit();
		});

		// Language
		if (Journal['ocv'] !== 4) {
			$('#form-language .language-select').off('click').on('click', function (e) {
				e.preventDefault();

				$('#form-language input[name=\'code\']').val($(this).data('name'));

				$('#form-language').submit();
			});
		}
	});

	// popup
	$(document).delegate('[data-open-popup]', 'click', function (e) {
		const $template = $($(this).data('open-popup'));

		// show modal
		$('.popup-wrapper').remove();

		$('body').append($template.html());

		setTimeout(function () {
			$('html').addClass('popup-open popup-center');
			Journal.lazy();
		}, 10);
	});

	// popup close oc4
	$(document).delegate('[data-bs-dismiss="modal"]', 'click', function (e) {
		$(e.target).closest('.modal').modal('hide');
	});

	// flyout menu offset
	function flyoutOffset(el) {
		el.style.setProperty('--element-top-offset', el.getBoundingClientRect().top + 'px');
	}

	$(document).on('shown.bs.dropdown', function (e) {
		const $parent = $(e.relatedTarget).parent();

		if ($parent.hasClass('flyout-menu-item')) {
			const $dropdown = $('> .j-dropdown', $parent);

			flyoutOffset($dropdown[0]);
		}
	});

	$(window).on('scroll', function () {
		if (!Journal['mobile_header_active']) {
			document.querySelectorAll('.flyout-menu > .j-menu > .flyout-menu-item.mega-menu > .j-dropdown').forEach(flyoutOffset);
		}
	});
})(jQuery);

(function ($) {
	// title h1 third party extensions
	$('#content > h1').addClass('title page-title');
})(jQuery);

// @todo check the following

(function ($) {
	var $html = $('html');
	var $body = $('body');

	$(document).delegate('.btn-extra', 'click', function () {
		parent.window.__popup_url = $(this).data('product_url') || '';
	});

	// Main Menu Hover Site Overlay

	var $desktop_main_menu_wrapper = $('.desktop-main-menu-wrapper');
	var $desktop_cart_wrapper = $('.desktop-cart-wrapper');
	var $desktop_search_wrapper = $('.desktop-search-wrapper');

	$desktop_main_menu_wrapper.delegate('.main-menu > .j-menu > .dropdown', 'mouseover', function () {
		$body.addClass('menu-open');
	});

	$desktop_main_menu_wrapper.delegate('.main-menu', 'mouseleave', function () {
		$body.removeClass('menu-open');
	});

	$desktop_cart_wrapper.delegate('#cart', 'mouseover', function () {
		$body.addClass('cart-open');
	});

	$desktop_cart_wrapper.delegate('#cart', 'mouseleave', function () {
		$body.removeClass('cart-open');
	});

	$desktop_search_wrapper.delegate('.search-categories', 'mouseover', function () {
		$body.addClass('search-open');
	});

	$desktop_search_wrapper.delegate('.search-categories', 'mouseleave', function () {
		$body.removeClass('search-open');
	});


	// $desktop_main_menu_wrapper.delegate('.main-menu > .j-menu > .mega-menu', 'mouseover', function () {
	// 	$(this).addClass('animation-delay');
	// });
	//
	// $desktop_main_menu_wrapper.delegate('.main-menu > .j-menu > .mega-menu', 'mouseleave', function () {
	// 	var $this = $(this);
	// 	setTimeout(function () {
	// 		$this.removeClass('animation-delay');
	// 	}, 250);
	// });

	//$('head').append('<style>.desktop-main-menu-wrapper .menu-item.dropdown::before {height: ' + ($body.height() - $('header').height()) + 'px} </style>');

	//Footer Links module collapse
	$('.links-menu .module-title').addClass('closed');

	$('.links-menu .module-title').click(function () {
		$(this).toggleClass('closed');
	});

	// Popup scroll from outside
	const target = document.querySelector(Journal['isPopup'] ? 'html' : '.popup-inner-body > .grid-rows');

	if (target && parent.document.documentElement.classList.contains('popup-open')) {
		parent.document.addEventListener('wheel', function (event) {
			target.scrollTop += event.deltaY;
		});
	}

	if (Journal['isOptionsPopup']) {
		const $el = document.querySelector('.popup-options .button-group-page');

		document.documentElement.style.setProperty('--popup-fixed-buttons', `${$el ? $el.offsetHeight : 0}px`);
	}

	if (Journal['isQuickviewPopup']) {
		const $el = document.querySelector('.popup-quickview .button-group-page');

		document.documentElement.style.setProperty('--popup-fixed-buttons', `${$el ? $el.offsetHeight : 0}px`);
	}

	// Manufacturer index height
	if (document.documentElement.classList.contains('route-product-manufacturer')) {
		const $el = document.querySelector('.brand-index');

		document.documentElement.style.setProperty('--brand-index-height', `${$el ? $el.offsetHeight : 0}px`);
	}

	// Product page fixed buttons height
	if (document.documentElement.classList.contains('route-product-product')) {
		const $el = document.querySelector('.button-group-page');

		document.documentElement.style.setProperty('--fixed-product-buttons', `${$el ? $el.offsetHeight : 0}px`);
	}

	//Page buttons dual agree class
	if (document.querySelectorAll('.buttons > div').length > 1 && document.querySelectorAll('.buttons > div > .agree').length > 0) {
		document.querySelector('.buttons').classList.add('dual-agree');
	}
	$(function () {
		$('.product-img, .product-image img').hover(function (e) {
				$(this).attr('data-title', $(this).attr('title'));
				$(this).removeAttr('title');
			},
			function (e) {
				$(this).attr('title', $(this).attr('data-title'));
			});
	});

	$('#information-information #content table').each(function () {
		$(this).wrap('<div class="table-responsive"></div>')
	});

})(jQuery);

(function ($) {
	var $html = $('html');
	var $body = $('body');

	if ($html.hasClass('footer-reveal')) {
		var footerHeight = $('.desktop.footer-reveal footer').outerHeight();
		$('.desktop body').css('padding-bottom', footerHeight);
	}
})(jQuery);
