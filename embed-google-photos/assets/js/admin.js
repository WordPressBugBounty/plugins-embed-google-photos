function bpgpbHandleShortcode(id) {
	var input = document.querySelector('#bpgpbFrontShortcode-' + id + ' input');
	var tooltip = document.querySelector('#bpgpbFrontShortcode-' + id + ' .tooltip');
	var text = input.value;

	function showCopied() {
		tooltip.innerHTML = wp.i18n.__('Copied Successfully!', 'embed-google-photos');
		setTimeout(function () {
			tooltip.innerHTML = wp.i18n.__('Copy To Clipboard', 'embed-google-photos');
		}, 1500);
	}

	if (navigator.clipboard) {
		navigator.clipboard.writeText(text).then(showCopied).catch(fallbackCopy);
	} else {
		fallbackCopy();
	}

	function fallbackCopy() {
		input.select();
		input.setSelectionRange(0, 99999);
		try {
			var ok = document.execCommand('copy');
			if (ok) {
				showCopied();
			}
		} catch (err) {
			// failed
		}
	}
}
