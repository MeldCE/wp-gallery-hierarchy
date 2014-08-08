<?php

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
						echo ' href="' . GHierarcy::getImageURL($image) . '"';
						break;
					default:
						/// @todo Add the ability to have a link per thumbnail
						echo ' href="' . $options['link'] . '"';
						break;
				}
				echo '><img src="' . GHierarcy::getCImageURL($image) . '">';
				
				// Add comment
				switch ($options['caption']) {
					case 'none':
						break;
					case 'title':
						echo '<span>' . $image->title . '</span>';
						break;
					case 'caption':
						echo '<span>' . $image->caption . '</span>';
						break;
				}
						
				echo '</a>';
			}

			echo '</div>';
		}
	}
}
