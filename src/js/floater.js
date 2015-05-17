
(function($) {
	var groups = {};
	var options = {};
	var defaultOptions = {
		/**
		 * @prop generators {object} Object containing link types and
		 * generating functions to draw the
		 * contents of the floater. Will be given the object to draw into and
		 * the client window width and height.
		 * Called when the window is first popped up and any time the window
		 * is resized. The floater will be centered after this function has
		 * returned.
		 */
		generators: {
			image: imageDraw.bind(this),
		},
		
		/**
		 * @prop default {string} Default type of any URL links that can't be
		 * determined.
		 * Type should be 'image' or one of those in the generators option.
		 */
		default: 'image',
	
		/**
		 * @prop close {boolean} If true, a close button div will be added to the
		 * floater box.
		 */
		close: true,

		/**
		 * @prop backgroundClose {boolean} If true, clicking on the background
		 * will close the floater.
		 */
		backgroundClose: true,

		/**
		 * @prop ignorePrevented {boolean} If true, any events with the
		 * default prevented will be ignored
		 */
		ignorePrevented: true,

		/**
		 * @prop scroll {boolean} If true, scroll buttons will allow the user
		 * to scroll through the elements of that currently floated group.
		 */
		scroll: false,
		/**
		 * @prop autoscroll {integer} If not 0, will be the number of seconds
		 * each element is paused on for.
		 */
		autoscroll: 0,
	};

	function Viewer(id, element) {
		this.objects = {};
		this.options = ((id && options[id]) ? options[id] : defaultOptions);

		// Write floater
		$('body').append((this.objects.backing = $('<div class="fBacking"></div>')
				.append($('<div></div>').append((this.objects.box = $('<div></div>')
				.append((this.objects.div = $('<div></div>'))))))));

		// Add title and close
		if (this.options.close) {
			this.objects.box.prepend($('<div class="close"></div>')
					.click(this.close.bind(this)));
		}

		if (this.options.backgroundClose) {
			this.objects.backing.click(this.close.bind(this));
		}

		// Determine if there are other files
		if (this.options.scroll && id) {
			this.current = null;
			// Add scroll buttons
			if (groups[id]) {
				// Find current object in group
				var i = 0;
				while (i < groups[id].length) {
					if (groups[id][i].is(element)) {
						this.current = i;
						break;
					}
					i++;
				}

				if (this.current == null) {
					this.current = groups[id].length;
					groups[id].push(element);
				}

				this.objects.box.append((this.objects.left
						= $('<div class="left"></div>')
						.click(this.scroll.bind(this, id, -1, null)))).append(
						(this.objects.right = $('<div class="right"></div>')
						.click(this.scroll.bind(this, id, 1, null))));

				// Add autoscroll
				if (this.options.autoscroll) {
					this.objects.box.append(this.objects.autoControl
							= $('<div class="autoscroll">Pause</div>')
							.click(pause.bind(this, this.objects)));
					
					// Add autoscroll
					this.objects.autoscroll = setTimeout(autoscroll.bind(this,
							this.objects, 1), this.options.autoscroll);
				}
			}
		}

		callGenerator.call(this, element);
	}

	Viewer.prototype = {
		/**
		 * Close the floater stored in the given objects parameter
		 */
		close: function(ev) {
			if (ev) {
				if (!(ev.target === ev.currentTarget
						|| !$.contains(this.objects.box.get(0), ev.target))) {
					return;
				}
				ev.preventDefault();
			}

			// Kill myself
			this.objects.backing.remove();
			delete this;
		},

		/**
		 * Scrolls the floater objects.
		 *
		 * @param id {string} Image group identification string
		 * @param direction {1|-1} Direction to scroll the elements in
		 * @param noWrap {boolean} If not true, will go to the first element
		 *        if trying to scroll past the end of the array and the last
		 *        for past the start of the array.
		 */
		scroll: function(id, direction, noWrap, ev) {
			if (ev && ev.preventDefault) {
				ev.preventDefault();
			}

			// Find the element in
			if (!groups[id]) {
				return;
			}

			// Change current offset
			var newOffset = Math.max(0, Math.min(this.current
					+ direction, groups[id].length - 1));

			// Redraw if have a new offset
			if (newOffset != this.current) {
				this.current = newOffset;

				// Clear div
				this.objects.div.html('');

				// Call redraw
				callGenerator.call(this, groups[id][this.current]);
			}
		},

		/**
		 * Pauses/resumes the current autoscrolling
		 */
		pause: function() {
		}
	};

	/**
	 * Does the autoscroll for a Floater object
	 */
	function autoscroll() {
	}

	function callGenerator(element) {
		// @todo Draw div
		var type = this.options.default;
		if (this.options.generators[type]) {
			this.options.generators[type](element, this.objects);
		} else {
			/// @todo Error
		}
	}

	/**
	 * Function that handles the actual opening on the div
	 */
	function open(id, element, ev) {
		if (ev) {
			if (ev.defaultPrevented) {
				return;
			}
			ev.preventDefault();
		}

		if (!element) {
			if (!$(this)) {
				return false;
			}
			element = $(this);
		}

		return new Viewer(id, element);
	}

	function imageDraw() {
	}

	function initialise(opts, id, elements) {
		var defaultSet = false;
		var optGroup = false;

		// Set options for id
		if (opts && opts instanceof Object) {
			if (id) {
				var mergeOptions;
				if (options[id]) {
					mergeOptions = options[id];
				} else {
					mergeOptions = defaultOptions;
				}
				options[id] = $.extend({}, mergeOptions, opts);
			}
		}

		// Initialise each element
		if (elements) {
			elements.each(function() {
				var group;
				// Get data-group-id
				group = $(this).attr('data-group');
				if (!group && id) {
					group = id;
				}

				// Append to objects
				if (!groups[group]) {
					groups[group] = [];
				}

				groups[group].push($(this));

				// Attach click function
				$(this).click(open.bind(this, group, $(this)));
			});
		}
	}

	$.fn.extend({
		viewer: function(options, id) {
			initialise(options, id, $(this));

			return $(this);
		},
	});

	$.extend({
		viewer: {
			open: function(data, options) {
			},

			/**
			 * Set option(s) or retrieves a particular value of a specific option.
			 *
			 * @param id {string|null} Id of group of elements to change option(s) for
			 *           Set to null to change the default options (will not affect
			 *           current groups.
			 * @param option {string|object} String name of option to change/retrieve
			 *               or an object containing the new options.
			 * @param value {any} New value for option
			 */
			option: function(id, option, value) {
				if (option instanceof Object) {
					if (id) {
						options[id] = $.extend({},
								(options[id] ? options[id] : defaultOptions), option);
					} else {
						defaultOptions = $.extend({}, defaultOptions, option);
					}
				} else {
					var opts;
					if (id) {
						if (!options[id]) {
							options[id] = $.extend({}, defaultOptions);
						}
						opts = options[id];
					} else {
						opts = defaultOptions;
					}

					if (value !== undefined) {
						opts[option] = value;
						return value;
					} else {
						return opts[option];
					}
				}
			},

			clearGroup: function(id) {
				if (groups[id]) {
					delete groups[id];
				}
			}
		}
	});
})(jQuery);
