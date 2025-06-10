(function ($) {
	$(document).on('click', 'html.popup-login .login-form button, html.popup-register .register-form .buttons button', function (e) {
		e.preventDefault();

		const $this = $(this);
		const $form = $this.closest('form');

		$form.find('.has-error').removeClass('has-error');
		$form.find('.text-danger').remove();
		$this.jbutton('loading');

		$.ajax({
			url: $form.attr('action').replace('https:', location.protocol),
			type: 'post',
			data: $form.serialize(),
			dataType: 'json',
			error: function () {
				$this.jbutton('reset');
			},
			success: function (json) {
				if (json.redirect) {
					parent.window.location = json.redirect;
				}

				if (json.status === 'success') {
					if ($form.hasClass('login-form')) {
						if (parent.$('html').hasClass('route-account-logout')) {
							parent.window.location = $('base').attr('href');
						} else {
							parent.window.location.reload();
						}
					} else {
						if (json.customer) {
							parent.window.location = $('base').attr('href');
						} else {
							parent.window.location = 'index.php?route=account/success';
						}
					}
				} else {
					$this.jbutton('reset');

					const errors = json.response ? json.response : json.error;

					$.each(errors, function (field, value) {
						if (field === 'custom_field') {
							$.each(value, function (key, val) {
								$('#custom-field' + key).addClass('has-error').find('input').after('<div class="text-danger">' + val + '</div>');
							});
						} else if (field === 'captcha') {
							$form.find('.g-recaptcha, [name="captcha"]').closest('.form-group').addClass('has-error').after('<div class="text-danger">' + value + '</div>');
						} else {
							if (Journal.ocv == 4) {
								$form.find('#input-' + field.replaceAll("_", "-")).closest('.form-group').addClass('has-error').after('<div class="text-danger">' + value + '</div>');
								$form.find('[data-id="input-' + field.replaceAll("_", "-") + '"]').closest('.form-group').addClass('has-error').after('<div class="text-danger">' + value + '</div>');
							} else{
								$form.find('[name="' + field + '"]').closest('.form-group').addClass('has-error').after('<div class="text-danger">' + value + '</div>');
							}
						}
					});

					if (json.response && json.response.warning) {
						show_message({
							message: json.response.warning
						});
					}

					if (json.error && json.error.warning) {
						show_message({
							message: json.error.warning
						});
					}
				}
			}
		});
	});
})(jQuery);
