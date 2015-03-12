gH = (function ($) {
	var g = {},
			scanner = {},
			uploaders = {},
			$ = jQuery,
			hideClass = 'hide',
			imageUrl,
			cacheUrl,
			imageData,
			options;

	//= include editor.js
	//= include upload.js

	/**
	 * To use with array.sort
	 * arr.sort(function(o1, o2) {
   *   return naturalSorter(o1.t, o2.t);
	 * });
	 * http://stackoverflow.com/questions/19247495/alphanumeric-sorting-an-array-in-javascript
	 * http://jsfiddle.net/MikeGrace/Vgavb/
	 */
	function naturalSorter(as, bs){
    var a, b, a1, b1, i= 0, n, L,
    rx=/(\.\d+)|(\d+(\.\d+)?)|([^\d.]+)|(\.\D+)|(\.$)/g;
    if(as=== bs) return 0;
    a= as.toLowerCase().match(rx);
    b= bs.toLowerCase().match(rx);
    L= a.length;
    while(i<L){
			if(!b[i]) return 1;
			a1= a[i],
			b1= b[i++];
			if(a1!== b1){
				n= a1-b1;
				if(!isNaN(n)) return n;
				return a1>b1? 1:-1;
			}
    }

    return b[i]? -1:0;
	}

	function rFunc(func, context, add) {
		var a = Array.prototype.slice.call(arguments);
		// Remove known arguments off array
		a.splice(0, 3);
		return function () {
			if (add) {
				var b = [].concat(a);
				b = b.concat(Array.prototype.slice.call(arguments));
				func.apply(context, b);
			} else {
				func.apply(context, a);
			}
		};
	}

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
		$.post(ajaxurl + '?action=gh_scan', data, receiveScanRefresh);
	}

	function receiveScanRefresh(data, textStatus, jqXHR) {
		updateScanStatus(data);

		// Start update job if have a job currently running
		if (data.startTime) {
			setTimeout(refreshScanStatus, 10000);
		}
	}


	function addUploadedFile(id, uploader, file, response) {
		console.log('addUploadedFile called');
		// @todo Check for error

		console.log(id);
		console.log(uploaders);

		if (!uploaders[id]) {
			return;
		}

		var data = JSON.parse(response.response);

		console.log(data);

		if (data.error) {
		}

		if (data.files) {
			var f, file;

			for (f in data.files) {
				file = data.files[f];

				switch (file.type) {
					case 'image': // Print the image and information
						new editor(uploaders[id].uploadedDiv, file);
						break;
				}
			}
		}
	}

	function checkForUploadDir(id, ev) {
		console.log('id is ' + id);
		if (uploaders[id]) {
			console.log('got a valid id ' + id);
			console.log(uploaders[id]);
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
		console.log('resetUploader called');
		console.log(id);
		console.log(uploaders[id]);
		if (!uploaders[id]) {
			return;
		}

		console.log('creating timeout');
		setTimeout(pub.returnFunction(doUploaderReset, true, id), 2000);
	}

	function doUploaderReset(id) {
		console.log('kill kill kill');
		uploaders[id].uploader.destroy();
		initUploader(id);
	}

	function initUploader(id) {
		console.log('initUploader called');
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


	var pub = {
		init: function(opts) {
			options = opts;
			if (opts.imageUrl) imageUrl = opts.imageUrl;
			if (opts.cacheUrl) cacheUrl = opts.cacheUrl;
		},

		/**
		 * Controls the scanning functionality on the Load Images page.
		 *
		 * @param dom JQueryDOMObject Object to put scanning functionality into
		 * @param currentStatus Object Object containing current status
		 *
		 * @todo add multilingual support
		 */
		scanControl: function(dom, currentStatus) {
			scanner.dom = dom;
			dom.append((scanner.status = $('<p></p>')));
			dom.append((scanner.scanBtn = $('<a class="button">'
					+ 'Rescan Directories' + '</a>')));
			scanner.scanBtn.click(this.returnFunction(sendScanCommand, false, 'rescan'));
			dom.append(' ');
			dom.append((scanner.fullScanBtn = $('<a class="button">'
					+ 'Force Rescan of All Images' + '</a>')));
			scanner.fullScanBtn.click(this.returnFunction(sendScanCommand, false, 'full'));

			receiveScanRefresh(currentStatus);
		},

		uploader: function(id, obj, options) {
			console.log('starting uploader');
			if (id && obj) {
				console.log('got valid');
				console.log(options);
				uploaders[id] = {
					options: options,
					obj: obj,
					dir_id: false,
					uploadedDiv: $('#' + id + 'uploaded')
				};
				
				initUploader(id);
			}
		},

		setUploadDir: function(id, files) {
			if (id && uploaders[id]) {
				// Get folder (only first folder)
				if (files.constructor === Array) {
					files = files[0];
				}
				console.log(files);
			
				console.log('setting folder id to ' + files.id);
				uploaders[id].dirId = files.id;

				// Set the folder parameter on the uploader
				uploaders[id].uploader.setOption('multipart_params', {
					dir_id: files.id
				});
			}
		},

		gallery: function(id, insertOnly) {
			var idO;
			if ((idO = $('#' + id + 'pad'))) {
				g[id] = {
						'insertOnly': insertOnly,
						'builderOn': (insertOnly ? true : false),
						'selected': {}, // Stores the selected images
						'currentImages': null, // Stores the images displayed in pad
						'imageIndex': null, // Used to look an image in currentImages based on its id
						'selectOrder': [], // Stores the order of selected images
						'changed': {}, // Stores any changed information to send to server
						'currentOffset': 0, // Stores the current image offset
						'currentLimit': 0,
						'showingCurrent': false, // False if not or filtered image offset
						'idsOnly': false, // True when only ids should be in shortcode
						'pad': idO, // pad DOM element
						'input': $('#' + id + 'input'), // Input field used when inserting into post/page
						'form': $('#' + id + 'form'), // Form used when inserting into post/page
						'folders': [], // Selected folders array
						'foldersChanged': false, // Stores whether the folders have been changed
						'recurse': $('#' + id + 'recurse'), // Recursive checkbox
						'start': $('#' + id + 'start'), // Start date input
						'end': $('#' + id + 'end'), // End date input
						'name': $('#' + id + 'name'), // Name search input
						'title': $('#' + id + 'title'), // Title search input
						'comment': $('#' + id + 'comment'), // Comment
						'tags': $('#' + id + 'tags'),
						'group': $('#' + id + 'group'),
						'class': $('#' + id + 'class'),
						'limit': $('#' + id + 'limit'),
						'sort': $('#' + id + 'sort'),
						'caption': $('#' + id + 'caption'),
						'popup_caption': $('#' + id + 'popupCaption'),
						'link': $('#' + id + 'link'),
						'size': $('#' + id + 'size'),
						'type': $('#' + id + 'type'),
						'include_excluded': $('#' + id + 'includeExcluded'),
						'options': {
								'ghalbum': ['type'],
								'ghimage': ['size'],
						},
						'filter': $('#' + id + 'filter'),
						'filterButton': $('#' + id + 'filterButton'),
						'saveButton': $('#' + id + 'saveButton'),
						'filterLabel': $('#' + id + 'filterLabel'),
						'builder': $('#' + id + 'builder'),
						'builderLabel': $('#' + id + 'builderLabel'),
						'limit': $('#' + id + 'limit'), // Controls # of images per page
						'shortcode': $('#' + id + 'shortcode'),
						'selectedLabel': $('#' + id + 'selectedLabel'),
						'sctype': $('#' + id + 'sctype'),
						'pages': $('#' + id + 'pages'), // Span for page changer
						//'': $('#' + id + ''),
				};

				// Initialise currentLimit
				if (isNaN(g[id]['currentLimit'] = parseInt(g[id]['limit'].val()))) {
					g[id]['currentLimit'] = 50;
					g[id]['limit'].val(50);
				}

				// Initialise datetime fields
				g[id]['start'].datetimepicker({ 
						timeFormat: 'HH:mm',
						dateFormat: 'yy-mm-dd',
						stepMinute: 10,
						controlType: 'select',
						onClose: function(dateText, inst) {
							if (g[id]['end'].val() != '') {
								var testStartDate = g[id]['start'].datetimepicker('getDate');
								var testEndDate = g[id]['end'].datetimepicker('getDate');
								if (testStartDate > testEndDate)
										g[id]['end'].datetimepicker('setDate', testStartDate);
							}
						},
						onSelect: function (selectedDateTime){
							g[id]['end'].datetimepicker('option', 'minDate', g[id]['start'].datetimepicker('getDate') );
						}
				});
				g[id]['end'].datetimepicker({ 
						timeFormat: 'HH:mm',
						dateFormat: 'yy-mm-dd',
						stepMinute: 10,
						controlType: 'select',
						onClose: function(dateText, inst) {
							if (g[id]['start'].val() != '') {
								var testStartDate = g[id]['start'].datetimepicker('getDate');
								var testEndDate = g[id]['end'].datetimepicker('getDate');
								if (testStartDate > testEndDate)
											g[id]['start'].datetimepicker('setDate', testEndDate);
							}
						},
						onSelect: function (selectedDateTime){
							g[id]['start'].datetimepicker('option', 'maxDate', g[id]['end'].datetimepicker('getDate') );
						}
				});
			}
			
			this.redisplayShortcode(id);
		},

		/**
		 * Used to toggle the visibility of elements.
		 * @param id string Id of the gallery
		 * @param part string Part to toggle
		 * @param label string Label of part toggling
		 * @return boolean Whether the part is showing or not
		 */
		toggle: function(id, part, label, onLabel, offLabel) {
			if(!g[id]) {
				return;
			}

			if (!onLabel) onLabel = 'Show';
			if (!offLabel) offLabel = 'Hide';
			if (g[id] && g[id][part]) {
				if (g[id][part].hasClass(hideClass)) {
					g[id][part].removeClass(hideClass);
					g[id][part + 'Label'].html(offLabel + ' ' + label);
					return true;
				} else {
					g[id][part].addClass(hideClass);
					g[id][part + 'Label'].html(onLabel + ' ' + label);
					return false;
				}
			}
		},

		thumbnail: function(image) {
			return cacheUrl + '/' + image.replace(/\//g, '_');
		},

		/**
		 * Used to submit a new filter if the filter has been updated
		 * since last time.
		 */
		filter: function(id) {
			if(!g[id]) {
				return;
			}

			if (!g[id]['query']) {
				g[id]['query'] = {
						'folders': [],
						'recurse': 0,
						'start': '',
						'end': '',
						'name': '',
						'title': '',
						'comment': '',
						'tags': ''
				};
			}

			// Go through and see if it is the same query as last
			var p, v, i;
			var changed = false;
			for (p in g[id]['query']) {
				if (p == 'folders') {
					if (g[id].foldersChanged) {
						console.log('folders have changed');
						g[id].query[p] = g[id].folders;
						console.log(g[id].query);
						changed = true;
						g[id].foldersChanged = false;
					}
				} else if (g[id][p].prop('type') == 'checkbox') {
					v = g[id][p].prop('checked');
					console.log('isChecked? ' + v);
					if (v && !g[id]['query'][p]) {
						g[id]['query'][p] = 1;
						changed = true;
					} else if (!v && g[id]['query'][p]) {
						g[id]['query'][p] = 0;
						changed = true;
					}
				} else if (g[id]['query'][p] != (v = g[id][p].val())) {
					g[id]['query'][p] = v;
					changed = true;
				}
			}

			if (changed) {
				/// @todo Add localisation
				g[id]['filterButton'].html('Loading...');
				$.post(ajaxurl + '?action=gh_gallery', g[id]['query'], this.returnFunction(this.receiveData, true, id));
			}
		},

		toggleBuilder: function (id) {
			if(!g[id]) {
				return;
			}

			/// @todo Add localisation
			if (!g[id]['insertOnly']) {
				if ((g[id]['builderOn'] = this.toggle(id, 'builder', 'shortcode builder', 'Enable', 'Disable'))) {
					g[id]['pad'].addClass('builderOn');
				} else {
					g[id]['pad'].removeClass('builderOn');
				}
			} else {
				this.toggle(id, 'builder', 'shortcode options');
			}
		},

		receiveData: function (id, data, textStatus, jqXHR) {
			if(!g[id]) {
				return;
			}

			g[id]['imageData'] = data;
			
			/// @todo Add localisation
			g[id]['filterButton'].html('Filter');

			this.setCurrentImages(id, g[id]['imageData']);
			this.printImages(id, 0);

			g[id]['idsOnly'] = false;
			this.redisplayShortcode(id);
		},

		/**
		 * Handles a change in the number images per page.
		 */
		repage: function(id) {
			if(!g[id]) {
				return;
			}

			var limit = parseInt(g[id]['limit'].val());
			
			if (isNaN(limit)) {
				g[id]['limit'].val('50');
				limit = 50;
			}

			g[id]['currentLimit'] = limit;

			if (g[id]['currentImages']) {
				/* Calculate what offset the current offset would be on and
				 * amd calculate new offset from there
				 */
				if (limit) {
					g[id]['currentOffset'] = Math.floor(g[id]['currentOffset'] / limit) * limit;
				} else {
					g[id]['currentOffset'] = 0;
				}

				this.printImages(id, g[id]['currentOffset']);
			}
		},

		/**
		 * Repages the current images when the images per page is changed
		 */
		changePage: function(id) {
			if(!g[id]) {
				return;
			}

			// Get value
			var page;
			var currentPage = Math.floor(g[id]['currentOffset'] / g[id]['currentLimit']) + 1;
			var maxPage = Math.floor(g[id]['currentImages'].length / g[id]['currentLimit']) + 1;
			if (isNaN(page = parseInt(g[id]['pageNumber'].val()))) {
				page = currentPage;
				g[id]['pageNumber'].val(page);
			} else {
				// Check limits
				if (page > maxPage) {
					page = maxPage;
					g[id]['pageNumber'].val(page);
				} else if (page < 1) {
					page = 1;
					g[id]['pageNumber'].val(page);
				}
			}
			
			if (page != currentPage) {
				page = (page - 1) * g[id]['currentLimit'];
				this.printImages(id, page);
			}
		},

		/**
		 * Submits the insert form to insert images into the post/page.
		 */
		insert: function(id) {
			if(!g[id]) {
				return;
			}
			
			var code = this.gatherShortcodeData(id);

			if (g[id]['input'].length !== 0) {
				g[id]['input'].val(JSON.stringify(code));
				g[id]['form'].submit();
			}
		},

		/**
		 * Draws the current images in the page
		 *
		 * @param id string Id of the current gallery
		 * @param offset int Offset to use
		 */
		printImages: function(id, offset) {
			if(!g[id]) {
				return;
			}

			if (isNaN(offset = parseInt(offset))) {
				offset = 0;
			}

			// Stop if we have no images
			if (!g[id]['currentImages']) {
				return;
			}
			
			g[id]['currentOffset'] = offset;

			// Wipe the pad
			g[id]['pad'].html('');

			var limit = Math.min(offset + g[id]['currentLimit'], g[id]['currentImages'].length);
			offset = Math.min(offset, g[id]['currentImages'].length);

			var i;
			for(i = offset; i < limit; i++) {
				var d = (g[id]['currentImages'][i]['div'] = $(document.createElement('div'))).addClass('galleryThumb');
				var o,p,l;
				// Temporary Image Link
				d.append((l = $(document.createElement('a'))));
				l.attr('href', imageUrl + '/' + g[id]['currentImages'][i]['path']);
				l.attr('data-lightbox', 'thumbs');
				l.attr('data-title', 'ID: ' + g[id]['currentImages'][i]['id'] + '.'
						+ (g[id].currentImages[i].title ? '<br>Title: '
						+ g[id]['currentImages'][i]['title'] + '.' : '')
						+ (g[id].currentImages[i].comment ? '<br>Comment: '
						+ g[id].currentImages[i].comment + '.' : ''));
				// Image
				l.append((o = $('<img src="' + this.thumbnail(g[id]['currentImages'][i]['path']) + '">')));
				// Excluder
				d.append((o = $(document.createElement('div'))));
				o.click(this.returnFunction(this.exclude, true, id, i));
				o.addClass('exclude');
				o.attr('title', 'Exclude from galleries by default');
				// Selector
				d.append((o = $(document.createElement('div'))));
				o.addClass('select');
				o.click(this.returnFunction(this.select, true, id, i));
				o.attr('title', 'Include in selection');

				// Orderer
				d.append((o = $(document.createElement('div'))));
				o.addClass('orderer');
				o.append((p = $(document.createElement('div'))));
				p.addClass('dashicons dashicons-arrow-left-alt');
				p.click(this.returnFunction(this.order, true, id, i, -1));
				o.append((g[id]['currentImages'][i]['order'] = (p = $(document.createElement('div')))));
				p.addClass('order');
				o.append((p = $(document.createElement('div'))));
				p.addClass('dashicons dashicons-arrow-right-alt');
				p.click(this.returnFunction(this.order, true, id, i, 1));
				
				var iId = g[id].currentImages[i].id;
				// Check for exclusion/selection
				if (g[id]['currentImages'][i]['exclude'] 
						&& (g[id]['currentImages'][i]['exclude'] === '1'
						|| (g[id].changed[iId] && g[id].changed[iId].exclude
						&& g[id].changed[iId].exclude.new === true))) {
					d.addClass('excluded');
				}
				if (g[id]['selected'][g[id]['currentImages'][i]['id']]) {
					d.addClass('selected');
					g[id]['currentImages'][i]['order'].html(g[id]['selectOrder'].indexOf(g[id]['currentImages'][i]['id']) + 1);
				}

				g[id]['pad'].append(d);
			}

			// Draw pagination
			g[id]['pages'].html('');
			
			// Displaying number
			g[id]['pages'].append((o = $(document.createElement('span'))));
			o.addClass('displaying-num');
			o.html(g[id]['currentImages'].length + ' items');

			// First page
			g[id]['pages'].append((o = $(document.createElement('a'))));
			if (offset !== 0) {
				o.click(this.returnFunction(this.printImages, true, id, 0));
			} else {
				o.addClass('disabled');
			}
			o.html('&laquo;');
			o.addClass('first-page');
			// Prev page
			g[id]['pages'].append((o = $(document.createElement('a'))));
			if (offset !== 0) {
				// Sanity check
				if ((i = offset - limit) < 0)  {
					i = 0;
				}
				o.click(this.returnFunction(this.printImages, true, id, i));
			} else {
				o.addClass('disabled');
			}
			o.html('&lsaquo;');
			o.addClass('prev-page');

			// Current page input
			g[id]['pages'].append((o = (g[id]['pageNumber'] = $(document.createElement('input')))));
			o.attr('type', 'number');
			o.addClass('current-page');
			o.attr('title', 'Current Page');
			o.val(Math.ceil(offset / g[id]['currentLimit']) + 1);
			o.change(this.returnFunction(this.changePage, true, id));

			// Of
			g[id]['pages'].append(document.createTextNode(' of '));

			var maxOffsets = Math.floor(g[id]['currentImages'].length / g[id]['currentLimit']);
			var lastOffset = maxOffsets * g[id]['currentLimit'];
			// Total
			g[id]['pages'].append((o = $(document.createElement('span'))));
			o.addClass('total-pages');
			o.html(maxOffsets + 1);

			// Next page
			g[id]['pages'].append((o = $(document.createElement('a'))));
			if (offset !== lastOffset) {
				// Sanity check
				if ((i = offset + g[id]['currentLimit']) > lastOffset)  {
					i = lastOffset;
				}
				o.click(this.returnFunction(this.printImages, true, id, i));
			} else {
				o.addClass('disabled');
			}
			o.html('&rsaquo;');
			o.addClass('next-page');

			// Last page
			g[id]['pages'].append((o = $(document.createElement('a'))));
			if (offset !== lastOffset) {
				o.click(this.returnFunction(this.printImages, true, id, lastOffset));
			} else {
				o.addClass('disabled');
			}
			o.html('&raquo;');
			o.addClass('last-page');
		},

		changeFolder: function(id, files) {
			if (!g[id]) {
				return;
			}

			// Restart array
			g[id].folders = [];
			g[id].foldersChanged = true;

			var f;
			for (f in files) {
				g[id].folders.push(files[f].id);
			}

			console.log('folders updated for ' + id);
			console.log(g[id].folders);
		},

		setCurrentImages: function(id, images) {
			if(!g[id]) {
				return;
			}

			var i;
			g[id]['currentImages'] = images;
			g[id]['imageIndex'] = {};
			for (i in g[id]['currentImages']) {
				if (g[id]['currentImages'][i]['id']) {
					g[id]['imageIndex'][g[id]['currentImages'][i]['id']] = i;
				}
			}
		},

		toggleSelected: function(id) {
			if(!g[id]) {
				return;
			}

			var i;
			if (g[id]['showingCurrent'] !== false) {
				// Clean up unselected
				for (i in g[id]['selected']) {
					if (!g[id]['selected'][i]['selected']) {
						delete g[id]['selected'][i];
					}
				}
				this.setCurrentImages(id, g[id]['imageData']);
				g[id]['selectedLabel'].html('Show currently selected images');
				this.printImages(id, g[id]['showingCurrent']);
				g[id]['showingCurrent'] = false;
			} else {
				g[id]['currentImages'] = [];
				g[id]['imageIndex'] = {};
				var i;
				for (i in g[id]['selectOrder']) {
					g[id]['currentImages'][i] = g[id]['selected'][g[id]['selectOrder'][i]];
					g[id]['imageIndex'][g[id]['selectOrder'][i]] = i;
				}
				g[id]['showingCurrent'] = g[id]['currentOffset'];
				g[id]['currentOffset'] = 0;
				g[id]['selectedLabel'].html('Show filtered images');
				this.printImages(id, g[id]['showingCurrent']);
			}
		},

		// @todo Move to be a private function
		returnFunction: function(func, add) {
			var a = Array.prototype.slice.call(arguments);
			a.shift();
			a.shift();
			var t = this;
			return function () {
				if (add) {
					var b = [].concat(a);
					b = b.concat(Array.prototype.slice.call(arguments));
					func.apply(t, b);
				} else {
					func.apply(t, a);
				}
			};
		},

		clearSelected: function(id) {
			if(!g[id]) {
				return;
			}

			if (g[id]['selectOrder']) {
				var i, iId;
				
				if (g[id]['showingCurrent'] === false) {
					for (i in g[id]['selectOrder']) {
						iId = g[id]['selectOrder'][i];
						if (g[id]['imageIndex'][iId]) {
							iId = g[id]['imageIndex'][iId];
							g[id]['currentImages'][iId]['div'].removeClass('selected');
						}
					}
				}

				g[id]['selectOrder'] = [];
				g[id]['selected'] = {};

				if (g[id]['showingCurrent'] !== false) {
					this.toggleSelected(id);
				}

				g[id]['idsOnly'] = false;
				this.redisplayShortcode(id);
			}
		},

		order: function(id, i, add) {
			if(!g[id]) {
				return;
			}

			if (add !== -1 && add !== 1) {
				return;
			}
			if (g[id]['currentImages'][i]) {
				var iId = g[id]['currentImages'][i]['id'];
				var o = g[id]['selectOrder'].indexOf(iId);
				if ((add === -1 && o === 0) || (add === 1 && o === (g[id]['selectOrder'].length) - 1)) {
					return;
				}
				var oId = g[id]['selectOrder'][o + add];
				g[id]['selectOrder'][o + add] = g[id]['selectOrder'][o];
				g[id]['selectOrder'][o] = oId;
				if (g[id]['imageIndex'][oId] && g[id]['currentImages'][g[id]['imageIndex'][oId]]) {
					g[id]['currentImages'][g[id]['imageIndex'][oId]]['order'].html(o + 1);
				}
				g[id]['currentImages'][i]['order'].html(o + add + 1);
				
				this.redisplayShortcode(id);
			}
		},

		/**
		 * Retrieves shortcode data
		 */
		gatherShortcodeData: function(id) {
			if(!g[id]) {
				return;
			}

			var code = {
					code: g[id]['sctype'].val()
			};

			// Add selected ids
			if (g[id]['selectOrder'].length) {
				code['ids'] = g[id]['selectOrder'];
			}

			// Add filter
			if (!g[id]['idsOnly']) {
				// Folders
				if (g[id].folders) {
					code['folders'] = g[id].folders;
				}

				// Date
				code['start'] = g[id]['start'].val();
				code['end'] = g[id]['end'].val();

				var P = ['name', 'title', 'comment', 'tags'];
				for (p in P) {
					if ((part = g[id][P[p]].val())) {
						code[P[p]] = part;
					}
				}
			}
			
			return code;
		},

		/**
		 * Compiles the shortcode based on the information available
		 *
		 * @param id string The id of the gallery.
		 */
		compileShortcode: function(id) {
			if(!g[id]) {
				return;
			}
		
			var code = this.gatherShortcodeData(id);

			var filter = [];

			// Add selected ids
			if (code['ids']) {
				filter = code['ids'].slice(0);
			}

			// Add filter
			if (!g[id]['idsOnly']) {
				// Folders
				if (code['folders']) {
					filter.push('folder=' + code['folders'].join('|'));
				}

				// Date
				var start = code['start'];
				var end = code['end'];

				if (start || end) {
					filter.push('taken=' + (start ? start : '') + '|' + (end ? end : ''));
				}

				var part;
				var P = ['name', 'title', 'comment', 'tags'];
				for (p in P) {
					if ((part = code[P[p]])) {
						filter.push(P[p] + '=' + part);
					}
				}
			}

			return '[' + code.code + ' id="' + filter.join(',') + '"' + ']';
		},

		redisplayShortcode: function(id) {
			if(!g[id]) {
				return;
			}

			g[id]['shortcode'].html(this.compileShortcode(id));
		},

		/**
		 * Sends data back to the server to be saved
		 */
		save: function(id) {
			if(!g[id]) {
				return;
			}

			var i, v, data = {}, change = false;
			for (i in g[id]['changed']) {
				for (v in g[id]['changed'][i]) {
					if (g[id]['changed'][i][v]['new'] !== g[id]['changed'][i][v]['old']) {
						if (!data[i]) {
							data[i] = {}
						}
						data[i][v] = g[id]['changed'][i][v]['new'];
						change = true;
					}
				}
			}
			
			if (change) {
				// @todo Add localisation
				g[id]['saveButton'].html('Saving...');
				$.post(ajaxurl + '?action=gh_save', {'saveData': data}, this.returnFunction(this.confirmSave, true, id));
			}
		},

		confirmSave: function(id, data, textStatus, jqXHR) {
			if(!g[id]) {
				return;
			}

			if (!(data instanceof Object) || data.error) {
				alert(data.error);
			} else {
				// TODO Apply changes??
				g[id]['changed'] = {};
				alert(data.msg);
			}
			// @todo Add localisation
			g[id]['saveButton'].html('Save Image Changes');
			g[id]['saveButton'].addClass('disabled');
		},

		/**
		 * Selects an image for inclusion. Called when the user clicks the
		 * select button of an image.
		 *
		 * @param id string The id of the gallery.
		 * @param i int The index to the image selecting.
		 */
		select: function(id, i) {
			if(!g[id]) {
				return;
			}

			// Get image id
			if (g[id]['currentImages'][i]) {
				var iId = g[id]['currentImages'][i]['id'];
				var x;

				if (g[id]['selected'][iId] && g[id]['selected'][iId]['selected'] == true) {
					if ((x = g[id]['selectOrder'].indexOf(iId)) !== -1) {
						g[id]['selectOrder'].splice(x, 1);
					}
					g[id]['currentImages'][i]['div'].removeClass('selected');
					if (g[id]['showingCurrent'] !== false) {
						g[id]['selected'][iId]['selected'] = false;
					} else {
						delete g[id]['selected'][iId];
					}
					this.reOrder(id);
				} else {
					g[id]['selected'][iId] = g[id]['currentImages'][i];
					g[id]['selected'][iId]['selected'] = true;
					g[id]['selectOrder'].push(iId);
					g[id]['currentImages'][i]['order'].html(g[id]['selectOrder'].length);
					g[id]['currentImages'][i]['div'].addClass('selected');
					g[id]['idsOnly'] = true;
				}
				this.redisplayShortcode(id);
			}
		},

		reOrder: function(id) {
			if(!g[id]) {
				return;
			}

			var i, iId;

			for (i in g[id]['selectOrder']) {
				iId = g[id]['selectOrder'][i];
				if (g[id]['imageIndex'][iId]) {
					g[id]['currentImages'][g[id]['imageIndex'][iId]]['order'].html((i*1) + 1);
				}
			}
		},


		/**
		 * Excludes an image from inclusion. Called when the user clicks the
		 * exclude button of an image.
		 *
		 * @param id string The id of the gallery.
		 * @param i int The index to the image excluding.
		 */
		exclude: function(id, i) {
			if(!g[id]) {
				return;
			}

			// Get image id
			if (g[id]['imageData'][i]) {
				var iId = g[id]['imageData'][i]['id'];

				// Remove the disabled class from the Save button
				if (g[id]['saveButton'].hasClass('disabled')) {
					g[id]['saveButton'].removeClass('disabled');
				}

				if (!g[id]['changed'][iId]) {
					g[id]['changed'][iId] =  {};
				}

				if (!g[id]['changed'][iId]['exclude']) {
					var val = parseInt(g[id]['imageData'][i]['exclude']);
					g[id]['changed'][iId]['exclude'] = {
						'old': val,
						'new': val
					};
				}

				if (!g[id]['changed'][iId]['exclude']['new']) {
					g[id]['changed'][iId]['exclude']['new'] = 1;
					g[id]['imageData'][i].exclude = "1";
					g[id]['imageData'][i]['div'].addClass('excluded');
				} else {
					g[id]['changed'][iId]['exclude']['new'] = 0;
					g[id]['imageData'][i].exclude = "0";
					g[id]['imageData'][i]['div'].removeClass('excluded');
				}
			}
		}
	};

	return pub;
})(jQuery);

