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
	protected $disabled = false;
	protected static $scanTransientTime = 60;
	protected static $statusTransientTime = DAY_IN_SECONDS;

	/// Rescan variables
	protected $nextSet;
	protected $baseDir;

	protected function  __construct() {
		global $wpdb;

		$this->finfo = finfo_open(FILEINFO_MIME_TYPE);
		/// @todo finfo_close($finfo);
		$this->dirTable = $wpdb->prefix . 'gHierarchyDirs';
		$this->imageTable = $wpdb->prefix . 'gHierarchyImages';
			
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
										'gh_folder_keywords' => array(
												'title' => __('Folders to Keywords', 'gallery_hierarchy'),
												'description' => __('If this option is selected, each '
														. 'folder name the image is inside will be added as a'
														. 'keyword to the image information in the database. '
														. 'Folder names can be ignored by adding a \'-\' to '
														. 'the front of the name.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										)
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
												'default' => 100
										),
										'gh_add_title' => array(
												'title' => __('Add Title', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'image title will be added to the start of the '
														. 'image comment when being displayed on the image.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
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
										'ghpopup_description' => array(
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
						)
				)
		);
		static::$settings = new WPSettings($options);

		$this->baseDir = gHpath(WP_CONTENT_DIR, static::$settings->get_option('gh_folder'));
		// Remove trailing slash
		$this->baseDir = gHptrim($this->baseDir);
	}

	/**
	 * Function to initialise the plugin when in the dashboard
	 */
	static function adminInit() {
		if (!$instance) {
			static::$instance = new self();

			add_action('admin_enqueue_scripts', array(&static::$instance, 'adminEnqueue'));
			add_action('admin_menu', array(&static::$instance, 'adminMenuInit'));
		}
	}

	protected static function instance() {
		if (!static::$instance) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Enqueues scripts and stylesheets used by Gallery Hierarchy in the admin
	 * pages.
	 */
	function adminEnqueue() {
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

	function checkFunctions() {
		if (!function_exists('finfo_file')) {
			echo '<div id="message" class="error">'
					. __('The required Fileinfo Extension is not installed. Please install it',
					'gallery_hierarchy') . '</div>';
			$this->disable = true;
		}
	}

	function gHgalleryPage() {
		$this->checkFunctions();

		$id = uniqid();
		echo '<h1>' . __('Gallery Hierarchy', 'gallery_hierarchy')
				. ' <a href="' . admin_url('admin.php?page=gHOptions')
				. '" class="add-new-h1">'
				. __('Add New', 'gallery_hierarchy') . '</a></h1>';
		echo '<div id="' . $id . '"></div>';
		echo '<script src="' . '"></script>';
	}

	function gHLoadPage() {
		$this->checkFunctions();

		echo '<h1>' . __('Load Images into Gallery Hierarchy', 'gallery_hierarchy')
				. '</h1>';
		
		if ($this->disable) {
			echo '<p>Loading disabled as something is missing. Please fix it.</p>';
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
					'gallery_hierarchy') . gHPath(WP_CONTENT_DIR, static::$settings->get_option('gh_folder')) . '</p>';
			echo '<a href="' . add_query_arg('start', 'rescan') . '" class="button">'
					. __('Rescan Directory', 'gallery_hierarchy') . '</a> <a href="'
					. add_query_arg('start', 'full') . '" class="button button-cancel">'
					. __('Force Rescan of All Images', 'gallery_hierarchy') . '</a>';
			if (($status = get_transient(static::$statusTransient)) !== false) {
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
	 * Sets the two transients involved with scanning folders
	 * @param $scan string String to set scan transient to
	 * @param $status string String to set the status transient to
	 */
	static function setScanTransients($scan, $status) {
		set_transient(static::$statusTransient, $status,
				static::$statusTransientTime);
		set_transient(static::$scanTransient, $scan,
				static::$scanTransientTime);
		$me->nextSet = time() + 30;
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

		$me = static::instance();

		// Check folder exists
		if (!is_dir($me->baseDir)) {
			if (!mkdir($me->baseDir, 0755, true)) {
				throw new InvalidArgumentException(__('Could not create directory '
						. $me->baseDir, 'gallery_hierarchy'));
			}

			delete_transient(static::$scanTransient);

			return;
		}

		static::setScanTransients('scan', __('Scanning Folder',
				'gallery_hierarchy'));
		$me->nextSet = time() + 30;

		// Find all the folders and images
		$files = $me->scanFolder();

		static::setScanTransients('scan', 
				__('Found ', 'gallery_hierarchy') . count($files['d']) 
				. __(' folders and ', 'gallery_hierarchy') . count($files['i'])
				.__(' images.', 'gallery_hierarchy'));

		// Add directories
		// Get the current directories
		$dirs = $wpdb->get_results('SELECT dir,id FROM '
				. $me->dirTable, OBJECT_K);
		
		$i = 0;
		while ($i < count($files['d'])) {
			static::setScanTransients('scan', 'here');
			$dir =& $files['d'][$i];
			if (!isset($dirs[$dir])) {
				$me->registerDirectory($dir);
				$i++;
			} else {
				array_splice($files['d'], $i, 1);
				unset($dirs[$dir]);
			}

			// Report status
			if ($me->nextSet < time()) {
				static::setScanTransients('scan',  __('Adding folders...',
						'gallery_hierarchy') . $added . '/' . $count . 
						__(' new (', 'gallery_hierarchy') . count($files['d']) . 
						__(') total.', 'gallery_hierarchy'));
			}
		}

		/// @todo Remove any deleted directories
		//if (count($dirs)) {
		//	$wpdb->query($wpdb->prepare('DELETE FROM ' . $me->dirTable
		//			. 'WHERE id IN (' . . ')'));
		//}

		static::setScanTransients('scan',  __('Added ', 'gallery_hierarchy')
				. count($files['d']) . __(' folders, deleted ', 'gallery_hierarchy')
				. count($dirs) . __(' removed folders.', 'gallery_hierarchy'));


		// Add images
		
		//Get current images
		$images = $wpdb->get_results('SELECT image,id FROM '
				. $me->imageTable, OBJECT_K);

		$i = 0;
		while ($i < count($files['i'])) {
			$image =& $files['i'][$i];

			if ($fullScan || !isset($images[$image])) {
				$me->registerImage($fp, $fullScan);
				$i++;
			} else {
				array_splice($files['i'], $i, 1);
				unset($images[$image]);
			}

			// Report status
			if ($me->nextSet < time()) {
				set_transient(static::$scanTransient, __('Adding folders...',
						'gallery_hierarchy') . $added . '/' . $count . 
						__(' new (', 'gallery_hierarchy') . count($files['d']) . 
						__(') total.', 'gallery_hierarchy'), 60);
				$me->nextSet = time() + 30;
			}
		}
		
		//if (count($dirs)) {
		//	$wpdb->query($wpdb->prepare('DELETE FROM ' . $me->dirTable
		//			. 'WHERE id IN (' . . ')'));
		//}
		
		static::setScanTransients('scan',  __('Added ', 'gallery_hierarchy')
				. count($files['d']) . __(' folders, deleted ', 'gallery_hierarchy')
				. count($dirs) . __(' removed. ', 'gallery_hierarchy')
				. __('Added ', 'gallery_hierarchy') . count($files['i'])
				. __(' images, deleted ', 'gallery_hierarchy')
				. count($images) . __(' removed.', 'gallery_hierarchy'));

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
	 * @param $count array Array containing current count
	 * @return array An array containing the number of images and directories
	 *               found.
	 */
	protected function scanFolder($dir = '', array &$files = null) {
		// Initialise count array
		if (is_null($files)) {
			$files = array('d' => array(), 'i' => array());
		}

		//
		if ($dir) {
			$path = gHpath($this->baseDir, $dir);
		} else {
			$path = $this->baseDir;
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
			// Ignore dot files and directories
			if (substr($file, 0, 1) === '.') {
				continue;
			}

			$fpath = gHpath($path, $file);
			$file = gHpath($dir, $file);

			if (is_dir($fpath)) {
				$files['d'][] = $file;
				$this->scanFolder($file, $files);
			} else if (in_array(finfo_file($this->finfo, $fpath),
					$this->imageMimes)) {
				$files['i'][] = $file;
			}

			// Report status
			if ($this->nextSet < time()) {
				set_transient(static::$statusTransient, __('Scanning Folder...',
						'gallery_hierarchy') . count($files['d']) . __(' folders found,',
						'gallery_hierarchy') . count($files['i']) . __(' images found.',
						'gallery_hierarchy'), 60);
				$this->nextSet = time() + 30;
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

		static::setScanTransients('scan', 'registering ' . $dir);

		// Check is a directory
		if (!is_dir(gHpath($this->baseDir, $dir))) {
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
	protected getXMP($file) {
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
			$xmp[$key] = preg_match( "/$regex/is", $xmp_raw, $match ) ? $match[1] : '';

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

	/** 
	 * Registers an image in the database. If the image is not already in the
	 * database, the metadata will be extracted from the image, the image will
	 * be rotated and resized if required (according to the orientation metadata),
	 * a thumbnail will be created and the metadata will be stored in the
	 * database.
	 * @param $img string Image to add to the database
	 * @return true If the directory is new
	 * @return false If the directory is already in the database
	 */
	protected function registerImage($img, $force = false) {
		global $wpdb;

		// Check we have a valid image
		if (!is_file($img) || !in_array(file_info($finfo, $img), $imageMimes)) {
		}

		// Break if image is already in the database
		if (!$force && ($wpdb->get_var($wpdb->prepare('SELECT COUNT(image) FROM \'%s\' '
				. 'WHERE image=\'%s\'', $this->imageTable, $img)))) {
			return false;
		}

		// Create an image (for resizing, rotating and thumbnail)
		$imagick = new Imagick();
		$imagick->readImage($img);

		// Read metadata from the database
		if (function_exists(exif_read_data) && $meta = exif_read_data($img, 'IFD0,COMMENT,EXIF')) {
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
				/// @todo Remove orientation from image?

				$changed = false;
				// Flip / rototate the image
				if ($rotate || $flip) {
					rotateImage($rotate, $flip, null, $imagick);
					$changed = true;
				}

				// Resize the image if required
				if (get_option()) {
					resizeToLimit(null, $imagick);
					$changed = true;
				}

				// Write changed image to file
				if ($changed) {
					$imagick->writeImage($img);
				}
			}
		}

		$width = $imagick->getImageWidth();
		$height = $imagick->getImageHeight();

		// Create thumbnail
		createThumbnail(null, $imagick);

		// Build Tags
		if (isset($exif['Keywords'])) {
			$tags = preg_split(' *, *', $exif['Keywords']);
		} else {
			$tags = array();
		}

		if ($this->options['folderKeywords']) {	
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
		$wpdb->query($wpdb->prepare('INSERT %s (image, width, height, taken, '
				. 'title, comment, tags) VALUE (\'%s\', %d, %d, %d, \'%s\', '
				. ' \'%s\', \'%s\') ON DUPLICATE '
				. 'KEY UPDATE width=%d, height=%d'
				. ($taken ? $wpdb->prepare(', taken=%d', $taken) : '')
				. ($title ? $wpdb->prepare(', title=\'%s\'', $title) : '')
				. ($comment ? $wpdb->prepare(', comment=\'%s\'', $comment) : '')
				. ($tags ? $wpdb->prepare(', tags=\'%s\'', $tags) : ''),
				$this->imageTable, $img, $width, $height, $taken, $title, $comment,
				$tags, $width, $height
		));

		return true;
	}

	/**
	 * Ensures that everything is set up for this plugin when it is activated
	 * including the required tables in the database.
	 * @todo Add index for dir and image names
	 */
	static function install() {
		global $wpdb;

		if (!$instance) {
			static::$instance = new self();
		}

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
		$sql = "CREATE TABLE " . static::$instance->dirTable . " ( \n"
				. "id smallint(5) NOT NULL AUTO_INCREMENT, \n"
				. "dir varchar(350) NOT NULL, \n"
			  . "added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, \n"
			  . "PRIMARY KEY (id) \n"
				. ") $charset_collate;";

		dbDelta( $sql );

		// Check that the image table is there
		$sql = "CREATE TABLE " . static::$instance->imageTable . " ( \n"
				. "id smallint(5) NOT NULL AUTO_INCREMENT, \n"
				. "image text NOT NULL, \n"
				. "width smallint(5) unsigned NOT NULL, \n"
				. "height smallint(5) unsigned NOT NULL, \n"
			  . "added timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE "
				. "CURRENT_TIMESTAMP, \n"
			  . "taken timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', \n"
				. "title text NOT NULL DEFAULT '', \n"
				. "comment text NOT NULL DEFAULT '', \n"
				. "tags text NOT NULL DEFAULT '', \n"
				. "metadata text NOT NULL DEFAULT '', \n"
			  . "PRIMARY KEY (id) \n"
				. ") $charset_collate;";

		dbDelta( $sql );
	}
}
