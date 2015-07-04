var Types = {};

var typeOptions = {
	optional: false,
	oneshot: false
};

function newType(name, prototype, baseType) {
	if (Types[name]) {
		throw new 
	}

	Types[name] = function(el, value, options) {
		/* Don't properly initiate if we don't have an element (probably another
		 * type basing itself on this one
		 */
		if (el) {
			this.options = $.extend({}, typeOptions, options);

			if (this.html && this.html.apply) {
				this.html.apply(this, el, value);
			}

			if (baseType && Types[baseType) {
				Types[name].prototype = $.extend(new Types[baseType], prototype);
			} else {
				Types[name].prototype = prototype;
			}
		}
	};
}

newType('text', {
	html: function(el, value) {
		el.append((this.input = $('<input' + (value ? ' value="' + value
				+ '"' : '') + '>')));
	},
	valueOf: function() {
		return this.input.val();
	}
};

Types.bool = function(el, value) {
	if (el) {
	}
};

newType('bool', {
	html: function(el, value) {
		el.append((this.input = $('<input type="checkbox"'
				+ (value ? ' checked' : '') + '>')));
	},
	valueOf: function() {
		return (this.input.attr('checked') ? true : false);
	}
});

newType('datetime', {
	html: function(el, value) {
		switch (this.options.type) {
			case 'time':
			case 'datetime':
			case 'daterange':
			case 'timerange':
			case 'datetimerange':
			case 'date':
			default:
	},
	valueOf: function() {
	}
});

newType('select', {
	html: function(el, value) {
		switch (this.options.type) {
			case 'hierarchical': // Hierarchical
				break;
			case 'boxes':
				var i;
				var type = 'radio';
				this.inputs = {};
				if (this.options.multiple) {
					type = 'checkbox';
				}

				if (this.options.values) {
					for (i in this.options.values) {
						el.append($('<label>' + this.options.values[i] + '</label>')
								.prepend(this.inputs[i] = $('<input type="' + type
										+ '" value="' + i + '">')));
					}
				}

				break;
			default:
				el.append(this.input = $('<select'
						+ (this.options.multiple ? ' multiple' : '')
						+ '></select>'));

				if (this.options.values) {
					for (i in this.options.values) {
						this.input.append('<option value="' + i + '">'
								+ this.options.values[i] + '</option>');
					}
				}

				break;
		}
	},
	valueOf: function() {
	}
});

newType('dimension', {
	html: function(el, value) {
		el
				.append(this.x = $('<input type="number">'))
				.append(' x ');
				.append(this.x = $('<input type="number">'));
	},
	valueOf: function() {
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

newType('', {
	html: function(el, value) {
	},
	valueOf: function() {
	}
});


