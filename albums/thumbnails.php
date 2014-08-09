<?php
//require_once('../lib/GHierarchy.php');

class GHThumbnails implements GHAlbum {
	static function label() {
		return 'thumbnails';
	}

	static function name() {
		return __('Thumbnails', 'gallery-hierarchy');
	}

	static function description() {
		return __('Simple album for displaying a single or group of thumbnails.',
				'gallery-hierarchy');
	}

	static function enqueue() {
	}

	static function printAlbum(&$images, &$options) {
		if ($images) {
			echo '<div' . ($options['class'] ? ' class="' . $options['class'] . '"'
					: '') . '>';
			foreach ($images as &$image) {
				// Create link
				echo '<a';
				switch ($options['link']) {
					case 'none':
						break;
					case 'popup':
						echo ' href="' . GHierarchy::getImageURL($image) . '"';
						break;
					default:
						/// @todo Add the ability to have a link per thumbnail
						echo ' href="' . $options['link'] . '"';
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

				echo GHierarchy::lightboxData($image, $options['group'], $caption);

				echo '><img src="' . GHierarchy::getCImageURL($image) . '">';
				
				// Add comment
				switch ($options['caption']) {
					case 'title':
						echo '<span>' . $image->title . '&nbsp;</span>';
						break;
					case 'caption':
						echo '<span>' . $image->caption . '&nbsp;</span>';
						break;
					case 'none':
					default:
						echo '<span>&nbsp;</span>';
						break;
				}
						
				echo '</a>';
			}

			echo '</div>';
		}
	}
}
