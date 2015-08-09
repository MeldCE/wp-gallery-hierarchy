var Types = {};

var typeOptions = {
	/** @prop {bool} optional If true, a value will not be required.
	 */
	optional: false,
	/** @prop {bool} oneshot If true, a value, once assigned, will not be able to be
	 *        changed.
	 */
	oneshot: false,
	/** @prop {bool} prepend Set to true to prepend the input elements to the
	 *        front of the element given (default is to append)
	 */
	prepend: false
};

/**
 * Function to define a new type.
 * New types should bind the function this.change to the onchange event for
 * each input.
 *
 * @param {String} name String identifier of the new type
 * @param {Object} prototype Object containing the prototype functions and
 *        variables for the new type
 * @param {String} baseType String identifier of the type to base the new type
 *        off - the prototype of this type will be the base for the new type.
 */
function newType(name, prototype, baseType) {
	var aliases;
	if (name instanceof Array) {
		aliases = name;
		name = aliases.unshift();
	}

	if (Types[name]) {
		//throw new 
		return;
	}

	Types[name] = function(el, value, options) {
		/* Don't properly initiate if we don't have an element (probably another
		 * type basing itself on this one
		 */
		if (el) { /// @todo Add tests for JQueryDOMElement
			// Build options from base options
			this.options = $.extend({}, typeOptions, options);

			if (el !== true) { ///xxx && this.html && this.html.call) {
				this.html.call(this, el, value);
				this.drawn = true;
			}
		}
	};


	if (baseType && Types[baseType]) {
		Types[name].prototype = $.extend(new Types[baseType], prototype);
	} else {
		Types[name].prototype = prototype;
	}

	Types[name].prototype.change = function(ev) {
		// Run any user defined functions
		if (this.options.change) {
			this.options.change(ev, this.valueOf());
		}
	};

	Types[name].prototype.draw = function(el, value) {
		if (el && !this.drawn) {
			this.html.call(this, el, value);
			this.drawn = true;
		}
	};

	// Create aliases
	if (aliases) {
		var i;
		for (i in aliases) {
			if (!Types[aliases[i]]) {
				Types[aliases[i]] = Types[name];
			}
		}
	}
}

newType('text', {
	html: function(el, value) {
		var prop;
		if (this.options.prepend) {
			prop = 'prepend';
		} else {
			prop = 'append';
		}

		el[prop]((this.input = $('<input' + (value ? ' value="' + value
				+ '"' : '') + '>')
				.bind('change', this.change.bind(this))));
	},
	valueOf: function() {
		return this.input.val();
	}
});

newType(['bool', 'boolean'], {
	html: function(el, value) {
		var prop;
		if (this.options.prepend) {
			prop = 'prepend';
		} else {
			prop = 'append';
		}

		el[prop]((this.input = $('<input type="checkbox"'
				+ (value ? ' checked' : '') + '>')
				.bind('change', this.change.bind(this))));
	},
	valueOf: function() {
		return (this.input.attr('checked') ? true : false);
	}
});

