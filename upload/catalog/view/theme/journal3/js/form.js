(function ($) {
	function form(el) {
		const $el = $(el);
		const $form = $el.find('form');

		// date pickers
		$el.find('.date button, .time button, .datetime button').on('click', function () {
			const $this = $(this);

			if (!$.fn.datetimepicker) {
				Journal.load(Journal['assets']['datetimepicker'], 'datetimepicker', function () {
					const language = $form.data('language');

					$('.date', el).datetimepicker({
						language: language,
						pickTime: false
					});

					$('.datetime', el).datetimepicker({
						language: language,
						pickDate: true,
						pickTime: true
					});

					$('.time', el).datetimepicker({
						language: language,
						pickDate: false
					});

					setTimeout(function () {
						$this.trigger('click');
					}, 10);
				});
			}
		});

		// upload button
		$el.find('.upload-btn').on('click', function () {
			var node = this;

			$('#form-upload').remove();

			$('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" /></form>');

			$('#form-upload input[name=\'file\']').trigger('click');

			if (typeof timer != 'undefined') {
				clearInterval(timer);
			}

			timer = setInterval(function () {
				if ($('#form-upload input[name=\'file\']').val() != '') {
					clearInterval(timer);

					$.ajax({
						url: 'index.php?route=tool/upload',
						type: 'post',
						dataType: 'json',
						data: new FormData($('#form-upload')[0]),
						cache: false,
						contentType: false,
						processData: false,
						beforeSend: function () {
							$(node).jbutton('loading');
						},
						complete: function () {
							$(node).jbutton('reset');
						},
						success: function (json) {
							$('.text-danger').remove();

							if (json['error']) {
								$(node).parent().find('input').after('<div class="text-danger">' + json['error'] + '</div>');
							}

							if (json['success']) {
								alert(json['success']);

								$(node).parent().find('input').val(json['code']);
							}
						},
						error: function (xhr, ajaxOptions, thrownError) {
							alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
						}
					});
				}
			}, 500);
		});

		// submit
		$el.on('click', '.btn-primary', function (e) {
			e.preventDefault();

			const $this = $(this);
			$form.find('.has-error').removeClass('has-error');
			$form.find('.text-danger').remove();

			const data = $form.serializeArray();

			data.push({
				name: 'url',
				value: parent.window.__popup_url || parent.window.location.toString()
			});

			$.ajax({
				url: $form.attr('action'),
				type: 'post',
				data: data,
				dataType: 'json',
				beforeSend: function () {
					$this.jbutton('loading');
				},
				complete: function () {
					$this.jbutton('reset');
				},
				success: function (response) {
					if (response.status === 'success') {
						if (response.response.redirect) {
							parent.window.location = response.response.redirect
						} else {
							show_message({
								message: response.response.message,
							});
							$form[0].reset();
							parent.window.__popup_url = undefined;
							parent.$('.module-popup-' + Journal['modulePopupId'] + ' .popup-close').trigger('click');
						}
					}

					if (response.status === 'error') {
						$.each(response.response.errors, function (field, error) {
							if (field === 'agree') {
								show_message({
									message: error,
								});
							} else if (field === 'captcha') {
								$form.find('.captcha').addClass('has-error');
							} else {
								$form.find('[name^="' + field + '"]').closest('.form-group').addClass('has-error').after('<div class="text-danger">' + error + '</div>');
							}
						});
					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
				}
			});
		});
	}

	Journal.lazy('form', '.module-form', {
		load: function (el) {
			form(el);
		}
	});
})(jQuery);
