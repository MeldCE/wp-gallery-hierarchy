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

	static function attributes() {
	}

	static function printAlbum(&$images, &$options) {
		$html = '';
		if ($images) {
			$html .= '<div' . ($options['class'] ? ' class="' . $options['class'] . '"'
					: '') . '>';
		
			foreach ($images as &$image) {
				// Create link
				$html .= '<a' . ($image->link ? ' href="' . $image->link . '"' : '');
				
				// Add lightbox data
				$html .= GHierarchy::lightboxData($image, $options['group'], $image->popup_caption);

				$html .= '><img src="' . GHierarchy::getCImageURL($image) . '">';

				// Metadata
				if ($metadata = GHierarchy::imageMetadata($image->id, $options)) {
					$html .= '<span class="metadata">';

					foreach ($metadata as $m) {
						if (property_exists($image, $m)) {
							$html .= '<span class="' . $m . '">' . $image->$m . '</span>';
						}
					}

					$html .= '</span>';
				}

				$html .= '<span>' . ($image->caption ? $image->caption : '&nbsp;')
						. '</span>';
						
				$html .= '</a>';
			}

			$html .= '</div>';
		}

		return $html;
	}

	static function printStyle() {
?>
.gh.ghthumb {
	text-align: center;
}

.gh.ghthumb a {
	display: inline-block;
	border: solid 1px #ccc;
	box-shadow: 3px 3px 3px #999;
	margin: 5px;
	padding: 5px;
}

<?php
	}
}
