(function ($) {
	function newsletter(el) {
		const $el = $(el);
		const $form = $el.find('form');

		// submit
		$el.on('click', '.btn-primary', function (e) {
			e.preventDefault();

			const $this = $(this);

			function ajax(unsubscribe) {
				$.ajax({
					url: $form.attr('action') + (unsubscribe ? '&unsubscribe=1' : ''),
					type: 'post',
					dataType: 'json',
					data: $form.serialize(),
					beforeSend: function () {
						$this.jbutton('loading');
					},
					complete: function () {
						$this.jbutton('reset');
					},
					success: function (json) {
						if (json.status === 'success') {
							if (json.response.unsubscribe) {
								if (confirm(json.response.message)) {
									ajax(true);
								}
							} else {
								if (json.response.subscribed) {
									const $popup_wrapper = $el.closest('.popup-wrapper');

									if ($popup_wrapper.length) {
										const options = $popup_wrapper.data('options');

										$popup_wrapper.find('.popup-close').trigger('click');

										if (options && options.cookie) {
											localStorage.setItem('p-' + options.cookie, '1');
										}
									}
								}

								show_message({
									message: json.response.message,
								});
							}
						} else {
							show_message({
								message: json.response,
							});
						}
					},
					error: function (xhr, ajaxOptions, thrownError) {
						alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
					}
				})
			}

			ajax();
		});
	}

	Journal.lazy('newsletter', '.module-newsletter', {
		load: function (el) {
			newsletter(el);
		}
	});
})(jQuery);
