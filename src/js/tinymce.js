(function($) {
	var dataTag = 'data-gh-code';

	function setDataTag(obj) {
		if (!obj) {
			obj = this.div;
		}

		obj.attr(dataTag,
				window.encodeURIComponent(this.shortcode.string()));
	}

	function completeRedraw(data) {
		// Change stuff into a shortcode prototype so gH.arranger can update the shortcode and change the images

		console.log('received the following for ' + this.shortcode.string());
		console.log(data);

		if (data) {
			var content;

			if (data instanceof Object) {
				if (data.html) {
					content = $(data.html);
					setDataTag.call(this, content);

					this.div.replaceWith(content);

					this.div = content;
				}
				if (data.class) {
					this.div.addClass(data.class);
				}
				if (data.func && gH[data.func]) {
					if (data.args && data.args.unshift) {
						// Add the div and this
						data.args.unshift(this);
						data.args.unshift(this.div);

						gH[data.func].apply(null, data.args);
					} else {
						gH[data.func](this.div, this);
					}
				}
				if (data.extension && $.fn.extension) {
					if (data.args && data.args.unshift) {
						// Add the div
						data.args.unshift(this.div);

						this.div[data.func].apply(null, data.args);
					} else {
						this.div[data.func]();
					}
				}
			} else {
				content = $(data);
				setDataTag.call(this, content);

				this.div.replaceWith(content);

				this.div = content;

				this.div
						// Disable standard Wordpress click function
						.bind('click', function(ev) {
							ev.stopPropagation();
						})
						.bind('tap', this.popupGallery.bind(this))
						.data('gHDrawn', true);
			}
			
			//this.div.attr('contenteditable', false);
		}
	}

	function GHTinyDiv (div, editor) {
		this.shortcode = window.decodeURIComponent(div.attr(dataTag));

		console.log(this.shortcode);

		this.editor = editor;

		// Convert shortcode to an object
		if (!(this.shortcode = wp.shortcode.next(
				'ghalbum|gharranger|ghthumb|ghimage', this.shortcode))) {
			/// @todo Add error
			return;
		}

		this.shortcode = this.shortcode.shortcode;

		console.log(this.shortcode);

		this.div = div;
		this.div.data('gHObject', this);
		// Make the content not editable

		this.redraw();
	}

	GHTinyDiv.prototype = {
		redraw: function() {
			this.div.data('gHDrawn', true);
			
			//this.div.attr('contenteditable', false);

			$.post(ajaxurl + '?action=gh_tiny', {
				a: 'html',
				/// @todo Pass object instead of text
				sc: this.shortcode.string()
			}, completeRedraw.bind(this));
		},

		popupGallery: function(ev) {
			if (ev && ev.preventDefault) {
				if (ev.isDefaultPrevented()) {
					return;
				}

				ev.preventDefault();
			}
			
			var width = Math.min(1100, $(window).width() - 100);
			var height = $(window).height() - 100;

			// Build URL
			console.log(gH);
			var url = gH.mediaUrl
					+ '?tab=ghierarchy&sc=' + window.encodeURIComponent(this.shortcode.string()) + '&tinymce_popup=1';
		
			console.log('URL is: ' + url);

			this.editor.windowManager.open({
				title: 'Edit Gallery Hierarchy Shortcode',
				file: url,
				resizable: true,
				maximizable: true,
				width: width,
				height: height
			}, {gHEditingDiv: this.div});
			//ev.preventDefault();
			ev.stopPropagation();
		},

		/**
		 * Set an attribute in the shortcode
		 *
		 * @param attr {String} Attribute to set/delete
		 * @param value {Any} Value to set
		 */
		setSCAttr: function(attr, value) {
			var ret = this.shortcode.set(attr, value);

			setDataTag.call(this);

			return ret;
		},

		getSCAttr: function(attr) {
			var ret = this.shortcode.get(attr);

			setDataTag.call(this);

			return ret;
		}
	};

	tinymce.PluginManager.add('gHierarchy', function( editor, url ) {

		//helper functions 
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ?  window.decodeURIComponent(n[1]) : '';
		};

		function html( cls, data ,con) {
			var placeholder = url + '/img/' + getAttr(data,'type') + '.jpg';
			data = window.encodeURIComponent( data );
			content = window.encodeURIComponent( con );

			return '<img src="' + placeholder + '" class="mceItem ' + cls + '" ' + 'data-sh-attr="' + data + '" data-sh-content="'+ con+'" data-mce-resize="false" data-mce-placeholder="1" />';
		}

		function replaceShortcodes( content ) {
			return content.replace(/\[gh(album|thumb|image|arranger)( [^\]]*)?\]/g, function (shortcode) {
				var encSC = window.encodeURIComponent(shortcode);
				return '<!--gHStart--><div ' + dataTag + '="'
						+ encSC + '">&nbsp;</div><!--gHEnd-->';
			});
		}
		
		function drawShortcodes(ev) {
			var doc = $(editor.getDoc());

			doc.find('div[' + dataTag + ']').each(function() {
				var div = $(this);
				// Check that is hasn't already been updated
				if (div.data('gHDrawn')) {
					return;
				}

				new GHTinyDiv(div, editor);
			});
		}

		function restoreShortcodes( content ) {
			return content.replace(
					/*@todo Make it so the a|div isn't captured if you can? */
					/<!--gHStart-->[^]*?<(a|div) .*?data-gh-code="(.*?)".*?>[^]*?[^]*?<!--gHEnd-->/mg, function(match, el, sc) {
				return window.decodeURIComponent(sc);
			});
		}

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){ 
			event.content = replaceShortcodes(event.content);
		});

		// Set function to build divs once the content has been loaded
		editor.on('LoadContent', drawShortcodes);
		editor.on('NodeChange', drawShortcodes);

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});
	});
})(jQuery);
