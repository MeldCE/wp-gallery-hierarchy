(function($) {
	tinymce.PluginManager.add('gHierarchy', function( editor, url ) {
		var dataTag = 'data-gh-code';

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
				var shortcode = div.attr(dataTag);
				$.post(ajaxurl + '?action=gh_tiny', {
					a: 'html',
					sc: window.decodeURIComponent(shortcode)
				}, function(data) {
					var width = Math.min(1100, $(window).width() - 100);
					var height = $(window).height() - 100;

					console.log('received the following for ' + window.decodeURIComponent(shortcode));
					console.log(data);

					if (data) {
						var content = $(data);
						content.attr(dataTag, shortcode);

						div.replaceWith(content);

						div = content;
					}

					div
							// Disable standard Wordpress click function
							.bind('click', function(ev) {
								ev.stopPropagation();
							})
							.bind('tap', function(ev) {
								// Build URL
								var url = 'http://192.168.0.118/ngotaxi/wp-admin/'
										+ 'media-upload.php?chromeless=1&post_id=1385&'
										+ 'tab=ghierarchy&sc=' + shortcode + '&tinymce_popup=1';
								
								editor.windowManager.open({
									title: 'Edit Gallery Hierarchy Shortcode',
									file: url,
									resizable: true,
									maximizable: true,
									width: width,
									height: height
								}, {gHEditingDiv: content});
								//ev.preventDefault();
								ev.stopPropagation();
							})
							.data('gHDrawn', true);
				});
			});
		}

		function restoreShortcodes( content ) {
			return content.replace(
					/<!--gHStart-->[^]*?<div.*? data-gh-code="(.*?)".*?>[^]*?<\/div>[^]*?<!--gHEnd-->/mg, function(match, sc) {
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
