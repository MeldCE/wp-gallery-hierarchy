
// @todo Remove
//= include floater.js

gH = (function ($) {
	var g = {},
			scanner = {},
			uploaders = {},
			hideClass = 'hide',
			imageData,
			options;

	/**
	 * Used to toggle the visibility of elements.
	 * @param id string Id of the gallery
	 * @param part string Part to toggle
	 * @param label string Label of part toggling
	 *
	 * @return boolean Whether the part is showing or not
	 */
	function toggle(el, label, hideLabel, showLabel, force) {
		var shown;

		if (el instanceof String) {
			el = $('#' + el);
		}
		if (label instanceof String) {
			label = $('#' + label);
			if (!label.has()) {
				label = false;
			}
		}

		if (el.has()) {
			shown = el.is(':visible');

			if (!(force === undefined || force === null)) {
				if ((force && shown) || (!force && !shown)) {
					return shown;
				}
			}

			if (shown) {
				el.hide();
				label.html(showLabel);
				return false;
			} else {
				el.show();
				label.html(hideLabel);
				return true;
			}
		}
	}
	
	$.viewer.option('gHBrowser', {
		generators: {
			imageEditor: displayImageEditor,
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
	function displayImage(obj, file) {
		obj.append($('<a href="' + gH.imageUrl + '/' + file.path
				+ '" title="' 
				+ (file.title ? file.title + ' (#' + file.id + ')' : '#' + file.id)
				+ '" target="_blank"><img src="'
				+ thumbnail(file.path) + '"></a>')
				.viewer(null, 'gHBrowser').data('imageData', file));
	}


	var editorOptions = {
		fullImage: true
	};

	/**
	 * Used to diplay the image editor in the floater window
	 */
	function displayImageEditor(link, objects) {
		var editor = new Editor(objects.div, link.data('imageData'),
				editorOptions);

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

	function thumbnail(image) {
		return gH.cacheUrl + '/' + image.replace(/\//g, '_');
	}

	function full(image) {
		return gH.imageUrl + '/' + image;
	}

	//= include editor.js
	//= include table.js
	//= include uploader.js
	//= include scanner.js
	//= include browser.js
	//= include gallery.js

	/**
	 * Shows hides the shortcode type options based on what shortcode type is
	 * currently selected.
	 */
	function changeVisibleOptions(id) {
		if (!g[id]) {
			return;
		}

		var t, c = g[id].sctype.val();

		for (t in g[id].options) {
			if (g[id]['options-' + t]) {
				if (c === t) {
					g[id]['options-' + t].show();
				} else {
					g[id]['options-' + t].hide();
				}
			}
		}
	}

	function arrangerParseLayout(images, tinyDiv) {
		var layout, i, id, parts;

		if (layout = tinyDiv.getSCAttr('layout')) {
			// Remove percentage maarker at start
			layout = layout.replace(/^%+/, '');
			layout = layout.split('|');

			for (i in layout) {
				// Split off the id
				parts = layout[i].split(':');
				id = parts.splice(0,1);

				if (images[id]) {
					switch (parts.length) {
						case 4:
							images[id].offset = dimStringToArray(parts[3]);
						case 3:
							images[id].scale = dimStringToArray(parts[2]);
						case 2:
							images[id].box = dimStringToArray(parts[0]);
							images[id].position = dimStringToArray(parts[1]);

							break;
						default:
							// @todo error
					}
				}
			}
		}
	}

	function dimStringToArray(dim) {
		console.log('dimtoArray called on ' + dim);
		dim = dim.split(',');
		var f;

		if (dim.length == 1) {
			return dim[0];
		} else {
			return [
				(isNaN(f = parseFloat(dim[0])) ? dim[0] : f),
				(isNaN(f = parseFloat(dim[1])) ? dim[1] : f),
			];
		}
	}

	function arrangerUpdateShortcode(tinyDiv, images) {
		console.log(images);

		// Compile layout parameter
		var i, layouts = [], layout;
		for (i in images) {
			layout = images[i].id + ':'
					// Box (size)
					+ images[i].box[0].toFixed(2) + ','
					+ images[i].box[1].toFixed(2)
					+ ':'
					// Position
					+ images[i].position[0].toFixed(2) + ','
					+ images[i].position[1].toFixed(2);

			if (images[i].scale) {
				layout += ':'
						+ images[i].scale[0].toFixed(2) + ','
						+ images[i].scale[1].toFixed(2);
			}

			if (images[i].offset) {
				layout += ':'
						+ images[i].offset[0].toFixed(2) + ','
						+ images[i].offset[1].toFixed(2);
			}

			layouts.push(layout);
		}

		layout = '%' + layouts.join('|');

		// Add to shortcode

		console.log(layout);

		tinyDiv.setSCAttr('layout', layout);
	}

	var pub = {
		init: function(opts) {
			options = opts;
			if (opts.imageUrl) this.imageUrl = opts.imageUrl;
			if (opts.cacheUrl) this.cacheUrl = opts.cacheUrl;
			if (opts.mediaUrl) this.mediaUrl = opts.mediaUrl;
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
						image: displayImage
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

		arranger: function(obj, tinyDiv, images, options) {
			var i;
			var imageObject = {};

			// Add hrefs to all the images
			for (i in images) {
				images[i].href = full(images[i].path);

				imageObject[images[i].id] = images[i];
			}

			arrangerParseLayout(imageObject, tinyDiv);

			//obj = doc.find('#' + obj);
			console.log('gh arranger called');
			console.log(arguments);
			console.log(obj.length);

			var options = {
				images: images,
				finishAction: arrangerUpdateShortcode.bind(null, tinyDiv),
				actions: [
					{ 
						label: 'Modify images/shortcode',
						func: tinyDiv.popupGallery.bind(tinyDiv)
					}
				]
			};

			var layout;

			if ((layout = tinyDiv.getSCAttr('layout')) && layout.startsWith('%')) {
				options.percent = true;
			}

			// Start arranger
			obj.arranger(options);
		},

		gallery: function(id, insertOnly, options, value) {
			if (options.metadata) {
				editorOptions.metadata = options.metadata;
			}
			new Gallery(id, options, value);
		},

		featuredEditor: function(id, options, value) {
			var input = $('#' + id);
			var button = $('#' + id + 'button');
			var el = $('#' + id + 'gallery');

			if (!options) {
				options = {};
			}

			options = $.extend(options, {
				shortcodeBuilder: false,
				filterInput: input
			});

			if (input.has() && button.has() && el.has()) {
				// Hide the gallery
				el.hide();
				button.click(toggle.bind(this, el, button, 'Hide filter editor',
						'Show filter editor', null));
				new Gallery(el, options, value);
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
	};

	return pub;
})(jQuery);

