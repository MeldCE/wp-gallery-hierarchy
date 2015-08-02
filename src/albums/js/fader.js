if(jQuery) (function($){
	if (!$.fn.jsfader) {
		function restartTimeout() {
			if (this.timer) {
				clearTimeout(this.timer);
			}

			console.log(this.options);

			this.timer = setTimeout(rFunc(scroll, this, false, 1),
					this.options.showTime);
		}

		function show(index) {
			if (index === this.current || index < 0 || index >= this.images.length) {
				return false;
			}


		}

		/**
		 * Prototype to create and managed a JSFader album
		 *
		 * @param obj {JQueryDOMObject} JQuery DOM object to use to display album
		 * @param images {Array} Array containing the images to display in the
		 *        album
		 * @param options {Object} Object containing album options
		 */
		function JSFader(obj, images, options) {
			// Merge options with default options
			this.options = $.extend({
				showTime: 2000,
				fadeTime: 1000,
				class: 'gHJSFader'
			}, options);
			
			this.obj = obj;
			var id, i = 0;
			this.images = images;

			console.log(images);

			// Add class to obj
			if (this.options.class) {
				this.obj.addClass(this.options.class);
			}

			// Generate arrows
			this.obj.append($('<a class="left"></a>')
					.click(scroll.bind(this, false, -1)));
					//.click(rFunc(scroll, this, false, -1)));
			this.obj.append($('<a class="left"></a>')
					.click(rFunc(scroll, this, false, -1)));

			// Generate index numbers
			var indexObj;
			this.obj.append(indexObj = $('<div class="index"></div>'));
			var i;
			for (i in this.images) {
				indexObj.append($('<a>' + ((i*1) + 1) + '</a>'));
						//.click(rFunc(scroll, this, false, 0, i)));
			}

			show.call(this, 0);

			start.call(this);
			//restartTimeout.apply(this);
		}

		JSFader.prototype = {
			/**
			 * Scrolls the images either by a certain number of steps, or to a
			 * particular index.
			 *
			 * @param step Integer Number to scroll by. Positive for forwards,
			 *             negative for backwards
			 * @param index Integer Index (starting from 0) of the image to jump to
			 */
			scroll: function(step, index) {
				var newImage;

				if (!step) {
					if (!index) {
						return;
					}
					if (this.images[index]) {
						// Check if that is the current image
						if (index == this.current) {
							restartTimeout.apply(this);
							return;
						}

						newImage = imdex;
					} else if (this.imageIndex[index]) {
						// Check if that is the current image
						if (this.indexImage[index] == this.current) {
							restartTimeout.apply(this);
							return;
						}

						newImage = this.imageIndex[index];
					} else {
						// Invalid index
						return;
					}
				} else {
					newImage = this.current + step;
					newImage = newImage % this.images.length;
				}

				// Bring new image to the front
				this.obj.detach(this.images[newImage]);
				this.obj.prepend(this.images[newImage]);

				// Start fade in transition
				this.images[newImage].fadeIn(this.options.fadeTime,
						this.options.fadeType, rFunc(finishFade, this, false, newImage)); 
			},
		};

		$.extend($.fn, {
			jsfader: (function() {

				return function(images, options) {
					$(this).each( function() {
						new JSFader($(this), options);
					});
				};
			})()
		});
	}
})(jQuery);
