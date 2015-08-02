<?php
//require_once('../lib/GHierarchy.php');

class GHJSFader implements GHAlbum {
	static function label() {
		return 'jsfader';
	}

	static function name() {
		return __('Javascript Fade Slideshow', 'gallery-hierarchy');
	}

	static function description() {
		return __('A fader slideshow using background images, a div and '
				. 'javascript.',
				'gallery-hierarchy');
	}

	static function enqueue() {
	}

	static function attributes() {
	}

	static function printAlbum(&$images, &$options) {
		$html = '';
		if ($images) {
			$id = uniqid();

			// Create div
			$html = '<div id="' . $id . '" style="'
					. ($options['width'] ? 'width: \'' . $options['width'] . '\',' : '')
					. ($options['height'] ? 'height: \'' . $options['height'] . '\',' : '')
					. '"></div>';

			$html .= '<script>$(\'#' . $id . '\').jsfader(' . json_encode($images)
					. ', ' . json_encode($options) . ');</script>';
			
			/* xxx
			// Add script
			$html .= '<script src="' . plugins_url('lib/js/fadeSlideShow.js', dirname(__FILE__))
					. '"></script><script>'
					. 'jQuery(function($) { $(\'#' . $id . '\').fadeSlideShow({'
					. 'interval: 2500,'
					. ($options['width'] ? 'width: \'' . $options['width'] . '\',' : '')
					. ($options['height'] ? 'height: \'' . $options['height'] . '\',' : '')
					. '}); });'
					. '</script>';
			$html .= '<div class="ghfader'
					. ($options['class'] ? ' ' . $options['class'] : '')
					. '"><div id="' . $id . '">';
			foreach ($images as &$image) {
				// Create link
				if (!$url = GHierarchy::getImageURL($image)) {
					continue;
				}
				//$url = $image->path;
				$html .= '<a';
				switch ($options['link']) {
					case 'none':
						break;
					case 'popup':
						$html .= ' href="' . $url . '"';
						break;
					default:
						/// @todo Add the ability to have a link per thumbnail
						$html .= ' href="' . $options['link'] . '"';
						break;
				}
				
				// Add comment
				switch ($options['popup_caption']) {
					case 'title':
						$caption = $image->title;
						break;
					case 'comment':
						$caption = '';
						if ($options['add_title']) {
							if (($caption = $image->title)) {
								if (substr($caption, count($caption)-1,1) !== '.') {
									$caption .=  '. ';
								}
							}
						}
						$caption .= $image->comment;
						break;
					case 'none':
					default:
						$caption = null;
						break;
				}

				$html .= GHierarchy::lightboxData($image, $options['group'], $caption);

				$html .= '><img src="'
				// @todo Need to get cached for specific image size...?
				. $url
				. '">';
				
				// Add comment
				switch ($options['caption']) {
					case 'title':
						$html .= '<span>' . $image->title . '&nbsp;</span>';
						break;
					case 'caption':
						$html .= '<span>' . $image->caption . '&nbsp;</span>';
						break;
					case 'none':
					default:
						$html .= '<span>&nbsp;</span>';
						break;
				}
						
				$html .= '</a>';
			}

			$html .= '</div></div>';
			*/
		}

		return $html;
	}

	static function printStyle() {
		?>
		.ghfader {
			position: relative;
			padding: 0px;
		}

		.ghfader div {
			padding: 0px;
		}

		.ghfader > #fssPrev {
			display: none;
		}

		.ghfader > #fssNext {
			display: none;
		}

		.ghfader > #fssPlayPause {
			display: none;
		}

		.ghfader > #fssList {
			position: absolute;
			width: 100%;
			bottom: 10px;
			text-align: center;
			padding: 0px;
			margin: 0px;
		}

		.ghfader > #fssList li {
			display: inline-block;
			overflow: hidden;
			padding: 2px 5px;
		}
		
		.ghfader > #fssList li a {
			color: #fff;
			font-weight: bold;
		}
		
		.ghfader > #fssList li a:hover {
			text-decoration: none;
		}
		
		.ghfader > #fssList li a:before {
			content: '\25cb';
		}
		
		.ghfader > #fssList li.fssActive a:before {
			content: '\25cf';
		}

		<?php
	}
}
