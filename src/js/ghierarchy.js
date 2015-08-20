
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
			imageEditor: displayImageEditor.bind(this),
		},
		scroll: true,
		default: 'imageEditor'
	});

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

	function thumbnail(image) {
		return cacheUrl + '/' + image.replace(/\//g, '_');
	}

	function full(image) {
		return imageUrl + '/' + image;
	}

	//= include editor.js
	//= include types.js
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

	function arrangerUpdateShortcode(images) {
		console.log(images);
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

		arranger: function(obj, images, options) {
			var i;

			// Add hrefs to all the images
			for (i in images) {
				images[i].href = full(images[i].path);
			}

			/// @todo Find a better way?

			//obj = doc.find('#' + obj);
			console.log('gh arranger called');
			console.log(arguments);
			console.log(obj.length);

			// Start arranger
			obj.arranger({
				images: images,
				finishAction: arrangerUpdateShortcode
			});
		},

		gallery: function(id, insertOnly, options, value) {
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

