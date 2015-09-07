<?php
//require_once('../lib/GHierarchy.php');

class GHArranger implements GHAlbum {
	static function label() {
		return 'arranger';
	}

	static function name() {
		return __('Layout', 'gallery-hierarchy');
	}

	static function description() {
		return __('An album enabling you to lay out the images how you want',
				'gallery-hierarchy');
	}

	static function enqueue() {
	}

	static function attributes() {
		return array(
			'idsOnly' => array(
				'type' => 'system',
				'value' => true
			)
		);
	}

	static function printAlbum(&$images, &$options, $inEditor = false) {
		$html = '';
		if (isset($_GET['action']) && $_GET['action'] == 'gh_tiny') {
			$html = array(
				'func' => 'arranger',
				'args' => array(
					$images,
					$options
				),
				'class' => 'arranger'
			);
		} else if ($images) {
			$id = uniqid();
			$html .= '<div id="' . $id . '"'
					. ($options['class'] ? ' class="' . $options['class'] . '"' : '')
					. ($options['width'] ? ' style="width: ' . $options['width']
					. 'px"' : '')
					. '>';

			// Parse map for the arrangement
			if (isset($options['layout'])) {
				if ($options['layout']) {
					$layout = static::makeMap($options['layout']);
				}
			}

			foreach ($images as &$image) {
				// Create an array to pass to the arrangement


				// Create link
				$html .= '<a data-id="' . $image->id . '"'
					. ' style="background: url(\'' . GHierarchy::getImageURL($image)
					. '\')' . '"' . ($image->link ? ' href="' . $image->link . '"' : '');
				
				// Add lightbox data
				$html .= GHierarchy::lightboxData($image, $options['group'], $image->popup_caption);

				$html .= '>';
				
				$html .= '<span>' . ($image->caption ? $image->caption : '&nbsp;')
						. '</span>';
						
				$html .= '</a>';
			}


			$html .= '</div>'
					. '<script src="' . plugins_url('/lib/js/arrangement.js', __DIR__) . '"></script>'
					. '<script src=""></script>'
					. '<script>'
					. 'console.log(' . json_encode($layout) . ');'
					. 'jQuery(\'#' . $id . '\').arrangement({'
					. 'images: ' . json_encode($layout)
					. '});'
					.'</script>';
		}

		return $html;
	}

	protected static function makeMap($layout) {
		// Remove percentage maarker at start
		$layout = ltrim($layout, '%');
		
		$layout = explode('|', $layout);

		$map = array();

		foreach ($layout as &$image) {
			// Split off the id
			$parts = explode(':', $image);
			$id = array_shift($parts);

			$map[$id] = array();

			switch (count($parts)) {
				case 4:
					$map[$id]['offset'] = static::dimStringToArray($parts[3]);
				case 3:
					$map[$id]['scale'] = static::dimStringToArray($parts[2]);
				case 2:
					$map[$id]['box'] = static::dimStringToArray($parts[0]);
					$map[$id]['position'] = static::dimStringToArray($parts[1]);

					break;
				default:
					// @todo error
			}
		}

		return $map;
	}

	protected static function dimStringToArray($dim) {
		$dim = explode(',', $dim);

		if (count($dim) == 1) {
			return $dim[0];
		} else {
			return array(
				(($f = floatval($dim[0])) ? $f : $dim[0]),
				(($f = floatval($dim[1])) ? $f : $dim[1])
			);
		}
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
