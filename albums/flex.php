<?php
//require_once('../lib/GHierarchy.php');

class GHFlexbox implements GHAlbum {
	static function label() {
		return 'flexbox';
	}

	static function name() {
		return __('Flexbox', 'gallery-hierarchy');
	}

	static function description() {
		return __('Simple album for displaying a single or group of thumbnails using an HTML5 flexbox.',
				'gallery-hierarchy');
	}

	static function enqueue() {
	}

	static function attributes() {
	}

	static function printAlbum(&$images, &$options) {
		$html = '';
		if ($images) {
			$html .= '<div' . ($options['class'] ? ' class="' . $options['class'] . '"'
					: '') . '>';
			$html .= '<style scoped>'
					. 'div.flex {'
					. 'display: flexbox;'
					. ((isset($options['direction'])
					&& in_array($options['direction'], array('column', 'row-reverse',
					'column_reverse'))) ? 'flex-direction: ' . $options['direction'] : '')
					. '}'
					. '</style>'
					. '<div class="flex">';
			foreach ($images as &$image) {
				// Create link
				$html .= '<a';
				switch ($options['link']) {
					case 'none':
						break;
					case 'popup':
						$html .= ' href="' . GHierarchy::getImageURL($image) . '"';
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

				$html .= '><img src="' . GHierarchy::getCImageURL($image) . '">';
				
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
		}

		return $html;
	}

	static function printStyle() {
	}
}
