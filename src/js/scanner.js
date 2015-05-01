function updateScanStatus(currentStatus) {
	if (currentStatus.startTime) { // Have scan running
		scanner.status.html('<b>Current scan status: </b>');

		scanner.scanBtn.addClass('disabled');
		scanner.fullScanBtn.addClass('disabled');
	} else {
		scanner.status.html('<b>Previous scan\'s last status: </b>');

		scanner.scanBtn.removeClass('disabled');
		scanner.fullScanBtn.removeClass('disabled');
	}
	if (currentStatus.status) {
		scanner.status.append(currentStatus.status);

		if (currentStatus.time) {
			scanner.status.append(' (' + currentStatus.time + ')');
		}
	} else {
		scanner.status.append('None');
	}
}

function refreshScanStatus() {
	sendScanCommand('status');
}

function sendScanCommand(cmd, data) {
	if (!data) {
		data = {};
	}
	data.a = cmd;

	scanner.status.html('Starting scan... If this message doesn\'t change '
			+ 'you may need to refresh the page.');
	$.post(ajaxurl + '?action=gh_scan', data, receiveScanRefresh);
}

function receiveScanRefresh(data, textStatus, jqXHR) {
	updateScanStatus(data);

	// Start update job if have a job currently running
	if (data.startTime) {
		setTimeout(refreshScanStatus, 10000);
	}
}