newType('datetime', {
	html: function(el, value) {
		var prop;
		if (this.options.prepend) {
			prop = 'prepend';
		} else {
			prop = 'append';
		}

		switch (this.options.type) {
			case 'time':
				el[prop](this.input = $('<input type="time">')
						.bind('change', this.change.bind(this)));
				// Use jquery timeselect if time not a valid input type
				if (this.input.attr('type') !== 'time') {
					this.input.timepicker();
				}
				break;
			case 'datetime':
				el[prop](this.input = $('<input type="datetime">')
						.bind('change', this.change.bind(this)));
				// Use jquery timeselect if time not a valid input type
				if (this.input.attr('type') !== 'datetime') {
					this.input.datetimepicker();
				}
				break;
			case 'daterange':
				el[prop](this.start = $('<input type="date">')
						.bind('change', this.change.bind(this)), ' to ',
						this.stop = $('<input type="date">')
						.bind('change', this.change.bind(this))
				);

				// Use jquery timeselect if time not a valid input type
				if (this.start.attr('type') !== 'date') {
					this.start.datepicker();
					this.stop.datepicker();
				}
				break;
			case 'timerange':
				el[prop](this.start = $('<input type="time">')
						.bind('change', this.change.bind(this)), ' to ',
						this.stop = $('<input type="time">')
						.bind('change', this.change.bind(this))
				);

				// Use jquery timeselect if time not a valid input type
				if (this.start.attr('type') !== 'time') {
					this.start.timepicker();
					this.stop.timepicker();
				}
				break;
			case 'datetimerange':
				el[prop](this.start = $('<input type="datetime-local">')
						.bind('change', this.change.bind(this)), ' to ',
						this.stop = $('<input type="datetime-local">')
						.bind('change', this.change.bind(this))
				);

				// Use jquery timeselect if time not a valid input type
				if (this.start.attr('type') !== 'datetime-local') {
					this.start.datetimepicker();
					this.stop.datetimepicker();
				}
				break;
			case 'date':
			default:
				el[prop](this.input = $('<input type="date">')
						.bind('change', this.change.bind(this)));
				// Use jquery timeselect if time not a valid input type
				if (this.input.attr('type') !== 'date') {
					this.input.datepicker();
				}
				break;
		}
	},
	valueOf: function() {
		switch (this.options.type) {
			case 'timerange':
			case 'daterange':
			case 'datetimerange':
				// Convert the datetime to a Date object then get the timestamp
				// Will convert back to a date/time/datetime on the server
				var start = this.start.val();
				var stop = this.stop.val();
				var val = {};
				
				if (start) {
					start = new Date(start);
					val.start =  start.getTime() / 1000 | 0;
				}
				if (stop) {
					stop = new Date(stop);
					val.stop =  stop.getTime() / 1000 | 0;
				}
				if (val.start || val.stop) {
					return val;
				} else {
					return;
				}
			case 'time':
			case 'datetime':
			case 'date':
			default:
				// Convert the datetime to a Date object then get the timestamp
				// Will convert back to a date/time/datetime on the server
				var val = this.input.val();
				if (val) {
					val = new Date(val);
					return val.getTime() / 1000 | 0;
				} else {
					return;
				}
		}
	}
});

newType('select', {
	html: function(el, value) {
		var prop;
		if (this.options.prepend) {
			prop = 'prepend';
		} else {
			prop = 'append';
		}

		switch (this.options.type) {
			case 'hierarchical': // Hierarchical using jquery-hierarchy-select
				var x;
				this.input = new $.Hierarchical(el, this.options, value);
				//el[prop](x = $('<div></div>'));
				//x.folders(this.options);
				break;
			case 'boxes':
				var i;
				var selected = 'selected';
				var type = 'radio';
				this.inputs = {};
				if (this.options.multiple) {
					type = 'checkbox';
					selected = 'checked';
				}

				if (this.options.values) {
					for (i in this.options.values) {
						// @TODO Add handling of multiple values
						el.append($('<label>' + this.options.values[i] + '</label>')
								.prepend(this.inputs[i] = $('<input type="' + type
										+ '" value="' + i + '"'
										+ (i == value ? ' ' + selected : '') + '>')
										.bind('change', this.change.bind(this))));
					}
				}

				break;
			default:
				el[prop](this.input = $('<select'
						+ (this.options.multiple ? ' multiple' : '')
						+ '></select>')
						.bind('change', this.change.bind(this)));

				if (this.options.values) {
					for (i in this.options.values) {
						this.input.append('<option value="' + i + '"'
								+ (i == value ? ' selected' : '') + '>'
								+ this.options.values[i] + '</option>');
					}
				}

				break;
		}
	},
	valueOf: function() {
		switch (this.options.type) {
			case 'hierarchical':
				var selected = this.input.valueOf();
				var i, array = [];
				for (i in selected) {
					array.push(i);
				}
				return array;
			default:
				return this.input.val();
				break;
		}
	}
});

newType('dimension', {
	html: function(el, value) {
		var prop;
		if (this.options.prepend) {
			prop = 'prepend';
		} else {
			prop = 'append';
		}

		el[prop](this.x = $('<input type="number">')
				.bind('change', this.change.bind(this)),
				' x ',
				this.y = $('<input type="number">')
				.bind('change', this.change.bind(this))
		);
	},
	valueOf: function() {
		if (!this.x.val() || ! this.y.val()) {
			return;
		}
		if (this.options.associative) {
			return {
				x: this.x.val(),
				0: this.x.val(),
				y: this.y.val(),
				1: this.y.val()
			};
		} else {
			return [this.x.val(), this.y.val()];
		}
	}
});

/*
newType('', {
	html: function(el, value) {
	},
	valueOf: function() {
	}
});
*/


