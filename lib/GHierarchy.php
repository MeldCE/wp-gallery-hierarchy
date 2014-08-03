<?php
require_once('utils.php');
require_once('GHSettings.php');

class GHierarchy {
	protected static $instance = null;
	protected $imageMimes = array('image/jpeg');
	protected $finfo;
	private $dirTable;
	private $imageTable;
	protected static $title;
	protected static $settings;
	protected static $scanTransient = 'gHScanTran';
	protected $disabled = false;

	/// Rescan variables
	protected $nextSet;
	protected $baseDir;

	protected function  __construct() {
		$this->finfo = finfo_open(FILEINFO_MIME_TYPE);
		/// @todo finfo_close($finfo);
		$this->dirTable = $wpdb->prefix . 'gHierarchyDirs';
		$this->imageTable = $wpdb->prefix . 'gHierarchyImages';
	}

	/**
	 * Function to initialise the plugin when in the dashboard
	 */
	static function init() {
		if (!$instance) {
			static::$instance = new self();
			
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

			static::$settings = new GHSettings($options);

			add_action('admin_enqueue_scripts', array(&static::$instance, 'adminEnqueue'));
			add_action('admin_menu', array(&static::$instance, 'adminMenuInit'));

			add_action('gh_rescan', array(&static::$instance, 'scan'));

		}
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
						$status = __('Starting rescan', 'gallery_hierarchy');
						set_transient(static::$scanTransient, $status, 60);
						wp_schedule_single_event(time(), 'gh_rescan');
						break;
					case 'full':
						$status = __('Forcing full rescan', 'gallery_hierarchy');
						set_transient(static::$scanTransient, $status, 60);
						wp_schedule_single_event(time(), 'gh_rescan', array(true));
						break;
				}
			}
		} else {
			$status = null;
		}
		echo '<h2>' . __('Manually uploaded files into the folder?',
				'gallery_hierarchy') . '</h2>';
		echo '<p>' . __('Use the buttons below to rescan the folder.',
				'gallery_hierarchy') . gHPath(WP_CONTENT_DIR, static::$settings->get_option('gh_folder')) . '</p>';
		// Check if a job is currently running
		if ($status || ($status = get_transient(static::$scanTransient)) !== false) {
			echo '<p>' . __("Scan currently running. Status: $status",
					'gallery_hierarchy') . '</p>';
			//echo '<a href="" class="button">' . __('Stop current scan',
			//		'gallery_hierarchy') . '</a>';
		} else {
			echo '<a href="' . add_query_arg('start', 'rescan') . '" class="button">'
					. __('Rescan Directory', 'gallery_hierarchy') . '</a> <a href="'
					. add_query_arg('start', 'full') . '" class="button button-cancel">'
					. __('Force Rescan of All Images', 'gallery_hierarchy') . '</a>';
		}
		echo '<h2>' . __('Have images you want to upload now?',
				'gallery_hierarchy') . '</h2>';
		echo '<p>' . __('Choose where you want to upload them and upload them '
				. 'using the form below.', 'gallery_hierarchy') . '</p>';
	}

	/**
	 * Controls the scanning of the directory for new directories and
	 * images.
	 * @param $fullScan boolean If true, the database will be completely
	 *                  rebuit.
	 * @todo Need to remove photos that are no longer there from the database
	 *       and the cache.
	 */
	static function scan($fullScan = false) {
		if (!$instance) {
			static::init();
		}

		static::$instance->baseDir = gHpath(WP_CONTENT_DIR, static::$settings->get_option('gh_folder'));
		// Remove trailing slash
		static::$instance->baseDir = gHptrim(static::$instance->baseDir);

		// Check folder exists
		if (!is_dir(static::$instance->baseDir)) {
			if (!mkdir(static::$instance->baseDir, 0755, true)) {
				throw new InvalidArgumentException(__('Could not create directory '
						. static::$instance->baseDir, 'gallery_hierarchy'));
			}

			delete_transient(static::$scanTransient);

			return;
		}

		set_transient(static::$scanTransient, __('Scanning Folder',
				'gallery_hierarchy'), 60);
		static::$instance->nextSet = time() + 30;

		// Find all the folders and images
		$files = static::$instance->scanFolder();

		// Add directories
		$count = 0;
		$added = 0;
		foreach ($files['d'] as $dir) {
			if (registerDirectory($fp)) {
				$added++;
			}
			$count++;

			// Report status
			if (static::$instance->nextSet < time()) {
				set_transient(static::$scanTransient, __('Adding folders...',
						'gallery_hierarchy') . $added . '/' . $count . 
						__(' new (', 'gallery_hierarchy') . count($files['d']) . 
						__(') total.', 'gallery_hierarchy'), 60);
				static::$instance->nextSet = time() + 30;
			}
		}

		// Add images
		$count = 0;
		$added = 0;
		foreach ($files['i'] as $file) {
			if (registerImage($fp, $fullScan)) {
				$added++;
			}
			$count++;

			// Report status
			if (static::$instance->nextSet < time()) {
				set_transient(static::$scanTransient, __('Adding folders...',
						'gallery_hierarchy') . $added . '/' . $count . 
						__(' new (', 'gallery_hierarchy') . count($files['d']) . 
						__(') total.', 'gallery_hierarchy'), 60);
				static::$instance->nextSet = time() + 30;
			}
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
	 * @param $count array Array containing current count
	 * @return array An array containing the number of images and directories
	 *               found.
	 */
	function scanFolder($dir = '', array &$files = null) {
		// Initialise count array
		if (is_null($count)) {
			$files = array('d' => array(), 'i' => array());
		}

		//
		if ($dir) {
			$path = gHpath($this->baseDir, $dir);
		} else {
			$path = $this->baseDir;
		}

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
				$files['d'][] = $fpath;
				$this->scanFolder($file, $files);
			} else if (in_array(finfo_file($finfo, $fpath), $imageMimes)) {
				$files['i'][] = $file;
			}

			// Report status
			if ($this->nextSet < time()) {
				set_transient(static::$scanTransient, __('Scanning Folder...',
						'gallery_hierarchy') . count($files['d']) . __(' folders found,',
						'gallery_hierarchy') . count($files['i']) . __(' images found.',
						'gallery_hierarchy'), 60);
				$this->nextSet = time() + 30;
			}
		}

		return $files;
	}

	/** Registers a directory in the database. The database is used to generate
	 *  directory lists in the interface.
	 *  @param $dir string Directory to add to the database
	 *  @return true If the directory is new
	 *  @return false If the directory is already in the database
	 */
	function registerDirectory(string $dir) {
		global $wpdb;

		// Remove trailing slash
		gHptrim($dir);

		// Check is a directory
		if (!is_dir($dir)) {
			raise;
		}

		/** Try to add
		 *  $wpdb->insert( $table, $data, $format );
		 *  @see https://codex.wordpress.org/Class_Reference/wpdb
		 */
		$wpdb->query(
				$wpdb->prepare('INSERT IGNORE \'%s\' (dir) VALUE (\'%s\')',
						$this->dirTable, $dir)
		);

		if ($wpdb->num_rows) {
			return true;
		}

		return false;
	}

	/** Registers an image in the database. If the image is not already in the
	 *  database, the metadata will be extracted from the image, the image will
	 *  be rotated and resized if required (according to the orientation metadata),
	 *  a thumbnail will be created and the metadata will be stored in the
	 *  database.
	 *  @param $img string Image to add to the database
	 *  @return true If the directory is new
	 *  @return false If the directory is already in the database
	 */
	function registerImage(string $img, $force = false) {
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

		// Build Keywords
		if (isset($exif['Keywords'])) {
			$keywords = preg_split(' *, *', $exif['Keywords']);
		} else {
			$keywords = array();
		}

		if ($this->options['folderKeywords']) {	
			$dir = dirname($img);

			$dir = explode(DIRECTORY_SEPARATOR, $dir);

			foreach ($dir as $d) {
				if (substr($d, 0, 1) !== '-') {
					$keywords[] = $d;
				}
			}
		}

		if ($keywords) {
			$keywords = join(',', $keywords);
		}

		// Write image to database
		$wpdb->query($wpdb->prepare('INSERT %s (image, width, height, taken, '
				. 'title, comment, keywords) VALUE (\'%s\', %d, %d, %d, \'%s\', '
				. ' \'%s\', \'%s\') ON DUPLICATE '
				. 'KEY UPDATE width=%d, height=%d'
				. ($taken ? $wpdb->prepare(', taken=%d', $taken) : '')
				. ($title ? $wpdb->prepare(', title=\'%s\'', $title) : '')
				. ($comment ? $wpdb->prepare(', comment=\'%s\'', $comment) : '')
				. ($keywords ? $wpdb->prepare(', keywords=\'%s\'', $keywords) : ''),
				$this->imageTable, $img, $width, $height, $taken, $title, $comment,
				$keywords, $width, $height
		));

		return true;
	}
}
