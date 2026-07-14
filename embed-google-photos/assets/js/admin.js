function bpgpbHandleShortcode(id) {
	var input = document.querySelector('#bpgpbFrontShortcode-' + id + ' input');
	var tooltip = document.querySelector('#bpgpbFrontShortcode-' + id + ' .tooltip');
	input.select();
	input.setSelectionRange(0, 30);
	document.execCommand('copy');
	tooltip.innerHTML = wp.i18n.__('Copied Successfully!', 'embed-google-photos');
	setTimeout(function () {
		tooltip.innerHTML = wp.i18n.__('Copy To Clipboard', 'embed-google-photos');
	}, 1500);
}
