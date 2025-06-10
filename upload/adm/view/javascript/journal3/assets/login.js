$(function () {
	if ($('form input[name="redirect"]').length) {
		window.sessionStorage.setItem('j3_hash', encodeURIComponent(window.location.hash));
	} else {
		window.sessionStorage.removeItem('j3_hash');
	}
});
