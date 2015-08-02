if(jQuery) (function($){
	if (!$.fn.jsfader) {



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
				fadeTime: 2000,
				class: 'gHJSFader'
			}, options);
			
			this.obj = obj;
			var id, i = 0;
			this.images = images;

			console.log('Starting a JSFader');
			console.log(images);

			// Add class to obj
			if (this.options.class) {
				this.obj.addClass(this.options.class);
			}

			// Add required style
			this.obj.css({
				position: 'relative',
				backgroundSize: 'cover',
				padding: '0px',
				backgroundPosition: 'center',
			});

			// Add width to fix jQuery bug
			if (this.options.width) {
				this.obj.width(this.options.width);
			}

			// Create a div for the changing image
			this.obj.append(this.transition = $('<div></div>').css({
				width: '100%',
				height: '100%',
				position: 'absolute',
				backgroundSize: 'cover',
				top: '0px',
				left: '0px',
				margin: '0px',
				padding: '0px',
				backgroundPosition: 'center',
			}));

			// Generate arrows
			this.obj.append($('<a class="left"></a>')
					.click(scroll.bind(this, false, -1)));
					//.click(rFunc(scroll, this, false, -1)));
			this.obj.append($('<a class="right"></a>')
					.click(scroll.bind(this, false, 1)));

			// Generate index numbers
			var indexObj;
			if (this.options.index) {
				this.obj.append(indexObj = $('<div class="index"></div>'));
			}
			this.keys = [];
			var i;
			var maxRatio = 0;
			for (i in this.images) {
				this.keys.push(i);
				if (this.options.index) {
					indexObj.append($('<a>' + this.keys.length + '</a>'));
				}
						//.click(rFunc(scroll, this, false, 0, i)));
				maxRatio = Math.max(this.images[i].width / this.images[i].height, maxRatio);
			}

			console.log('got maxRatio of ' + maxRatio);

			// Adjust height using maxRatio if we don't have a height
			if (!this.options.height) {
				console.log('no height');
				this.obj.height(this.obj.width() * maxRatio);
			} else {
				this.obj.height(this.options.height);
			}

			this.scroll(0);

			this.start();
			//restartTimeout.apply(this);
		}

		JSFader.prototype = {
			start: function() {
				if (this.timer) {
					clearTimeout(this.timer);
				}

				console.log(this.options);

				this.timer = setInterval(this.scroll.bind(this, false, 1, true),
						(this.options.showTime + this.options.fadeTime));
			},

			scroll: function(index, step, animate) {
				console.log('hello');
				console.log(this);
				if (/*!isNaN(index) &&*/ step) {
					index = this.current + step % (this.keys.length - 1);
				/*} else if (!parseInt(index) && !step) {
					return false;*/
				}

				console.log('have index ' + index + ', step ' + step);

				index = index % (this.keys.length - 1);
				
				console.log('image ' + index + ' is');
				console.log(this.images);
				console.log(this.images[this.keys[index]]);

				if (animate) {
					// Set the background to the current image
					this.obj.css({
						backgroundImage: 'url(\'' + gH.imageUrl + '/'
								+ this.images[this.keys[this.current]].path + '\')'
					});

					this.transition.css({
						backgroundImage: 'url(\'' + gH.imageUrl + '/'
								+ this.images[this.keys[index]].path + '\')',
						opacity: 0
					});

					console.log('fadeTime is ' + this.options.fadeTime);

					// Start transition
					this.transition.animate({opacity: 1}, this.options.fadeTime);
					//		'easein');
				} else {
					// Change background to correct image
					this.obj.css({
						backgroundImage: 'url(\'' + gH.imageUrl + '/'
								+ this.images[this.keys[index]].path + '\')'
					});

					// Get rid of the transition
					this.transition.css({
						opacity: 'hide'
					});
				}

				this.current = index;
			}
		};

		$.extend($.fn, {
			jsFader: (function() {

				return function(images, options) {
					$(this).each( function() {
						new JSFader($(this), images, options);
					});
				};
			})()
		});
	}
})(jQuery);
