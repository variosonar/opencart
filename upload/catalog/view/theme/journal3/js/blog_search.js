(function ($) {
	function search(el) {
		const $this = $(el);
		const $input = $this.find('input');
		const $button = $this.find('button');

		$button.on('click', function () {
			const search = $input.val().trim();

			if (search) {
				parent.window.location = $this.data('url') + encodeURIComponent(search);
			}
		});

		$input.on('keydown', function (e) {
			if (e.keyCode === 13) {
				const search = $input.val().trim();

				if (search) {
					parent.window.location = $this.data('url') + encodeURIComponent(search);
				}
			}
		});
	}

	Journal.lazy('search', '.module-blog_search', {
		load: function (el) {
			search(el);
		}
	});
})(jQuery);
