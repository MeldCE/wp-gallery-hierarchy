
// @todo Remove
//= include floater.js

gH = (function ($) {
	var g = {},
			scanner = {},
			uploaders = {},
			hideClass = 'hide',
			imageUrl,
			cacheUrl,
			imageData,
			options;

	//= include editor.js
	//= include uploader.js
	//= include scanner.js
	//= include browser.js

	$.viewer.option('gHBrowser', {
		generators: {
			imageEditor: displayImageEditor.bind(this),
		},
		scroll: true,
		default: 'imageEditor'
	});

	/**
	 * Create html to display an image into a given JQueryDOMObject
	 *
	 * @param obj JQueryDOMObject JQuery object to put the image into
	 * @param file Object Object containing information on the file
	 */
	function displayImage(id, obj, file) {
		obj.append($('<a href="' + imageUrl + '/' + file.path
				+ '" target="_blank"><img src="'
				+ this.thumbnail(file.path) + '"></a>')
				.viewer(null, 'gHBrowser').data('imageData', file));
	}

	/**
	 * Used to diplay the image editor in the floater window
	 */
	function displayImageEditor(link, objects) {
		var editor = new Editor(objects.div, link.data('imageData'), {
			fullImage: true
		});

		editor.img.load(function() {
			//calculateImageHeight.call(this);
			calculateImageHeight(objects, editor);

			window.addEventListener('resize',
					calculateImageHeight.bind(this, objects, editor));
		}.bind(this));
	}

	/**
	 * Used to resize the image in image editor in the floater so it is the
	 * maximum size possible
	 */
	function calculateImageHeight(objects, editor) {
		var oWidth = editor.img.prop('naturalWidth');
		var oHeight = editor.img.prop('naturalHeight');

		// Start with window width and height minus padding (2*15px)
		var width = window.innerWidth - 40;
		var height = window.innerHeight - 90;

		// Calculate side-by-side
		var sWidth = width - (objects.div.innerWidth() - editor.img.innerWidth());
		var sHeight = height - (objects.div.innerHeight() - editor.img.innerHeight());

		// Calculate on top
		//width -= 

		// Make image best height / width
		if (sWidth >= oWidth && sHeight >= oHeight) {
			editor.img.height(oHeight);
			editor.img.width(oWidth);
		} else if ((sWidth / oWidth) > (sHeight / oHeight)) {
			editor.img.height(sHeight);
			editor.img.width(oWidth / oHeight * sHeight);
		} else {
			editor.img.height(oHeight / oWidth * sWidth);
			editor.img.width(sWidth);
		}
	}

	function gallerySelect(gid, ids, files) {
		g[gid].selectOrder = ids;

		g[gid].idsOnly = (ids.length ? true : false);


		this.redisplayShortcode(gid);
	}

	function galleryExclude(gid, id, excluded, file) {
		// Remove the disabled class from the Save button
		if (g[gid]['saveButton'].hasClass('disabled')) {
			g[gid]['saveButton'].removeClass('disabled');
		}
		
		if (!g[gid].changed[id]) {
			g[gid].changed[id] =  {};
		}

		if (!g[gid].changed[id].exclude) {
			var val = parseInt(file.exclude);
			g[gid].changed[id].exclude = {
				'old': val,
			};
		}
		
		g[gid].changed[id].exclude.new = (excluded ? 1 : 0);
		file.exclude = (excluded ? '1' : '0');
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
			scanner.scanBtn.click(sendScanCommand.bind(this, 'rescan', null));
			dom.append(' ');
			dom.append((scanner.fullScanBtn = $('<a class="button">'
					+ 'Force Rescan of All Images' + '</a>')));
			scanner.fullScanBtn.click(sendScanCommand.bind(this, 'full', null));

			receiveScanRefresh(currentStatus);
		},

		uploader: function(id, obj, options) {
			if (id && obj) {
				uploaders[id] = {
					options: options,
					obj: obj,
					dir_id: false,
					uploadedDiv: $('#' + id + 'uploaded')
				};

				// Create browser for uploaded files
				uploaders[id].browser = new Browser(uploaders[id].uploadedDiv, {
					limit: 50,
					selection: true,
					exclusion: galleryExclude.bind(this, id),
					generators: {
						image: displayImage.bind(this, id)
					}
				});
				
				initUploader(id);
			}
		},

		setUploadDir: function(id, files) {
			if (id && uploaders[id]) {
				// Get folder (only first folder)
				if (files.constructor === Array) {
					files = files[0];
				}
			
				uploaders[id].dirId = files.id;

				// Set the folder parameter on the uploader
				uploaders[id].uploader.setOption('multipart_params', {
					dir_id: files.id
				});
			}
		},

		gallery: function(id, insertOnly) {
			var pad;
			if ((pad = $('#' + id + 'pad'))) {
				g[id] = {
						'insertOnly': insertOnly,
						'builderOn': (insertOnly ? true : false),
						'pad': pad,
						'selected': {}, // Stores the selected images
						'currentImages': null, // Stores the images displayed in pad
						'imageIndex': null, // Used to look an image in currentImages based on its id
						'selectOrder': [], // Stores the order of selected images
						'changed': {}, // Stores any changed information to send to server
						'currentOffset': 0, // Stores the current image offset
						'currentLimit': 0,
						'showingCurrent': false, // False if not or filtered image offset
						'idsOnly': false, // True when only ids should be in shortcode
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
						'includeFilter': $('#' + id + 'includeFilter'),
						'includeExcluded': $('#' + id + 'include_excluded'),
						'sort': $('#' + id + 'sort'),
						'caption': $('#' + id + 'caption'),
						'class': $('#' + id + 'class'),
						'popup_caption': $('#' + id + 'popupCaption'),
						'link': $('#' + id + 'link'),
						'size': $('#' + id + 'size'),
						'type': $('#' + id + 'type'),
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

				// Add change event watchers to fields
				g[id]['sctype'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['class'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['group'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['recurse'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['start'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['end'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['name'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['title'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['comment'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['tags'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['includeFilter'].change(pub.redisplayShortcode.bind(this, id));
				g[id]['includeExcluded'].change(pub.redisplayShortcode.bind(this, id));
				//g[id][''].change(pub.redisplayShortcode.bind(this, id));

				g[id].browser = new Browser(pad, {
					selection: gallerySelect.bind(this, id),
					orderedSelection: (true ? true : false),
					exclusion: galleryExclude.bind(this, id),
					limit: 50,
					generators: {
						image: displayImage.bind(this, id),
					}
				});

				this.redisplayShortcode(id);
			}
			
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

		full: function(image) {
			return imageUrl + '/' + image;
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
						g[id].query[p] = g[id].folders;
						changed = true;
						g[id].foldersChanged = false;
					}
				} else if (g[id][p].prop('type') == 'checkbox') {
					v = g[id][p].prop('checked');
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
				$.post(ajaxurl + '?action=gh_gallery', g[id]['query'],
						this.receiveImages.bind(this, id));
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

		receiveImages: function (id, data, textStatus, jqXHR) {
			if(!g[id]) {
				return;
			}

			if (data.error) {
				alert(data.error);
				return;
			}
			
			// Remap data
			var i, images = {};
			for (i in data) {
				images[data[i].id] = data[i];
			}

			/// @todo Add localisation
			g[id]['filterButton'].html('Filter');

			g[id].browser.displayFiles(images);

			g[id]['idsOnly'] = false;
			this.redisplayShortcode(id);
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
			if (!g[id]['idsOnly'] || g[id]['includeFilter'].attr('checked')) {
				// Folders
				if (g[id].folders.length) {
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
			
			// Check include excluded
			if (g[id].includeExcluded.attr('checked')) {
				code.include_excluded = 1;
			}


			var o, O = ['class', 'group'];
			for (o in O) {
				if ((part = g[id][O[o]].val())) {
					code[O[o]] = part;
				}
			}
		
			return code;
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

			pub.redisplayShortcode(id);
		},

		/**
		 * Compiles the shortcode based on the information available
		 *
		 * @param id string The id of the gallery.
		 * @todo Add additional options to shortcode
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
			if (!g[id]['idsOnly'] || g[id]['includeFilter'].attr('checked')) {
				// Folders
				if (code['folders']) {
					filter.push((g[id].recurse.attr('checked') ? 'r' : '')
							+ 'folder=' + code['folders'].join('|'));
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

			var others = [];
			var val, o, O = ['class', 'group', 'include_excluded'];
			for (o in O) {
				if ((val = code[O[o]])) {
					others.push(O[o] + '="' + val + '"');
				}
			}

			return '[' + code.code
					+ (filter.length ? ' id="' + filter.join(',') + '"' : '') 
					+ (others.length ? ' ' + others.join(' ') : '') + ']';
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
				$.post(ajaxurl + '?action=gh_save', {a: 'save', data: data},
						this.confirmSave.bind(this, id));
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
	};

	return pub;
})(jQuery);

