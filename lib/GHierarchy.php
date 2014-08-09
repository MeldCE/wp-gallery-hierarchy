<?php
require_once('utils.php');
require_once('wp-settings/WPSettings.php');

class GHierarchy {
	protected static $instance = null;
	protected $imageMimes = array('image/jpeg');
	protected $finfo;
	private $dirTable;
	private $imageTable;
	protected static $title;
	protected static $settings;
	protected static $scanTransient = 'gHScanTran';
	protected static $statusTransient = 'gHStatusTran';
	protected static $filesTransient = 'gHFilesTran';
	protected $disabled = array();
	protected static $scanTransientTime = 60;
	protected static $statusTransientTime = DAY_IN_SECONDS;
	protected static $filesTransientTime = DAY_IN_SECONDS;
	protected static $runAdminInit = false;

	protected static $albums = null;

	/// Rescan variables
	protected static $nextSet = 0;
	protected $imageDir;
	protected $imageUrl;
	protected $cacheDir;
	protected $cacheUrl;

	protected function  __construct() {
		global $wpdb;

		$this->finfo = finfo_open(FILEINFO_MIME_TYPE);
		/// @todo finfo_close($finfo);
		$this->dirTable = $wpdb->prefix . 'gHierarchyDirs';
		$this->imageTable = $wpdb->prefix . 'gHierarchyImages';
		
		// Make the array of albums
		$albums = array();
		$albumDescription = '';
		foreach (static::getAlbums() as $a => &$album) {
			$albumDescription .= $album['name'] . ' - ' . $album['description'] . '<br>';
			$albums[$a] = $album['name'];
		}

		$options = array(
				'title' => __('Gallery Hierarchy Options', 'gallery_hierarchy'),
				'id' => 'gHOptions',
				'settings' => array(
						'gHFolders' => array(
								'title' => __('Folder Options', 'gallery_hierarchy'),
								'fields' => array(
										'gh_folder' => array(
												'title' => __('Image Folder', 'gallery_hierarchy'),
												'description' => __('This should be a relative path '
														. 'inside of wp-content to a folder containing your '
														. 'images.', 'gallery_hierarchy'),
												'type' => 'folder',
												'default' => 'gHImages'
										),
										'gh_cache_folder' => array(
												'title' => __('Cache Image Folder', 'gallery_hierarchy'),
												'description' => __('This should be a relative path '
														. 'inside of wp-content to a folder that will be '
														. 'used to store images created by Gallery '
														. 'Hierarchy, including thumbnails.',
														'gallery_hierarchy'),
												'type' => 'folder',
												'default' => 'gHCache'
										),
								)
						),
						'gHThumbnails' => array(
								'title' => __('Thumbnail Options', 'gallery_hierarchy'),
								'fields' => array(
										'gh_thumbnail_size' => array(
												'title' => __('Thumbnail Dimensions',
														'gallery_hierarchy'),
												'description' => __('Size to make the thumbnails.',
														'gallery_hierarchy'),
												'type' => 'dimensions',
												'default' => array(200, 150)
										),
										'gh_crop_thumbnails' => array(
												'title' => __('Crop Thumbnails', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'image will be cropped so that if fills the entire '
														. 'thumbnail.', 'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										)
								)
						),
						'gHScanning' => array(
								'title' => __('Image Loading Options', 'gallery_hierarchy'),
								'fields' => array(
										'gh_resize_images' => array(
												'title' => __('Resize Images', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'images will be resized to the maximum '
														. 'dimensions '
														. 'specified in the Image Dimensions setting'
														. 'below.', 'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'gh_image_size' => array(
												'title' => __('Image Dimensions',
														'gallery_hierarchy'),
												'description' => __('Maximum size of the images.',
														'gallery_hierarchy'),
												'type' => 'dimensions',
												'default' => array(1100, 1100)
										),
										'gh_folder_keywords' => array(
												'title' => __('Folders to Tags', 'gallery_hierarchy'),
												'description' => __('If this option is selected, each '
														. 'folder name the image is inside will be added as a'
														. 'tag to the image information in the database. '
														. 'Folder names can be ignored by adding a \'-\' to '
														. 'the front of the name.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										)
								)
						),
						'gHDisplay' => array(
								'title' => __('Display Options', 'gallery_hierarchy'),
								'fields' => array(
										'gh_add_title' => array(
												'title' => __('Add Title', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'image title will be added to the start of the '
														. 'image comment when being displayed on the image.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'gh_group' => array(
												'title' => __('Group Images by Default',
														'gallery_hierarchy'),
												'description' => __('If this option is selected, '
														. 'images will be grouped by default into the '
														. 'group "group".',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'gh_thumb_album' => array(
												'title' => __('Album For Thumbnail Shortcut',
														'gallery_hierarchy'),
												'description' => __('What album type to use for the '
														. 'thumbnail shortcode.', 'gallery_hierarchy')
														. '<br>' . $albumDescription,
												'type' => 'select',
												'values' => $albums,
												'default' => 'thumbnail'
										),
										'gh_thumb_class' => array(
												'title' => __('Default Thumbnail Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'thumbnail by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'gh_thumb_class_append' => array(
												'title' => __('Append Specified Thumbnail Classes',
														'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'gh_thumb_description' => array(
												'title' => __('Thumbnail Description',
														'gallery_hierarchy'),
												'description' => __('What is shown by default underneath '
														. 'a thumbnail.', 'gallery_hierarchy'),
												'type' => 'select',
												'values' => array(
														 '' => __('Nothing', 'gallery_hierarchy'),
														 'title' => __('Image Title', 'gallery_hierarchy'),
														 'comment' => __('Image Comment', 'gallery_hierarchy')
												),
												'default' => ''
										),
										'gh_album_class' => array(
												'title' => __('Default Album Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'album by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'gh_album_class_append' => array(
												'title' => __('Append Specified Album Classes',
														'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'gh_album_description' => array(
												'title' => __('Album Description', 'gallery_hierarchy'),
												'description' => __('What is shown by default underneath '
														. 'an album image.', 'gallery_hierarchy'),
												'type' => 'select',
												'values' => array(
														 '' => __('Nothing', 'gallery_hierarchy'),
														 'title' => __('Image Title', 'gallery_hierarchy'),
														 'comment' => __('Image Comment', 'gallery_hierarchy')
												),
												'default' => 'comment'
										),
										'gh_image_class' => array(
												'title' => __('Default Image Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'image by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'gh_image_class_append' => array(
												'title' => __('Append Specified Image Classes', 'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'gh_image_description' => array(
												'title' => __('Image Description', 'gallery_hierarchy'),
												'description' => __('What is shown by default underneath '
														. 'an image.', 'gallery_hierarchy'),
												'type' => 'select',
												'values' => array(
														 '' => __('Nothing', 'gallery_hierarchy'),
														 'title' => __('Image Title', 'gallery_hierarchy'),
														 'comment' => __('Image Comment', 'gallery_hierarchy')
												),
												'default' => 'title'
										),
										'gh_popup_description' => array(
												'title' => __('Image Popup Description',
														'gallery_hierarchy'),
												'description' => __('What is shown by default underneath '
														. 'an image popup.', 'gallery_hierarchy'),
												'type' => 'select',
												'values' => array(
														 '' => __('Nothing', 'gallery_hierarchy'),
														 'title' => __('Image Title', 'gallery_hierarchy'),
														 'comment' => __('Image Comment', 'gallery_hierarchy')
												),
												'default' => 'title'
										)
								)
						),
						'gHOther' => array(
								'title' => __('Other Options', 'gallery_hierarchy'),
								'fields' => array(
										'gh_num_images' => array(
												'title' => __('Images per Page', 'gallery_hierarchy'),
												'description' => __('Default number of images per '
														. 'page to show in the gallery view. Set to 0 '
														. 'for all of the images (could be really '
														. 'slow).', 'gallery_hierarchy'),
												'type' => 'number',
												'default' => 50
										),
								)
						)
				)
		);
		static::$settings = new WPSettings($options);

		// Create path to image Directory
		$imageDir = static::$settings->get_option('gh_folder');
		$this->imageDir = gHpath(WP_CONTENT_DIR, $imageDir);
		// Remove trailing slash
		$this->imageDir = gHptrim($this->imageDir);
		$this->imageUrl = content_url($imageDir);
		// Create path to cache directory
		$cacheDir = static::$settings->get_option('gh_cache_folder');
		$this->cacheDir = gHpath(WP_CONTENT_DIR, $cacheDir);
		$this->cacheUrl = content_url($cacheDir);
		// Remove trailing slash
		$this->cacheDir = gHptrim($this->cacheDir);
	}

	/**
	 * Function to initialise the plugin when in the dashboard
	 */
	static function adminInit() {
		if (!static::$runAdminInit) {
			$me = static::instance();

			add_action('admin_enqueue_scripts', array(&$me, 'adminEnqueue'));
			add_action('admin_menu', array(&$me, 'adminMenuInit'));

			static::$runAdminInit = true;
		}
	}

	protected static function &instance() {
		if (!static::$instance) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Enqueues scripts and stylesheets used by Gallery Hierarchy in the admin
	 * pages.
	 */
	static function adminEnqueue() {
		static::enqueue();
		wp_enqueue_script('ghierarchy', 
				plugins_url('/js/ghierarchy.js', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy',
				plugins_url('/css/ghierarchy.css', dirname(__FILE__)));
	}

	/**
	 * Enqueues scripts and stylesheets used by Gallery Hierarchy.
	 */
	static function enqueue() {
		// Enqueue lightbox script
		wp_enqueue_script('lightbox', 
				plugins_url('/lib/lightbox2/js/lightbox.min.js', dirname(__FILE__)));
		wp_enqueue_style('lightbox',
				plugins_url('/lib/lightbox2/css/lightbox.css', dirname(__FILE__)));
	}

	/**
	 * Function to create the Gallery Hierarchy admin menu.
	 * Called by @see gHierarchy::init()
	 */
	function adminMenuInit() {
		add_menu_page(__('Gallery Hierarchy', 'gallery_hierarchy'), 
				__('Gallery Hierarchy', 'gallery_hierarchy'), 'edit_posts',
				'gHierarchy', array(&$this, 'gHgalleryPage'),
				'dashicons-format-gallery', 50);
		add_submenu_page('gHierarchy',
				__('Load Images into Gallery Hierarchy', 'gallery_hierarchy'),
				__('Load Images', 'gallery_hierarchy'), 'upload_files', 'gHLoad',
				array(&$this, 'gHLoadPage'));
		add_submenu_page('gHierarchy',
				__('Gallery Hierarchy Options', 'gallery_hierarchy'),
				__('Options', 'gallery_hierarchy'), 'manage_options', 'gHOptions',
				array(static::$settings, 'printOptions'));
	}

	protected function echoError($message) {
		echo '<div id="message" class="error">' . $message . '</div>';
	}

	protected function checkFunctions() {
		if (!function_exists('finfo_file')) {
			$this->echoError(__('The required Fileinfo Extension is not installed. Please install it',
					'gallery_hierarchy'));
			$this->disable = true;
		}
		if (!class_exists('Imagick')) {
			$this->echoError(__('Require the Imagick PHP extension to run.',
					'gallery_hierarchy'));
			$this->disable = true;
		}
		if (!function_exists('exif_read_data')) {
			$this->echoError(__('Require the EXIF data PHP extension to run.',
					'gallery_hierarchy'));
			$this->disable = true;
		}
		if (!is_dir($this->imageDir)) {
			// Try and create the image directory
			if (!mkdir($this->imageDir, 0755, true)) {
				$this->echoError(__('Could not create image directory: ',
						'gallery_hierarchy') . $this->imageDir);
				$this->disable = true;
			}
		}
		if (!is_dir($this->cacheDir)) {
			// Try and create the image directory
			if (!mkdir($this->cacheDir, 0755, true)) {
				$this->echoError(__('Could not create cache directory: ',
						'gallery_hierarchy') . $this->cacheDir);
				$this->disable = true;
			}
		}
		// Check cache dir is writable
		if (!is_writable($this->cacheDir)) {
			$this->echoError(__('Need to be able to write to the cache directory',
					'gallery_hierarchy'));
			$this->disable = true;
		}
	}

	protected function printGallery() {
		global $wpdb;
		$id = uniqid();
		// @todo Check if a scan has been run...? Check if we have images?
		echo '<form id="' . $id . '">';
		// Print folders
		$folders = $wpdb->get_results('SELECT id, dir FROM ' . $this->dirTable
				. ' ORDER BY dir');
		echo '<label for="' . $id . 'folders">' . __('Folders:',
				'gallery_hierarchy') . '</label><select name="' . $id . 'folders[]" '
				. 'multiple="multiple">';
		if ($folders) {
			foreach ($folders as &$f) {
				echo '<option value="' . $f->id . '">' . $f->dir . '</option>';
			}
		}
		echo '</select>';
		echo '<div id="' . $id . 'pad"></div>';
		echo '<script>gH.gallery(\'' . $id . '\');</script>';
	}

	/**
	 * Prints the main Gallery Hierarchy page.
	 */
	function gHgalleryPage() {
		$this->checkFunctions();

		echo '<h1>' . __('Gallery Hierarchy', 'gallery_hierarchy')
				. ' <a href="' . admin_url('admin.php?page=gHOptions')
				. '" class="add-new-h1">'
				. __('Add New', 'gallery_hierarchy') . '</a></h1>';

		$this->printGallery();

		/** @todo Remove
		$this->doShortcode(array(
			//'id' => 'folder=70',
			'id' => '1349,folder=70,tags=Wildlife&sarah,990',
			), '', 'ghimage');
		
		$this->doShortcode(array(
			//'id' => 'folder=70',
			'id' => '1349,folder=70,tags=Wildlife&sarah,990',
			), '', 'ghimage');
		$this->doShortcode(array(
			//'id' => 'folder=70',
			'id' => 'folder=70,tags=Wildlife&sarah',
			), '', 'ghthumb');
		*/
	}

	/**
	 * Prints the Load Images page
	 */
	function gHLoadPage() {
		$this->checkFunctions();

		echo '<h1>' . __('Load Images into Gallery Hierarchy', 'gallery_hierarchy')
				. '</h1>';
		
		if ($this->disabled) {
			echo '<p>' . __(' Loading disabled. Please fix it.', 'gallery_hierarchy')
					. '</p>';
			return;
		}

		// Check if we should start a job
		if (isset($_REQUEST['start'])) {
			// Check to make sure something hasn't already started
			if (($status = get_transient(static::$scanTransient)) === false) {
				switch($_REQUEST['start']) {
					case 'rescan':
						$status = __('Starting rescan...', 'gallery_hierarchy');
						$args = null;
						break;
					case 'full':
						$status = __('Forcing full rescan...', 'gallery_hierarchy');
						$args = array(true);
						break;
				}
				static::setScanTransients('start', $status);
				wp_schedule_single_event(time(), 'gh_rescan');//, $args);
			}
		} else {
			$status = null;
		}
		echo '<h2>' . __('Manually uploaded files into the folder?',
				'gallery_hierarchy') . '</h2>';
		// Check if a job is currently running
		if (get_transient(static::$scanTransient) !== false) {
			echo '<p>' . __("Scan currently running.",
					'gallery_hierarchy') . '</p>';
			if (($status = get_transient(static::$statusTransient)) !== false) {
				echo '<p>' . __("Status: ",
						'gallery_hierarchy') . $status . '</p>';
			}
			//echo '<a href="" class="button">' . __('Stop current scan',
			//		'gallery_hierarchy') . '</a>';
		} else {
			echo '<p>' . __('Use the buttons below to rescan the folder.',
					'gallery_hierarchy') . '</p>';
			echo '<a href="' . add_query_arg('start', 'rescan') . '" class="button">'
					. __('Rescan Directory', 'gallery_hierarchy') . '</a> <a href="'
					. add_query_arg('start', 'full') . '" class="button button-cancel">'
					. __('Force Rescan of All Images', 'gallery_hierarchy') . '</a>';
			if (($status = get_transient(static::$statusTransient)) !== false) {
				if (get_transient(static::$filesTransient) !== false) {
				echo '<p><em>' . __('It seems there is an unfinished scan. '
						. 'It might have exceeded the maximum running time set '
						. 'by the server configuration. ', 'gallery_hierarchy')
						. '<a href="' . add_query_arg('start', 'rescan') . '">'
						. __('Please resume the scan', 'gallery_hierarchy')
						. '.</a></em></p>';
				}
				echo '<p>' . __('Last status from last scan: ', 'gallery_hierarchy')
						. $status . '</p>';
			}
		}
		echo '<h2>' . __('Have images you want to upload now?',
				'gallery_hierarchy') . '</h2>';
		echo '<p>' . __('Choose where you want to upload them and upload them '
				. 'using the form below.', 'gallery_hierarchy') . '</p>';
	}

	/**
	 * Returns an array containing information on the albums loaded and
	 * available.
	 *
	 * @return array Associative array containing the information on the albums.
	 *         The key to the array is the album label, and the information
	 *         contains the album name, description and class.
	 */
	static function &getAlbums() {
		if (!static::$albums) {
			static::$albums = array();
			foreach(get_declared_classes() as $className) {
				if( in_array('GHAlbum', class_implements($className)) ) {
					if (array_key_exists($className, static::$albums)) {
						/// @todo Need a warning message in here...
					} else {
						static::$albums[$className::label()] = array(
								'name' => $className::name(),
								'description' => $className::description(),
								'class' => $className
						);
					}
				}
			}
		}
		
		return static::$albums;
	}

	/**
	 * Handles the parsing of the logic part of the image selection.
	 * 
	 * @param $logic string String containing the logic to parse.
	 * @param $query string sprintf MySQL string to insert the values into
	 *               once parsed.
	 * @param $regex boolean If true, will escape the values as if they were
	 *               going into a regular expression
	 * @return string A string containing the MySQL statements of the parsed
	 *                logic.
	 */
	protected function parseLogic($logic, $query, $regex = false) {
		global $wpdb;
		
		// Split up the string into it's groups
		$logic = preg_split('/([&|]?)([()])([&|]?)/', $logic, -1,
				PREG_SPLIT_DELIM_CAPTURE);
		// Go through and parse each group
		foreach ($logic as $i => &$p) {
			switch ($p) {
				case '|':
					$logic[$i] = ' OR ';
					break;
				case '&':
					$logic[$i] = ' AND ';
				case '(':
				case ')':
				case '':
					break;
				default:
					$qPart = '';
					$logic[$i] = preg_split('/([&|])/', $p, -1,
							PREG_SPLIT_DELIM_CAPTURE);
					foreach ($p as $j => &$q) {
						switch ($q) {
							case '&':
								// Ensure we are not at the start or the end
								if ($j != 0 && $j != (count($p) - 1)) {
									$qPart .= ' AND ';
								}
								break;
							case '|':
								// Ensure we are not at the start or the end
								if ($j != 0 && $j != (count($p) - 1)) {
									$qPart .= ' OR ';
								}
								break;
							default:
								if ($regex) {
									$qPart .= sprintf($query, preg_quote($q, '\''));
								} else {
									$qPart .= sprintf($query, str_replace('\'', '\\\'', $q));
								}
								break;
						}
					}

					$logic[$i] = $qPart;
					break;
			}
		}

		return join('', $logic);
	}

	/**
	 * Generates the require parameters to be included in the <a> tag of the
	 * image for lightbox to work on the image.
	 *
	 * @param $image stdClass Object containing the image to generate the
	 *               parameters for.
	 * @param $group string Lightbox group to add the image to.
	 * @return string String to be added to the <a> tag containing the
	 *                parameters.
	 */
	static function lightboxData(stdClass &$image, $group = null,
			$caption = null) {
		$html = ' data-lightbox="' . ($group ? $group : uniqid()) . '"';
		
		if ($caption) {
			$html .= ' data-title="' . $caption . '"';
		}
		
		return $html;
	}

	/**
	 * Controls the generation of HTML for the shortcode replacement.
	 * This function will also fill out the attributes with the default
	 * values from the plugin options (does not use the Wordpress function to do
	 * this to save unnessecary calls to get_option).
	 *
	 * @param $atts Array Associative array containing the attriutes specified in
	 *              the shortcode
	 * @param $content string Content inside of the shortcode (shouldn't be any)
	 * @param $tag string Tag of the shortcode.
	 */
	static function doShortcode($atts, $content, $tag) {
		global $wpdb;

		$me = static::instance();

		// Fill out the attributes with the default
		switch ($tag) {
			case 'ghimage':
				$classO = 'gh_image_class';
				$classAO = 'gh_image_class_append';
				$caption = 'gh_image_description';
				break;
			case 'ghthumb':
				$classO = 'gh_thumb_class';
				$classAO = 'gh_thumb_class_append';
				$caption = 'gh_thumb_description';
				break;
			case 'ghalbum':
				$classO = 'gh_album_class';
				$classAO = 'gh_album_class_append';
				$caption = 'gh_album_description';
				break;
		}

		// `id="<id1>,<id2>,..."` - list of photos (some sort of query or list)
		// (`ghalbum` `ghthumbnail` `ghimage`)
		$parts = explode(',', $atts['id']);
		$ids = array();
		$idP = array();
		$folders = array();
		$taken = array();
		$query = array();

		foreach ($parts as $p => &$part) {
			if (strpos($part, '=') !== false) {
				$like = false;
				$part = explode('=',$part);
				if (isset($part[1]) && $part[1]) {
					switch($part[0]) {
						case 'rfolder':
							$like = true;
						case 'folder':
							$fids = explode('|', $part[1]);
							// Make sure fids are only numbers
							$f = 0;
							while ($f < count($fids)) {
								if (!gHisInt($fids[$f])) {
									array_splice($fids, $f, 1);
								} else {
									$f++;
								}
							}
							if ($fids && ($result = $wpdb->get_col('SELECT dir FROM '
									. $me->dirTable . ' WHERE id IN (' . join(',', $fids)
									. ')'))) {
								$folders = array_merge($folders, $result);
							}
							break;
						case 'taken':
							if (strpos($part[1], '|') !== false) {
								$part[1] = explode('|', $part[1]);

							// Check the dates are valid
							}
							break;
						case 'tags':
							$query[$part[0]] = $me->parseLogic($part[1], $part[0]
									. ' REGEXP \'(,|^)%s(,|$)\'', true);
							break;
						case 'title':
						case 'comment':
							$query[$part[0]] = $me->parseLogic($part[1], $part[0]
									. ' = \'%s\'');
							break;
						default:
							// Ignore as not valid
							continue;
					}
				} else {
					continue;
				}
			} else {
				if (is_numeric($part)) {
					$ids[] = $part;
					$idP[] = $p + 1;
				}
			}
		}

		$w = array();

		// Build Ids
		if ($ids) {
			$w[] = 'id IN (' . join(', ', $ids) . ')';
		}

		// Build Folders
		if ($folders) {
			$q = array();
			foreach ($folders as &$f) {
				$q[] = 'file LIKE (\'' . preg_replace('/([%\\\'])/', '\\\1', $f)
						. DIRECTORY_SEPARATOR . '%\')';
			}
			$query['folders'] = join(' OR ', $q);
		}

		if ($query) {
			$w[] = '((' . join(') AND (', array_values($query)) . ')' 
			. (isset($atts['include_excluded']) && $atts['include_excluded']
			? ' AND excluded=0' : '') . ')';
		}
		$q = 'SELECT * FROM ' . $me->imageTable 
				. ($w ? ' WHERE ' . join(' OR ', $w) : '');
		$images = $wpdb->get_results($q, OBJECT_K);

		// Rebuild array if based on ids @todo Implement attribute for this
		// Determine position of specified images based on positional weighting
		if ($ids) {
			$weight = 0;
			foreach ($idP as $i) {
				$weight += $i;
			}

			$weight = $weight/count($ids);

			$idImages = array();

			foreach ($ids as $i) {
				$idImages[$i] = $images[$i];
				unset($images[$i]);
			}

			$newImages = array();
			if ($weight <= (count($parts)/2)) {
				$images = array_merge($idImages, $images);
				/** @todo Remove
				foreach ($idImages as &$i) {
					$newImages[] = $i;
				}
				foreach ($images as &$i) {
					$newImages[] = $i;
				}*/
			} else {
				$images = array_merge($images, $idImages);
				/** @todo Remove
				foreach ($images as &$i) {
					$newImages[] = $i;
				}
				foreach ($idImages as &$i) {
					$newImages[] = $i;
				}*/
			}
		}

		// `group="<group1>"` - id for linking photos to scroll through with
		// lightbox (`ghthumbnail` `ghimage`)
		if (!isset($atts['group'])
				&& static::$settings->get_option('gh_group')) {
			$atts['group'] = 'group';
		}

		// `class="<class1> <class2> ...` - additional classes to put on the images
		// (`ghthumbnail` `ghimage`)
		if (!isset($atts['class'])
				|| static::$settings->get_option($classAO)) {
			if (!isset($atts['class'])) {
				$atts['class'] = '';
			}

			$atts['class'] .= ' ' . static::$settings->get_option($classO);
		}

		// `caption="(none|title|comment)"` - Type of caption to show. Default set
		// in plugin options (`ghalbum` `ghthumbnail` `ghimage`)
		$captionMap = array(
				'ghalbum' => 'gh_album_description',
				'ghthumb' => 'gh_thumb_description',
				'ghimage' => 'gh_image_description'
		);
		if (!isset($atts['caption'])) {
			$atts['caption'] = static::$settings->get_option($captionMap[$tag]);
		}

		// add_title
		$atts['add_title'] = static::$settings->get_option('gh_add_title');

		// `popup_caption="(none|title|comment)"` - Type of caption to show on
		//	popup. Default set in plugin options (`ghalbum` `ghthumbnail`
		// `ghimage`)
		if (!isset($atts['popup_caption'])) {
			$atts['popup_caption'] =
					static::$settings->get_option('gh_popup_description');
		}
		
		// `link="(none|popup|<url>)"` - URL link on image, by default it will be
		// the image url and will cause a lightbox popup
		/// @todo Make it a setting?
		if (!isset($atts['link'])) {
			$atts['link'] = 'popup';
		}

		switch ($tag) {
			case 'ghimage':
				// `size="(<width>x<height>)"` - size of image (`ghimage`)
				if (isset($atts['size']) && $atts['size']) {
					$atts['size'] = explode('x', $atts['size']);
					if (count($atts['size']) == 2 && gHisInt($atts['size'][0])
							&& gHisInt($atts['size'][1])) {
						$atts['size'] = array('width' => $atts['size'][0],
								'height' => $atts['size'][1]);
					} else {
						$atts['size'] = false;
					}
				} else {
					$atts['size'] = false;
				}

				$me->printImage($images, $atts);
				break;
			case 'ghthumb':
				$atts['type'] = static::$settings->get_option('gh_thumb_album');
			case 'ghalbum':
				// `type="<type1>"` - of album (`ghalbum`)
				// Check we have a valid album, if not, use the thumbnail one
				$albums = static::getAlbums();
				if (!isset($atts['type']) || !isset($albums[$atts['type']])) {
					$atts['type'] = static::$settings->get_option('gh_thumb_album');
				}

				if (isset($atts['type']) && isset($albums[$atts['type']])) {
					$albums[$atts['type']]['class']::printAlbum($images, $atts);
				}
				break;
		}
	}

	/**
	 * Prints galbum images.
	 *
	 * @param $images array Array containing stdClasses containing image
	 *                information.
	 * @param $options array Array containing shortcode attributes given to
	 *                 shortcode.
	 */
	protected function printImage(&$images, &$options) {
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

			echo '><img src="' . GHierarchy::getCImageURL($image, $options['size'])
				. '">';
			
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
	}

	/**
	 * Sets the two transients involved with scanning folders
	 * @param $scan string String to set scan transient to
	 * @param $status string String to set the status transient to
	 */
	static function setScanTransients($scan, $status, &$files = null) {
		if ($files) {
			set_transient(static::$filesTransient, json_encode($files),
					static::$filesTransientTime);
		}
		set_transient(static::$statusTransient, $status,
				static::$statusTransientTime);
		set_transient(static::$scanTransient, $scan,
				static::$scanTransientTime);
		static::$nextSet = time() + 10;
	}

	/**
	 * Controls the scanning of the directory for new directories and
	 * images.
	 * @param $fullScan boolean If true, the database will be completely
	 *                  rebuit.
	 * @todo Need to remove photos that are no longer there from the database
	 *       and the cache.
	 * @todo Need to be able to stop the scan part way through - possibly need
	 *       a stop transient?
	 */
	static function scan($fullScan = false) {
		global $wpdb;

		try {
			$me = static::instance();

			if ($fullScan ||
					!($files = json_decode(get_transient(static::$filesTransient), true))) {
				static::setScanTransients('scan', __('Scanning Folder',
						'gallery_hierarchy'));

				// Find all the folders and images
				$files = $me->scanFolder();
			}

			static::setScanTransients('scan', 
					__('Found ', 'gallery_hierarchy') . $files['totalDirs']
					. __(' folders and ', 'gallery_hierarchy') . $files['totalImages']
					.__(' images.', 'gallery_hierarchy'), $files);

			// Add directories
			// Get the current directories
			$dirs = $wpdb->get_results('SELECT dir,id FROM '
					. $me->dirTable, OBJECT_K);
			
			$i = 0;
			while (($dir = array_shift($files['dirs'])) !== null) {
				if (!isset($dirs[$dir])) {
					$me->registerDirectory($dir);
					$files['newDirs']++;
				} else {
					unset($dirs[$dir]);
				}

				// Report status
				if (static::$nextSet < time()) {
					static::setScanTransients('scan',  
							__("Added $files[newDirs] new folders. ", 'gallery_hierarchy')
							. count($files['dirs']) . __(' to check.', 'gallery_hierarchy'),
							$files); 
				}
			}

			/// @todo Remove any deleted directories
			//if (count($dirs)) {
			//	$wpdb->query($wpdb->prepare('DELETE FROM ' . $me->dirTable
			//			. 'WHERE id IN (' . . ')'));
			//}

			static::setScanTransients('scan',  __('Added ',
					'gallery_hierarchy') . $files['newDirs']
					. __(' folders, deleted ', 'gallery_hierarchy')
					. count($dirs) . __(' removed folders. Now adding ',
					'gallery_hierarchy') . $files['totalImages'] . __(' images.',
					'gallery_hierarchy'), $files);


			// Add images
			
			//Get current images
			$images = $wpdb->get_results('SELECT file,id,added FROM '
					. $me->imageTable, OBJECT_K);

			while(($image = array_shift($files['images'])) !== null) {
				$iPath = gHpath($me->imageDir, $image);
				
				if (isset($images[$image])) {
					if (filemtime($iPath) > $images[$image]->added) {
						$me->registerImage($image, $images[$image]->id);
						$files['newImages']++;
					} else if ($fullScan) {
						$me->registerImage($image, $images[$image]->id, true);
						$files['newImages']++;
					} else {
						unset($images[$image]);
					}
				} else {
					$me->registerImage($image);
					$files['newImages']++;
				}

				// Report status
				if (static::$nextSet < time()) {
					static::setScanTransients('scan',  
							__("Added $files[newImages] new images. ", 'gallery_hierarchy')
							. count($files['images']) . __(' to check.', 'gallery_hierarchy'),
							$files);
				}
			}
			
			//if (count($dirs)) {
			//	$wpdb->query($wpdb->prepare('DELETE FROM ' . $me->dirTable
			//			. 'WHERE id IN (' . . ')'));
			//}
			
			static::setScanTransients('scan',  __('Added ', 'gallery_hierarchy')
					. $files['newDirs'] . __(' folders, deleted ', 'gallery_hierarchy')
					. count($dirs) . __(' removed. ', 'gallery_hierarchy')
					. __('Added ', 'gallery_hierarchy') . $files['newImages']
					. __(' images, deleted ', 'gallery_hierarchy')
					. count($images) . __(' removed.', 'gallery_hierarchy'));
			delete_transient(static::$filesTransient);
		} catch (Exception $e) {
			static::setScanTransients('scan', __('Error: ',
					'gallery_hierarchy') . $e->getMessage());
		}


		// Delete transient
		delete_transient(static::$scanTransient);
	}

	/** 
	 * Scans a directory recursively for images. Any images or directories it
	 * finds will be registered in the database.
	 * For the ability to give useful updates, the scan happens first, and then
	 * the directories and images are processed.
	 *
	 * @param $dir string Path of directory to scan.
	 * @return array An array containing the number of images and directories
	 *               found.
	 */
	protected function scanFolder($dir = '', array &$files = null) {
		// Initialise count array
		if (is_null($files)) {
			$files = array(
					'dirs' => array(),
					'totalDirs' => 0,
					'newDirs' => 0,
					'images' => array(),
					'totalImages' => 0,
					'newImages' => 0,
			);
		}

		//
		if ($dir) {
			$path = gHpath($this->imageDir, $dir);
		} else {
			$path = $this->imageDir;
		}

		$files['path'] = $path;

		// Check is a directory
		if (!is_dir($path)) {
			throw new InvalidArgumentException(__("$path is not a directory",
					'gallery_hierarchy'));
		}

		if (!($res = opendir($path))) {
			throw new InvalidArgumentException(__("Could not open $path",
					'gallery_hierarchy'));
		}

		while (($file = readdir($res)) !== false) {
			$files['r'][] = $file;
			// Ignore dot files and directories
			if (substr($file, 0, 1) === '.') {
				continue;
			}

			$fpath = gHpath($path, $file);
			if ($dir) {
				$file = gHpath($dir, $file);
			}

			if (is_dir($fpath)) {
				$files['dirs'][] = $file;
				$files['totalDirs']++;
				$this->scanFolder($file, $files);
			} else if (in_array(finfo_file($this->finfo, $fpath),
					$this->imageMimes)) {
				$files['images'][] = $file;
				$files['totalImages']++;
			}

			// Report status
			if (static::$nextSet < time()) {
				static::setScanTransients('scanning', __('Scanning Folder...',
						'gallery_hierarchy') . $files['totalDirs'] . __(' folders found, ',
						'gallery_hierarchy') . $files['totalImages'] . __(' images found.',
						'gallery_hierarchy'));
				}
		}

		return $files;
	}

	/**
	 * Registers a directory in the database. The database is used to generate
	 * directory lists in the interface.
	 * @param $dir string Directory to add to the database
	 * @return true If the directory is new
	 * @return false If the directory is already in the database
	 */
	protected function registerDirectory($dir) {
		global $wpdb;

		// Remove trailing slash
		gHptrim($dir);

		// Check is a directory
		if (!is_dir(gHpath($this->imageDir, $dir))) {
			throw new InvalidArgumentException($dir . ' is not a valid directory');
		}

		$wpdb->insert($this->dirTable, array('dir' => $dir));
	}

	/**
	 * Extracts and parses XMP data from a given image
	 *
	 * @para $file string Path to the image to read the XMP data from
	 * @return array Array containing the XMP data
	 * @retval false If no XMP data existed
	 *
	 * Thanks to Bryan Geraghty for the parsing
	 */
	protected function getXMP($file) {
		$buffer = '';
		$startTag = '<x:xmpmeta';
		$endTag = '</x:xmpmeta>';
		$cSize = 50;

		$start = false;
		$end = false;
		$success = false;

		$fp = fopen($file, 'r');

		while (($chunk = fread($fp, $cSize)) !== false) {
			if ($chunk === "") {
				break;
			}

			$buffer .= $chunk;

			if ($start === false) {
				$start = strpos($buffer, $startTag);

				if ($start !== false) {
					$buffer = substr($buffer, $start);
					$start = 0;
				}
			}

			if ($start === 0) {
				$end = strpos($buffer, $endTag);

				if ($end !== false) {
					$buffer = substr($buffer, 0, $end + strlen($endTag));
					$success = true;
					break;
				}
			}

			if ($start !== 0 && strlen($buffer) > 2*$cSize) {
				$buffer = substr($buffer, $cSize);
			}
		}

		fclose($fp);

		if ($success) {
			$xmp = array();
			foreach ( array(
					'Creator Email' => '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
					'Owner Name'    => '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
					'Creation Date' => '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
					'Modification Date'     => '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
					'Label'         => '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
					'Credit'        => '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
					'Source'        => '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
					'Headline'      => '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
					'City'          => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
					'State'         => '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
					'Country'       => '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
					'Country Code'  => '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
					'Location'      => '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
					'Title'         => '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
					'Description'   => '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
					'Creator'       => '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
					'Keywords'      => '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
					'Hierarchical Keywords' => '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>'
			) as $key => $regex ) {
												 
			// get a single text string
			$xmp[$key] = preg_match( "/$regex/is", $buffer, $match ) ? $match[1] : '';

			// if string contains a list, then re-assign the variable as an array with the list elements
			$xmp[$key] = preg_match_all( "/<rdf:li[^>]*>([^>]*)<\/rdf:li>/is", $xmp[$key], $match ) ? $match[1] : $xmp[$key];

			// hierarchical keywords need to be split into a third dimension
			if ( ! empty( $xmp[$key] ) && $key == 'Hierarchical Keywords' ) {
				foreach ( $xmp[$key] as $li => $val ) $xmp[$key][$li] = explode( '|', $val );
					unset ( $li, $val );
				}
			}

			return $xmp;
		}

		return null;
	}

	/**
	 * Returns the path to the cached image for a specific image and
	 * width and height
	 *
	 * @param $image string Image path relative to the base directory
	 * @param $size array Array containing the new width ([0]) and height ([1])
	 * @return string Path to cached image relative to the cache base directory
	 */
	protected function getCImagePath($image, $size = null) {
		/// @todo Find a better way to do this
		$image = explode('.', $image);
		$ext = array_pop($image);
		$image = join('.', $image);

		$name = str_replace(DIRECTORY_SEPARATOR, '_', $image);

		if ($size) {
			$name .= '-' . $size['width'] . 'x' . $size['height'];
		}

		$name .= '.' . $ext;

		return $name;
	}

	/**
	 * Returns the URL to the image in the object given
	 *
	 * @param $image Object Row object containing information on image
	 * @return string URL to image
	 */
	static function getImageURL(stdClass &$image) {
		$me = static::instance();

		return gHurl($me->imageUrl, $image->file);
	}

	/**
	 * Returns the URL to a cached image of the image in the object
	 * given. This function will also ensure that the cached image file exists.
	 * If it does not, it will be created before returning the URL.
	 *
	 * @param $image Object Row object containing information on image
	 * @param $size Array Size of cached image to return. If null, will return
	 *              the thumbnail image. If false, will return the fullsized
	 *              image.
	 * @return string URL to image
	 */
	static function getCImageURL(stdClass &$image, $size = null) {
		$me = static::instance();

		if ($size === false) {
			return static::getImageURL($image);
		}
		$iName = $me->getCImagePath($image->file, $size);

		$iPath = gHpath($me->cacheDir, $iName);

		// Ensure the cached image exists
		if (!is_file($iPath)) {
			if (!$size) {
				$me->createThumbnail($iName);
			} else {
				$me->resizeImage($image->file, null, $size, false, $iPath);
			}
		}

		return gHurl($me->cacheUrl, $iName);
	}

	/**
	 * Creates a thumbnail image of a given image.
	 *
	 * @param $image string Path to the image relative to the base directory
	 * @param $imagick Imagick Imagick object already containing the image
	 * @note The Imagick object will be modified
	 */
	protected function createThumbnail($image, &$imagick = null) {
		$thumbnailSize = static::$settings->get_option('gh_thumbnail_size');
		$crop = static::$settings->get_option('gh_crop_thumbnails');

		$this->resizeImage($image, $imagick, $thumbnailSize, $crop);

		// Write thumbnail
		$tName = $this->getCImagePath($image);

		$tPath = gHpath($this->cacheDir, $tName);

		$imagick->writeImage($tPath);
	}

	/**
	 * Creates a thumbnail image of a given image.
	 *
	 * @param $image string Path to the image relative to the base directory
	 * @param $imagick Imagick Imagick object already containing the image.
	 *                         If not given, the image will be overwritten.
	 *                         If given, the image will not be written.
	 * @param $newSize Array Array containing the new width ([0]) and height
	 *                       ([1])
	 * @param $crop Boolean If true, the image will be resized and cropped to
	 *                      fit the exact given dimensions. If false, the
	 *                      image will be resized to fit inside the given
	 *                      dimensions.
	 * @param $newImagePath string Path to image to write to.
	 * @retval true If the image was written to.
	 * @retval false If the image was not written to.
	 * @note If given, the Imagick object will be modified!
	 */
	protected function resizeImage($image, &$imagick, $newSize, $crop = false, $newImagePath = false) {
		$write = false;
		if ($newImagePath) {
			$write = true;
		}

		if (!$imagick) {
			$write = true;
			$iPath = gHpath($this->imageDir, $image);

			// Check we have a valid image
			if (!is_file($iPath) || !in_array(finfo_file($this->finfo,
					$iPath), $this->imageMimes)) {
				return; /// @todo Do something worse
			}

			// Create an image (for resizing, rotating and thumbnail)
			$imagick = new Imagick();
			$imagick->readImage($iPath);
		}

		$cw = $imagick->getImageWidth();
		$ch = $imagick->getImageHeight();

		// First check if we need to do anything
		if ($cw <= $newSize['width'] && $ch <= $newSize['height']) {
			return null;
		}

		if ($crop) {
			$cRatio = $cw/$ch;
			$nRatio = $newSize['width']/$newSize['height'];
			if ($cRatio > $nRatio) { // Need to crop width
				$nWidth = $ch * $nRatio;
				$nx = ($cw - $nWidth) / 2;
				$imagick->cropImage($nWidth, $ch, $nx, 0);
				//$imagick->setImagePage(0, 0, 0, 0);
			} else if ($cRatio < $nRatio) { // Need to crop height
				$nHeight = $cw / $nRatio;
				$ny = ($ch - $nHeight) / 2;
				$imagick->cropImage($cw, $nHeight, 0, $ny);
				//$imagick->setImagePage(0, 0, 0, 0);
			}
		}

		// Resize the image
		$imagick->resizeImage($newSize['width'], $newSize['height'],
				imagick::FILTER_CATROM, 1, true);

		if ($write) {
			if ($newImagePath) {
				$imagick->writeImage($newImagePath);
			} else {
				$imagick->writeImage($iPath . '.new');
				rename($iPath . '.new', $iPath);
			}
			unset($imagick);
			return true;
		}

		return false;
	}

	/** 
	 * Registers an image in the database. If the image is not already in the
	 * database, the metadata will be extracted from the image, the image will
	 * be rotated and resized if required (according to the orientation metadata),
	 * a thumbnail will be created and the metadata will be stored in the
	 * database. If the image is already in the database, if not forced, we will
	 * assume that the image has been updated and rotate the image as per its
	 * orientation. If we are forced, we will not (we will assume that we just
	 * want to reread the metadata and recreate the thumbnail.
	 *
	 * @param $img string Image to add to the database
	 * @param $id number Id of image if it is already in the database
	 * @param $forced boolean If true, it will not try and rotate the image
	 * @retval true If the directory is new
	 * @retval false If the directory is already in the database
	 */
	protected function registerImage($img, $id = null, $forced = false) {
		global $wpdb;

		$iPath = gHpath($this->imageDir, $img);

		// Check we have a valid image
		if (!is_file($iPath) || !in_array(finfo_file($this->finfo,
				$iPath), $this->imageMimes)) {
			return; /// @todo Do something worse
		}

		// Create an image (for resizing, rotating and thumbnail)
		$imagick = new Imagick();
		$imagick->readImage($iPath);

		// Read metadata from the database
		if ($exif = exif_read_data($iPath, 0)) {
			$changed = false;

			if (!$id || ($id && !$forced)) {
				// Check the orientation
				if (isset($exif['Orientation'])) {
					$rotate = 0;
					$flip = '';

					switch ($exif['Orientation']) {
						case 5 : // vertical flip + 90 rotate right
							$flip = 'V';
						case 6 : // 90 rotate right
							$rotate = 90;
							break;
						case 7 : // horizontal flip + 90 rotate right
							$flip = 'H';
						case 8 : // 90 rotate left
							$rotate = -90;
							break;
						case 4 : // vertical flip
							$flip = 'V';
							break;
						case 3 : // 180 rotate left
							$rotate = 180;
							break;
						case 2 : // horizontal flip
							$flip = 'H';
							break;						
						case 1 : // no action in the case it doesn't need a rotation
						default:
							break; 
					}

					// Flip / rototate the image
					if ($rotate || $flip) {
						rotateImage($rotate, $flip, null, $imagick);
						$changed = true;
					}
					/// @todo Remove orientation from image?
				}

				// Resize the image if required
				if (static::$settings->get_option('gh_resize_images')) {
					if ($this->resizeImage(null, $imagick,
							static::$settings->get_option('gh_image_size')) !== null) {
						$changed = true;
					}
				}

				// Write changed image to file
				if ($changed) {
					// Write to a temporary file first
					$imagick->writeImage($iPath . '.new');
					rename($iPath . '.new', $iPath);
				}
			}
		}

		$width = $imagick->getImageWidth();
		$height = $imagick->getImageHeight();

		// Create thumbnail
		$this->createThumbnail($img, $imagick);

		// Build metadata information
		// Try and get XMP data
		$xmp = $this->getXMP($iPath);

		// Title
		if (isset($exif['Title'])) { // "windows" information
			$title = $exif['Title'];
		} else if (isset($xmp['Title'])) {
			$title = $xmp['Title'];
		} else {
			$title = '';
		}
		// Just grab the first title if it is an array
		if (is_array($title)) {
			$title = $title[0];
		}

		// Comment
		if (isset($exif['ImageDescription']) && $exif['ImageDescription']) {
			$comment = $exif['ImageDescription'];
		} else if (isset($exif['Comments']) && $exif['Comments']) { // "windows" information
			$comment = $exif['Comments'];
		} else if (isset($xmp['Description']) && $xmp['Description']) {
			$comment = $xmp['Description'];
		} else {
			$comment = '';
		}
		// Just grab the first comment if it is an array
		if (is_array($comment)) {
			$comment = $comment[0];
		}

		// Created Date
		$taken = '';
		if (isset($exif['DateTimeOriginal']) && $exif['DateTimeOriginal']) {
			$taken = mysqlDate($exif['DateTimeOriginal'], '%Y:%m:%d %H:%M:%S');
		}
		if (isset($exif['DateTime']) && $exif['DateTime']) {
			$taken = mysqlDate($exif['DateTime'], '%Y:%m:%d %H:%M:%S');
		}
		if (!$taken && isset($xmp['Creation Date']) && $xmp['Creation Date']) {
			$taken = mysqlDate($xmp['Creation Date'], '%Y-%m-%dT%H:%M:%S');
		} 
		if (!$taken) {
			$taken = '';
		}

		// Author
		if (isset($exif['Artist']) && $exif['Artist']) {
			$author = $exif['Artist'];
		} else if (isset($exif['Author']) && $exif['Author']) { // "windows" information
			$author = $exif['Author'];
		} else if (isset($xmp['Creator']) && $xmp['Creator']) {
			$author = $xmp['Creator'];
		} else {
			$author = '';
		}

		// Other metadata
		// Author (or whatever it is), focal length, camera model, iso,
		// apature

		// Build Tags
		if (isset($exif['Keywords']) && $exif['Keywords']) {
			$tags = $exif['Keywords'];
		} else if (isset($xmp['Keywords']) && $xmp['Keywords']) {
			$tags = $xmp['Keywords'];
		} else {
			$tags = array();
		}
		if (is_string($tags)) {
			$tags = preg_split(' *, *', $tags);
		}

		if (static::$settings->get_option('gh_folder_keywords')) {	
			$dir = dirname($img);

			$dir = explode(DIRECTORY_SEPARATOR, $dir);

			foreach ($dir as $d) {
				if (substr($d, 0, 1) !== '-') {
					$tags[] = $d;
				}
			}
		}

		if ($tags) {
			$tags = join(',', $tags);
		}

		// Write image to database
		$data = array(
				'file' => $img,
				'width' => $width,
				'height' => $height
		);
		if ($taken) $data['taken'] = $taken;
		if ($title) $data['title'] = $title;
		if ($comment) $data['comment'] = $comment;
		if ($tags) $data['tags'] = $tags;
		//if ($) $data[''] = $;

		if ($id) {
			$wpdb->update($this->imageTable, $data, array('id' => $id));
		} else {
			$wpdb->insert($this->imageTable, $data);
		}
	}

	/**
	 * Ensures that everything is set up for this plugin when it is activated
	 * including the required tables in the database.
	 * @todo Add index for dir and image names
	 */
	static function install() {
		global $wpdb;

		$me = static::instance();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/*
		 * We'll set the default character set and collation for this table.
		 * If we don't do this, some characters could end up being converted 
		 * to just ?'s when saved in our table.
		 */
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}

		// Check that the directory table is there
		$sql = "CREATE TABLE " . $me->dirTable . " ( \n"
				. "id smallint(5) NOT NULL AUTO_INCREMENT, \n"
				. "dir varchar(350) NOT NULL, \n"
			  . "added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, \n"
			  . "PRIMARY KEY (id) \n"
				. ") $charset_collate;";

		dbDelta( $sql );

		// Check that the image table is there
		$sql = "CREATE TABLE " . $me->imageTable . " ( \n"
				. "id smallint(5) NOT NULL AUTO_INCREMENT, \n"
				. "file text NOT NULL, \n"
				. "width smallint(5) unsigned NOT NULL, \n"
				. "height smallint(5) unsigned NOT NULL, \n"
			  . "added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, \n"
			  . "taken timestamp, \n"
				. "title text, \n"
				. "comment text, \n"
				. "tags text, \n"
				. "metadata text, \n"
				. "exclude tinyint(1) unsigned NOT NULL DEFAULT 0, \n"
			  . "PRIMARY KEY (id) \n"
				. ") $charset_collate;";

		dbDelta( $sql );
	}
}
