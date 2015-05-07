if(jQuery) (function($){
	if (!$.fn.faderAlbum) {
		$.extend($.fn, {
			faderAlbum: (function() {
				function rFunc(func, context, appendParams) {
					console.log('trying to return a rFunc');
					console.log(func);
					console.log(context);
					var a = Array.prototype.slice.call(arguments);
					a.splice(0, 3);
					return function () {
						if (appendParams) {
							var b = [].concat(a);
							b = b.concat(Array.prototype.slice.call(arguments));
							func.apply(context, b);
						} else {
							func.apply(context, a);
						}
					};
				}

				function restartTimeout() {
					if (this.timer) {
						clearTimeout(this.timer);
					}

					console.log(this.options);

					this.timer = setTimeout(rFunc(scroll, this, false, 1),
							this.options.showTime);
				}

				function finishFade(newImage) {
					this.images[this.current].hide();
					this.current = newImage;
				}

				function FaderAlbum(obj, options) {
					// Merge options with default options
					this.options = $.extend({
						showTime: 2000,
						fadeTime: 1000,
						class: 'gHFader'
					}, options);
					
					this.obj = obj
					var images = [];
					var imageIndex = {};
					var id, i = 0;
					this.images = images;
					
					this.obj.children().each(function() {
						images[i] = $(this);
						if ($(this).attr('id')) {
							imageIndex[id] = i;
						}
						i++;
					});

					this.images = images;
					this.imageIndex = imageIndex;

					console.log('test!!!!');
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
						if (i != 0) {
							this.images[i].hide();
							console.log('hiding ' + i);
						}
						indexObj.append($('<a>' + ((i*1) + 1) + '</a>'));
								//.click(rFunc(scroll, this, false, 0, i)));
					}
					this.current = 0;

					//restartTimeout.apply(this);
				}

				FaderAlbum.prototype = {
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

				return function(options) {
					$(this).each( function() {
						new FaderAlbum($(this), options);
					});
				};
			})()
		});
	}
})(jQuery);
