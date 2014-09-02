var gH = (function () {
	var g = {};
	var $ = jQuery;
	var hideClass = 'hide';
	var imageUrl;
	var cacheUrl;
	var imageData;

	return {
		gallery: function(id, imageUrl, cacheUrl, insertOnly) {
			var idO;
			if ((idO = $('#' + id + 'pad'))) {
				g[id] = {
						'insertOnly': insertOnly,
						'builderOn': (insertOnly ? true : false),
						'imageUrl': imageUrl,
						'cacheUrl': cacheUrl,
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
						'folders': $('#' + id + 'folders'), // Field select input
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

				// Initialise folders field
				g[id]['folders'].multiselect({
						'noneSelectedText': 'Select folders',
						'selectedText': '# folders selected'
						}); // @todo .multiselectfilter();

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
			return image.replace(/\//g, '_');
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
						'recurse': false,
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
					v = g[id][p].val();
					if (g[id]['query'][p] == null && v == null) {
						continue;
					}
					if (g[id]['query'][p] == null || v == null || g[id]['query'][p].length != v.length) {
						g[id]['query'][p] = v;
						changed = true;
					}

					for (i in g[id]['query'][p]) {
						if (g[id]['query'][p][i] != v[i]) {
							g[id]['query'][p] = v;
							changed = true;
						}
					}
				} else if (g[id][p].prop('type') == 'checkbox') {
					v = g[id][p].prop('checked');
					if (g[id]['query'][p] ? v : !v) {
						g[id]['query'][p] = v;
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
				$.post(ajaxurl + '?action=gh_gallery', g[id]['query'], this.returnFunction(this.receiveData, id));
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
				l.attr('href', g[id]['imageUrl'] + '/' + g[id]['currentImages'][i]['file']);
				l.attr('data-lightbox', 'thumbs');
				l.attr('data-title', 'ID: ' + g[id]['currentImages'][i]['id'] + '. Title: ' + g[id]['currentImages'][i]['title'] + '. Comment: ' + g[id]['currentImages'][i]['comment']);
				// Image
				l.append((o = $(document.createElement('img'))));
				o.attr('src', g[id]['cacheUrl'] + '/' + this.thumbnail(g[id]['currentImages'][i]['file']));
				// Excluder
				d.append((o = $(document.createElement('div'))));
				o.click(this.returnFunction(this.exclude, id, i));
				o.addClass('exclude');
				o.attr('title', 'Exclude from galleries by default');
				// Selector
				d.append((o = $(document.createElement('div'))));
				o.addClass('select');
				o.click(this.returnFunction(this.select, id, i));
				o.attr('title', 'Include in selection');

				// Orderer
				d.append((o = $(document.createElement('div'))));
				o.addClass('orderer');
				o.append((p = $(document.createElement('div'))));
				p.addClass('dashicons dashicons-arrow-left-alt');
				p.click(this.returnFunction(this.order, id, i, -1));
				o.append((g[id]['currentImages'][i]['order'] = (p = $(document.createElement('div')))));
				p.addClass('order');
				o.append((p = $(document.createElement('div'))));
				p.addClass('dashicons dashicons-arrow-right-alt');
				p.click(this.returnFunction(this.order, id, i, 1));
				
				// Check for exclusion/selection
				if (g[id]['currentImages'][i]['exclude'] 
						&& (g[id]['currentImages'][i]['exclude'] === '1'
						|| g[id]['currentImages'][i]['exclude'] === true)) {
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
				o.click(this.returnFunction(this.printImages, id, 0));
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
				o.click(this.returnFunction(this.printImages, id, i));
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
			o.change(this.returnFunction(this.changePage, id));

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
				o.click(this.returnFunction(this.printImages, id, i));
			} else {
				o.addClass('disabled');
			}
			o.html('&rsaquo;');
			o.addClass('next-page');

			// Last page
			g[id]['pages'].append((o = $(document.createElement('a'))));
			if (offset !== lastOffset) {
				o.click(this.returnFunction(this.printImages, id, lastOffset));
			} else {
				o.addClass('disabled');
			}
			o.html('&raquo;');
			o.addClass('last-page');
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

		returnFunction: function(func) {
			var a = Array.prototype.slice.call(arguments);
			a.shift();
			var t = this;
			return function () {
				a = a.concat(Array.prototype.slice.call(arguments));
				func.apply(t, a);
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
				var folders = g[id]['folders'].val();
				if (folders) {
					code['folders'] = folders;
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
				console.log('Change ' + i);
				for (v in g[id]['changed'][i]) {
					console.log('Change value ' + v);
					if (g[id]['changed'][i][v]['new'] !== g[id]['changed'][i][v]['old']) {
						if (!data[i]) {
							console.log('Setting new value ' + g[id]['changed'][i][v]['new']);
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
				$.post(ajaxurl + '?action=gh_save', {'saveData': data}, this.returnFunction(this.confirmSave, id));
			}
		},

		confirmSave: function(id, data, textStatus, jqXHR) {
			if(!g[id]) {
				return;
			}

			alert(data);
			if(data.substring( 0, 'Error'.length ) !== 'Error') {
				g[id]['changed'] = {};
			}
			// @todo Add localisation
			g[id]['saveButton'].html('Save Image Changes');
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

				if (!g[id]['changed'][iId]) {
					g[id]['changed'][iId] =  {};
				}

				if (!g[id]['changed'][iId]['exclude']) {
					g[id]['changed'][iId]['exclude'] = {
						'old': g[id]['imageData'][i]['exclude']
					};
				}

				if (!g[id]['changed'][iId]['exclude']['new']) {
					g[id]['changed'][iId]['exclude']['new'] = true;
					g[id]['imageData'][i]['div'].addClass('excluded');
				} else {
					g[id]['changed'][iId]['exclude']['new'] = false;
					g[id]['imageData'][i]['div'].removeClass('excluded');
				}
			}
		}
	};
})();
