<?php
//require_once('../lib/GHierarchy.php');

class GHPackery implements GHAlbum {
	static function label() {
		return 'packery';
	}

	static function name() {
		return __('Packery', 'gallery-hierarchy');
	}

	static function description() {
		return __('Simple album for displaying a single or group of thumbnails in a Packery.',
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
			//$html .= '<div' . ($options['class'] ? ' class="' . $options['class'] . '"'
			//		: '') . ' id="' . $id . '">';
			$html .= '<div id="' . $id . '">';
			foreach ($images as &$image) {
				// Create link
				$html .= '<a' . ($image->link ? ' href="' . $image->link . '"' : '');
				
				// Add lightbox data
				$html .= GHierarchy::lightboxData($image, $options['group'], $image->popup_caption);

				$html .= '><img src="' . GHierarchy::getCImageURL($image) . '">';
				
				// Add comment
				$html .= '<span>' . ($image->caption ? $image->caption : '&nbsp;')
						. '</span>';
						
				$html .= '</a>';
			}

			$html .= '</div>';
			// Todo move to enqueue
			$html .= '<script src="' . plugins_url('/lib/js/packery.pkgd.min.js', dirname(__FILE__)) . '"></script>';
			$html .= '<script>'
					. 'jQuery(function($) {'
					. '$(\'#' . $id . '\').packery({'
					. 'itemSelector: \'a\','
					. 'gutter: 10'
					. '});'
					. '});'
					. '</script>';
		}

		return $html;
	}

	static function printStyle() {
	}
}
