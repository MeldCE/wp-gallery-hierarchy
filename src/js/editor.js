/**
 * This module contains the Javascript for the generation and function of the
 * Gallery Hierarchy image editor.
 *
 * @param obj JQueryDOMObject DOM object for the editor to be appended to
 * @param file Object Object containing the information on the file
 */
var editor = (function() {
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
	
	var editor = function(obj, file, editable) {
		this.obj = obj;
		this.file = file;
		this.editable = editable;

		this.draw();
	};

	editor.prototype = {
		/**
		 * Draws the image editor
		 */
		draw: function() {
			var div, dDiv, iDiv, img;
			
			this.obj.append((div = $('<div></div>')));
			this.div = div;
			div.addClass('gHEditor');

			div.append('<h5>' + this.file.file + '</h5>');
			div.append((iDiv = $('<div></div>')));
			iDiv.append((img = $('<img src="'
					+ pub.thumbnail(this.file.path) + '">')));
			div.append((details = $('<table></table>')));

			// Print details
			// File Name @todo Make so you can change
			// Image Dimensions
			details.append(drawRow('Image Dimensions:', 
					this.file.width + 'x' + this.file.height + 'px'));
			// Image Title
			details.append(drawRow('Title:', 
					(this.title = $('<input type="text" value="'
					+ (this.file.title ? this.file.title : '')
					+ '">'))));
			// Image Comment
			details.append(drawRow('Comment:',
					(this.comment = $('<textarea>'
					+ (this.file.comment ? this.file.comment : '') + '</textarea>'))));
			// Image Tags
			details.append(drawRow('Tags (comma-separated):', 
					(this.tags = $('<input type="text" value="'
					+ (this.file.tags ? this.file.tags : '')
					+ '">'))));
			// Image Gallery Exclusion
			details.append(drawRow('Exclude by Default:', 
					(this.exclude = $('<input type="checkbox"'
					+ (this.file.exclude ? ' checked' : '') + '>'))));


			// Image Actions
			details.append(drawRow(null, [
				(this.saveLink = $('<a>Save</a>')
						.click(rFunc(this.save, this, false))),
				(this.removeLink = $('<a>Remove</a>')
						.click(rFunc(this.remove, this, false))),
				(this.delLink = $('<a>Delete</a>')
						.click(rFunc(this.delete, this, false))),
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
				tags: this.comment.val(),
				exclude: (this.exclude.attr('checked') ? 1 : 0)
			};

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

	return editor;
})();

