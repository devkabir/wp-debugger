(function () {
	var button = document.querySelector('[data-wp-debugger-copy="stack-trace"]');
	var textarea = document.getElementById('wp-debugger-stack-trace');
	if (!button || !textarea) {
		return;
	}
	button.addEventListener('click', function () {
		var text = textarea.value || '';
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text);
			return;
		}
		textarea.focus();
		textarea.select();
		document.execCommand('copy');
	});
})();
