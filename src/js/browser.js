var Browser = (function($) {
	//var options;
	//var doms = {};

	/**
	 * Handles a change in the number images per page. Hooked onto the onChange
	 * event for the limit input field.
	 */
	function repage() {
		if (this.options.limit !== filesPerPage.call(this)) {
			printFiles.call(this);
		}
	}

	/**
	 * Repages the current images when the images per page is changed. Hooked
	 * onto the click events for the next/prev and page links and for the
	 * onChange manual page input field.
	 *
	 * @param step Integer Number of pages to step by. Can be +ve or -ve
	 * @param page Integer Page to skip to (will be ignored if step != 0)
	 */
	function changePage(step, page) {
		// Get value
		var page;
		var currentLimit = filesPerPage.call(this);
		var currentPage = Math.floor(this.currentOffset / currentLimit) + 1;
		var maxPage = Math.floor(len(this.currentFiles) / currentLimit) + 1;

		if (step) {
			page = Math.max(1, currentPage + step);
		} else if (page === -1) {
			page = maxPage;
		}

		if (!page) {
			if (isNaN(page = parseInt(this.doms.pageNumber.val()))) {
				page = currentPage;
				this.doms.pageNumber.val(page);
			}
		}

		page = Math.max(1, Math.min(page, maxPage));

		this.doms.pageNumber.val(page);
		
		
		if (page != currentPage) {
			page = (page - 1) * currentLimit;
			printFiles.call(this, page);
		}
	}

	function filesPerPage(limit) {
		if (this.doms.limit === undefined) {
			return null;
		}

		if (limit !== undefined && !isNaN(limit)) {
			// Set new value
			this.doms.limit.val(limit);
		} else {
			// Get current value or use default
			limit = parseInt(this.doms.limit.val());

			if (isNaN(limit)) {
				this.doms.limit.val(this.options.limit);
				return this.options.limit;
			}
		}

		return limit;
	}

	/**
	 * Draws the current files in the page
	 *
	 * @param id string Id of the current gallery
	 */
	function printFiles(offset) {
		// Stop if we have no images
		if (!this.currentFiles) {
			return;
		}
	
		if (offset !== null && offset !== undefined) {
			this.currentOffset = Math.max(0,
					Math.min(offset, len(this.currentFiles)));
		}

		// Wipe the pad
		this.doms.pad.html('');

		// Clear fileDoms and shownFiles
		this.fileDoms = {};
		this.shownFiles = {};

		var currentLimit = filesPerPage.call(this);
		var end = Math.min(this.currentOffset + currentLimit,
				len(this.currentFiles));

		var maxOffsets = Math.floor(len(this.currentFiles) / currentLimit);
		var lastOffset = maxOffsets * currentLimit;

		// Displaying number
		this.doms.displaying.html(len(this.currentFiles)
				+ (len(this.currentFiles) > 1 ? ' items' : ' item'));
			
		// Disable First/prev page
		if (this.currentOffset) {
			this.doms.firstPage.removeClass('disabled');
			this.doms.prevPage.removeClass('disabled');
		} else {
			this.doms.firstPage.addClass('disabled');
			this.doms.prevPage.addClass('disabled');
		}

		// Current page input and total
		this.doms.pageNumber.val(Math.ceil(this.currentOffset / currentLimit) + 1);
		this.doms.totalPages.html(maxOffsets + 1);

		// Disable next/last page
		if (this.currentOffset !== lastOffset) {
			this.doms.nextPage.removeClass('disabled');
			this.doms.lastPage.removeClass('disabled');
		} else {
			this.doms.nextPage.addClass('disabled');
			this.doms.lastPage.addClass('disabled');
		}


		// Start traversal
		traverse.call(this, this.doms.pad, this.currentFiles, this.currentOffset, end);
	}

	/**
	 * Traverses the files and prints
	 *
	 * @param obj JQueryDOMObject Object to append the files to
	 * @param files Object Object containing the files to be printed
	 * @param offset Integer Current offset. Will be checked to see if should
	 *               finish printing
	 *
	 * @returns Integer The new offset
	 */
	function traverse(obj, files, start, end, offset) {
		var f, div;

		if (!offset) {
			offset = 0;
		}

		for (f in files) {

			// Set default type
			if (files[f].type === undefined) {
				files[f].type = this.options.defaultType;
			}

			//if (files[f].type == 'folder') {
			if (files[f].files !== undefined) {
				if (offset >= start) {
					this.fileDoms[f] = {};
					// Create a folder div
					obj.prepend($('<div class="folder"></div>')
							.append('<div>' + files[f].name + '</div>')
							.append((this.fileDoms[f].div = $('<div></div>'))));

					// Call traverse
					if (files[f].files) {
						traverse(this.fileDoms[f].div, files[f].files, start, end, offset);
					}
				}

				offset++;
			} else if (this.options.generators[files[f].type]) {
				if (offset >= start) {
					this.fileDoms[f] = {};
					this.shownFiles[f] = files[f];
					obj.append((this.fileDoms[f].div = $('<div class="file"></div>')));

					if (this.options.selection) {
						this.fileDoms[f].div.append($('<div'
								+ (this.options.selectionClass ? ' class="'
								+ this.options.selectionClass + '"' : '') + '>').data(files[f])
								.click(select.bind(this, f, files[f])));

						// Select if selected
						if (this.selected[f]) {
							this.fileDoms[f].div.addClass(this.options.selectedClass);
						}

						if (this.options.orderedSelection) {
							this.fileDoms[f].div.append($('<div'
									+ (this.options.orderClass ? ' class="'
									+ this.options.orderClass + '"' : '') + '>').data(files[f])
									.append($('<div class="up"></div>').
									click(changeOrder.bind(this, f, -1)))
									.append((
											this.fileDoms[f].order = $('<div class="order"></div>').
									click(promptOrder.bind(this, f))))
									.append($('<div class="down"></div>').
									click(changeOrder.bind(this, f, 1))));
						
							if (this.selected[f]) {
								this.fileDoms[f].order.html(this.selectOrder.indexOf(f) + 1);
							}
							//p.addClass('dashicons dashicons-arrow-left-alt');
							//p.addClass('dashicons dashicons-arrow-right-alt');
						}
					}

					if (this.options.exclusion) {
						this.fileDoms[f].div.append($('<div'
								+ (this.options.exclusionClass ? ' class="'
								+ this.options.exclusionClass + '"' : '') + '>').data(files[f])
								.click(exclude.bind(this, f, files, undefined)));

						// Select if selected
						if (this.selected[f]) {
							this.fileDoms[f].div.addClass(this.options.selectedClass);
						}

						if (files[f].excluded) {
							obj.addClass(this.options.excludedClass);
						}
					}

					this.options.generators[files[f].type](this.fileDoms[f].div, files[f]);
				}

				offset++;
			}

			if (offset >= end) {
				return offset;
			}
		}
		
		return offset;
	}

	function reorder() {
		var i, id;

		for (i in this.selectOrder) {
			id = this.selectOrder[i];
			if (this.fileDoms[id]) {
				this.fileDoms[id].order.html((i*1) + 1);
			}
		}
	}

	function select(id, file) {
		var x;

		if (this.selected[id]) {
			if ((x = this.selectOrder.indexOf(id)) !== -1) {
				this.selectOrder.splice(x, 1);
			}
			this.fileDoms[id].div.removeClass(this.options.selectedClass);
			if (this.showingCurrent !== false) {
				this.fileDoms[id].selected = false;
			} else {
				delete this.selected[id];
			}
			reorder.call(this);
		} else {
			this.selected[id] = file;
			this.selectOrder.push(id);
			this.fileDoms[id].order.html(this.selectOrder.length);
			this.fileDoms[id].div.addClass(this.options.selectedClass);
			this.fileDoms[id].selected = true;
		}
		
		this.options.selection(this.selectOrder, this.selected);
	}

	/**
	 * Toogle/set/unset exclusion of a single file
	 *
	 * @param id {} Id of the file to change the exclusion on
	 * @param file Object Object of the file to change the exclusion on
	 * @param exclude {boolean|undefined} Force exclusion
	 */
	function exclude(id, file, exclude) {
		if (this.fileDoms[id].div.hasClass(this.options.excludedClass)) {
			this.fileDoms[id].div.removeClass(this.options.excludedClass);
			excluded = false;
		} else {
			this.fileDoms[id].div.addClass(this.options.excludedClass);
			excluded = true;
		}
		
		this.options.exclusion(id, excluded, file);
	}

	/**
	 * Bulk exclude/un-exclude currently selected files
	 *
	 * @param exclude {boolean|undefined} If true, selected will be excluded
	 */
	function excludeSelected(exclude) {
		var f;

		for (f in this.selected) {
			exclude.call(this, f, this.selected[f], exclude);
		}
	}

	function changeOrder(id, direction) {
		var oldPosition = this.selectOrder.indexOf(id);

		var position = Math.max(0, Math.min(oldPosition + direction,
					this.selectOrder.length - 1));

		if (oldPosition == -1 || position == oldPosition) {
			return;
		}

		this.selectOrder.splice(oldPosition, 1);
		this.selectOrder.splice(position, 0, id);

		reorder.call(this);

		this.options.selection(this.selectOrder, this.selected);
	}

	function promptOrder(id) {
		var position;
		if ((position = prompt('Enter a new position for the file'))
				!== undefined) {
			// Limit position
			position = Math.max(0, Math.min(position -1,
					this.selectOrder.length - 1));

			var oldPosition = this.selectOrder.indexOf(id);

			if (oldPosition == -1 || position == oldPosition) {
				return;
			}

			this.selectOrder.splice(oldPosition, 1);
			this.selectOrder.splice(position, 0, id);

			reorder.call(this);
		}
	}

	function setCurrentImages(images) {
		var i;
		this.currentFiles = images;
		this.imageIndex = {};
		for (i in this.currentFiles) {
			if (this.currentFiles[i]['id']) {
				this.imageIndex[this.currentFiles[i]['id']] = i;
			}
		}
	}

	function toggleSelected(noPrint) {
		var i;
		if (this.showingCurrent !== false) {
			// Clean up unselected
			for (i in this.selected) {
				if (this.selectOrder.indexOf(i) === -1) {
					delete this.selected[i];
				}
			}
			this.currentFiles = this.displayFiles;
			this.currentOffset = this.showingCurrent;
			this.doms.showSelected.html('Show selected');
			this.showingCurrent = false;
		} else {
			this.displayFiles = this.currentFiles;
			this.currentFiles = {};
			//this.imageIndex = {};
			var i;
			for (i in this.selectOrder) {
				this.currentFiles[this.selectOrder[i]] = this.selected[this.selectOrder[i]];
				//this.imageIndex[this.selectOrder[i]] = i;
			}
			this.showingCurrent = this.currentOffset;
			this.currentOffset = 0;
			this.doms.showSelected.html('Hide Selected');
		}

		if (!noPrint) {
			printFiles.call(this);
		}
	}

	function rebuildIndexes() {
		// Count the number of files excluding folders
	}

	function mergeFiles(newFiles, files) {
		if (!this.currentFiles) {
			this.currentFiles = newFiles;
			return;
		}
		if (!files) {
			files = this.currentFiles;
		}

		var f;
		for (f in newFiles) {
			if (!files[f]) {
				files[f] = newFiles[f];
			} else {
				// Quick conflict check
				if (files[f].type != newFiles[f].type) {
					// Error?
				}
				
				if (newFiles[f].files) {
					if (!files[f].files) {
						files[f].files = newFiles[f].files;
					} else {
						mergeFiles(newFiles[f].files, files[f].files);
					}
				}
			}
		}
	}

	function len(obj) {
		if (!obj) {
			return null;
		}
		if (obj instanceof Array) {
			return obj.length;
		}

		if (obj instanceof Object) {
			return Object.keys(obj).length;
		}
	}

	/**
	 * Change the currently selected
	 *
	 * @param selection {-1|0|1} Select all (1), none (0), or invert (-1)
	 * @param page {boolean} Whether to only select on current page.
	 */
	function changeSelection(selection, page) {
		var i, f, files;
		if (page) {
			files = this.shownFiles;
		} else {
			files = this.currentFiles;
		}

		switch (selection) {
			case 1: // All
				for (f in files) {
					if (!this.selected[f]) {
						this.selected[f] = files[f];
						this.selectOrder.push(f);
						if (this.fileDoms[f])
								this.fileDoms[f].div.addClass(this.options.selectedClass);
					}
				}
				break;
			case 0: // None
				for (f in files) {
					if ((i = this.selectOrder.indexOf(f)) !== -1) {
						this.selectOrder.splice(i, 1);
						delete this.selected[f];
						if (this.fileDoms[f])
								this.fileDoms[f].div.removeClass(this.options.selectedClass);
					}
				}
				break;
			case -1: // Invert Selection
				for (f in files) {
					if ((i = this.selectOrder.indexOf(f)) !== -1) {
						this.selectOrder.splice(i, 1);
						delete this.selected[f];
						if (this.fileDoms[f])
								this.fileDoms[f].div.removeClass(this.options.selectedClass);
					} else {
						this.selected[f] = files[f];
						this.selectOrder.push(f);
						if (this.fileDoms[f])
								this.fileDoms[f].div.addClass(this.options.selectedClass);
					}
				}
				break;
		}
	}

	function clearSelection() {
		if (!this.showingCurrent
				|| confirm('Are you sure? Entire current selection will be cleared')) {
			// Clear selected class
			var d;
			for (d in this.fileDoms) {
				this.fileDoms[d].div.removeClass(this.options.selectedClass);
			}

			// Clear stores
			this.selected = {};
			this.selectOrder = [];
		}
	}

	/**
	 * Prototype for creating a image browser
	 *
	 * @param obj JQueryDOMObject DOM object to append the browser to.
	 * @param options Object Object containing the options to create the browser
	 *             with.
	 * @param files Object Object containing the files to preload into the
	 *               browser.
	 *
	 * The files object should be in the format
	 * {
	 *   'id': {
	 *     file: '',
	 *     type: <'image'|'folder'|'archive'>,
	 *     files: {
	 *       ...
	 *     }
	 *   },
	 *   ...
	 * }
	 *
	 * The object for each file will be linked to the object that the events are
	 * hooked to.
	 *
	 */  
	function Viewer(obj, options, files) {
		this.doms = {};
		/// Stores the JQueryDOMObjects of the currently shown files
		this.fileDoms = {};
		/// Stores the currently shown files (excluding folders)
		this.shownFiles = {};
		this.selected = {};
		this.selectOrder = [];
		this.currentFiles = {};
		this.currentOffset = 0;
		this.showingCurrent = false;

		this.options = $.extend({
			/** Remember selected images from previous
			 *  groups of images.
			 */
			rememberSelection: true, 
			/**
			 * Class to add to the browser div
			 */
			class: 'gHBrowser',
			/**
			 * Whether or not to create a new div inside the given object for the
			 * browser
			 */
			cleanDiv: false,
			/**
			 * Function to be run when selection is changed
			 */
			selection: null,
			/**
			 * Class to add to the select button div
			 */
			selectionClass: 'select',
			/**
			 * Class to add to selected files
			 */
			selectedClass: 'selected',
			/**
			 * If true, selection will be ordered
			 */
			orderedSelection: false,
			/**
			 * Class to add to the order div
			 */
			orderClass: 'orderer',
			/**
			 * Function to be run when exclusion is changed
			 */
			exclusion: null,
			/**
			 * Class to add to the exclude button div
			 */
			exclusionClass: 'exclude',
			/**
			 * Class to add to excluded files
			 */
			excludedClass: 'excluded',
			/**
			 * Default number of files to show per page
			 */
			limit: 50,
			/**
			 * Functions to use for printing files of different types.
			 * Function will be passed the JQuery DOM object to append the file to
			 * and the object containing the information on the file to print.
			 */
			generators: {
			},
			/**
			 * File type to use if the file does not have a specified type (used
			 * to call the correct html generator for the file.
			 */
			defaultType: 'image',
			/**
			 * @todo Use ordering to order the files - Could be done in PHP
			 */
		}, options);

		if (this.options.cleanDiv) {
			obj.append((this.doms.obj = $('<div></div>')));
		} else {
			this.doms.obj = obj;
		}

		if (this.options.orderedSelection) {
			this.doms.obj.addClass('ordered');
		}

		if (this.options.class) {
			this.doms.obj.addClass(this.options.class);
		}

		var select, action;

		var i = Math.random().toString(36).substr(2, 9);
		var r = Math.random().toString(36).substr(2, 9);
		// Draw parts
		this.doms.obj
				// Draw pagination etc
				.append($('<div class="tablenav"></div>')
						.append('<label for="' + i + '">Images per page:</label>')
						.append((this.doms.limit = $('<input type="number"  id="'
								+ i + '">').change(repage.bind(this))))
						.append(select = $('<div class="drop">Select</div>')
								.append($('<ul></ul>')
										.append($('<li></li>')
												.append($('<a>all</a>')
														.click(changeSelection.bind(this, 1, true)))
												.append(' / ')
												.append($('<a>none</a>')
														.click(changeSelection.bind(this, 0, true)))
												.append(' on this page / ')
												.append($('<a>invert page selection</a>')
														.click(changeSelection.bind(this, -1, true)))
										)
										.append($('<li></li>')
												.append($('<a>all</a>')
														.click(changeSelection.bind(this, 1, false)))
												.append(' / ')
												.append($('<a>none</a>')
														.click(changeSelection.bind(this, 0, false)))
												.append(' / ')
												.append($('<a>invert</a>')
														.click(changeSelection.bind(this, -1, false)))
										)
								)
						)
						.append($('<div class="drop">With selected</div>').append(
								(actions = $('<ul></ul>'))))
						.append((this.doms.showSelected = $('<span>Show selected</span>')
								.click(toggleSelected.bind(this, false))))
						.append($('<span></span>')
								.append($('<input type="checkbox" id="' + r + '"'
										+ (this.options.rememberSelection ? ' checked' : '')
										+ '>').change((function() {
											this.options.rememberSelection =
													($(this).attr('checked') ? true : false);
										}).bind(this)))
								.append('<label for="' + r + '">Remember selection</label>'))
						.append((this.doms.pages
								= $('<span class="tablenav-pages"></span>')))
				)
				.append((this.doms.pad = $('<div class="browser"></div>')));

		// Validate limit
		if (isNaN(this.options.limit)) {
			this.options.limit = 50;
		}

		filesPerPage.call(this, this.options.limit);

		// Add actions
		// Add exclusion
		actions.append($('<li>Set exclusion</li>')
				.click(excludeSelected.bind(this, true)));
		actions.append($('<li>Unset exclusion</li>')
				.click(excludeSelected.bind(this, false)));
		// Add custom actions
		if (this.options.actions) {
			var a;
			for (a in this.options.actions) {
				actions.append($('<li>' + a + '</li>')
						.click(this.options.actions[a]));
			}
		}

		// Displaying number
		this.doms.pages.append((this.doms.displaying = $('<span '
				+ 'class="displaying-num"></span>')));
			
			
		// First page
		this.doms.pages.append((this.doms.firstPage = $('<a class="first-page">'
				+ '&laquo;</a>').click(changePage.bind(this, null, 1))));

		// Prev page
		this.doms.pages.append((this.doms.prevPage = $('<a class="prev-page">'
				+ '&lsaquo;</a>').click(changePage.bind(this, -1, null))));

		// Current page input and total
		this.doms.pages.append((this.doms.pageNumber = $('<input type="number" '
				+ 'class="current-page" title="Current Page">')
				.change(changePage.bind(this))))
				.append(' of ')
				.append((this.doms.totalPages = $('<span class="total-pages">'
						+ '</span>')));

		// Next page
		this.doms.pages.append((this.doms.nextPage = $('<a class="next-page">'
				+ '&rsaquo;</a>').click(changePage.bind(this, 1))));

		// Last page
		this.doms.pages.append((this.doms.lastPage = $('<a class="last-page">'
				+ '&raquo;</a>').click(changePage.bind(this, null, -1))));
		
		
		// TODO Add insert class if enabled
		if (this.options.insert) {
			this.doms.pad.addClass('builderOn');
		}

		// Hide if don't have images
		if (!files) {
			this.doms.obj.hide();
		} else {
			this.currentFiles = files;
			printFiles.call(this, 0);
		}
	}

	Viewer.prototype = {
		/**
		 * Remove file(s) from the stored file(s)
		 * @param fileIds string/array Id(s) of file(s) to remove.
		 */
		removeFile: function(fileIds) {
		},

		/**
		 * Display the given files. If currently showing selected files, will
		 * switch to showing the stored images.
		 * @param files {Object} Files to display @see Viewer for format of object
		 * @param append {boolean} If true, append images to the currently displayed
		 *               images
		 * @todo What to do if receive a null for files...?
		 */
		displayFiles: function(files, append) {
			// Show div
			this.doms.obj.show();

			if (this.showingCurrent !== false) {
				toggleSelected.call(this, true);
			}

			if (append) {
				mergeFiles.call(this, files);
				printFiles.call(this, 0);
			} else {
				this.currentFiles = files;
			
				// Reset current offset
				printFiles.call(this, 0);
			}
		},
	}

	return Viewer;
})(jQuery)
