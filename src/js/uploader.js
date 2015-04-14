var uploader = {
	
};

function addUploadedFile(id, uploader, file, response) {
	// @todo Check for error

	if (!uploaders[id]) {
		return;
	}

	var data = JSON.parse(response.response);

	if (data.error) {
	}

	if (data.files) {
		var f, file;

		for (f in data.files) {
			file = data.files[f];

			switch (file.type) {
				case 'image': // Print the image and information
					new Editor(uploaders[id].uploadedDiv, file);
					break;
			}
		}
	}
}

function checkForUploadDir(id, ev) {
	if (uploaders[id]) {
		ev.preventDefault();

		if (!uploaders[id].dirId) {
				ev.stopImmediatePropagation();
				alert("Please choose a directory to upload the files into");
				return false;
		}
		else {
				return true;
		}
	}
}

function resetUploader(id) {
	if (!uploaders[id]) {
		return;
	}

	setTimeout(pub.returnFunction(doUploaderReset, true, id), 2000);
}

function doUploaderReset(id) {
	uploaders[id].uploader.destroy();
	initUploader(id);
}

function initUploader(id) {
	uploaders[id].obj.pluploadQueue(uploaders[id].options);
	
	var uploader = uploaders[id].obj.pluploadQueue();

	uploaders[id].uploader = uploader;

	// Hook function onto start button to stop upload if don't have a
	// destination folder
	var startButton = uploaders[id].obj.find('a.plupload_start');
	startButton.click(pub.returnFunction(checkForUploadDir, true, id));
	
	// Rearrange event handler for start button, to ensure that it has the ability
	// to execute first
	var clickEvents = $._data(startButton[0], 'events').click;
	if (clickEvents.length == 2) clickEvents.unshift(clickEvents.pop());

	// Bind to events
	uploader.bind('FileUploaded', pub.returnFunction(addUploadedFile, true, id));
	uploader.bind('UploadComplete', pub.returnFunction(resetUploader, true, id));

	// Set dir_id if we have one
	if (uploaders[id].dirId) {
		pub.setUploadDir(id, {id: uploaders[id].dirId});
	}
}
