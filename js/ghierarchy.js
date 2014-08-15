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
						'selectOrder': [], // Stores the order of selected images
						'changed': {}, // Stores any changed information to send to server
						'currentOffset': 0, // Stores the current image offset
						'currentLimit': 0,
						'showingCurrent': false, // False if not or filtered image offset
						'idsOnly': false, // True when only ids should be in shortcode
						'pad': idO,
						'folders': $('#' + id + 'folders'),
						'recurse': $('#' + id + 'recurse'),
						'start': $('#' + id + 'start'),
						'end': $('#' + id + 'end'),
						'name': $('#' + id + 'name'),
						'title': $('#' + id + 'title'),
						'comment': $('#' + id + 'comment'),
						'tags': $('#' + id + 'tags'),
						'filter': $('#' + id + 'filter'),
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
			
			this.compileShortcode(id);
		},

		/**
		 * Used to toggle the visibility of elements.
		 * @param id string Id of the gallery
		 * @param part string Part to toggle
		 * @param label string Label of part toggling
		 * @return boolean Whether the part is showing or not
		 */
		toggle: function(id, part, label, onLabel, offLabel) {
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
				$.post(ajaxurl + '?action=gh_gallery', g[id]['query'], this.returnFunction(this.receiveData, id));
				//$.post(ajaxurl + '?action=ghierarchy', {'test': 'oob'}, this.successFunction(id));
			}
		},

		toggleBuilder: function (id) {
			/// @todo Add localisation
			if (!g[id]['insertOnly']) {
				if ((g[id]['builderOn'] = this.toggle(id, 'builder', 'shortcode builder', 'Enable', 'Disable'))) {
					g[id]['pad'].addClass('builderOn');
				} else {
					g[id]['pad'].removeClass('builderOn');
				}
			} else {
				this.toggle(id, 'builder', 'shortcode');
			}
		},

		receiveData: function (id, data, textStatus, jqXHR) {
			g[id]['imageData'] = data;

			var i;
			g[id]['imageIndex'] = {};
			for (i in g[id]['imageData']) {
				g[id]['imageIndex'][g[id]['imageData'][i]['id']] = i;
			}

			g[id]['currentImages'] = g[id]['imageData'];
			this.printImages(id, 0);

			g[id]['idsOnly'] = false;
			this.compileShortcode(id);
		},

		/**
		 * Handles a change in the number images per page.
		 */
		repage: function(id) {
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

		changePage: function(id) {
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
		 * Draws the current images in the page
		 *
		 * @param id string Id of the current gallery
		 * @param offset int Offset to use
		 */
		printImages: function(id, offset) {
			if (isNaN(offset = parseInt(offset))) {
				offset = 0;
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

		toggleSelected: function(id) {
			if (g[id]['showingCurrent'] !== false) {
				g[id]['currentImages'] = g[id]['imageData'];
				g[id]['selectedLabel'].html('Show currently selected images');
				this.printImages(id, g[id]['showingCurrent']);
				g[id]['showingCurrent'] = false;
			} else {
				g[id]['currentImages'] = [];
				var i;
				for (i in g[id]['selectOrder']) {
					g[id]['currentImages'][i] = g[id]['selected'][g[id]['selectOrder'][i]];
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

		order: function(id, i, add) {
			if (add !== -1 && add !== 1) {
				return;
			}
			if (g[id]['imageData'][i]) {
				var iId = g[id]['imageData'][i]['id'];
				var o = g[id]['selectOrder'].indexOf(iId);
				if ((add === -1 && o === 0) || (add === 1 && o === (g[id]['selectOrder'].length) - 1)) {
					return;
				}
				var oId = g[id]['selectOrder'][o + add];
				g[id]['selectOrder'][o + add] = g[id]['selectOrder'][o];
				g[id]['selectOrder'][o] = oId;
				if (g[id]['imageData'][g[id]['imageIndex'][oId]]) {
					g[id]['imageData'][g[id]['imageIndex'][oId]]['order'].html(o + 1);
				}
				g[id]['imageData'][i]['order'].html(o + add + 1);
				
				this.compileShortcode(id);
			}
		},

		/**
		 * Compiles the shortcode based on the information available
		 *
		 * @param id string The id of the gallery.
		 */
		compileShortcode: function(id) {
			var code = '[' + g[id]['sctype'].val();

			var filter = []

			// Add selected ids
			if (g[id]['selectOrder'].length) {
				filter = g[id]['selectOrder'].slice(0);
			}

			// Add filter
			if (!g[id]['idsOnly']) {
				// Folders
				var folders = g[id]['folders'].val();
				if (folders) {
					filter.push('folder=' + folders.join('|'));
				}

				// Date
				var start = g[id]['start'].val();
				var end = g[id]['end'].val();

				if (start || end) {
					filter.push('taken=' + (start ? start : '') + '|' + (end ? end : ''));
				}

				var part;
				var P = ['name', 'title', 'comment', 'tags'];
				for (p in P) {
					if ((part = g[id][P[p]].val())) {
						filter.push(P[p] + '=' + part);
					}
				}
			}

			code += ' id="' + filter.join(',') + '"';

			code += ']';
			
			g[id]['shortcode'].html(code);
		},

		/**
		 * Sends data back to the server to be saved
		 */
		save: function(id) {
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
				$.post(ajaxurl + '?action=gh_save', {'saveData': data}, this.returnFunction(this.confirmSave, id));
			}
		},

		confirmSave: function(id, data, textStatus, jqXHR) {
			alert(data);
			if(data.substring( 0, 'Error'.length ) !== 'Error') {
				g[id]['changed'] = {};
			}
		},

		/**
		 * Selects an image for inclusion. Called when the user clicks the
		 * select button of an image.
		 *
		 * @param id string The id of the gallery.
		 * @param i int The index to the image selecting.
		 */
		select: function(id, i) {
			// Get image id
			if (g[id]['imageData'][i]) {
				var iId = g[id]['imageData'][i]['id'];
				var x;
				if (g[id]['selected'][iId]) {
					if ((x = g[id]['selectOrder'].indexOf(iId)) !== -1) {
						g[id]['selectOrder'].splice(x, 1);
					}
					g[id]['imageData'][i]['div'].removeClass('selected');
					g[id]['selected'][iId]['selected'] = false;
				} else {
					g[id]['selected'][iId] = g[id]['imageData'][i];
					g[id]['selected'][iId]['selected'] = true;
					g[id]['selectOrder'].push(iId);
					g[id]['imageData'][i]['order'].html(g[id]['selectOrder'].length);
					g[id]['imageData'][i]['div'].addClass('selected');
					g[id]['idsOnly'] = true;
				}
				this.compileShortcode(id);
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
