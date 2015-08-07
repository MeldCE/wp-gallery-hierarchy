(function($) {
	console.log('calling function');
	tinymce.PluginManager.add('gHierarchy', function( editor, url ) {
		console.log('plugin being init');
		
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
			console.log('replaceShortcodes called on content');
			return content.replace(/\[gh(album|thumb|image)( [^\]]*)?\]/g, function (shortcode) {
				var encSC = window.encodeURIComponent(shortcode);
				return '<!--gHStart--><div ' + dataTag + '="'
						+ encSC + '">&nbsp;</div><!--gHEnd-->';
			});

			/*return content.replace( /\[bs3_panel([^\]]*)\]([^\]]*)\[\/bs3_panel\]/g, function( all,attr,con) {
				return html( 'wp-bs3_panel', attr , con);
			});*/
		}

		
		function drawShortcodes(ev) {
			console.log('drawShortcodes called');
			console.log(editor.getDoc());
			console.log(editor);
			var doc = $(editor.getDoc());

			doc.find('div[' + dataTag + ']').each(function() {
				var div = $(this);
				var shortcode = div.attr(dataTag);
				console.log('found div with shortcode: ' + window.decodeURIComponent(shortcode));
				$.post(ajaxurl + '?action=gh_tiny', {
					a: 'html',
					sc: window.decodeURIComponent(shortcode)
				}, function(data) {
					var width = Math.min(1100, $(window).width() - 100);
					var height = $(window).height() - 100;

					var content = $(data);
					content.attr(dataTag, shortcode);

					div.replaceWith(content);

					content
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
							});
				});
				console.log('sent');
			});
		}

		function restoreShortcodes( content ) {
			//console.log('running restoreShortcodes');
			//console.log(content);
			return content.replace(
					/<!--gHStart-->[^]*?<div.*? data-gh-code="(.*?)".*?>[^]*?<\/div>[^]*?<!--gHEnd-->/mg, function(match, sc) {
				//console.log('found one: ' + match);
				return window.decodeURIComponent(sc);
			});

			//return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
			/*	var data = getAttr( image, 'data-sh-attr' );
				var con = getAttr( image, 'data-sh-content' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']' + con + '[/'+sh_tag+']</p>';
				}
				return match;
			});*/
		}

		/*/add popup
		editor.addCommand('bs3_panel_popup', function(ui, v) {
			//setup defaults
			var header = '';
			if (v.header)
				header = v.header;
			var footer = '';
			if (v.footer)
				footer = v.footer;
			var type = 'default';
			if (v.type)
				type = v.type;
			var content = '';
			if (v.content)
				content = v.content;

			editor.windowManager.open( {
				title: 'Bootstrap Panel Shortcode',
				body: [
					{
						type: 'textbox',
						name: 'header',
						label: 'Panel Header',
						value: header,
						tooltip: 'Leave blank for none'
					},
					{
						type: 'textbox',
						name: 'footer',
						label: 'Panel Footer',
						value: footer,
						tooltip: 'Leave blank for none'
					},
					{
						type: 'listbox',
						name: 'type',
						label: 'Panel Type',
						value: type,
						'values': [
							{text: 'Default', value: 'default'},
							{text: 'Info', value: 'info'},
							{text: 'Primary', value: 'primary'},
							{text: 'Success', value: 'success'},
							{text: 'Warning', value: 'warning'},
							{text: 'Danger', value: 'danger'}
						],
						tooltip: 'Select the type of panel you want'
					},
					{
						type: 'textbox',
						name: 'content',
						label: 'Panel Content',
						value: content,
						multiline: true,
						minWidth: 300,
						minHeight: 100
					}
				],
				onsubmit: function( e ) {
					var shortcode_str = '[' + sh_tag + ' type="'+e.data.type+'"';
					//check for header
					if (typeof e.data.header != 'undefined' && e.data.header.length)
						shortcode_str += ' header="' + e.data.header + '"';
					//check for footer
					if (typeof e.data.footer != 'undefined' && e.data.footer.length)
						shortcode_str += ' footer="' + e.data.footer + '"';

					//add panel content
					shortcode_str += ']' + e.data.content + '[/' + sh_tag + ']';
					//insert shortcode to tinymce
					editor.insertContent( shortcode_str);
				}
			});
	      	});

		//add button
		editor.addButton('bs3_panel', {
			icon: 'bs3_panel',
			tooltip: 'BootStrap Panel',
			onclick: function() {
				editor.execCommand('bs3_panel_popup','',{
					header : '',
					footer : '',
					type   : 'default',
					content: ''
				});
			}
		});*/

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){ 
			event.content = replaceShortcodes(event.content);
			console.log('content now');
			console.log(event.content);
		});

		// Set function to build divs once the content has been loaded
		editor.on('LoadContent', drawShortcodes);

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});

		/*/open popup on placeholder double click
		editor.on('DblClick',function(e) {
			var cls  = e.target.className.indexOf('wp-bs3_panel');
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-bs3_panel') > -1 ) {
				var title = e.target.attributes['data-sh-attr'].value;
				title = window.decodeURIComponent(title);
				console.log(title);
				var content = e.target.attributes['data-sh-content'].value;
				editor.execCommand('bs3_panel_popup','',{
					header : getAttr(title,'header'),
					footer : getAttr(title,'footer'),
					type   : getAttr(title,'type'),
					content: content
				});
			}
		});*/
	});
})(jQuery);
