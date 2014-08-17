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
	protected static $statusTimeTransient = 'gHStatusTimeTran';
	protected static $filesTransient = 'gHFilesTran';
	// @todo ?? protected $disabled = array();
	protected $disable = false;
	protected static $scanTransientTime = 60;
	protected static $statusTransientTime = DAY_IN_SECONDS;
	protected static $statusTimeTransientTime = DAY_IN_SECONDS;
	protected static $filesTransientTime = DAY_IN_SECONDS;
	protected static $runAdminInit = false;
	protected static $dbVersion = 2;

	protected static $lp;

	protected static $imageTableFields = array(
			'id' => 'smallint(5) NOT NULL AUTO_INCREMENT',
			'file' => 'text NOT NULL',
			'width' => 'smallint(5) unsigned NOT NULL',
			'height' => 'smallint(5) unsigned NOT NULL',
			'updated' => 'timestamp NOT NULL',
			'taken' => 'timestamp',
			'title' => 'text',
			'comment' => 'text',
			'tags' => 'text',
			'metadata' => 'text',
			'exclude' => 'tinyint(1) unsigned NOT NULL DEFAULT 0',
	);

	protected static $dirTableFields = array(
			'id' => 'smallint(5) NOT NULL AUTO_INCREMENT',
			'dir' => 'varchar(350) NOT NULL',
			'added' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
	);

	protected static $albums = null;

	/// Rescan variables
	protected static $nextSet = 0;
	protected $imageDir;
	protected $imageUrl;
	protected $cacheDir;
	protected $cacheUrl;

	function __destruct() {
		if (static::$lp) {
			fclose(static::$lp);
		}
	}

	protected function  __construct() {
		global $wpdb;

		//static::$lp = fopen('galleruy-hierarchy.log', 'a');

		$this->finfo = finfo_open(FILEINFO_MIME_TYPE);
		/// @todo finfo_close($finfo);
		$this->dirTable = $wpdb->prefix . 'gHierarchyDirs';
		$this->imageTable = $wpdb->prefix . 'gHierarchyImages';
	
		// Make the array of albums
		$albums = array();
		$albumDescription = '';
		$albumsData = static::getAlbums();
		foreach ($albumsData as $a => $album) {
			$albumDescription .= $album['name'] . ' - ' . $album['description'] . '<br>';
			$albums[$a] = $album['name'];
		}

		$options = array(
				'title' => __('Gallery Hierarchy Options', 'gallery_hierarchy'),
				'id' => 'gHOptions',
				'useTabs' => true,
				'prefix' => 'gh_',
				'settings' => array(
						'gHFolders' => array(
								'title' => __('Folder Options', 'gallery_hierarchy'),
								'fields' => array(
										'folder' => array(
												'title' => __('Image Folder', 'gallery_hierarchy'),
												'description' => __('This should be a relative path '
														. 'inside of wp-content to a folder containing your '
														. 'images.', 'gallery_hierarchy'),
												'type' => 'folder',
												'default' => 'gHImages'
										),
										'cache_folder' => array(
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
										'thumbnail_size' => array(
												'title' => __('Thumbnail Dimensions',
														'gallery_hierarchy'),
												'description' => __('Size to make the thumbnails.',
														'gallery_hierarchy'),
												'type' => 'dimensions',
												'default' => array(200, 200)
										),
										'crop_thumbnails' => array(
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
										'resize_images' => array(
												'title' => __('Resize Images', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'images will be resized to the maximum '
														. 'dimensions '
														. 'specified in the Image Dimensions setting'
														. 'below.', 'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'rotate_images' => array(
												'title' => __('Rotate Images', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'images will be rotated to the correct '
														. 'orientation based on the image metadata.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'image_size' => array(
												'title' => __('Image Dimensions',
														'gallery_hierarchy'),
												'description' => __('Maximum size of the images.',
														'gallery_hierarchy'),
												'type' => 'dimensions',
												'default' => array(1100, 1100)
										),
										'folder_keywords' => array(
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
										'add_title' => array(
												'title' => __('Add Title', 'gallery_hierarchy'),
												'description' => __('If this option is selected, the '
														. 'image title will be added to the start of the '
														. 'image comment when being displayed on the image.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'group' => array(
												'title' => __('Group Images by Default',
														'gallery_hierarchy'),
												'description' => __('If this option is selected, '
														. 'images will be grouped by default into the '
														. 'group "group".',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => true
										),
										'thumb_album' => array(
												'title' => __('Album For Thumbnail Shortcut',
														'gallery_hierarchy'),
												'description' => __('What album type to use for the '
														. 'thumbnail shortcode.', 'gallery_hierarchy')
														. '<br>' . $albumDescription,
												'type' => 'select',
												'values' => $albums,
												'default' => 'thumbnail'
										),
										'thumb_class' => array(
												'title' => __('Default Thumbnail Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'thumbnail by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'thumb_class_append' => array(
												'title' => __('Append Specified Thumbnail Classes',
														'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'thumb_description' => array(
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
										'album_class' => array(
												'title' => __('Default Album Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'album by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'album_class_append' => array(
												'title' => __('Append Specified Album Classes',
														'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'album_description' => array(
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
										'image_class' => array(
												'title' => __('Default Image Class', 'gallery_hierarchy'),
												'description' => __('The classes to set on a '
														. 'image by default (space separated).',
														'gallery_hierarchy'),
												'type' => 'text',
												'default' => '',
										),
										'image_class_append' => array(
												'title' => __('Append Specified Image Classes', 'gallery_hierarchy'),
												'description' => __('If true, any classes given in '
														. 'the shortcode will be appended to the default '
														. ' classes given above.',
														'gallery_hierarchy'),
												'type' => 'boolean',
												'default' => false
										),
										'image_description' => array(
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
										'popup_description' => array(
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
										'num_images' => array(
												'title' => __('Images per Page', 'gallery_hierarchy'),
												'description' => __('Default number of images per '
														. 'page to show in the gallery view. Set to 0 '
														. 'for all of the images (could be really '
														. 'slow).', 'gallery_hierarchy'),
												'type' => 'number',
												'default' => 50
										),
										'db_version' => array(
												'title' => __('Database Version', 'gallery_hierarchy'),
												'description' => __('Stores the current database '
														. 'version to know when the database needs to be '
														. 'upgraded.', 'gallery_hierarchy'),
												'type' => 'internal',
										),
								)
						)
				)
		);
		static::$settings = new WPSettings($options);

		// Create path to image Directory
		$imageDir = static::$settings->folder;
		$this->imageDir = gHpath(WP_CONTENT_DIR, $imageDir);
		// Remove trailing slash
		$this->imageDir = gHptrim($this->imageDir);
		$this->imageUrl = content_url($imageDir);
		// Create path to cache directory
		$cacheDir = static::$settings->cache_folder;
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
		/// @todo @see http://codex.wordpress.org/I18n_for_WordPress_Developers
		wp_enqueue_script('ghierarchy', 
				plugins_url('/js/ghierarchy.min.js', dirname(__FILE__)));
		//wp_enqueue_style( 'dashicons' );
		wp_enqueue_style('ghierarchy',
				plugins_url('/css/ghierarchy.min.css', dirname(__FILE__)), array('dashicons'));
		wp_enqueue_script('jquery-ui-multiselect', 
				plugins_url('/lib/jquery-ui-multiselect/src/jquery.multiselect.min.js', dirname(__FILE__)),
				array('jquery', 'jquery-ui-core'));
		wp_enqueue_script('jquery-ui-multiselect-filter', 
				plugins_url('/lib/jquery-ui-multiselect/src/jquery.multiselect.filter.min.js', dirname(__FILE__)),
				array('jquery', 'jquery-ui-core', 'jquery-ui-multiselect'));
		wp_enqueue_style('jquery-ui-multiselect',
				plugins_url('/lib/jquery-ui-multiselect/jquery.multiselect.css', dirname(__FILE__)));
		wp_enqueue_style('jquery-ui-multiselect-filter',
				plugins_url('/lib/jquery-ui-multiselect/jquery.multiselect.filter.css', dirname(__FILE__)));
		wp_enqueue_script('media-upload');
		wp_enqueue_script('jquery-ui-timepicker', 
				plugins_url('/lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.js', dirname(__FILE__)),
				array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider'));
		wp_enqueue_style('jquery-ui-timerpicker',
				plugins_url('/lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui',
				plugins_url('/css/jquery-ui/jquery-ui.min.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui-structure',
				plugins_url('/css/jquery-ui/jquery-ui.structure.min.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui-theme',
				plugins_url('/css/jquery-ui/jquery-ui.theme.min.css', dirname(__FILE__)));
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
	 * Adds links to the plugin metadata on the Installed plugins page
	 */
	static function pluginMeta($links, $file) {
		/// @todo Make better
		if ( $file == plugin_basename(str_replace('lib', 'gallery-hierarchy.php', __DIR__))) {
			$links[] = '<a '
					. 'href="https://github.com/weldstudio/wp-gallery-hierarchy/issues"'
					. 'title="' . __('Issues', 'gallery_hierarchy') . '">'
					. __('Issues', 'gallery_hierarchy') . '</a>';
			$links[] = '<a href="http://gittip.weldce.com" title="'
					. __('Gift a weekly amount', 'gallery_hierarchy')
					. '" target="_blank">'
					. __('Gift a weekly amount', 'gallery_hierarchy') . '</a>';
			$links[] = '<a href="http://gift.weldce.com" title="'
					. __('Gift a little', 'gallery_hierarchy') . '" target="_blank">'
					. __('Gift a little', 'gallery_hierarchy') . '</a>';
		}

		return $links;
	}

	/**
	 * Function to create the Gallery Hierarchy admin menu.
	 * Called by @see gHierarchy::init()
	 */
	function adminMenuInit() {
		add_menu_page(__('Gallery Hierarchy', 'gallery_hierarchy'), 
				__('Gallery Hierarchy', 'gallery_hierarchy'), 'edit_posts',
				'gHierarchy', array(&$this, 'galleryPage'),
				'dashicons-format-gallery', 50);
		add_submenu_page('gHierarchy',
				__('Load Images into Gallery Hierarchy', 'gallery_hierarchy'),
				__('Load Images', 'gallery_hierarchy'), 'upload_files', 'gHLoad',
				array(&$this, 'loadPage'));
		add_submenu_page('gHierarchy',
				__('Gallery Hierarchy Options', 'gallery_hierarchy'),
				__('Options', 'gallery_hierarchy'), 'manage_options', 'gHOptions',
				array(static::$settings, 'printOptions'));
	}

	/**
	 * Handles AJAX calls from the gallery javascript
	 * @todo Look at merging some of the code with doShortcode
	 */
	static function ajaxGallery() {
		global $wpdb;

		$me = static::instance();

		// Build query
		$parts = array();

		// @todo Recursive
		if (isset($_POST['folders']) && is_array($_POST['folders'])) {
			// Check folder ids
			$f = 0;
			while ($f < count($_POST['folders'])) {
				if (!gHisInt($_POST['folders'][$f])) {
					array_splice($_POST['folders'], $f, 1);
				} else {
					$f++;
				}
			}
			if ($_POST['folders'] && ($result = $wpdb->get_col('SELECT dir FROM '
					. $me->dirTable . ' WHERE id IN (' . join(',', $_POST['folders'])
					. ')'))) {
				// Build Folders
				$q = array();
				foreach ($result as &$f) {
					$q[] = 'file LIKE (\'' . preg_replace('/([%\\\'])/', '\\\1', $f)
							. DIRECTORY_SEPARATOR . '%\')';
				}
				$parts[] = join(' OR ', $q);
			}
		}

		// Build date
		// Check dates are valid
		if (!isset($_POST['start'])
			|| !strptime($_POST['start'], '%Y-%m-%d %H:%i')) {
			$_POST['start'] = false;
		}
		if (!isset($_POST['end'])
			|| !strptime($_POST['end'], '%Y-%m-%d %H:%i')) {
			$_POST['end'] = false;
		}

		if ($_POST['start'] && $_POST['end']) {
			$parts[] = 'taken BETWEEN \'' . $_POST['start'] . '\' AND \''
					. $_POST['end'] . '\'';
		} else if ($_POST['start']) {
			$parts[] = 'taken >= \'' . $_POST['start'] . '\'';
		} else if ($_POST['end']) {
			$parts[] = 'taken <= \'' . $_POST['end'] . '\'';
		}

		// File name
		if (isset($_POST['name']) && $_POST['name']) {
			if(($q = $me->parseLogic($_POST['name'], 'file LIKE \'%%%s%%\''))) {
				$parts[] = $q;
			}
		}

		// Title
		if (isset($_POST['title']) && $_POST['title']) {
			if(($q = $me->parseLogic($_POST['title'], 'title LIKE \'%%%s%%\''))) {
				$parts[] = $q;
			}
		}

		// Comments
		if (isset($_POST['comments']) && $_POST['comments']) {
			if(($q = $me->parseLogic($_POST['comments'], 'comments LIKE \'%%%s%%\''))) {
				$parts[] = $q;
			}
		}

		// Tags
		if (isset($_POST['tags']) && $_POST['tags']) {
			if(($q = $me->parseLogic($_POST['tags'], 'tags REGEXP \'(,|^)%s(,|$)\'',
					true))) {
				$parts[] = $q;
			}
		}

		// Build Query
		$q = 'SELECT * FROM ' . $me->imageTable . ($parts ? ' WHERE (('
				.join(') AND (', $parts) . ')' . ')' : '');
		$images = $wpdb->get_results($q, ARRAY_A);

		header('Content-Type: application/json');

		echo json_encode($images);

		exit;
	}

	static function ajaxSave() {
		global $wpdb;

		$me = static::instance();

		// Go through data to see if we have valid changes
		if (is_array($_POST['saveData'])) {
			foreach ($_POST['saveData'] as $i => &$data) {
				if (gHisInt($i)) {
					$parts = array();
					foreach ($data as $f => &$v) {
						switch ($f) {
							case 'exclude':
								if ($v) {
									$v = 1;
								} else {
									$v = 0;
								}
								break;
							case 'taken':
							case 'title':
							case 'comment':
							case 'tags':
							case 'exclude':
							default:
								continue;
						}
						$parts[$f] = $v;
					}

					if ($parts) {
						if (!$wpdb->update($me->imageTable, $parts, array('id' => $i))) {
							echo 'Error: ' . __('There was an error updating the images. '
									. 'Please try again', 'gallery_hierarchy'); //$wpdb->last_error;
							exit;
						}
					}
				}
			}
		}

		echo __('Images updated successfully', 'gallery_hierarchy');
		exit;
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

	/**
	 * Prints the gallery/search HTML
	 */
	protected function printGallery($insert = false) {
		global $wpdb;
		$id = uniqid();
		// @todo Check if a scan has been run...? Check if we have images?
		echo '<h2>' . __('Search Filter', 'gallery_hierarchy') . '</h2>';
		// Folders field
		$folders = $wpdb->get_results('SELECT id, dir FROM ' . $this->dirTable
				. ' ORDER BY dir');
		echo '<p><label for="' . $id . 'folders">' . __('Folders:',
				'gallery_hierarchy') . '</label> <select name="' . $id . 'folders[]" '
				. 'id="' . $id . 'folders" multiple="multiple">';
		echo '<option value=""></option>';
		if ($folders) {
			foreach ($folders as &$f) {
				echo '<option value="' . $f->id . '">' . $f->dir . '</option>';
			}
		}
		echo '</select></p>';

		echo '<p><label for="' . $id . 'recurse">' . __('Include Subfolders:',
				'gallery_hierarchy') . '</label> <input type="checkbox" name="' . $id
				. 'recurse" id="' . $id . 'recurse"></p>';
	
		echo '<p><a onclick="gH.toggle(\'' . $id . '\', \'filter\', \''
				. __('advanced filter', 'gallery_hierarchy') . '\');" id="' . $id
				. 'filterLabel">' . __('Show advanced filter',
				'gallery_hierarchy') . '</a></p>';

		echo '<div id="' . $id . 'filter" class="hide">';
		// Date fields
		echo '<p><label for="' . $id . 'dates">' . __('Photos Taken Between:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="datetime" name="' . $id . 'start" id="' . $id
				. 'start">' . __(' and ', 'gallery_hierarchy')
				. '<input type="datetime" name="' . $id . 'end" id="' . $id
				. 'end"></p>';
		
		// Filename field
		echo '<p><label for="' . $id . 'name">' . __('Filename Contains:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'name" id="' . $id. 'name"></p>';
		
		// Title field
		echo '<p><label for="' . $id . 'title">' . __('Title Contains:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'title" id="' . $id. 'title"></p>';
		
		// Comment field
		echo '<p><label for="' . $id . 'comment">' . __('Comments Contain:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'comments" id="' . $id. 'comments"></p>';
		
		// Tags field
		echo '<p><label for="' . $id . 'tags">' . __('Has Tags:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'tags" id="' . $id. 'tags"></p>';

		echo '</div>';
	
		// Shortcode builder
		echo '<p><a onclick="gH.toggleBuilder(\'' . $id . '\');" id="' . $id
				. 'builderLabel">' . __('Enable shortcode builder',
				'gallery_hierarchy') . '</a></p>';
		// Builder div
		echo '<div id="' . $id . 'builder" class="hide">';
		// Shortcode type
		echo '<p><label for="' . $id . 'sctype">' . __('Shortcode Type:',
				'gallery_hierarchy') . '</label> <select name="' . $id . 'sctype" '
				. 'id="' . $id . 'sctype" onchange="gH.compileShortcode(\'' . $id
				. '\');">';
		echo '<option value="ghthumb">' . __('A thumbnail', 'gallery_hierarchy')
				. '</option>';
		echo '<option value="ghalbum">' . __('An album', 'gallery_hierarchy')
				. '</option>';
		echo '<option value="ghimage">' . __('An image', 'gallery_hierarchy')
				. '</option>';
		echo '</select>';
		// Shortcode window
		echo '<p>' . __('Shortcode:', 'gallery_hierarchy') . ' <span id="' . $id
				. 'shortcode"></span></p>';
		// Toggle selected
		echo '<p><a onclick="gH.toggleSelected(\'' . $id . '\');" id="' . $id
				. 'selectedLabel">' . __('Show currently selected images',
				'gallery_hierarchy') . '</a> <a onclick="gH.clearSelected(\'' . $id
				. '\');" id="' . $id . 'builderLabel">'
				. __('Clear selected images', 'gallery_hierarchy') . '</a></p>';
		echo '</div>';


		echo '<p><a onclick="gH.filter(\'' . $id . '\');" class="button" id="'
				. $id . 'filterButton">' . __('Filter', 'gallery_hierarchy') . '</a> ';
		echo '<a onclick="gH.save(\'' . $id . '\');" class="button" id="' . $id
				. 'saveButton">' . __('Save Image Changes', 'gallery_hierarchy')
				. '</a></p>';

		// Pagination
		echo '<p class="tablenav"><label for="' . $id . 'limit">' . __('Images per page:',
				'gallery_hierarchy') . '</label> <input type="number" name="' . $id
				. 'limit" id="' . $id. 'limit" onchange="gH.repage(\'' . $id
				. '\');" value="' . static::$settings->num_images . '"><span id="'
				. $id . 'pages" class="tablenav-pages"></span></p>';

		// Photo div
		echo '<div id="' . $id . 'pad" class="gHpad"></div>';
		echo '<script>gH.gallery(\'' . $id . '\', \'' . $this->imageUrl . '\', \''
				. $this->cacheUrl . '\', ' . ($insert ? 1 : 0) . ');</script>';
	}

	/**
	 * Prints the main Gallery Hierarchy page.
	 */
	function galleryPage() {
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
	function loadPage() {
		$this->checkFunctions();

		echo '<h1>' . __('Load Images into Gallery Hierarchy', 'gallery_hierarchy')
				. '</h1>';
		
		if ($this->disable) {
			echo '<p>' . __(' Loading disabled. Please fix it.', 'gallery_hierarchy')
					. '</p>';
			return;
		}

		if (isset($_REQUEST['remove'])) {
			wp_clear_scheduled_hook('gh_rescan');
		}

		// Check if we should start a job
		if (isset($_REQUEST['start'])) {
			// Check to make sure something hasn't already started
			if (($status = get_transient(static::$scanTransient)) === false) {
				$start = false;
				switch($_REQUEST['start']) {
					case 'rescan':
						//$status = __('Starting rescan...', 'gallery_hierarchy');
						$args = null;
						$start = true;
						break;
					case 'full':
						//$status = __('Forcing full rescan...', 'gallery_hierarchy');
						$args = array(true);
						$start = true;
						break;
				}
				if ($start) {
					//static::setScanTransients('start', $status);
					wp_schedule_single_event(time(), 'gh_rescan');//, $args);
				}
			}
		} else if (isset($_REQUEST['clear'])) {
			// Check to make sure something hasn't already started
			if (($status = get_transient(static::$scanTransient)) === false) {
				switch($_REQUEST['clear']) {
					case 'clear':
						delete_transient(static::$filesTransient);
						break;
				}
			}
		} else {
			$status = null;
		}
		echo '<h2>' . __('Manually uploaded files into the folder?',
				'gallery_hierarchy') . '</h2>';
		if (($status = get_transient(static::$statusTransient)) !== false) {
			if (($time = get_transient(static::$statusTimeTransient)) !== false) {
				$status .= ' <i>(' . __('Updated ', 'gallery_hierarchy')
						. date_i18n( get_option( 'date_format' ) . ' @ '
						. get_option( 'time_format'), $time) . ')</i>';
			}
		}
		// Check if a job is currently running
		if (get_transient(static::$scanTransient) !== false) {
			echo '<p>' . __("Scan currently running. Timeout is ",
					'gallery_hierarchy') . static::$scanTransientTime . 's</p>';
			if ($status) {
				echo '<p>' . __("Status: ",
						'gallery_hierarchy') . $status . '</p>';
			}
			//echo '<a href="" class="button">' . __('Stop current scan',
			//		'gallery_hierarchy') . '</a>';
		} else if ( ($time = wp_next_scheduled('gh_rescan'))) {
			echo '<p>' . __('Rescan scheduled for ', 'gallery_hierarchy')
					. strftime('%a (%e) at %H:%M:%S %Z', $time) . '. '
					. '<a href="' . add_query_arg('remove', '1') . '">'
					. __('Clear job', 'gallery_hierarchy') . '</a></p>';
			echo '<p>' . __('Once job starts, status updates will be shown here.',
					'gallery_hierarchy') . '</p>';
			// @todo Add a more information link for this problem
			echo '<p>' . __('Job hasn\'t started? You may need to visit your '
					. '<a href="' . get_option('site_url') . '">Wordpress site</a> '
					. 'to get it started.',
					'gallery_hierarchy') . '</p>';
		} else {
			echo '<p>' . __('Use the buttons below to rescan the folder.',
					'gallery_hierarchy') . '</p>';
			echo '<a href="' . add_query_arg('start', 'rescan') . '" class="button">'
					. __('Rescan Directory', 'gallery_hierarchy') . '</a> <a href="'
					. add_query_arg('start', 'full') . '" class="button button-cancel">'
					. __('Force Rescan of All Images', 'gallery_hierarchy') . '</a>';
			if ($status) {
				if (get_transient(static::$filesTransient) !== false) {
					$maxTime = ini_get('max_execution_time');
					echo '<p><em>' . __('It seems there is an unfinished scan. '
							. 'It might have exceeded the maximum running time set '
							. 'by the server configuration (' . $maxTime . 's). If the last '
							. 'status update was longer ago than ' . $maxTime . 's, ',
							'gallery_hierarchy')
							. '<a href="' . add_query_arg('start', 'rescan') . '">'
							. __('please resume the scan', 'gallery_hierarchy')
							. '</a> or '
							. '<a href="' . add_query_arg('clear', 'clear') . '">'
							. __('clear the scan', 'gallery_hierarchy')
							. '</a>.</em></p>';
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

		$html = '';

		// Fill out the attributes with the default
		switch ($tag) {
			case 'ghimage':
				$classO = 'image_class';
				$classAO = 'image_class_append';
				$caption = 'image_description';
				break;
			case 'ghthumb':
				$classO = 'thumb_class';
				$classAO = 'thumb_class_append';
				$caption = 'thumb_description';
				break;
			case 'ghalbum':
				$classO = 'album_class';
				$classAO = 'album_class_append';
				$caption = 'album_description';
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
				&& static::$settings->group) {
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
		$atts['add_title'] = static::$settings->add_title;

		// `popup_caption="(none|title|comment)"` - Type of caption to show on
		//	popup. Default set in plugin options (`ghalbum` `ghthumbnail`
		// `ghimage`)
		if (!isset($atts['popup_caption'])) {
			$atts['popup_caption'] =
					static::$settings->popup_description;
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

				$html = $me->printImage($images, $atts);
				break;
			case 'ghthumb':
				$atts['type'] = static::$settings->thumb_album;
			case 'ghalbum':
				// `type="<type1>"` - of album (`ghalbum`)
				// Check we have a valid album, if not, use the thumbnail one
				$albums = static::getAlbums();
				if (!isset($atts['type']) || !isset($albums[$atts['type']])) {
					$atts['type'] = static::$settings->thumb_album;
				}

				if (isset($atts['type']) && isset($albums[$atts['type']])) {
					$html = $albums[$atts['type']]['class']::printAlbum($images, $atts);
				}
				break;
		}
		
		return $html;
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
		$html = '';

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

			$html .= ' class= "' . $options['class'] . '"><img src="' . GHierarchy::getCImageURL($image, $options['size'])
				. '">';
			
			// Add comment
			switch ($options['caption']) {
				case 'none':
					break;
				case 'title':
					$html .= '<span>' . $image->title . '</span>';
					break;
				case 'caption':
					$html .= '<span>' . $image->caption . '</span>';
					break;
			}
					
			$html .= '</a>';
		}

		return $html;
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
		set_transient(static::$statusTimeTransient, time(),
				static::$statusTimeTransientTime);
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

			// Stats
			$files['newDirs'] = 0;
			$files['newImages'] = 0;
			$files['updatedImages'] = 0;
			$files['redoneImages'] = 0;

			static::setScanTransients('scan', 
					__("Found $files[totalDirs] folders and $files[totalImages] "
					. ' images.', 'gallery_hierarchy'), $files);

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

			static::setScanTransients('scan',  __("Added $files[newDirs] folders, ",
					'gallery_hierarchy')
					. count($dirs) . __(' folders total. Now looking at '
					. "$files[totalImages] images. ",
					'gallery_hierarchy'), $files);


			// Add images
			
			//Get current images
			$images = $wpdb->get_results('SELECT file,id,updated FROM ' 
					. $me->imageTable, OBJECT_K);

			while(($image = array_shift($files['images'])) !== null) {
				$iPath = gHpath($me->imageDir, $image);

				if (isset($images[$image])) {
					$updated = phpDate($images[$image]->updated);
					//$updated = $images[$image]->updated;
			
					// Don't bugger round with the timezones, just use utc
					$ftime = gmdate('U', filemtime($iPath));

					if (static::$lp) fwrite(static::$lp, "$iPath: $ftime > $updated?\n");

					if ($ftime > $updated) {
						if (static::$lp) fwrite(static::$lp, "Updating $iPath\n");
						$me->registerImage($image, $images[$image]->id);
						$files['updatedImages']++;
					} else if ($fullScan) {
						if (static::$lp) fwrite(static::$lp, "Redoing $iPath\n");
						$me->registerImage($image, $images[$image]->id, true);
						$files['redoneImages']++;
					} else {
						unset($images[$image]);
					}
				} else {
					if (static::$lp) fwrite(static::$lp, "Adding $iPath\n");
					$me->registerImage($image);
					$files['newImages']++;
				}

				// Report status
				if (static::$nextSet < time()) {
					static::setScanTransients('scan',
							($files['newImages'] ? __("Added $files[newImages] new images. ",
							'gallery_hierarchy') : '')
							. ($files['updatedImages'] ? __("Updated $files[updatedImages] "
							. "images. ",
							'gallery_hierarchy') : '')
							. ($files['redoneImages'] ? __("Redid $files[redoneImages] "
							. "images. ", 'gallery_hierarchy') : '')
							. count($files['images']) . __(' to check.', 'gallery_hierarchy'),
							$files);
				}
			}

			//if (count($dirs)) {
			//	$wpdb->query($wpdb->prepare('DELETE FROM ' . $me->dirTable
			//			. 'WHERE id IN (' . . ')'));
			//}

			$changes = ($files['newDirs'] ? __("Added $files[newDirs] folders",
					'gallery_hierarchy') : '')
					//. count($dirs) . __(' removed. ', 'gallery_hierarchy')
					. ($files['newImages'] ? __("Added $files[newImages] new images. ",
					'gallery_hierarchy') : '')
					. ($files['updatedImages'] ? __("Updated $files[updatedImages] "
					. "images. ",
					'gallery_hierarchy') : '')
					. ($files['redoneImages'] ? __("Redid $files[redoneImages] "
					. "images. ", 'gallery_hierarchy') : '');
					//. __('Deleted ', 'gallery_hierarchy')
					//. count($images) . __(' removed.', 'gallery_hierarchy')
			
			static::setScanTransients('scan',
					__('Scan complete. ', 'gallery_hierarchy')
					. ($changes ? __('Changes were: ', 'gallery_hierarchy') . $changes
					: __('No changes found.', 'gallery_hierarchy')));
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
					'images' => array(),
					'totalImages' => 0,
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
				static::setScanTransients('scanning', __('Scanning Folder. ',
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
		$thumbnailSize = static::$settings->thumbnail_size;
		$crop = static::$settings->crop_thumbnails;

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
	 * @retval true If the image was resized.
	 * @retval false If the image was not resized.
	 * @note If given, the Imagick object will be modified!
	 */
	protected function resizeImage($image, &$imagick, $newSize, $crop = false,
			$newImagePath = false) {
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
				return false; /// @todo Do something worse
			}

			// Create an image (for resizing, rotating and thumbnail)
			$imagick = new Imagick();
			$imagick->readImage($iPath);
		}

		$cw = $imagick->getImageWidth();
		$ch = $imagick->getImageHeight();

		// First check if we need to do anything
		if ($cw <= $newSize['width'] && $ch <= $newSize['height']) {
			return false;
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
		}

		return true;
	}

	/**
	 * Rotates and flips a given image.
	 *
	 * @param $image string Path to the image relative to the base directory
	 * @param $imagick Imagick Imagick object already containing the image.
	 *                         If not given, the image will be overwritten.
	 *                         If given, the image will not be written.
	 * @param $flip ('V'|'H'|false) Whether or not to flip the image in the
	 *              vertical plane, horizontal plane or both. Flip will
	 *              occur before the rotate.
	 * @param $rotate number Degrees to rotate the image clockwise
	 * @param $newImagePath string Path to image to write to.
	 * @retval true If the image was changed.
	 * @retval false If the image was not changed.
	 * @note If given, the Imagick object will be modified!
	 */
	protected function rotateImage($image, &$imagick, $flip = false, $rotate = 0,
			$newImagePath = false) {
		$changed = false;

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

		// Flip the image
		if ($flip) {
			if (strpos($flip, 'V') !== false) {
				$imagick->flipImage();
				$changed = true;
			}
			if (strpos($flip, 'H') !== false) {
				$imagick->flopImage();
				$changed = true;
			}
		}

		// Rotate the image
		if ($rotate) {
			$imagick->rotateImage(new ImagickPixel('none'), $rotate);
			$changed = true;
		}

		if ($write) {
			if ($newImagePath) {
				$imagick->writeImage($newImagePath);
			} else {
				$imagick->writeImage($iPath . '.new');
				rename($iPath . '.new', $iPath);
			}
			unset($imagick);
		}

		return $changed;
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
				if (static::$settings->rotate_images) {
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
							$changed = $this->rotateImage(null, $imagick, $flip, $rotate);
						}
						/// @todo Remove orientation from image?
					}
				}

				// Resize the image if required
				if (static::$settings->resize_images) {
					$changed = $this->resizeImage(null, $imagick,
							static::$settings->image_size) || $changed;
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

		if (static::$settings->folder_keywords) {	
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

		if (static::$lp) fwrite(static::$lp, "$img date will be " . time() . " " 
				. gmdate('Y-m-d H:i:s', time()) . "\n");

		// Write image to database
		$data = array(
				'file' => $img,
				'width' => $width,
				'height' => $height,
				'updated' => gmdate('Y-m-d H:i:s', time())
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
		static::installDatabase();
	}

	static function checkDatabase() {
		global $wpdb;
		$me = static::instance();

		$current = static::$settings->db_version;

		if (!$current) {
			/**
			 * Update version 2 - changing added field to updated and adding
			 * ON UPDATE CURRENT_TIMESTAMP to updated
			 */
			$wpdb->query('ALTER TABLE ' . $me->imageTable . ' CHANGE added '
					. 'updated timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'');
			static::$settings->db_version = static::$dbVersion;
		}
	}

	static function installDatabase() {
		global $wpdb;

		$me = static::instance();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$sql = $me->buildTableSql($me->dirTable, static::$dirTableFields, 'id');
		dbDelta($sql);

		$sql = $me->buildTableSql($me->imageTable,
				static::$imageTableFields, 'id');
		dbDelta($sql);

		static::$settings->db_version = static::$dbVersion;
	}

	/**
	 * Builds an SQL statement for creating a table.
	 * @param $table string Name of the table.
	 * @param $fields array Associative array of the field name and details
	 * @param $primary string The field name to use as the primary key
	 */
	protected function buildTableSql($table, $fields, $primary) {
		global $wpdb;

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
		$sql = 'CREATE TABLE ' . $table . " ( \n";

		foreach ($fields as $f => &$field) {
			$sql .= $f . ' ' . $field . ", \n";
		}

		$sql .= 'PRIMARY KEY  (' . $primary . ") \n";
		$sql .= ') ' . $charset_collate . ';';

		return $sql;
	}
}
