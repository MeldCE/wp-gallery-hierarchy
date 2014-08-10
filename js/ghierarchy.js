var gH = (function () {
	var g = {};
	var $ = jQuery;
	var hideClass = 'hide';
	var imageUrl;
	var cacheUrl;
	var imageData;

	return {
		gallery: function(id, iUrl, cUrl) {
			var idO;
			if ((idO = $('#' + id + 'pad'))) {
				imageUrl = iUrl;
				cacheUrl = cUrl;
				g[id] = {
						'pad': idO,
						'folders': $('#' + id + 'folders'),
						'recurse': $('#' + id + 'recurse'),
						'start': $('#' + id + 'start'),
						'end': $('#' + id + 'end'),
						'title': $('#' + id + 'title'),
						'comment': $('#' + id + 'comment'),
						'tags': $('#' + id + 'tags'),
						'filter': $('#' + id + 'filter'),
						'filterLabel': $('#' + id + 'filterLabel'),
						'builder': $('#' + id + 'builder'),
						'builder': $('#' + id + 'builderLabel'),
						'limit': $('#' + id + 'limit'),
						//'': $('#' + id + ''),
				};

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
		},

		/**
		 * Used to toggle the visibility of elements.
		 * @param id string Id of the gallery
		 * @param part string Part to toggle
		 * @param label string Label of part toggling
		 */
		toggle: function(id, part, label) {
			if (g[id] && g[id][part]) {
				if (g[id][part].hasClass(hideClass)) {
					g[id][part].removeClass(hideClass);
					g[id][part + 'Label'].val('Hide ' + label);
				} else {
					g[id][part].addClass(hideClass);
					g[id][part + 'Label'].val('Show ' + label);
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
				$.post(ajaxurl + '?action=gh_gallery', g[id]['query'], this.successFunction(id));
				//$.post(ajaxurl + '?action=ghierarchy', {'test': 'oob'}, this.successFunction(id));
			}
		},

		successFunction: function (id) {
			return function(data, textStatus, jqXHR) {
				gH.receiveData(id, data, textStatus, jqXHR);
			};
		},

		receiveData: function (id, data, textStatus, jqXHR) {
			imageData = data;

			//g[id]['pad'].html(data);
			this.printImages(id, 0);
		},

		printImages: function(id, offset) {
			var limit = parseInt(g[id]['limit'].val());

			// Wipe the pad
			g[id]['pad'].html('');

			// @todo
			if (limit === false) {
				limit = 50;
			} else if (!limit) {
				limit = imageData.length;
			}

			limit = Math.min(limit, imageData.length);
			//offset = Math.min(offset, imageData.length);

			var i;
			for(i = offset; i < limit; i++) {
				var d = $(document.createElement('div'));
				d.addClass('galleryThumb');
				var b = $(document.createElement('div'));
				b.addClass('exclude');
				var c = $(document.createElement('div'));
				c.addClass('select');
				var o = document.createElement('img');
				o.src = cacheUrl + '/' + this.thumbnail(imageData[i]['file']);
				d.append(o).append(b).append(c);
				g[id]['pad'].append(d);
			}
		},
	};
})();
