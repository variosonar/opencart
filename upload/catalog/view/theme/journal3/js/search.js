(function ($) {
	const $search = $('#search');
	const $search_input = $search.find('input[name=\'search\']');
	const $search_button = $('.search-button');

	function search() {
		const query = $search_input.val().trim();

		if (!query) {
			return false;
		}

		let url = Journal['search_url'].replace('__QUERY__', query);

		const category_id = parseInt($search_input.attr('data-category_id'), 10);

		if (category_id) {
			url += '&category_id=' + category_id.toString();

			if (Journal['searchStyleSearchAutoSuggestSubCategories']) {
				url += '&sub_category=1';
			}
		}

		if (Journal['searchStyleSearchAutoSuggestDescription']) {
			url += '&description=1';
		}

		window.location.href = url;
	}

	// set search category
	$(document).on('click', '.search-categories li', function (e) {
		const $this = $(this);

		$('.search-categories-button').html($this.html());

		$search_input.attr('data-category_id', $this.attr('data-category_id'));
		$search_input.focus();
	});

	// search page open
	$search.on('shown.bs.dropdown', function (e) {
		if (e.relatedTarget.classList.contains('search-trigger')) {
			document.documentElement.classList.add('search-page-open');
		}
	});

	$search.on('hide.bs.dropdown', function (e) {
		if (e.relatedTarget.classList.contains('search-trigger')) {
			document.documentElement.classList.remove('search-page-open');
		}
	});

	// trigger focus
	$search.on('shown.bs.dropdown', function () {
		$search_input.focus();
	});

	// search focus
	$search_input.on('focus', function () {
		$(this).closest('.header-search').addClass('focused');
	});

	$search_input.on('blur', function () {
		$(this).closest('.header-search').removeClass('focused');
	});

	$(function () {
		// search on click
		$search_button.off('click').on('click', function (e) {
			search();
		});

		// search on enter
		$search_input.off('keydown').on('keydown', function (e) {
			if (e.keyCode === 13) {
				search();
			}
		});
	});

	// Autosuggest
	if (Journal['searchStyleSearchAutoSuggestStatus']) {
		$('#search-input-el').one('focus', function () {
			Journal.load(Journal['assets']['typeahead'], 'typeahead', function () {
				$search_input.typeahead({
					classNames: {
						menu: `tt-menu ${Journal[Journal['mobile_header_active'] ? 'header_mobile_search_results_color_scheme' : 'header_search_results_color_scheme']}`
					},
					hint: true,
					minLength: 1,
					autoSelect: true
				}, {
					async: true,
					display: 'name',
					limit: Infinity,
					source: function (query, processSync, processAsync) {
						let url = Journal['autosuggest_url'].replace('__QUERY__', query);

						const category_id = parseInt($search_input.attr('data-category_id'), 10);

						if (category_id) {
							url += '&category_id=' + category_id.toString();

							if (Journal['searchStyleSearchAutoSuggestSubCategories']) {
								url += '&sub_category=1';
							}
						}

						if (Journal['searchStyleSearchAutoSuggestDescription']) {
							url += '&description=1';
						}

						if (window['__journal_search_timeout']) {
							clearTimeout(window['__journal_search_timeout']);
						}

						window['__journal_search_timeout'] = setTimeout(function () {
							if (window['__journal_search_ajax']) {
								window['__journal_search_ajax'].abort();
							}

							window['__journal_search_ajax'] = $.get({
								url: url,
								dataType: 'json',
								success: function (json) {
									return processAsync(json['response']);
								}
							});
						}, 250);
					},
					templates: {
						suggestion: function (data) {
							if (data['view_more']) {
								return '<div class="search-result view-more"><a href="' + data['href'] + '">' + data['name'] + '</a></div>';
							}

							if (data['no_results']) {
								return '<div class="search-result no-results"><a>' + data['name'] + '</a></div>';
							}

							var html = '';

							html += '<div class="search-result"><a href="' + data['href'] + '">';

							if (data['thumb']) {
								html += '<img src="' + data['thumb'] + '" srcset="' + data['thumb'] + ' 1x, ' + data['thumb2'] + ' 2x" />';
							}

							var classes = [];

							if (data['quantity'] <= 0) {
								classes.push('out-of-stock');
							}

							if (!data['price_value']) {
								classes.push('has-zero-price');
							}

							html += '<span class="' + classes.join(' ') + '">';

							html += '<span class="product-name">' + data['name'] + '</span>';

							if (data['price']) {
								if (data['special']) {
									html += '<span><span class="price-old">' + data['price'] + '</span><span class="price-new">' + data['special'] + '</span></span>';
								} else {
									html += '<span class="price">' + data['price'] + '</span>';
								}
							}

							html += '</span>';

							html += '</a></div>';

							return html;
						}
					}

				});

				$('.header-search > span > div').addClass('tt-empty');

				$('#search-input-el').trigger('focus');

				// mobile page zoom fix
				$('.mobile .tt-menu').on('click', function (e) {
					e.stopPropagation();
				});
			});
		});
	}
})(jQuery);
