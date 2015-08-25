//(function($) {
	var dataTag = 'data-gh-code';
	
	/**
	 * Parse a dependency object and map the fields to each type of dependency
	 *
	 * @param dependencies {Object} Dependency object to map.
	 *
	 * @returns {Object} Object containing an array of fields for each type of
	 *          dependencies
	 */
	var logicalCommands = ['$and', '$or', '$not', '$nor'];
	function mapDependencies(dependencies, mapped) {
		if (mapped === undefined) {
			mapped = {};
		}

		var t,i;

		for (t in dependencies) {
			mapped[t] = [];

			for (i in dependencies[t]) {
				// Go into logical command and then merge
				if (i.startsWith('$')) {
					if (logicalCommands.indexOf(i) !== -1) {
						mapped = mapDependencies(dependencies[t][i], mapped);
					}
				} else {
					if (mapped[t].indexOf(i) === -1) {
						mapped[t].push(i);
					}
				}
			}
		}
		
		return mapped;
	}
	
	/**
	 * Check and action a specific or all dependency types
	 *
	 * @param id {String} String identifier of field to check dependencies of.
	 * @param field {Object} Object containing field to check dependencies of.
	 * @param type {String|false} Dependency type to check, or false to check all
	 * @param changedField {String} String identifier of field that has changed.
	 */
	function checkDependencies(id, field, type, changedField) {
		if (field.dependencies) {
			// Do visible/hide tests
			if ((!type || type === 'visible')
					&& field.dependencies.visible) {
				if (dependancyMatch.call(this, field.dependencies.visible)) {
					this[id].div.show();
				} else {
					this[id].div.hide();
				}
			}
			if (!field.dependencies.visible && (!type || type === 'hide')
					&& field.dependencies.hide) {
				if (dependancyMatch.call(this, field.dependencies.hide)) {
					this[id].div.hide();
				} else {
					this[id].div.show();
				}
			}
		}
	}

	/**
	 * Checks a dependency and returns either true or false
	 */
	function dependancyMatch(dependancy, and) {
		var i;
		var result = false;

		for (i in dependancy) {
			// Check for basic type
			if (!(dependancy[i] instanceof Object || dependancy[i] instanceof Array)) {
				result = (this[i].valueOf() === dependancy[i]);
				if ((!result && and) || (result && !and)) {
					break;
				}
			}
		}

		return result;
	}

	/**
	 * Creates the HTML for a single filter.
	 *
	 * @param el {JQueryDOMObject} Element to append the filter HTML to.fields
	 * @param value {Object} Object containing the shortcode information
	 *
	 * @returns {Object} Object containing the form elements of the filter
	 */
	function filterHTML(parentEl, value) {
		var filterObj = {};
		var filterDiv, div, el;

		parentEl.append(filterObj.div = $('<div></div>')
				.append(div = $('<div></div>')
		));

		filterObj.fields = new Table(filterObj.div, {fields: this.filterFields},
				value);

		return filterObj;
	}

	function timeToMySQL(time) {
		var date = new Date();
		var n, mysqlTime;

		date.setTime(time * 1000);

		mysqlTime = date.getFullYear() + '-';
		var parts = [date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(),
				date.getUTCMinutes()];
		var ends = ['-', ' ', ':', ''];
		
		for (n in parts) {
			if (parts[n] < 10) {
				mysqlTime += '0' + parts[n] + ends[n];
			} else {
				mysqlTime += parts[n] + ends[n];
			}
		}

		return mysqlTime;
	}

	function mergeBaseOptions(fields) {
		var f;

		for (f in fields) {
			if (fields[f].fields) {
				mergeBaseOptions.call(this, fields[f].fields);
			} else {
				fields[f].options = $.extend({}, this.baseFieldOptions, fields[f].options);
			}
		}
	}
	
	/**
	 * Creates the HTML for the shortcode builder.
	 *
	 * @param el {JQueryDOMObject} Element to append the shortcode builder HTML
	 *        fields
	 * @param value {Object} Object containing the shortcode information
	 *
	 * @returns {Object} Object containing the form elements of the filter
	 */
	function shortcodeHTML(parentEl, value) {
		var parts = {};
		var i, j;
		var shortcodeObject = {};
		var shortcodeDiv, div, el;

		parentEl.append(shortcodeObject.div = $('<div></div>'));

		/// @todo Move labels into language pack and include placeholder for field
		var fields = {
			sctype: {
				label: 'Shortcode type: ',
				//description: '',
				type: 'select',
				options: {
					values: {
						ghthumb: 'Thumbnails',
						ghalbum: 'An album',
						ghimage: 'An image',
						gharranger: 'Image arranger'
					}
				}
			},
			type: {
				label: 'Album type: ',
				type: 'select',
				options: {
				},
				dependencies: {
					visible: {
						sctype: 'ghalbum'
					}
				}
			},
			options: {
				hideable: true,
				hide: true,
				hideLabel: 'Hide shortcode options',
				showLabel: 'Show shortcode options',
				flat: true,
				fields: {
					group: {
						label: 'Image group id: ',
						type: 'text',
						//description: ''
					},
					class: {
						label: 'Classes: ',
						type: 'text',
					},
					include_excluded: {
						label: 'Include excluded images',
						type: 'bool',
						options: {
							prepend: true
						}
					},
					size: {
						label: 'Size: ',
						type: 'dimension',
						dependencies: {
							visible: {
								sctype: 'ghimage', 
							}
						}
					},
					/** @todo For captions would be better to have text replace */
					caption: {
						label: 'Image caption text: ',
						type: 'select',
						options: {
							//multiple: true,
							values: {
								'': 'Title and Comment',
								none: 'No caption',
								title: 'Title',
								comment: 'Comment',
								//date: 'Date'
							}
						}
					},
					popup_caption: {
						label: 'Popup image caption text: ',
						type: 'select',
						options: {
							//multiple: true,
							values: {
								'': 'Title and Comment',
								none: 'No caption',
								title: 'Title',
								comment: 'Comment',
								//date: 'Date'
							}
						}
					},
					defaultLink: {
						label: 'Default image link: ',
						type: 'text'
					},
					link: {
						label: 'Image links: ',
						multiple: true,
						startNone: true,
						inline: true,
						fields: {
							/** @todo Something like a textbox with a button next to to add
							 * ids or to add pages (when get to it).
							 */
							id: {
								label: 'Image ids: ',
								type: 'text', /// @todo Change to something more useful
							},
							link: {
								label: 'Link: ',
								type: 'text', /// @todo Change to something more usefule
							}
						}
					}
				}
			}
		};

		// Build the values for the shortcode
		var values = (fields.type.options.values = {});
		var descs = [];
		var typeOptions = {};
		var album;
		for (i in this.options.albums) {
			album = this.options.albums[i];

			// Add name to select
			values[i] = album.name;
			
			// Add description to description
			descs.push(album.name + ' - ' + album.description);
			
			// Add options
			if (album.options) {
				fields[i + 'Options'] = album.options;
			}
		}
		fields.type.description = descs.join('<br/>');

		// Merge base options
		mergeBaseOptions.call(this, fields);

		if (this.options.insert) {
			el = parentEl;
		} else {
			el = shortcodeObject.div;
		}
		el.append($('<div>Shortcode: </div>').append(
				shortcodeObject.shortcodeDiv = $('<span></span>')));
		
		shortcodeObject.fields = new Table(shortcodeObject.div, {
			fields: fields,
			hideable: true,
			hide: true, // @todo Make so shows when insert activated...?
			hideLabel: 'Hide shortcode builder',
			showLabel: 'Show shortcode builder'
		}, value);

		return shortcodeObject;
	}

	function submitShortcode() {
		var div;
	
		try {
			if (tinyMCEPopup && (div = tinyMCEPopup.getWindowArg('gHEditingDiv'))) {
				// Set attribute to shortcode
				div.attr(dataTag, window.encodeURIComponent(compileShortcode.call(this)));
				div.data('gHDrawn', false);
				tinyMCEPopup.editor.nodeChanged();
				tinyMCEPopup.close();

				// @todo Call to get updated html

				return;
			}
		} catch(err) {
		}

		// from wp-admin/includes/media.php +239 media_send_to_editor()
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_editor(compileShortcode.call(this));

		// Clear selection
		this.browser.clearSelection();
	}

	function deleteShortcode() {
		var div;
	
		try {
			if (tinyMCEPopup && (div = tinyMCEPopup.getWindowArg('gHEditingDiv'))) {
				// Set attribute to shortcode
				div.remove();
				tinyMCEPopup.close();

				// @todo Call to get updated html

				return;
			}
		} catch(err) {
		}
	}

	/**
	 *
	 * @param id {String} String id of changed field
	 * @param field {Object} Field Object
	 */
	function handleChange(id, field) {
		var i, browserFilter;
		// Check to see if the browser filter has changed
		//console.log(compileFilter.call(this, this.browserFilter));

		if (this.browserFilter.fields.fields.indexOf(id) !== -1) {
			this.filterRetrieved = false;
			this.filterButton.removeClass('disabled');
		}

		// Redisplay the shortcode
		redisplayShortcode.call(this);
	}

	function redisplayShortcode() {
		if (this.options.insert) {
			console.log(this.builder.fields.sctype);
			console.log(this.builder.fields.sctype.valueOf());
			if (this.builder.fields.sctype.valueOf() === 'gharranger') {
				console.log('forcing');
				this.idsMust = true;
				console.log(this.selectOrder);
				// Disable insert button if don't have any ids
				if (!this.selectOrder.length && !this.insertButton.hasClass('disabled')) {
					this.insertButton.addClass('disabled');
				} else if (this.selectOrder.length && this.insertButton.hasClass('disabled')) {
					this.insertButton.removeClass('disabled');
				}
			} else {
				this.idsMust = false;
				
				if (this.insertButton.hasClass('disabled')) {
					this.insertButton.removeClass('disabled');
				}
			}
		}

		if (this.options.shortcodeBuilder) {
			this.builder.shortcodeDiv.html(compileShortcode.call(this));
		}

		if (this.options.filterInput) {
			this.options.filterInput.val(compileFilterText.call(this,
					this.browserFilter));
		}
	}

	function compileFilter(filter) {
		var compiledFilter = {};
		var have = false;

		var folders = filter.fields.folders.valueOf();

		// Folders
		if (folders.length) {
			have = true;
			compiledFilter.folders = folders;
			
			if (filter.fields.recurse.valueOf()) {
				compiledFilter.recurse = true;
			}
		}

		// Advanced fields
		var v, f, fields = ['title', 'comments', 'tags', 'name', 'time'];
		
		for (f in fields) {
			if ((v = filter.fields[fields[f]].valueOf())) {
				have = true;
				compiledFilter[fields[f]] = v;
			}
		}
		
		if (have) {
			return compiledFilter;
		} else {
			return;
		}
	}

	function compileFilterText(table) {
		var parts = [];
		var ids;

		// IDs
		if (ids = this.browser.valueOf()) {
			parts = parts.concat(ids);
		}

		// Folders
		var folders = table.fields.folders.valueOf();
		if (folders.length)
		parts.push((table.fields.recurse.valueOf() ? 'r' : '')
				+ 'folder=' + folders.join('|'));

		// Text filters
		options = ['title', 'comments', 'tags', 'name'];

		for (i in options) {
			if (v = table.fields[options[i]].valueOf()) {
				parts.push(options[i] + '=' + v);
			}
		}
		
		var temp = new Date();
		// Date
		var date;
		if (v = table.fields.time.valueOf()) {
			// Start date
			if (v.start) {
				date = timeToMySQL(v.start);
			}

			if (v.stop) {
				date += '|' + timeToMySQL(v.stop);
			}
			
			parts.push('date=' + date);
		}
	
		if (parts.length) {
			return parts.join(',');
		}
		
		return;
	}

	function toggleBuilder() {
		if (toggle(this.builder.div, this.builderButton,
				'Disable shortcode builder', 'Enable shortcode builder', null)) {
			this.pad.addClass('builderOn');
		} else {
			this.pad.removeClass('builderOn');
		}
	}
	
	function compileShortcode() {
		var i, v;
		var shortcode = '[' + this.builder.fields.sctype;
		var options;

		console.log('idsOnly? ' + this.idsMust);

		// Add filter/selected ids
		if (this.imagesSelected || this.idsMust) {
			shortcode += ' id="' + this.selectOrder.join(',') + '"';
		} else {
			if (v = compileFilterText.call(this, this.browserFilter)) {
				shortcode += ' id="' + v + '"';
			}
		}

		// Add added filters

		// Add options
		for (i in this.builder.fields)

		var options = ['group', 'class', 'include_excluded', 'size', 'caption',
				'popup_caption'];

		// Add shortcode-type-specific options
		switch (this.builder.fields['sctype'].valueOf()) {
			case 'ghthumb':
				/// @todo Add selected thumbnail album options
				break;
			case 'ghalbum':
				options.push('type');
				break;
			case 'ghimage':
				options.push('size');
				break;
		}

		for (i in options) {
			//console.log('checking ' + options[i]);
			if (v = this.builder.fields[options[i]].valueOf()) {
				shortcode += ' ' + options[i] + '="' + v + '"';
			}
		}	


		shortcode += ']';

		return shortcode;
	}

	/**
	 * Sends data back to the server to be saved
	 */
	function saveEdits() {
		var i, v, data = {}, change = false;
		for (i in this.changed) {
			for (v in this.changed[i]) {
				if (this.changed[i][v]['new'] !== this.changed[i][v]['old']) {
					if (!data[i]) {
						data[i] = {}
					}
					data[i][v] = this.changed[i][v]['new'];
					change = true;
				}
			}
		}
		
		if (change) {
			// @todo Add localisation
			this.saveButton.html('Saving...');
			$.post(ajaxurl + '?action=gh_save', {a: 'save', data: data},
					confirmSave.bind(this));
		}
	}

	function confirmSave(data, textStatus, jqXHR) {
		if (!(data instanceof Object) || data.error) {
			alert(data.error);
		} else {
			// TODO Apply changes??
			this.changed = {};
			alert(data.msg);
		}
		// @todo Add localisation
		this.saveButton.html('Save Image Changes');
		this.saveButton.addClass('disabled');
	}
	

	function getFilteredImages() {
		if (!this.filterRetrieved) {
			var data = this.browserFilter.fields.valueOf();
			
			this.filterButton.html('Loading...');


			$.post(ajaxurl + '?action=gh_gallery', data,
					receiveImages.bind(this));
		}
	}

	function receiveImages(data, textStatus, jqXHR) {
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
			this.filterButton.html('Filter');

			this.browser.displayFiles(images);

			this.imagesSelected = false;
			this.filterRetrieved = true;
			this.filterButton.addClass('disabled');
			redisplayShortcode.call(this);
	}

	function galleryHTML(value) {
		var el;
		// Set base options for all fields
		this.baseFieldOptions = {
			change: handleChange.bind(this)
		};
		
		this.filterFields = {
			folders: {
				flat: true,
				fields: {
					folders: {
						label: 'Folder(s): ',
						type: 'select',
						options: {
							type: 'hierarchical',
							multiple: true,
							files: this.options.folders,
						}
					},
					recurse: {
						label: ' include subfolders',
						type: 'bool',
						options: {
							prepend: true
						}
					}
				}
			},
			advanced: {
				flat: true,
				hideable: true,
				hide: true,
				hideLabel: 'Hide advanced filtering',
				showLabel: 'Show advanced filtering',
				fields: {
					title: {
						label: 'Title contains: ',
						type: 'text',
					},
					comments: {
						label: 'Comments contain: ',
						type: 'text'
					},
					tags: {
						label: 'Tags contain: ',
						type: 'text'
					},
					time: {
						label: 'Photos taken between: ',
						type: 'datetime',
						options: {
							type: 'datetimerange'
						}
					},
					name: {
						label: 'Filename contains: ',
						type: 'text'
					},
				}
			}
		};

		// Merge in base options
		mergeBaseOptions.call(this, this.filterFields);

		// Browser filter
		this.browserFilter = filterHTML.call(this, this.el, value);

		// Add filter buttons
		this.el.append(el = $('<div></div>').append(this.filterButton
				= $('<a class="button">Filter</a>')
				.click(getFilteredImages.bind(this))));

		if (this.options.shortcodeBuilder) {
			if (!this.options.insert) {
				// Shortcode builder
				this.el.append($('<div></div>').append(this.builderButton = $('<a>'
						+ 'Enable shortcode builder' + '</a>')));
			}

			this.builder = shortcodeHTML.call(this, this.el, value);

			if (!this.options.insert) {
				this.builder.div.hide();

				// Add toggle to link
				this.builderButton.click(toggleBuilder.bind(this));
			}

			// Create shortcode specific options
			var t, code;
			for (t in this.options.shortcodes) {
				code = this.options.shortcodes[t];

				// Add shortcode to type chooser
				this.builder.type.append('<option value="' + t + '">'
						+ code.label + '</option>');

				// Add options
				this.builder.div.append(this.builder.types[t] = $('<div></div>'))
						.append('<div></div>');
			}
		}

		/// @tood Redo exlusions button and shortcode attribute
		this.el.append(this.saveButton = $('<a class="button disabled">'
				+ 'Save image exclusions' + '</a>')
				.click(saveEdits.bind(this)));

		// Add insert html
		if (this.options.insert) {
			this.el.append(el = $('<div></div>')
					.append(this.insertButton = $('<a class="button">'
					+ (this.options.update ? 'Update shortcode' : 'Insert shortcode')
					+ '</a>'))
					.click(submitShortcode.bind(this)));
			if (this.options.update) {
				el.append(' ')
						.append(this.deleteButton = $('<a class="button">'
						+ 'Delete shortcode' + '</a>'))
						.click(deleteShortcode.bind(this));
			}
		}

		this.el.append(this.pad = $('<div></div>'));

		// Add insert html
		if (this.options.insert) {
			this.pad.addClass('builderOn');
		}

		// Create browser
		this.browser = new Browser(this.pad, {
			selection: gallerySelect.bind(this),
			orderedSelection: (true ? true : false),
			exclusion: galleryExclude.bind(this),
			limit: 50,
			generators: {
				image: displayImage.bind(this),
			}
		});

		// Load the filter if we have images
		var filter;
		if ((filter = compileFilter(this.browserFilter)) || (value && value.ids)) {
			this.filterButton.html('Loading...');
			// Get images
			$.post(ajaxurl + '?action=gh_gallery', (filter || {ids: value.ids}),
					loadSelectedImages.bind(this, filter, value));
		}

		if (this.options.shortcodeBuilder) {
			redisplayShortcode.call(this);
		}
	}

	/**
	 * Loads pre-selected images and changes the browser to selected images
	 */
	function loadSelectedImages(filter, value, data, textStatus, jqXHR) {
		if (data.error) {
			alert(data.error);
			return;
		}

		/// @todo Add localisation
		this.filterButton.html('Filter');

		// Add images
		this.browser.displayFiles(data);

		// Select
		if (value.ids) {
			this.browser.select(value.ids);
		}

		if (!filter && value.ids) {
			this.browser.showSelected(true);
		}
		
		redisplayShortcode.call(this);
	}

	/**
	 * Create html to display an image into a given JQueryDOMObject
	 *
	 * @param obj JQueryDOMObject JQuery object to put the image into
	 * @param file Object Object containing information on the file
	 */
	/*function displayImage(obj, file) {
		obj.append($('<a href="' + imageUrl + '/' + file.path
				+ '" target="_blank"><img src="'
				+ thumbnail(file.path) + '"></a>')
				.viewer(null, 'gHBrowser').data('imageData', file));
	}*/
	
	function gallerySelect(ids, files) {
		this.selectOrder = ids;

		this.imagesSelected = (ids.length ? true : false);


		redisplayShortcode.call(this);
	}

	function galleryExclude(id, excluded, file) {
		// Remove the disabled class from the Save button
		if (this.saveButton.hasClass('disabled')) {
			this.saveButton.removeClass('disabled');
		}
		
		if (!this.changed[id]) {
			this.changed[id] =  {};
		}

		if (!this.changed[id].exclude) {
			var val = parseInt(file.exclude);
			this.changed[id].exclude = {
				'old': val,
			};
		}
		
		this.changed[id].exclude.new = (excluded ? 1 : 0);
		file.exclude = (excluded ? '1' : '0');
	}

	function albumDescriptionHTML(el) {
		var a;

		for (a in this.options.albums) {
			el.append('<p>' + this.options.albums[a].name + ' - '
					+ this.options.albums[a].description + '</p>');
		}
	}

	/**
	 * 
	 *
	 * @param el {String|JQueryDOMObject} String identifier or JQuery DOM Object
	 *        of HTML element to place gallery into.
	 * @param options {Object} Object containing options.
	 */
	function Gallery(el, options, value) {
		if (!el) { /// @todo Better element test
			return;
		}

		if (typeof el === 'string') {
			el = $('#' + el);
		}

		if (el.has && el.length === 1) {
			this.el = el;
			this.options = $.extend({
				shortcodeBuilder: true
			}, options);
			this.selectOrder = [];
			this.filters = [];
			this.changed = {};
			this.idsMust = false;

			if (this.options.update) {
				this.options.insert = true;
			}

			/*/ Extract current values from fields
			if (this.options.filterInput) {
				var filter = 
			}*/

			galleryHTML.call(this, value);
		} else {
			return false;
		}
	}
//})(jQuery);
