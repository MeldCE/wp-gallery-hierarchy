<?php
//require_once('../lib/GHierarchy.php');

class GHFader implements GHAlbum {
	static function label() {
		return 'fader';
	}

	static function name() {
		return __('Fade Slideshow', 'gallery-hierarchy');
	}

	static function description() {
		return __('Fade image slideshow using Simple Fade Slideshow.',
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
				$html .= '<a' . ($image->link ? ' href="' . $image->link . '"' : '');
				
				// Add comment
				$html .= GHierarchy::lightboxData($image, $options['group'], $image->popup_caption);

				$html .= '><img src="'
				// @todo Need to get cached for specific image size...?
				. $url
				. '">';
				
				// Add comment
				$html .= '<span>' . ($image->caption ? $image->caption : '&nbsp;')
						. '</span>';
						
				$html .= '</a>';
			}

			$html .= '</div></div>';
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
