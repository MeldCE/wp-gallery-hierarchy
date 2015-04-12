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
			$html .= '<script src="' . plugins_url('js/fader.js', dirname(__FILE__))
					. '"></script>';
			$html .= '<div id="' . $id . '"'
					. ($options['class'] ? ' class="' . $options['class'] . '"' : '')
					. '>';
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

			$html .= '</div>';
			$html .= '<script>jQuery(function($) {'
					. '$(\'#' . $id . '\').faderAlbum();'
					. '});'
					. '</script>';
		}

		return $html;
	}

	static function printStyle() {
		?>
		<?php
	}
}
