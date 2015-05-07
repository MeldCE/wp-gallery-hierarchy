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
