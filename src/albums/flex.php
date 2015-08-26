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
					. '}' /// @todo Add fixed width/height depending on column or row flex
					. '</style>'
					. '<div class="flex">';
			foreach ($images as &$image) {
				// Create link
				$html .= '<a' . ($image->link ? ' href="' . $image->link . '"' : '');
				
				// Add lightbox data
				$html .= GHierarchy::lightboxData($image, $options['group'], $image->popup_caption);

				$html .= '><img src="' . GHierarchy::getCImageURL($image) . '">';
				
				$html .= '<span>' . ($image->caption ? $image->caption : '&nbsp;')
						. '</span>';
						
				$html .= '</a>';
			}

			$html .= '</div></div>';
		}

		return $html;
	}

	static function printStyle() {
	}
}
