/**
 * This module contains the Javascript for the generation and function of the
 * Gallery Hierarchy image editor.
 *
 * @param obj JQueryDOMObject DOM object for the editor to be appended to
 * @param file Object Object containing the information on the file
 */
var Editor = (function() {
	function drawRow(header, contents) {
		var td, row = $('<tr></tr>');
		if (header) {
			row.append('<th>' + header + '</th>');
		}
		row.append((td = $('<td' + (!header ? ' colspan="2"' : '') + '></td>')));
		
		if (contents.constructor === Array) {
			var i;
			for (i in contents) {
				td.append(contents[i]);
			}
		} else {
			td.append(contents);
		}

		return row;
	}

	/**
	 * Confirms actions by receiving the POST response
	 */
	function confirmAction(del, data, textStatus, jqXHR) {
		if (data instanceof Object) {
			if (data.error) {
				this.status.html(data.error);
			} else {
				this.status.html(data.msg);
			}

			var t = this;

			setTimeout(function() {
				if (del) {
					// Delete me
					t.div.remove();
					delete t;
				} else {
					t.status.html('');
				}
			}, 3000);
		}
	}
	
	var Editor = function(obj, file, options) {
		this.obj = obj;
		this.file = file;
		this.options = $.extend({
			editable: true,
			fullImage: false,
			showFileTitle: true,
		}, options);

		this.draw();
	};

	Editor.prototype = {
		/**
		 * Draws the image editor
		 */
		draw: function() {
			this.parts = {}
			var div, dDiv, iDiv, img;
			
			this.obj.append((div = $('<div></div>')));
			this.div = div;
			div.addClass('gHEditor');

			if (this.options.showFileTitle) {
				div.append('<h5>' + this.file.file + '</h5>');
			}
			div.append((iDiv = $('<div></div>')));
			iDiv.append((this.img = $('<img src="'
					+ (this.options.fullImage ? full(this.file.path) 
					: thumbnail(this.file.path)) + '">')));
			div.append((this.details = $('<table></table>')));

			// Print details
			// File Name @todo Make so you can change
			// Image ID
			this.details.append(drawRow('Image ID:', this.file.id));

			// Image Dimensions
			this.details.append(drawRow('Image Dimensions:', 
					this.file.width + 'x' + this.file.height + 'px'));
			
			// Taken data
			if (this.file.taken) {
				this.details.append(drawRow('Taken:', 
						this.file.taken));
			}
	
			// @todo Change to Table
			if (this.options.metadata) {
				var f;
				for (f in this.options.metadata) {
					switch(this.options.metadata[f].type) {
						case 'longtext':
							this.details.append(drawRow(this.options.metadata[f].label + ':', 
									(this[f] = $('<textarea>'
									+ (this.file[f] ? this.file[f] : '')
									+ '</textarea>'))));
							break;
						case 'csv':
							this.details.append(drawRow(this.options.metadata[f].label
									+ ' (comma-separated):', 
									(this[f] = $('<input type="text" value="'
									+ (this.file[f] ? this.file[f] : '')
									+ '">'))));
							break;
						case 'text':
						default:
							this.details.append(drawRow(this.options.metadata[f].label + ':', 
									(this[f] = $('<input type="text" value="'
									+ (this.file[f] ? this.file[f] : '')
									+ '">'))));
							break;
					}
				}
			}
			
			// Image Gallery Exclusion
			this.details.append(drawRow('Exclude by Default:', 
					(this.exclude = $('<input type="checkbox"'
					+ ((this.file.exclude && this.file.exclude == '1') ? ' checked'
					: '') + '>'))));

			// Image Actions
			this.details.append(drawRow(null, [
				(this.saveLink = $('<a>Save</a>')
						.click(this.save.bind(this))),
				(this.removeLink = $('<a>Remove</a>')
						.click(this.remove.bind(this))),
				(this.delLink = $('<a>Delete</a>')
						.click(this.delete.bind(this))),
				(this.status = $('<span></span>'))
			]));
		},

		/**
		 * Save edited information back to server
		 */
		save: function() {
			// Build information
			var data = {};
			data[this.file.id] = {
				id: this.file.id,
				title: this.title.val(),
				comment: this.comment.val(),
				tags: this.tags.val().replace(/ *, */, ','),
				exclude: (this.exclude.attr('checked') ? 1 : 0)
			};

			if (this.options.metadata) {
				var f;
				for (f in this.options.metadata) {
					if (this[f]) {
						switch(this.options.metadata[f].type) {
							case 'csv':
								data[this.file.id][f] = this[f].val().replace(/ *, */, ',');
								break;
							default:
								data[this.file.id][f] = this[f].val();
								break;
						}
					}
				}
			}

			$.post(ajaxurl + '?action=gh_save', {a: 'save', data: data},
					confirmAction.bind(this, false));
			this.status.html('Saving...');
		},

		/**
		 * Remove the image from the database (but not from the server)
		 */
		remove: function() {
			if (confirm('Image will be removed from the gallery, but not deleted '
					+ 'from the server. Continue?')) {
				$.post(ajaxurl + '?action=gh_save', {a: 'remove', id: this.file.id},
						confirmAction.bind(this, true));
				this.status.html('Removing...');
			}
		},

		/**
		 * Remove the image from the database and delete from the server
		 */
		delete: function() {
			if (confirm('Image will be removed from the gallery and deleted from '
					+ 'the server. Continue?')) {
				$.post(ajaxurl + '?action=gh_save', {a: 'delete', id: this.file.id},
						confirmAction.bind(this, true));
				this.status.html('Deleting...');
			}
		},
	};

	return Editor;
})();

