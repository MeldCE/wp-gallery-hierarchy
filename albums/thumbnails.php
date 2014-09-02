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
		$html = '';
		if ($images) {
			$html .= '<div' . ($options['class'] ? ' class="' . $options['class'] . '"'
					: '') . '>';
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
		}

		return $html;
	}

	static function printStyle() {
?>
.gh.ghthumbnail {
	text-align: center;
}

.gh.ghthumbnail a {
	display: inline-block;
	border: solid 1px #ccc;
	box-shadow: 3px 3px 3px #999;
	margin: 5px;
	padding: 5px;
}

<?php
	}
}
