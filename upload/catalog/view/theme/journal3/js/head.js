(function () {
	const documentClassList = document.documentElement.classList;

	// touchevents
	if (Journal['isDesktop'] && (('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0))) {
		let timeout;

		document.addEventListener('touchstart', function () {
			if (timeout) {
				clearTimeout(timeout);
			}

			Journal['isTouch'] = true;

			documentClassList.remove('no-touchevents');
			documentClassList.add('touchevents');

			timeout = setTimeout(function () {
				Journal['isTouch'] = false;

				documentClassList.add('no-touchevents');
				documentClassList.remove('touchevents');
			}, 400);
		});
	}

	// flexbox gap
	if (!(function () {
		// create flex container with row-gap set
		const flex = document.createElement('div');
		flex.style.display = 'flex';
		flex.style.flexDirection = 'column';
		flex.style.rowGap = '1px';

		// create two elements inside it
		flex.appendChild(document.createElement('div'));
		flex.appendChild(document.createElement('div'));

		// append to the DOM (needed to obtain scrollHeight)
		document.documentElement.appendChild(flex);

		const isSupported = flex.scrollHeight === 1; // flex container should be 1px high from the row-gap

		flex.parentNode.removeChild(flex);

		return isSupported;
	})()) {
		documentClassList.add('no-flexbox-gap');
	}

	// delegate event
	document.addDelegatedEventListener = function (eventName, elementSelector, handler) {
		document.addEventListener(eventName, function (e) {
			// loop parent nodes from the target to the delegation node
			for (let target = e.target; target && target !== this; target = target.parentNode) {
				if (target.matches(elementSelector)) {
					handler.call(target, e);
					break;
				}
			}
		}, false);
	}

	// detect ipads
	if (Journal['isDesktop'] && documentClassList.contains('safari') && !documentClassList.contains('ipad') && navigator.maxTouchPoints && navigator.maxTouchPoints > 2) {
		window.fetch('index.php?route=journal3/journal' + Journal['route_separator'] + 'device_detect', {
			method: 'POST',
			body: 'device=ipad',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			}
		}).then(function (data) {
			return data.json();
		}).then(function (data) {
			if (data.response.reload) {
				window.location.reload();
			}
		});
	}

	// j-editor
	if (Journal['isDesktop']) {
		if (window.localStorage.getItem('j-editor') !== 'hidden') {
			documentClassList.add('admin-bar-visible');
		}
	}
})();

(function () {
	if (Journal['isPopup']) {
		return;
	}

	const documentClassList = document.documentElement.classList;

	// move cart content on mobile headers
	if (Journal['mobile_header_active'] || !Journal['cartDropdown']) {
		document.addEventListener('DOMContentLoaded', function () {
			const wrapper = document.querySelector('.mobile-cart-content-wrapper');
			const cart_content = document.querySelector('.cart-content > ul');
			const cart = document.querySelector('#cart');

			if (wrapper && cart_content) {
				wrapper.appendChild(cart_content);
				cart.classList.remove('dropdown');
				const trigger = document.querySelector('#cart > a');
				trigger.removeAttribute('data-toggle');
				trigger.setAttribute('data-off-canvas', 'mobile-cart-content-container');
			}
		});
	}

	// move elements on small screens
	if (Journal['mobile_header_active']) {
		return;
	}

	const wrappers = ['search', 'cart', 'logo', 'language', 'currency'];
	const mobileHeaderMedia = window.matchMedia('(max-width: ' + Math.max(parseInt(Journal['mobileHeaderAt'], 10), 540) + 'px)');

	function callback() {
		const event = document.createEvent('CustomEvent');

		if (mobileHeaderMedia.matches) {
			mobileClasses();
			mobileHeader();
			mobileCart();

			event.initEvent('JournalMobileHeaderActive');
		} else {
			desktopClasses();
			desktopHeader();
			desktopCart();

			event.initEvent('JournalDesktopHeaderActive');
		}

		document.dispatchEvent(event);
	}

	mobileHeaderMedia.addListener(callback);

	if (mobileHeaderMedia.matches) {
		mobileClasses();
	}

	if (mobileHeaderMedia.matches) {
		document.addEventListener('DOMContentLoaded', function () {
			callback();
		});
	}

	function mobileClasses() {
		documentClassList.add('mobile-header-active');
		documentClassList.remove('desktop-header-active');
	}

	function desktopClasses() {
		documentClassList.add('desktop-header-active');
		documentClassList.remove('mobile-header-active');
	}

	function mobileHeader() {
		Object.keys(wrappers).forEach(function (k) {
			const element = document.querySelector('#' + wrappers[k]);
			const wrapper = document.querySelector('.mobile-' + wrappers[k] + '-wrapper');

			if (element && wrapper) {
				wrapper.appendChild(element);
			}
		});

		const search = document.querySelector('#search');
		const cart = document.querySelector('#cart');

		if (search && (Journal['searchStyle'] === 'full')) {
			search.classList.remove('full-search');
			search.classList.add('mini-search');
		}

		if (cart && (Journal['cartStyle'] === 'full')) {
			cart.classList.remove('full-cart');
			cart.classList.add('mini-cart');
		}

		if (cart && Journal['cartDropdown']) {
			cart.classList.remove('dropdown');
			const trigger = document.querySelector('#cart > a');
			trigger.removeAttribute('data-toggle');
			trigger.setAttribute('data-off-canvas', 'mobile-cart-content-container');
		}
	}

	function desktopHeader() {
		Object.keys(wrappers).forEach(function (k) {
			const element = document.querySelector('#' + wrappers[k]);
			const wrapper = document.querySelector('.desktop-' + wrappers[k] + '-wrapper');

			if (element && wrapper) {
				wrapper.appendChild(element);
			}
		});

		const search = document.querySelector('#search');
		const cart = document.querySelector('#cart');

		if (search && (Journal['searchStyle'] === 'full')) {
			search.classList.remove('mini-search');
			search.classList.add('full-search');
		}

		if (cart && (Journal['cartStyle'] === 'full')) {
			cart.classList.remove('mini-cart');
			cart.classList.add('full-cart');
		}

		if (cart && Journal['cartDropdown']) {
			cart.classList.add('dropdown');
			const trigger = document.querySelector('#cart > a');
			trigger.setAttribute('data-toggle', 'dropdown');
			trigger.removeAttribute('data-off-canvas');
		}

		documentClassList.remove('mobile-cart-content-container-open');
		documentClassList.remove('mobile-main-menu-container-open');
		documentClassList.remove('mobile-filter-container-open');
		documentClassList.remove('mobile-overlay');
	}

	function mobileCart() {
		const wrapper = document.querySelector('.mobile-cart-content-wrapper');
		const cart_content = document.querySelector('.cart-content > ul');

		if (wrapper && cart_content) {
			wrapper.appendChild(cart_content);
		}
	}

	function desktopCart() {
		const wrapper = document.querySelector('#cart-content');
		const cart_content = document.querySelector('.mobile-cart-content-wrapper > ul');

		if (wrapper && cart_content) {
			wrapper.appendChild(cart_content);
		}
	}

})();

(function () {
	if (Journal['isPopup']) {
		return;
	}

	if (!Journal['mobileMenuOn']) {
		return;
	}

	const documentClassList = document.documentElement.classList;

	const mobileMenuMedia = window.matchMedia('(max-width: ' + Math.max(parseInt(Journal['mobileMenuOn'], 10), 540) + 'px)');

	let selectors;
	let classes = [
		'mobile-menu-active'
	];

	if (Journal['mobileMenuMenus'] === 'menu-1') {
		selectors = '#main-menu';
		classes.push('mobile-menu-1-active');
	} else if (Journal['mobileMenuMenus'] === 'menu-2') {
		selectors = '#main-menu-2';
		classes.push('mobile-menu-2-active');
	} else {
		selectors = '#main-menu, #main-menu-2';
		classes.push('mobile-menu-1-active');
		classes.push('mobile-menu-2-active');
	}

	function callback() {
		if (mobileMenuMedia.matches) {
			const wrapper = document.querySelector('.desktop-mobile-main-menu-wrapper');
			const contents = document.querySelectorAll(selectors);

			if (wrapper && contents.length) {
				contents.forEach(function (content) {
					wrapper.appendChild(content);

					content.querySelectorAll('template').forEach(function (element) {
						Journal.template(element);
					});

					content.querySelectorAll('.main-menu .dropdown-toggle').forEach(function (element) {
						element.classList.remove('dropdown-toggle');
						element.classList.add('collapse-toggle');
						element.removeAttribute('data-toggle');
					});

					content.querySelectorAll('.main-menu .dropdown-menu').forEach(function (element) {
						element.classList.remove('dropdown-menu');
						element.classList.remove('j-dropdown');
						element.classList.add('collapse');
					});

					content.classList.add('accordion-menu');
				});

				Journal.lazy();
			}

			classes.forEach(function (cls) {
				documentClassList.add(cls);
			});
		} else {
			const wrapper = document.querySelector('.desktop-main-menu-wrapper');
			const contents = document.querySelectorAll(selectors);

			if (wrapper && contents.length) {
				contents.forEach(function (content) {
					wrapper.appendChild(content);

					content.querySelectorAll('.main-menu .collapse-toggle').forEach(function (element) {
						element.classList.add('dropdown-toggle');
						element.classList.remove('collapse-toggle');
						element.setAttribute('data-toggle', 'dropdown-hover');
					});

					content.querySelectorAll('.main-menu .collapse').forEach(function (element) {
						element.classList.add('dropdown-menu');
						element.classList.add('j-dropdown');
						element.classList.remove('collapse');
					});

					content.classList.remove('accordion-menu');
				});

				const $mm1 = document.querySelector('#main-menu');
				const $mm2 = document.querySelector('#main-menu-2');

				if ($mm1 && $mm2) {
					wrapper.insertBefore($mm1, $mm2);
				}
			}

			documentClassList.remove('desktop-mobile-main-menu-container-open');
			documentClassList.remove('mobile-overlay');

			classes.forEach(function (cls) {
				documentClassList.remove(cls);
			});
		}
	}

	mobileMenuMedia.addListener(callback);

	if (mobileMenuMedia.matches) {
		classes.forEach(function (cls) {
			documentClassList.add(cls);
		});
	}

	if (mobileMenuMedia.matches) {
		document.addEventListener('DOMContentLoaded', function () {
			callback();
		});
	}
})();

(function () {
	if (Journal['isPopup']) {
		return;
	}

	const documentClassList = document.documentElement.classList;

	Journal['globalPageHideColumnLeftAt'] = Math.max(+Journal['globalPageHideColumnLeftAt'] || 0, 100);
	Journal['globalPageHideColumnRightAt'] = Math.max(+Journal['globalPageHideColumnRightAt'] || 0, 100);

	const columnMedias = {
		left: window.matchMedia('(max-width: ' + Journal['globalPageHideColumnLeftAt'] + 'px)'),
		right: window.matchMedia('(max-width: ' + Journal['globalPageHideColumnRightAt'] + 'px)')
	}

	function callback() {
		Object.keys(columnMedias).forEach(function (key) {
			if (columnMedias[key].matches) {
				documentClassList.add(key + '-column-disabled');
				mobileFilter(key);
			} else {
				documentClassList.remove(key + '-column-disabled');
				desktopFilter(key);
			}
		});
	}

	Object.keys(columnMedias).forEach(function (key) {
		if (columnMedias[key].matches) {
			documentClassList.add(key + '-column-disabled');
		}

		columnMedias[key].addListener(callback);
	});

	document.addEventListener('DOMContentLoaded', function () {
		callback();
	});

	function mobileFilter(column) {
		const element = document.querySelector('#column-' + column + ' #filter');
		const wrapper = document.querySelector('.mobile-filter-wrapper');

		if (element && wrapper) {
			documentClassList.add('mobile-filter-active');
			wrapper.appendChild(element);
		}
	}

	function desktopFilter(column) {
		const element = document.querySelector('#filter');
		const wrapper = document.querySelector('#column-' + column + ' .desktop-filter-wrapper');

		if (element && wrapper) {
			documentClassList.remove('mobile-filter-active');
			documentClassList.remove('mobile-filter-container-open');
			wrapper.appendChild(element);
		}
	}
})();

(function () {
	const style = document.createElement('style');
	const documentClassList = document.documentElement.classList;

	document.head.appendChild(style);

	// popup
	if (Journal['popup']) {
		if (localStorage.getItem('p-' + Journal['popup']['c'])) {
			document.addEventListener('DOMContentLoaded', function () {
				document.querySelector('.popup-wrapper').remove();
			});
		} else {
			if (Journal['popup']['o']['showAfter']) {
				setTimeout(function () {
					documentClassList.add('popup-open', 'popup-center');
				}, Journal['popup']['o']['showAfter']);
			} else {
				documentClassList.add('popup-open', 'popup-center');
			}

			if (Journal['popup']['o']['hideAfter']) {
				setTimeout(function () {
					documentClassList.remove('popup-open', 'popup-center');
				}, Journal['popup']['o']['hideAfter']);
			}
		}
	}

	document.addEventListener('click', function (e) {
		if (e.target.matches('.popup-close, .popup-bg-closable, .btn-popup:not([href]), .btn-popup:not([href]) span')) {
			const $popup_wrapper = document.querySelector('.popup-wrapper');
			const $checkbox = document.querySelector('.popup-wrapper .popup-footer input[type="checkbox"]');
			const options = $popup_wrapper.dataset.options ? JSON.parse($popup_wrapper.dataset.options) : null;

			if ($checkbox && options && options.cookie) {
				if ($checkbox.checked) {
					localStorage.setItem('p-' + options.cookie, '1');
				} else {
					localStorage.removeItem('p-' + options.cookie);
				}
			}

			documentClassList.remove('popup-open', 'popup-center', 'popup-iframe-loaded');

			setTimeout(function () {
				$popup_wrapper.remove();
			}, 500);
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			const $popup_wrapper = document.querySelector('.popup-wrapper');

			if (!$popup_wrapper) {
				return;
			}

			const $checkbox = document.querySelector('.popup-wrapper .popup-footer input[type="checkbox"]');
			const options = $popup_wrapper.dataset.options ? JSON.parse($popup_wrapper.dataset.options) : null;

			if ($checkbox && options && options.cookie) {
				if ($checkbox.checked) {
					localStorage.setItem('p-' + options.cookie, '1');
				} else {
					localStorage.removeItem('p-' + options.cookie);
				}
			}

			documentClassList.remove('popup-open', 'popup-center');

			setTimeout(function () {
				$popup_wrapper.remove();
			}, 500);
		}
	});

	// notification
	if (Journal['notification']) {
		if (localStorage.getItem('n-' + Journal['notification']['c'])) {
			style.sheet.insertRule('.module-notification-' + Journal['notification']['m'] + '{ display:none !important }');
		}
	}

	document.addEventListener('click', function (e) {
		if (e.target.matches('.notification-close')) {
			const $this = $(e.target);
			const height = $this.parent().outerHeight();

			$this.parent().next('div').css('margin-top', -height);

			$('.removed').removeClass('removed');

			$this.parent().addClass('fade-out').on('transitionend MSTransitionEnd webkitTransitionEnd oTransitionEnd', function () {
				$(this).next('div').addClass('removed').css('margin-top', '');
				$(this).remove();
			});

			if (e.target.parentNode.classList.contains('module-notification')) {
				localStorage.setItem('n-' + Journal['notification']['c'], '1');
			}
		}
	});

	// header notice
	if (Journal['header_notice']) {
		if (localStorage.getItem('hn-' + Journal['header_notice']['c'])) {
			style.sheet.insertRule('.module-header_notice-' + Journal['header_notice']['m'] + '{ display:none !important }');
			document.documentElement.style.setProperty('--header-notice-height', '0px');
		}
	}

	document.addDelegatedEventListener('click', '.header-notice-close-button button', function (e) {
		const $el = this.closest('.module-header_notice');
		const options = JSON.parse($el.dataset.options || '{}');

		if (options['cookie']) {
			localStorage.setItem('hn-' + options['cookie'], '1');
		}

		$el.style.height = $el.offsetHeight + 'px';
		$el.style.transitionProperty = 'height';
		$el.style.transitionDuration = parseInt(Journal['header_notice']['o']['duration']) + 'ms';
		$el.style.transitionTimingFunction = Journal['header_notice']['o']['ease'];

		$el.getClientRects();

		$el.style.height = 0;

		document.documentElement.style.setProperty('--header-notice-height', '0px');
	});

	// layout notice
	if (Journal['layout_notice']) {
		if (localStorage.getItem('ln-' + Journal['layout_notice']['c'])) {
			style.sheet.insertRule('.module-layout_notice-' + Journal['layout_notice']['m'] + '{ display:none !important }');
		}
	}

	document.addDelegatedEventListener('click', '.layout-notice-close-button button', function (e) {
		const $el = this.closest('.module-layout_notice');
		const options = JSON.parse($el.dataset.options || '{}');

		if (options['cookie']) {
			localStorage.setItem('ln-' + options['cookie'], '1');
		}

		$el.style.height = $el.offsetHeight + 'px';
		$el.style.transitionProperty = 'height';
		$el.style.transitionDuration = parseInt(Journal['layout_notice']['o']['duration']) + 'ms';
		$el.style.transitionTimingFunction = Journal['layout_notice']['o']['ease'];

		$el.getClientRects();

		$el.style.height = 0;
	});
})();

(function () {
	// load
	Journal.load = function (urls, bundle, success) {
		if (loadjs.isDefined(bundle)) {
			loadjs.ready(bundle, {
				success: success
			});
		} else {
			loadjs(urls, bundle, {
				async: false,
				before: function (path, el) {
					document.head.prepend(el);
					return false;
				},
				success: success
			});
		}
	};

	// lazy
	Journal.lazy = function (name, selector, options) {
		window['__journal_lazy'] = window['__journal_lazy'] || {};

		if (arguments.length) {
			window['__journal_lazy'][name] = lozad(selector, options);
			window['__journal_lazy'][name].observe();
		} else {
			Object.entries(window['__journal_lazy']).forEach(function (entry) {
				entry[1].observe();
			});
		}
	};

	// template
	Journal.template = function (el) {
		if (el.attributes.length) {
			const div = document.createElement('div');

			div.innerHTML = el.innerHTML;

			for (let i = 0; i < el.attributes.length; i++) {
				div.setAttribute(el.attributes[i].name, el.attributes[i].value);
			}

			el.parentNode.replaceChild(div, el);
		} else {
			const child = el.content.firstElementChild.cloneNode(true);

			el.parentNode.replaceChild(child, el);

		}

		el.querySelectorAll('template').forEach(function (el) {
			Journal.template(el);
		});
	};
})();

(function () {
	const classList = document.documentElement.classList;

	// sticky position
	let stickyPos = 0;

	function updateStickyPos() {

		if (classList.contains('desktop-header-active')) {
			//Mega menu item height for dropdown offset
			if (!Journal['headerMainMenuFullHeight']) {
				const megaMenu = document.querySelector('.main-menu-item.mega-menu');

				if (megaMenu) {
					megaMenu.style.setProperty('--item-height', megaMenu.offsetHeight);
				}
			}
			if (classList.contains('sticky-default')) {
				stickyPos = document.querySelector('.top-bar')?.offsetHeight || 0;
			} else if (classList.contains('sticky-menu')) {
				stickyPos = (document.querySelector('.top-bar')?.offsetHeight || 0) + (document.querySelector('.mid-bar')?.offsetHeight || 0);
			}
		} else {
			stickyPos = document.querySelector('.mobile-top-bar')?.offsetHeight || 0;
		}

		const $hn = document.querySelector('.module-header_notice');

		if ($hn && $hn.offsetHeight) {
			stickyPos += $hn.offsetHeight;
			document.documentElement.style.setProperty('--header-notice-height', $hn.offsetHeight + 'px');
		}
	}

	if (Journal['stickyStatus']) {
		document.addEventListener('JournalDesktopHeaderActive', function (e) {
			updateStickyPos();
		});

		document.addEventListener('JournalMobileHeaderActive', function (e) {
			updateStickyPos();
		});

		document.addEventListener('DOMContentLoaded', function () {
			updateStickyPos();
		});
	}

	// scroll direction + sticky header class
	let scrollY = window.scrollY;
	let scrollDirection = '';

	document.addEventListener('scroll', function () {
		// scroll direction
		let currentDirection = '';

		if (window.scrollY > 100 && window.scrollY > scrollY) {
			currentDirection = 'down';
		} else if (window.scrollY >= (document.body.clientHeight - window.innerHeight)) {
			currentDirection = 'down';
		} else {
			currentDirection = 'up';
		}

		if (window.scrollY > Journal['flyoutStickyOffset']) {
			document.documentElement.setAttribute('data-sticky', 'is-sticky');
		} else {
			document.documentElement.setAttribute('data-sticky', 'not-sticky');
		}

		if (currentDirection !== scrollDirection) {
			document.documentElement.setAttribute('data-scroll', currentDirection);
		}

		// mac scroll always fix
		if (classList.contains('mac') && window.innerWidth > window.document.body.clientWidth && scrollY > 0) {
			classList.add('mac-scroll');
		}

		// sticky class
		if (Journal['stickyStatus']) {
			const stickyClass = classList.contains('desktop-header-active') ? 'header-sticky' : 'mobile-sticky';
			const $header = document.querySelector('.header');
			const $mobile_header = document.querySelector('.mobile-header');
			const $menu_wrapper = document.querySelector('.desktop-main-menu-wrapper.menu-bar');
			let header_color_scheme = Journal['header_color_scheme'];
			let header_mobile_color_scheme = Journal['header_mobile_color_scheme'];
			let header_menu_color_scheme = Journal['header_menu_color_scheme'];

			if (document.documentElement.classList.contains('route-common-home')) {
				if (Journal['header_home_color_scheme']) {
					header_color_scheme = Journal['header_home_color_scheme'];
				}
			}

			if (window.scrollY > stickyPos) {
				classList.add(stickyClass);

				if (classList.contains('desktop-header-active')) {
					if (Journal['sticky_color_scheme']) {
						if (header_color_scheme) {
							$header.classList.remove(header_color_scheme);
						}

						$header.classList.add(Journal['sticky_color_scheme']);
					}

					if (Journal['sticky_main_menu_color_scheme']) {
						if (header_menu_color_scheme) {
							$menu_wrapper.classList.remove(header_menu_color_scheme);
						}

						$menu_wrapper.classList.add(Journal['sticky_main_menu_color_scheme']);
					}
				} else {
					if (Journal['mobile_sticky_color_scheme']) {
						if (header_mobile_color_scheme) {
							$mobile_header.classList.remove(header_mobile_color_scheme);
						}

						$mobile_header.classList.add(Journal['mobile_sticky_color_scheme']);
					}
				}
			} else {
				classList.remove(stickyClass);

				if (classList.contains('desktop-header-active')) {
					if (Journal['sticky_color_scheme']) {
						if (header_color_scheme) {
							$header.classList.add(header_color_scheme);
						}

						$header.classList.remove(Journal['sticky_color_scheme']);
					}

					if (Journal['sticky_main_menu_color_scheme']) {
						if (header_menu_color_scheme) {
							$menu_wrapper.classList.add(header_menu_color_scheme);
						}

						$menu_wrapper.classList.remove(Journal['sticky_main_menu_color_scheme']);
					}
				} else {
					if (Journal['mobile_sticky_color_scheme']) {
						if (header_mobile_color_scheme) {
							$mobile_header.classList.add(header_mobile_color_scheme);
						}

						$mobile_header.classList.remove(Journal['mobile_sticky_color_scheme']);
					}
				}
			}
		}

		scrollY = window.scrollY;
		scrollDirection = currentDirection;

		//document.documentElement.style.setProperty('--body-scroll', scrollY + 'px');

	});

	document.addEventListener('DOMContentLoaded', function () {
		if (classList.contains('mac') && window.innerWidth > window.document.body.clientWidth) {
			classList.add('mac-scroll');
		}
	});
})();

// Desktop main menu horizontal scroll

(function () {
	if (Journal['isPopup']) {
		return;
	}

	if (!Journal['mobile_header_active']) {
		Journal.dropdownOffset = function () {
			const mainMenu = document.querySelectorAll('header [id*="main-menu"]');
			const dropdown = document.querySelectorAll('header [id*="main-menu"] > .j-menu > .dropdown');
			const megaMenu = document.querySelectorAll('header [id*="main-menu"] > .j-menu > .mega-menu');
			const megaMenuFull = document.querySelectorAll('header [id*="main-menu"] > .j-menu > .menu-fullwidth');

			const flyoutMenuItem = document.querySelectorAll('.flyout-menu > .j-menu > .flyout-menu-item.mega-menu > .j-dropdown');

			const $midBar = document.querySelector('header .mid-bar');
			let midBar = $midBar ? $midBar.getBoundingClientRect().right : 0;


			mainMenu.forEach(function (el) {
				//el.classList.add('activate-labels');
				let menuContainerRight = el.getBoundingClientRect().right;

				el.onscroll = function (e) {
					el.style.setProperty('--scroll-offset', Math.ceil(el.scrollLeft * (Journal['isRTL'] ? -1 : 1)) + 'px');
					if ((el.scrollWidth - el.clientWidth) <= el.scrollLeft) {
						el.classList.add('no-scroll-end');
					} else {
						el.classList.remove('no-scroll-end');
					}
				}
				dropdown.forEach(function (el) {
					el.style.setProperty('--element-offset', (Journal['isRTL'] ? midBar - el.getBoundingClientRect().right : el.offsetLeft) + 'px');
					el.style.setProperty('--element-width', el.clientWidth + 'px');
					el.style.setProperty('--element-height', el.clientHeight + 'px');
				});
				megaMenu.forEach(function (el) {
					el.style.setProperty('--mega-menu-top-offset', el.getBoundingClientRect().top + 'px');
				});
				megaMenuFull.forEach(function (el) {
					el.style.setProperty('--mega-menu-full-offset', (Journal['isRTL'] ? window.innerWidth - el.getBoundingClientRect().right : el.getBoundingClientRect().left) + 'px');
				});
			});
		}
		window.addEventListener('resize', Journal['dropdownOffset']);
		document.addEventListener('DOMContentLoaded', Journal['dropdownOffset']);
	}

	// Mobile secondary offset
	Journal.mobileDropdownOffset = function () {
		const mobileSecondaryMenu = document.querySelector('.mobile-secondary-menu');

		if (mobileSecondaryMenu) {

			const mobileMenu = document.querySelectorAll('.mobile-secondary-menu .top-menu');
			const mobileDropdown = document.querySelectorAll('.mobile-secondary-menu .top-menu > .j-menu > .dropdown');
			const mobileMenuRight = document.querySelector('.mobile-secondary-menu').getBoundingClientRect().right;

			mobileMenu.forEach(function (el) {
				el.onscroll = function (e) {
					el.style.setProperty('--scroll-offset', el.scrollLeft * (Journal['isRTL'] ? -1 : 1) + 'px');
				}
				mobileDropdown.forEach(function (el) {
					el.style.setProperty('--element-offset', (Journal['isRTL'] ? mobileMenuRight - el.getBoundingClientRect().right : el.offsetLeft) + 'px');
					el.style.setProperty('--element-width', el.clientWidth + 'px');
					el.style.setProperty('--element-height', el.clientHeight + 'px');
				});
			});
		}
	}
	window.addEventListener('resize', Journal['mobileDropdownOffset']);
	document.addEventListener('DOMContentLoaded', Journal['mobileDropdownOffset']);

	// Mobile off-canvas offset
	Journal.mobileOffCanvasDropdownOffset = function () {
		const mobileOffCanvasMenu = document.querySelector('.mobile-wrapper-top-menu');

		if (mobileOffCanvasMenu) {

			const mobileOffCanvas = document.querySelectorAll('.mobile-wrapper-top-menu .top-menu');
			const mobileOffCanvasDropdown = document.querySelectorAll('.mobile-wrapper-top-menu .top-menu > .j-menu > .dropdown');
			const mobileOffCanvasRight = document.querySelector('.mobile-wrapper-top-menu').getBoundingClientRect().right;

			mobileOffCanvas.forEach(function (el) {
				if (el.scrollWidth > el.parentElement.clientWidth) {
					el.parentElement.classList.add('has-scroll');
				} else {
					el.parentElement.classList.remove('has-scroll');
				}
				el.onscroll = function (e) {
					el.style.setProperty('--scroll-offset', el.scrollLeft * (Journal['isRTL'] ? -1 : 1) + 'px');
					if (el.scrollWidth > el.clientWidth) {
						el.parentElement.classList.add('has-scroll');
					} else {
						el.parentElement.classList.remove('has-scroll');
					}
					if (el.scrollLeft >= (el.scrollWidth - el.clientWidth - 1)) {
						el.parentElement.classList.add('no-scroll');
					} else {
						el.parentElement.classList.remove('no-scroll');
					}
				}
				mobileOffCanvasDropdown.forEach(function (el) {
					el.style.setProperty('--element-offset', (Journal['isRTL'] ? mobileOffCanvasRight - el.getBoundingClientRect().right : el.offsetLeft) + 'px');
					el.style.setProperty('--element-width', el.clientWidth + 'px');
					el.style.setProperty('--element-height', el.clientHeight + 'px');
				});
			});
		}
	}
	window.addEventListener('resize', Journal['mobileOffCanvasDropdownOffset']);
	document.addEventListener('DOMContentLoaded', Journal['mobileOffCanvasDropdownOffset']);


	// Mobile 1 search triangle and site overlay offset
	Journal.mobileSearch = function () {
		const mobile1 = document.querySelector('.mobile-header.mobile-1');

		if (mobile1) {
			let mobileHeight = mobile1.clientHeight;
			mobile1.style.setProperty('--mobile-1-height', mobileHeight + 'px');
			const miniSearch = document.querySelectorAll('.mobile-header.mobile-1 .mini-search #search');

			miniSearch.forEach(function (el) {
				el.style.setProperty('--element-offset', el.offsetLeft + 'px');
				el.style.setProperty('--element-width', el.clientWidth + 'px');
			});
		}
	}
	window.addEventListener('resize', Journal['mobileSearch']);
	document.addEventListener('DOMContentLoaded', Journal['mobileSearch']);

})();


(function () {
	if (Journal['isPopup']) {
		return;
	}

	Journal.tableScroll = function () {
		document.querySelectorAll('.table-responsive').forEach(function (el) {
			if (el.scrollWidth > el.clientWidth) {
				el.classList.add('table-scroll');
			} else {
				el.classList.remove('table-scroll');
			}
		});
	}

	window.addEventListener('resize', Journal['tableScroll']);
	document.addEventListener('DOMContentLoaded', Journal['tableScroll']);
})();
