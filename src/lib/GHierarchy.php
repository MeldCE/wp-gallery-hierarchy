<?php
require_once('utils.php');
require_once('lib/WPSettings.php');

class GHierarchy {
	protected static $instance = null;
	protected $imageMimes = array('image/jpeg', 'image/png');
	protected $finfo;
	private $dirTable;
	private $imageTable;
	protected static $title;
	protected static $settings;
	// Transient to store when last job was started
	protected static $scanTransient = 'gHScanTran';
	// Transient to store last status message
	protected static $statusTransient = 'gHStatusTran';
	// Transient to store last update time
	protected static $statusTimeTransient = 'gHStatusTimeTran';
	// Transient to store job files
	protected static $filesTransient = 'gHFilesTran';
	// Scan status for directories and for images
	protected static $dirScanStatus ='';
	protected static $imageScanStatus = '';
	// @todo ?? protected $disabled = array();
	protected $disable = false;
	// How often should a status update be set
	protected static $statusUpdateTime = 10;
	// How much time is too much time before we decide to try and restart a
	// scan job
	protected static $statusUpdateTimeout = 30;
	// How often should have a cron job running to check if the scan job has
	// completed
	protected static $cronRestartTime = 100;
	protected static $statusTransientTime = DAY_IN_SECONDS;
	protected static $statusTimeTransientTime = DAY_IN_SECONDS;
	protected static $filesTransientTime = DAY_IN_SECONDS;
	protected static $runAdminInit = false;
	protected static $dbVersion = 4;
	protected $directories = false;

	protected $dbErrors = array();

	protected static $shortcodes = array('ghthumb', 'ghalbum', 'ghimage');

	protected static $lp;

	protected static $imageTableFields = array(
			'fields' => array(
				'id' => 'smallint(5) NOT NULL AUTO_INCREMENT',
				'file' => 'text NOT NULL',
				'dir_id' => 'smallint(5) unsigned',
				'width' => 'smallint(5) unsigned NOT NULL',
				'height' => 'smallint(5) unsigned NOT NULL',
				'updated' => 'timestamp NOT NULL',
				'taken' => 'timestamp',
				'title' => 'text',
				'comment' => 'text',
				'tags' => 'text',
				'metadata' => 'text',
				'exclude' => 'tinyint(1) unsigned NOT NULL DEFAULT 0',
			),
			'indexes' => array(
				array('type' => 'PRIMARY', 'field' => 'id'),
			)
	);

	protected static $dirTableFields = array(
			'fields' => array(
				'id' => 'smallint(5) NOT NULL AUTO_INCREMENT',
				'parent_id' => 'smallint(5)',
				'dir' => 'varchar(350) NOT NULL',
				'added' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP'
			),
			'indexes' => array(
				array('type' => 'PRIMARY', 'field' => 'id'),
				array('field' => 'dir'),
				array('field' => 'parent_id')
			)
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
		} // static::$lp
	}

	protected function  __construct() {
		global $wpdb;

		static::$lp = fopen('gallery-hierarchy.log', 'a');
		if (static::$lp) fwrite(static::$lp, "GHierarchy initiated at " 
				. time() . "\n"); // static::$lp
		

		if (function_exists(finfo_open)) {
			$this->finfo = finfo_open(FILEINFO_MIME_TYPE);
		} else {
			$this->finfo = false;
		}
		/// @todo finfo_close($finfo);
		$this->dirTable = $wpdb->prefix . 'gHierarchyDirs';
		$this->imageTable = $wpdb->prefix . 'gHierarchyImages';
	
		// Make the array of albums
		$albums = array();
		$albumDescription = '';
		foreach (static::getAlbums() as $a => $album) {
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
						'upload_folder' => array(
								'title' => __('Upload Folder', 'gallery_hierarchy'),
								'description' => __('This should be a relative path '
										. 'inside of wp-content to a folder that will be used to '
										. 'temporarily store uploaded files.',
										'gallery_hierarchy'),
								'type' => 'folder',
								'default' => 'gHUploads'
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
								'default' => array('width' => 200, 'height' => 200)
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
					'title' => __('Image Scanning Options', 'gallery_hierarchy'),
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
							'default' => array('width' => 1100, 'height' => 1100)
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
						'use_included_styles' => array(
							'title' => __('Use Included Styles', 'gallery_hierarchy'),
							'description' => __('If this option is selected, the '
									. 'shortcode images will be styled with the '
									. 'included styles and classes.',
									'gallery_hierarchy'),
							'type' => 'boolean',
							'default' => true
						),
						'add_title' => array(
							'title' => __('Add Title', 'gallery_hierarchy'),
							'description' => __('If this option is selected, the '
									. 'image title will be added to the start of the '
									. 'image comment when being displayed on the image.',
									'gallery_hierarchy'),
							'type' => 'boolean',
							'default' => true
						),
						'title_glue' => array(
							'title' => __('Add Title Glue', 'gallery_hierarchy'),
							'description' => __('If the Add Title option is selected above, '
									. 'this text will be used to glue the title to the start of '
									. 'the comment.', 'gallery_hierarchy'),
							'type' => 'text',
							'default' => '. '
						),
						'smart_glue' => array(
							'title' => __('Smart Add Title Glue', 'gallery_hierarchy'),
							'description' => __('If this is selected, Gallery Hierarchy '
									. 'choose the best glue to use for adding the title onto '
									. 'the comment. If the comment starts with a capital letter '
									. '\'. \' will be used as the glue, otherwise \', \' will '
									. 'be used.', 'gallery_hierarchy'),
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
							'default' => 'thumbnails'
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
						),
						'floater' => array(
							'title' => __('Image Popup Script',
									'gallery_hierarchy'),
							'description' => __('What image popup script to use '
									. 'when images are clicked on. The default is to use '
									. 'Fancybox (included). You can also use Lightbox. To use '
									. 'lightbox, install the minimised Javascript file, '
									. 'lightbox.min.js, and the map file, lightbox.min.map, '
									. 'into the folder (wp-content/plugins/gallery_hierarchy/)'
									. 'lib/js/ and the stylesheet, lightbox.css, into '
									. 'the folder lib/css/.', 'gallery_hierarchy'),
							'type' => 'select',
							'values' => array(
								'none' => 'None',
								'fancybox' => 'Fancybox',
								'lightbox' => 'Lightbox'
							),
							'default' => 'fancybox'
						),
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
							'default' => 50,
						),
						'upload_chunk_size' => array(
							'title' => __('File upload chunk size', 'gallery_hierarchy'),
							'description' => __('Size (in kB) to break files into for '
									. 'upload. Can be used to work around server maximum upload '
									. 'file size settings. Set to 0 not chunk files to upload.',
									'gallery_hierarchy'),
							'type' => 'number',
							'default' => 1024,
						),
						'local_resize' => array(
							'title' => __('Resize Locally During Upload',
									'gallery_hierarchy'),
							'description' => __('If this option is checked, images '
									. 'will be resized prior to being uploaded using '
									. 'the upload tool.', 'gallery_hierarchy'),
							'type' => 'boolean',
							'default' => 'true'
						),
						'size_limit' => array(
							'title' => __('Image Upload Size Limit', 'gallery_hierarchy'),
							'description' => __('The file size limit of the files being '
									. 'uploaded in MB. Set to 0 for no limit.',
									'gallery_hierarchy'),
							'type' => 'number',
							'default' => 10,
						),
						'load_full_tree' => array(
							'title' => __('Load the full directory structure',
									'gallery_hierarchy'),
							'description' => __('If this option is checked, the full '
									. 'directory structure will be loaded when the folder '
									. 'selection tool is loaded, instead of just the top level '
									. 'directories (meaning it will not have to load them).',
									'gallery_hierarchy'),
							'type' => 'boolean',
							'default' => 'false'
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
		// Create path to upload directory
		$uploadDir = static::$settings->upload_folder;
		$this->uploadDir = gHpath(WP_CONTENT_DIR, $uploadDir);
		$this->uploadUrl = content_url($uploadDir);
		// Remove trailing slash
		$this->uploadDir = gHptrim($this->uploadDir);
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
	static function adminEnqueue($hook_suffix) {
		echo "\n<!-- TWS $hook_suffix -->\n";
		static::enqueue();
		if ($hook_suffix == 'gallery-hierarchy_page_gHLoad') {
			wp_enqueue_script('moxie', 
					plugins_url('/lib/js/moxie.min.js', dirname(__FILE__)));
			wp_enqueue_script('plupload-full', 
					plugins_url('/lib/js/plupload.full.min.js', dirname(__FILE__)),
					array('moxie'));
			wp_enqueue_script('plupload-queue', 
					plugins_url('/lib/js/jquery.plupload.queue.min.js', dirname(__FILE__)),
					array('plupload-full'));
			wp_enqueue_script('plupload-i18n', 
					plugins_url('/lib/i18n/en.js', dirname(__FILE__)));
			wp_enqueue_style('plupload',
					plugins_url('/lib/css/jquery.plupload.queue.css', dirname(__FILE__)));
		}
		if ($hook_suffix == 'gallery-hierarchy_page_gHOptions') {
			wp_enqueue_style('wpsettings',
					plugins_url('/lib/css/wpsettings.min.css', dirname(__FILE__)));
			wp_enqueue_script('wpsettings', 
					plugins_url('/lib/js/wpsettings.min.js', dirname(__FILE__)),
					array('jquery'));
		}
		/// @todo @see http://codex.wordpress.org/I18n_for_WordPress_Developers
		wp_enqueue_script('ghierarchy', 
				plugins_url('/js/ghierarchy.js', dirname(__FILE__)));
		//wp_enqueue_style( 'dashicons' );
		wp_enqueue_style('ghierarchy',
				plugins_url('/css/ghierarchy.min.css', dirname(__FILE__)), array('dashicons'));
		wp_enqueue_script('media-upload');
		wp_enqueue_script('jquery-ui-timepicker', 
				plugins_url('/lib/js/jquery-ui-timepicker-addon.js', dirname(__FILE__)),
				array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider'));
		wp_enqueue_style('jquery-ui-timerpicker',
				plugins_url('/lib/css/jquery-ui-timepicker-addon.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui',
				plugins_url('/lib/css/jquery-ui.min.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui-structure',
				plugins_url('/lib/css/jquery-ui.structure.min.css', dirname(__FILE__)));
		wp_enqueue_style('ghierarchy-jquery-ui-theme',
				plugins_url('/lib/css/jquery-ui.theme.min.css', dirname(__FILE__)));
		wp_enqueue_style('jquery-folders',
				plugins_url('/lib/css/folders.css', dirname(__FILE__)));
	}

	/**
	 * Enqueues scripts and stylesheets used by Gallery Hierarchy.
	 */
	static function enqueue() {
		$me = static::instance();

		// Enqueue lightbox script
		switch (static::$settings->floater) {
			case 'lightbox':
				wp_enqueue_script('lightbox', 
						plugins_url('/lib/js/lightbox.min.js', dirname(__FILE__)));
				wp_enqueue_style('lightbox',
						plugins_url('/lib/css/lightbox.css', dirname(__FILE__)));
				break;
			case 'fancybox':
				wp_enqueue_script('fancybox-js', 
						plugins_url('/lib/js/jquery.fancybox-1.3.4.pack.js',
						dirname(__FILE__)), array('jquery', 'fancybox-mouse'));
				wp_enqueue_script('fancybox-mouse', 
						plugins_url('/lib/js/jquery.mousewheel-3.0.4.pack.js',
						dirname(__FILE__)), array('jquery'));
				wp_enqueue_style('fancybox',
						plugins_url('/lib/css/jquery.fancybox-1.3.4.css',
						dirname(__FILE__)));
				break;
		}
		if (static::$settings->use_included_styles) {
			wp_enqueue_style('gallery_hierarchy-basic',
					plugins_url('/css/basicStyle.min.css', dirname(__FILE__)));
		}
	}

	static function head() {
		$me = static::instance();

		// Enqueue lightbox script
		switch (static::$settings->floater) {
			case 'fancybox':
				echo '<script type="text/javascript">'
						. 'jQuery(function() {'
						. 'jQuery("a[data-fancybox=\'fancybox\']").fancybox();'
						. '});'
						. '</script>';
				break;
		}
	}

	static function adminPrintInit() {
		$me = static::instance();

		echo '<script type="text/javascript">'
			. 'addLoadEvent(function() {'
			. 'gH.init({'
			. 'imageUrl: "' . $me->imageUrl . '",'
			. 'cacheUrl: "' . $me->cacheUrl . '",'
			. '});'
			. '});'
			. '</script>';
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
	 * Adds the Gallery Hierarchy link to the Add Media dialog
	 */
	static function uploadTabs($tabs) {
		$tabs['ghierarchy'] = __('Gallery Hierarchy', 'gallery_hierarchy');

		return $tabs;
	}

	/**
	 * Prints the Gallery Hierarchy Add Media tab.
	 */
	static function addMediaTab() {
		$me = static::instance();
		$error = false;
		$shortcode = false;

		if (isset($_REQUEST['shortcode'])) {
			$shortcode = str_replace('\\"', '"', $_REQUEST['shortcode']);
			if (($shortcode = json_decode($shortcode, true)) !== null) {
				if (($shortcode = $me->generateShortcode($shortcode))) {
				} else {
					$error = 'Could not generate shortcode from given data.';
				}
			} else {
				$error = 'Could not decode given data.';
			}
		}
		
		wp_iframe(array($me, 'printGallery'), true, $error);
		
		if ($shortcode) {
			media_send_to_editor($shortcode);
		}
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
	 * @todo Add nonce
	 */
	static function ajaxGallery() {
		global $wpdb;

		$me = static::instance();

		header('Content-Type: application/json');

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
			if ($_POST['folders']) {
				if ($_POST['recurse']) {
					$fids = $wpdb->get_col('SELECT id FROM ' . $me->dirTable
							. ' WHERE dir REGEXP CONCAT(\'^\', (SELECT '
							. 'GROUP_CONCAT(dir SEPARATOR \'|\') FROM '
							. $me->dirTable . ' WHERE id IN ('
							. join(', ', $_POST['folders']) . ')))');
				} else {
					$fids = $wpdb->get_col('SELECT id FROM ' . $me->dirTable
							. ' WHERE id IN (' . join(', ', $_POST['folders']) . ')');
				}
				
				if ($fids) {
					$parts[] = 'dir_id IN (' . join(', ', $fids) . ')';
				}
			}
		}

		if (static::$lp) fwrite(static::$lp, "Got start of '$_POST[start]' and "
				. "stop of '$_POST[end]'\n"); // static::$lp

		// Build date
		// Check dates are valid
		if (!isset($_POST['start'])) {
			$_POST['start'] = false;
		} else if ($_POST['start']
				&& !strptime($_POST['start'], '%Y-%m-%d %H:%M')) {
			echo json_encode(array(
				'data' => __('Invalid taken after date. Please reenter and try again.',
						'gallery_hierarchy')
			));
			return;
		}

		if (!isset($_POST['end'])) {
			$_POST['end'] = false;
		} else if ($_POST['end'] && !strptime($_POST['end'], '%Y-%m-%d %H:%M')) {
			echo json_encode(array(
				'data' => __('Invalid taken before date. Please reenter and try again.',
						'gallery_hierarchy')
			));
			return;
		}

		if (static::$lp) fwrite(static::$lp, "Parsed to start of '$_POST[start]' "
				. "and stop of '$_POST[end]'\n"); // static::$lp

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
		$q = 'SELECT *, CONCAT((SELECT dir FROM ' . $me->dirTable . ' WHERE id = '
				.'dir_id), \'' . DIRECTORY_SEPARATOR . '\', file) '
				. 'AS path FROM ' . $me->imageTable . ($parts ? ' WHERE (('
				.join(') AND (', $parts) . ')' . ')' : '') . ' ORDER BY taken';


		if (static::$lp) fwrite(static::$lp, "Ajax gallery SQL command is $q\n");
	
		$images = $wpdb->get_results($q, ARRAY_A);

		echo json_encode($images);

		exit;
	}

	static function ajaxSave() {
		global $wpdb;

		$me = static::instance();
		if (current_user_can('edit_others_pages')) {
			switch($_REQUEST['a']) {
				case 'save':
					// Go through data to see if we have valid changes
					if (is_array($_POST['data'])) {
						foreach ($_POST['data'] as $i => &$data) {
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
										$response = array(
											'error' => __('There was an error updating the images. '
													. 'Please try again', 'gallery_hierarchy') //$wpdb->last_error;
										);
										break;
									}
								}
							}
						}
					}
					
					$response = array(
						'msg' => __('Images updated successfully', 'gallery_hierarchy')
					);

					break;
				case 'remove':
				case 'delete':
					if (!isset($_REQUEST['id']) || !gHisInt($_REQUEST['id'])) {
						$response = array(
							'error' => __('Invalid image',
									'gallery_hierarchy')
						);
						break;
					}

					// Get the directory
					if (!($file = $wpdb->get_var($wpdb->prepare('SELECT CONCAT((SELECT '
							. 'dir FROM ' . $me->dirTable . ' WHERE id = dir_id), %s, '
							. 'file) FROM ' . $me->imageTable . ' WHERE id = %d',
							DIRECTORY_SEPARATOR, $_REQUEST['id'])))) {
						$response = array(
							'error' => __('Didn\'t receive a valid image to remove/delete.',
									'gallery_hierarchy'),
							'cmd' => $wpdb->prepare('SELECT CONCAT((SELECT '
							. 'dir FROM ' . $me->dirTable . ' WHERE id = dir_id), %s, '
							. 'file) FROM ' . $me->imageTable . ' WHERE id = %d',
							DIRECTORY_SEPARATOR, $_REQUEST['id'])
						);
						break;
					}

					// Delete if we need to
					if ($_REQUEST['a'] == 'delete') {
						// Find and delete files
						$path = gHPath($me->imageDir, $file);
						if (is_file($path)) {
							unlink($path);
						}

						$me->delCacheFiles($path);
					}

					// Remove from database
					if ($wpdb->delete($me->imageTable, array('id' => $_REQUEST['id']))) {
						if ($_REQUEST['a'] == 'delete') {
							$response = array(
								'msg' => __('Image deleted successfully', 'gallery_hierarchy')
							);
						} else {
							$response = array(
								'msg' => __('Image removed successfully', 'gallery_hierarchy')
							);
						}

						break;
					} else {
						$response = array(
							'error' => __('Could not confirm the removal of the image from '
									. 'the database. Please try again.', 'gallery_hierarchy')
						);
					}
					break;
				default:
					$response = array(
						'error' => __('Unknown action', 'gallery_hierarchy')
					);
			}
		} else {
			$response = array(
				'error' => __('Permission Denied', 'gallery_hierarchy')
			);
			break;
		}


		header('Content-Type: application/json');
		
		echo json_encode($response);

		exit;
	}

	/**
	 * Write what has been written to the output buffer and close the connection
	 * to the browser by telling it to close the connection and giving it a
	 * content length of the contents in the output buffer.
	 */
	static protected function closeConnection() {
		if (!($size = ob_get_length())) {
			$size = 0;
		}
		if (static::$lp) fwrite(static::$lp, "Closing connection with content "
				. "length of $size:\n" . ob_get_contents() . "\n"); // static::$lp

		header("Content-Encoding: none");
		header("Content-Length: $size");
		header("Connection: close");
		if (session_id()) session_write_close();
		ob_end_flush();     // Strange behaviour, will not work
		flush();            // Unless both are called !
		ob_end_clean();
		//if (function_exists('fastcgi_finish_request')) {
		//	if (static::$lp) fwrite(static::$lp, "Calling fastcgi_finish_request\n");
		//	fastcgi_finish_request();
		//}
	}

	/**
	 * Handles requests from the scanning interface
	 */
	static function ajaxScan() {
		$me = static::$instance;

		// Check what we are doing
		if (isset($_REQUEST['a'])) {
			$fullScan = false;
			switch ($_REQUEST['a']) {
				case'full':
					$fullScan = true;
				case 'rescan':
				case 'resume':
					if (!(isset($_REQUEST['d']) || $_REQUEST['d'] == 'true')) {
						//ob_start();
						header('Content-Type: application/json');
						print json_encode(array(
							'status' => __('Starting...', 'gallery_hierarchy'),
							'startTime' => time()
						));

						static::closeConnection();
					}

					static::scan($fullScan);
					break;
				case 'remove': // Remove scheduled job...? Do we need?
					wp_clear_scheduled_hook('gh_rescan');
					break;
				case 'status':
					header('Content-Type: application/json');
					switch (static::checkScanJob()) {
						case 0: // None running
						case 1: // Still running
							echo json_encode(static::getScanStatus());
							break;
						case 2: // Most likely killed
							$status = static::getScanStatus();
							$status['status'] .= ' Most likely killed, trying restart...';
							echo json_encode($status);
							static::closeConnection();
							static::scan($fullScan);
							break;
					}
					
					break;
			}
		}
		
		exit;
	}

	/**
	 * Handles the uploading of files from plUpload
	 */
	static function ajaxUpload() {
		global $wpdb;
		
		header('Content-Type: application/json');
		
		$me = static::$instance;

		if (empty($_FILES) || $_FILES['file']['error']) {
			$response = array(
				'error' => __('Failed to upload file.', 'gallery_hierarchy')
			);
		} else {
			$dirId = $_REQUEST['dir_id'];

			$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
			$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
			 
			$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
			$filePath = gHpath($me->uploadDir, $fileName);
			 
			 
			// Open temp file
			if (($out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab"))) {
				// Read binary input stream and append it to temp file
				if (($in = @fopen($_FILES['file']['tmp_name'], "rb"))) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else {
					$response = array(
						'error' => __('Failed to open uploaded file.',
								'gallery_hierarchy')
					);
				}
			 
				@fclose($in);
				@fclose($out);
			 
				@unlink($_FILES['file']['tmp_name']);
			} else {
				$response = array(
					'error' => __('Failed to open a temporary file.',
							'gallery_hierarchy')
				);
			}
			 
			 
			// Check if file has been uploaded
			if (!$chunks || $chunk == $chunks - 1) {
				// Strip the temp .part suffix off
				rename("{$filePath}.part", $filePath);

				// @todo Handle completed file
				$response = static::handleFile($dirId, $fileName);
			} else {
				$response = array();
			}
		}

		print json_encode($response);
		exit;
	}

	static protected function handleFile($dirId, $fileName, $level = 1) {
		global $wpdb;
		$me = static::$instance;

		// Get directory path
		if (!($dirPath = $wpdb->get_var('SELECT dir FROM ' . $me->dirTable
				. ' WHERE id = \'' . $dirId . '\''))) {
			// Error - Can't find directory
			return array(
				'error' => __('Could not determine destination folder.',
						'gallery_hierarchy')
			);
		}

		$tmpFile = gHpath($me->uploadDir, $fileName);

		// Check mime type
		switch(finfo_file($me->finfo, $tmpFile)) {
			case 'application/zip':
			case 'application/x-gtar-compressed':
			case 'application/x-tar':
				break;
			case 'image/png':
			case 'image/jpeg':
				// Check if the file already exists
				$endName = $fileName;
				$i = 1;
				while (file_exists(($endPath
						= gHpath($me->imageDir, $dirPath, $endName)))) {
					$nameParts = explode('.', $fileName);
					if (count($nameParts) == 1) {
						$endName = $nameParts[0] . '-'. $i++;
					} else {
						$ext = array_pop($nameParts);
						$endName = join('.', $nameParts) . '-' . $i++ . '.' . $ext;
					}
				}

				$endPath = gHpath($dirPath, $endName);

				// Move the image
				rename($tmpFile,
						gHpath($me->imageDir, $endPath));

				// Register the image
				if (($data = $me->registerImage($endPath))) {
					$data['type'] = 'image';
					$data['path'] = $endPath;
					return array(
						'files' => array($data['id'] => $data)
					);
				} else {

				}

				return false;
		}
	}

	protected function getFolderHierarchy() {
		global $wpdb;

		$folders = $wpdb->get_results('SELECT id, dir FROM ' . $this->dirTable
				. ' ORDER BY dir');

		$hierarchy = array();
		$parents = array();
		$p = 0;
		$current =& $hierarchy;
		$depth = 1;
		$previous = null;

		foreach ($folders as $folder) {
			$path = explode(DIRECTORY_SEPARATOR, $folder->dir);
			$pCount = count($path);

			if ($pCount > $depth) { // Sub-directory
				$parents[$p++] =& $current;
				$previous['sub'] = array();
				$current =& $previous['sub'];
				$depth = $pCount;
			} else if ($pCount < $depth) {
				$current =& $parents[--$p];
				$depth = $pCount;
			}

			$current[$folder->id] = array(
				'id' => $folder->id,
				'name' => $path[$pCount-1]
			);
			$previous =& $current[$folder->id];
		}	

		return $hierarchy;
	}

	protected function getFolderUl($folders = null) {
		$html = '';
		
		if (is_null($folders)) {
			$folders = $this->getFolderHierarchy();
		}

		if ($folders) {
			$html .= '<ul>';
			foreach ($folders as $i => &$folder) {
				$html .= '<li data-id="' . $i . '"><b>' . $folder['name'] . '</b>';
				if (isset($folder['sub'])) {
					$html .= '<span>&hellip;</span> ' . $this->getFolderUl($folder['sub']);
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}

		return $html;
	}

	protected static function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
			$path = $dir . DIRECTORY_SEPARATOR . $file;
      (is_dir($path)) ? static::delTree($path) : unlink($path); 
    } 
    return rmdir($dir); 
  }

	/**
	 * Handle Requests coming from JQuery-folders
	 */
	static function ajaxFolder($return = false, $full = false) {
		global $wpdb;
		$me = static::instance();

		if ($return || !isset($_REQUEST['a'])) {
			$action = false;
		} else {
			$action = $_REQUEST['a'];
		}

		switch($action) {
			case 'create':
				if (!current_user_can('edit_others_pages')) {
					$response = array(
						'error' => __('Permission Denied', 'gallery_hierarchy')
					);
					break;
				}

				if (!isset($_REQUEST['name'])) {
					$response = array(
						'error' => __('Didn\'t receive a name for the new folder',
								'gallery_hierarchy')
					);
					break;
				}

				if (isset($_REQUEST['id'])) {
					if (!gHisInt($_REQUEST['id'])) {
						$response = array(
							'error' => __('Invalid parent directory',
									'gallery_hierarchy')
						);
						break;
					}
					if (!($dirPath = $wpdb->get_var('SELECT dir FROM ' . $me->dirTable
							. ' WHERE id = \'' . $_REQUEST['id'] . '\''))) {
						$response = array(
							'error' => __('Couldn\'t find the parent directory',
									'gallery_hierarchy')
						);
						break;
					}

					$folder = gHpath($dirPath, $_REQUEST['name']);
				} else {
					$folder = gHpath($_REQUEST['name']);
				}

				$fPath = gHpath($me->imageDir, $folder);

				// Check to see if already there
				if (file_exists($fPath)) {
					// error
					$response = array(
						'error' => __('File with that name already exists',
								'gallery_hierarchy')
					);
					break;
				}

				// Create folder
				if (!@mkdir($fPath, 0755, true)) {
					// error
					$response = array(
						'error' => __('Couldn\'t make new folder',
						'gallery_hierarchy')
					);
					break;
				}

				// Register folder
				$id = $me->registerDirectory($folder);

				$response = array(
					'id' => $id
				);

				break;
			case 'delete':
				if (!current_user_can('edit_others_pages')) {
					$response = array(
						'error' => __('Permission Denied', 'gallery_hierarchy')
					);
					break;
				}

				if (!isset($_REQUEST['id']) || !gHisInt($_REQUEST['id'])) {
					$response = array(
						'error' => __('Didn\'t receive a valid folder to delete',
								'gallery_hierarchy')
					);
					break;
				}
			
				// Get dir path
				if (!($dir = $wpdb->get_var('SELECT dir FROM ' . $me->dirTable
						. ' WHERE id = \'' . $_REQUEST['id'] . '\''))) {
					$response = array(
						'error' => __('Couldn\'t find the folder to delete',
								'gallery_hierarchy')
					);
					break;
				}

				// Get sub-directories
				if (($ids = $wpdb->get_col('SELECT id FROM ' . $me->dirTable
						. ' WHERE  dir LIKE \'' . $dir . DIRECTORY_SEPARATOR . '%\''))) {
					$ids[] = $_REQUEST['id'];
					$where = 'IN (' . join($ids, ', ') . ')';
				} else {
					$where = '= ' . $_REQUEST['id'];
				}

				// Physically delete
				$me::delTree(gHPath($me->imageDir, $dir));

				// @todo Delete all thumbnails
				$me->delCacheFiles($dir);

				// Delete images
				$wpdb->query('DELETE FROM ' . $me->imageTable . ' WHERE dir_id '
						. $where);

				$imagesNum = $wpdb->rows_affected;

				// Delete folders
				$wpdb->query('DELETE FROM ' . $me->dirTable . ' WHERE id '
						. $where);

				$foldersNum = $wpdb->rows_affected;

				$response = array(
					'msg' => __("$imagesNum images deleted and $foldersNum folders "
							. "deleted", 'gallery_hierarchy')
				);

				break;
			default:
				$folders = array();
				$folderData = array();

				if ($full) {
					$cmd = 'SELECT dir, id FROM ' . $me->dirTable;
				} else {
					$cmd = 'SELECT d1.dir, d1.id, (SELECT COUNT(*) FROM ' . $me->dirTable
							. ' AS d2 WHERE d2.parent_id = d1.id) as children FROM ' . $me->dirTable
							. ' AS d1 WHERE d1.parent_id ';

					if (isset($_POST['id'])) {
						$cmd .= '= \'' . esc_sql($_POST['id']) . '\'';
					} else {
						$cmd .= 'IS NULL';
					}
				}

				if (($dirData = $wpdb->get_results($cmd, OBJECT_K))) {
					$dirs = array_keys($dirData);
					natcasesort($dirs);

					foreach ($dirs as $d) {
						$name = basename($d);
						$dir = dirname($d);

						$folderData[$d] = array(
							'id' => $dirData[$d]->id,
							'label' => $name
						);

						if ($dirData[$d]->children) {
							$folderData['sub'] = true;
						}

						// Add directory below parent directory if we have it
						if ($dir && isset($folderData[$dir])) {
							if (!isset($folderData[$dir]['sub'])) {
								$folderData[$dir]['sub'] = array();
							}
							$folderData[$dir]['sub'][] =& $folderData[$d];
						} else {
							$folders[] =& $folderData[$d];
						}
					}
				}

				if (!$return) {
					$response = $folders;

					break;
				} else {
					return $folders;
				}
		}
		
		header('Content-Type: application/json');
		print json_encode($response);

		exit;
	}

	protected function echoError($message) {
		echo '<div id="message" class="error">' . $message . '</div>';
	}

	protected function checkFunctions() {
		// Check for database errors
		if ($this->dbErrors) {
			$this->echoError(__('The following errors were encountered:',
				'gallery_hierarchy') . '<br/>' . join('<br/>', $this->dbErrors)
				. '<br/>' . __('Please try deactivating and reactivating the Gallery '
				. 'Hierarchy plugin. If that does not fix the issue, please ',
				'gallery_hierarchy') . '<a target="_blank" '
				. 'href="https://wordpress.org/support/plugin/gallery-hierarchy">'
				. __('report the error', 'gallery_hierarchy') . '</a>.'
				. __('We are very sorry for the inconvenience.', 'gallery_hierarchy'));
				
		}

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
		if (!is_dir($this->uploadDir)) {
			// Try and create the image directory
			if (!mkdir($this->uploadDir, 0755, true)) {
				$this->echoError(__('Could not create upload directory: ',
						'gallery_hierarchy') . $this->uploadDir);
				$this->disable = true;
			}
		}
		// Check upload dir is writable
		if (!is_writable($this->uploadDir)) {
			$this->echoError(__('Need to be able to write to the upload directory',
					'gallery_hierarchy'));
			$this->disable = true;
		}
	}

	/**
	 * Prints the gallery/search HTML
	 */
	function printGallery($insert = false, $error = false) {
		global $wpdb;
		$id = uniqid();
		
		// Get folders first to see if we have anything worth searching
		$images = $wpdb->get_var('SELECT id FROM ' . $this->imageTable
				. ' LIMIT 1');

		if (!$images) {
			echo '<p class="error">'
					. __('No images/folders found. Have you added any?',
					'gallery_hierarchy') . '</p>';
			return;
		}
		
		echo '<h2>' . __('Search Filter', 'gallery_hierarchy') . '</h2>';

		if ($error) {
			$this->echoError($error);
		}

		// Submit form if inserting
		if ($insert) {
			echo '<form id="' . $id . 'form" method="POST">'
					. '<input type="hidden" name="shortcode" id="' . $id . 'input">'
					. '</form>';
		}

		// Folders field
		echo '<div><label for="' . $id . 'folder">' . __('Folders:',
				'gallery_hierarchy') . '</label> '
				. $this->createFolderSelect($id . 'folder', array(
					'multiple' => true,
					'selection' => 'function (files) { gH.changeFolder(\'' . $id
							. '\', files); }'
				)) . '</div>';

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
		echo '<input type="text" name="' . $id . 'comment" id="' . $id. 'comment"></p>';
		
		// Tags field
		echo '<p><label for="' . $id . 'tags">' . __('Has Tags:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'tags" id="' . $id. 'tags"></p>';

		echo '</div>';
	
		// Shortcode builder
		echo '<p><a onclick="gH.toggleBuilder(\'' . $id . '\');" id="' . $id
				. 'builderLabel">' . ($insert ? __('Show shortcode options',
				'gallery_hierarchy') : __('Enable shortcode builder',
				'gallery_hierarchy')) . '</a></p>';
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
		// Shortcode options
		// Include current query in shortcode
		echo '<p><label for="' . $id . 'includeFilter">' . __('Include current '
				. 'filter in shortcode:', 'gallery_hierarchy') . '</label> ';
		echo '<input type="checkbox" name="' . $id . 'includeFilter" id="'
				. $id . 'includeFilter"></p>';
		// Include excluded images
		echo '<p><label for="' . $id . 'include_excluded">' . __('Include '
				. 'excluded images in filter result:', 'gallery_hierarchy')
				. '</label> ';
		echo '<input type="checkbox" name="' . $id . 'include_excluded" id="'
				. $id . 'include_excluded"></p>';
		// Groups option
		echo '<p><label for="' . $id . 'group">' . __('Image Group:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'group" id="' . $id. 'group"></p>';
		// Class option
		echo '<p><label for="' . $id . 'class">' . __('Classes:',
				'gallery_hierarchy') . '</label> ';
		echo '<input type="text" name="' . $id . 'class" id="' . $id. 'class"></p>';


		// Shortcode window
		echo '<p>' . __('Shortcode:', 'gallery_hierarchy') . ' <span id="' . $id
				. 'shortcode"></span></p>';
		echo '</div>';


		echo '<p><a onclick="gH.filter(\'' . $id . '\');" class="button" id="'
				. $id . 'filterButton">' . __('Filter', 'gallery_hierarchy') . '</a> ';
		echo '<a onclick="gH.save(\'' . $id . '\');" class="button disabled" id="'
				. $id . 'saveButton">' . __('Save Image Exclusions', 'gallery_hierarchy')
				. '</a>';
		if ($insert) {
			echo ' <a onclick="gH.insert(\'' . $id . '\');" class="button" id="'
					. $id . 'saveButton">' . __('Insert Images', 'gallery_hierarchy')
					. '</a>';
		}
		echo '</p>';

		// Pagination
		/** @xxx echo '<p class="tablenav"><label for="' . $id . 'limit">' . __('Images per page:',
				'gallery_hierarchy') . '</label> <input type="number" name="' . $id
				. 'limit" id="' . $id. 'limit" onchange="gH.repage(\'' . $id
				. '\');" value="' . static::$settings->num_images . '"><span id="'
				. $id . 'pages" class="tablenav-pages"></span></p>';*/

		// Photo div
		echo '<div id="' . $id . 'pad"></div>';
		/// @todo Add admin_url('admin-ajax.php')
		echo '<script>gH.gallery(\'' . $id . '\', ' . ($insert ? 1 : 0) . ');</script>';
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
	 * Prints the Load/Upload Images page
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

		echo '<h2>' . __('Manually uploaded files into the folder?',
				'gallery_hierarchy') . '</h2>';
		$id = uniqid();
		echo '<div id="' . $id . '"></div>';
		echo '<script>jQuery(gH.scanControl(jQuery(\'#' . $id . '\'), '
			. json_encode(static::getScanStatus()) . '));</script>';

		echo '<h2>' . __('Have images you want to upload now?',
				'gallery_hierarchy') . '</h2>';
		echo '<p>' . __('Choose where you want to upload them and upload them '
				. 'using the form below.', 'gallery_hierarchy') . '</p>';
		$id = uniqid();

		echo '<div class="gHUploader">'
				. '<div>Upload images to '
				. $this->createFolderSelect($id . 'folder', array(
					'selection' => 'function(files) { gH.setUploadDir(\'' . $id
							. '\', files); }',
					'create' => true
				))
				. '</div>'
				. '<div id="' . $id . '"><p>' . __('I\'m sorry. Your browser doesn\'t '
				. 'support any of our file uploaders at the moment. Please let us '
				. 'what browser you are using so we can add support (it may be you '
				. 'don\'t have javascript enabled).', 'gallery_hierarchy')
				. '</p></div>'
				. '</div>'
				. '<script>' . "\n"
				. '(function ($) { $(function() {'
				. 'gH.uploader(\'' . $id . '\', $(\'#' . $id . '\'), {'
				. 'runtimes: \'html5,html4\','
				. 'url: \'' . admin_url('admin-ajax.php?action=gh_upload') . '\','
				. 'dragdrop: true,';

		// Add resize
		if (static::$settings->local_resize) {
			$size = static::$settings->image_size;
			/// @todo Fix problem with WPSettings storing size and [0] and [1]
			echo 'resize: {'
					. 'width: ' . $size['width'] . ','
					. 'height: ' . $size['height'] . ','
					. '},';
		}

		// Add filters
		echo 'filters: {'
			. 'mime_types: ['
			. '{title: "Image Files", extensions: "jpg,jpeg,png"}'
			//@todo . '{title: "Compressed Files", extensions: "zip,tar.gz,tgz"}'
			. '],';
		// Add maximum limit
		if ($limit = static::$settings->size_limit) {
			echo 'max_file_size: "' . $limit . 'mb",';
		}
		echo '},';

		echo '});'
				. '})})(jQuery);'
				. '</script>';
		
		echo '<h3>' . __('Uploaded files',
				'gallery_hierarchy') . '</h3>';
		echo '<div class="gHBrowser" id="' . $id . 'uploaded"></div>';
	}

	/**
	 * Generates and returns the HTML required create a jquery-folders selection
	 * tool.
	 *
	 * @param $id string ID for the jquery-folders HTML object
	 * @param $options array Associative array containing the options to pass
	 *                 to the jquery-folders initiation. Should be quoted if a
	 *                 string.
	 */
	protected $writtenFolderSelectJavascript = false;
	protected function createFolderSelect($id, $options = array()) {
		$full = static::$settings->load_full_tree;

		$folders = static::ajaxFolder(true, $full);
		$options['files'] = $folders;

		$options['ajaxScript'] = '\''
				. admin_url('admin-ajax.php?action=gh_folder') . '\'';
		
		if (($size = static::$settings->upload_chunk_size)) {
			$options['chunk_size'] = "'${size}kb'";
		}

		$parts = array();
		foreach ($options as $o => $v) {
			if (is_array($v)) {
				$parts[] = $o . ': ' . json_encode($v);
			} else {
				$parts[] = $o . ': ' . $v;
			}
		}
		
		$html = '<div id="' . $id . '"></div>'
				. '<script>jQuery(function() {'
				. 'jQuery(\'#' . $id . '\').folders({' . join(', ', $parts) . '})'
				. '})</script>';

		if (!$writtenFolderSelectJavascript) {
			$html .= '<script src="' . plugins_url('/lib/js/folders.js',
					__DIR__) . '"></script>';
					/*. '<link rel="stylesheet" type="text/css" href="'
					. plugins_url('/lib/css/folders.css',
					__DIR__) . '" />'*/
			$writtenFolderSelectJavascript = true;
		}

		return $html;
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
		switch (static::$settings->floater) {
			case 'lightbox':
				$html = ' data-lightbox="' . ($group ? $group : uniqid()) . '"';
				
				if ($caption) {
					$html .= ' data-title="' . $caption . '"';
				}

				break;
			case 'fancybox':
				$html = ' data-fancybox="fancybox" rel="' . ($group ? $group : uniqid())
						. '"';
				
				if ($caption) {
					$html .= ' title="' . $caption . '"';
				}

				break;
			case 'none':
				break;
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
	static function doShortcode($atts, $content = '', $tag = null) {
		global $wpdb;

		if (!$tag) {
			if (!isset($atts['tag'])) {
				return '';
			}
			$tag = $atts['tag'];
		}

		if (!$tag || !in_array($tag, static::$shortcodes)) {
			return '';
		}

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
							if ($part[0] == 'rfolder') {
								$fids = $wpdb->get_col('SELECT id FROM ' . $me->dirTable
										. ' WHERE dir REGEXP CONCAT(\'^\', (SELECT '
										. 'GROUP_CONCAT(dir SEPARATOR \'|\') FROM '
										. $me->dirTable . ' WHERE id IN (' . join(', ', $fids)
										. ')))');
							} else {
								$fids = $wpdb->get_col('SELECT id FROM ' . $me->dirTable
										. ' WHERE id IN (' . join(', ', $fids) . ')');
							}
							
							if ($fids) {
								$folders[] = 'dir_id IN ('
										. join(', ', array_unique($fids)) . ')';
							}
							break;
						case 'taken':
							if (strpos($part[1], '|') !== false) {
								$part[1] = explode('|', $part[1]);

								// Check the dates are valid
								if (!strptime($part[1][0], '%Y-%m-%d %H:%M')) {
									$part[1][0] = false;
								}
								if (!strptime($part[1][1], '%Y-%m-%d %H:%M')) {
									$part[1][1] = false;
								}

								if ($part[1][0] && $part[1][1]) {
									$query[$part[0]] = $part[0] . ' BETWEEN \'' . $part[1][0]
											. '\' AND \'' . $part[1][1] . '\'';
								} else if ($part[1][0]) {
									$query[$part[0]] = $part[0] . ' >= \'' . $part[1][0] . '\'';
								} else if ($part[1][1]) {
									$query[$part[0]] = $part[0] . ' <= \'' . $part[1][1] . '\'';
								}
							} else {
								/// @todo
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
			$w[] = 'f.id IN (' . join(', ', $ids) . ')';
		}

		// Build Folders
		if ($folders) {
			$query['folders'] = '(' . join(' OR ', $folders) . ')';
		}

		// Include excluded


		if ($query) {
			$w[] = '((' . join(') AND (', array_values($query)) . ')' 
			. (isset($atts['include_excluded']) && $atts['include_excluded']
			? '' : ' AND exclude=0') . ')';
		}
		$q = 'SELECT f.*, CONCAT(d.dir, \'/\', f.file) AS path FROM '
				. $me->imageTable . ' AS f JOIN ' . $me->dirTable
				. ' AS d ON d.id = f.dir_id '
				. ($w ? ' WHERE ' . join(' OR ', $w) : '') . ' ORDER BY taken';
		
		if (static::$lp) fwrite(static::$lp, "doShortcode: Command is $q\n");

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
		if (!isset($atts['class']) || static::$settings->get_option($classAO)) {
			if (isset($atts['class']) && $atts['class']) {
				$atts['class'] .= ' ';
			} else {
				$atts['class'] = '';
			}
			$atts['class'] .= static::$settings->get_option($classO);
		}
		
		if (static::$settings->use_included_styles) {
			if (!isset($atts['class'])) {
				$atts['class'] = '';
			}

			$atts['class'] .= ($atts['class'] ? ' ' : '') . 'gh ' . $tag;
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
				if (static::$lp) fwrite(static::$lp, "Using album '$atts[type]' for album");
			case 'ghalbum':
				// `type="<type1>"` - of album (`ghalbum`)
				// Check we have a valid album, if not, use the thumbnail one
				$albums = static::getAlbums();
				if (!isset($atts['type']) || !isset($albums[$atts['type']])) {
					$atts['type'] = static::$settings->thumb_album;
				}

				if (isset($albums[$atts['type']])) {
					$albums[$atts['type']]['class']::enqueue();
					$html = $albums[$atts['type']]['class']::printAlbum($images, $atts);
				}
				break;
		}
		
		return $html;
	}

	/**
	 * Generates shortcode from given attributes.
	 *
	 * @param $atts Array Associative array containing the attriutes specified in
	 *              the shortcode
	 * @return string The generated shortcode.
	 * @retval false Could not generate shortcode
	 */
	 protected function generateShortcode($atts) {
			$filter = array();

			if (static::$lp) fwrite(static::$lp, "generateShortcode received "
					. print_r($atts, 1)); // static::$lp
	
			if (!isset($atts['code'])
						|| !in_array($atts['code'], static::$shortcodes)) {
				return false;
			}

			// Add selected ids
			if (isset($atts['ids'])) {
				$filter = $atts['ids'];
			}

			// Folders
			if (isset($atts['folders'])) {
				$filter[] = 'folder=' . implode('|', $atts['folders']);
			}

			// Check the dates are valid
			if (isset($atts['start'])
					&& !strptime($atts['start'], '%Y-%m-%d %H:%M')) {
				unset($atts['start']);
			}
			if (isset($atts['end'])
					&& !strptime($atts['end'], '%Y-%m-%d %H:%M')) {
				unset($atts['end']);
			}

			if (isset($atts['start']) || isset($atts['end'])) {
				$filter[] = ('taken=' . (isset($atts['start']) ? $atts['start'] : '') . '|'
						. (isset($atts['end']) ? $atts['end'] : ''));
			}

			$parts = array('name', 'title', 'comment', 'tags');
			foreach ($parts as $p) {
				if (isset($atts[$p])) {
					$filter[] = $p . '=' . $atts[$p];
				}
			}

			$others = array('class', 'group', 'include_excluded');
			$params = array();
			foreach ($others as $o) {
				if (isset($atts[$o]) && $atts[$o]) {
					$params[] = $o . '="' . $atts[$o] . '"';
				}
			}

			return '[' . $atts['code'] . ' id="' . implode(',', $filter) . '"'
					. ($params ? ' ' . implode(' ', $params) : '') . ']';
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
	 * @param $dirStatus {string|false} String to use for the directory status,
	 *        or false to keep status the same
	 * @param $imageStatus {string|false} String to use for the image status,
	 *        or false to keep the status the same
	 * @param $files {array} Associative array containing scan file data. Used
	 *        to resume killed scans.
	 */
	static function setScanTransients($dirStatus, $imageStatus, 
			$overallStatus, &$files = null) {
		if ($dirStatus !== false) static::$dirScanStatus = rtrim($dirStatus, '. ');
		if ($imageStatus !== false) static::$imageScanStatus = $imageStatus;
		if ($files) {
			set_transient(static::$filesTransient, json_encode($files));
		}

		$currentStatus = ($overallStatus ? $overallStatus . '. ' : '')
				. (static::$dirScanStatus ? static::$dirScanStatus . '. ' : '')
				. static::$imageScanStatus;

		set_transient(static::$statusTransient, $currentStatus);
		set_transient(static::$statusTimeTransient, time());

		if (static::$lp) fwrite(static::$lp, "Scan status  updated: "
				. "'$currentStatus' " . " at " . time() . "\n"); // static::$lp

		static::$nextSet = time() + static::$statusUpdateTime;
	}

	static protected function getScanStatus() {
		$data = array();
		if (($time = get_transient(static::$scanTransient))) {
			$data['startTime'] = $time;
		}

		if (($status = get_transient(static::$statusTransient))) {
			$data['status'] = $status;
		}

		if (($time = get_transient(static::$statusTimeTransient)) !== false) {
			$data['time'] =  date_i18n( get_option( 'date_format' ) . ' @ '
					. get_option( 'time_format'), $time);
		}

		return $data;
	}

	/**
	 * Checks currently running scan job by checking the scan transient.
	 *
	 * @retval 0 Scan job most likely not running
	 * @retval 1 Scan job most likely running
	 * @retval 2 Scan job most likely killed after running after the max
	 *           execution time.
	 */
	static protected function checkScanJob() {
		// Check to see if a job is already running (the scan transient is set)
		if (($starTime = get_transient(static::$scanTransient))) {
			// Check to see if a while has elapsed since the last running by
			// checking the last update time
			if (($lastTime = get_transient(static::$statusTimeTransient))) {
				if (time() > $lastTime + static::$statusUpdateTimeout) {
					// Job hasn't updated in a while - probably killed
					return 2;
				}
			}

			// Job most likely still running
			return 1;
		}

		// No job running
		return 0;
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

			// Cache directories so can use it to delete images later
			$me->getDirectories();

			switch (static::checkScanJob()) {
				case 0: // None running
				case 2: // Most likely killed
					break;
				case 1: // Still running
					// Clear the cron and reschedule
					wp_clear_scheduled_hook('gh_rescan');
					wp_schedule_single_event(time() + static::$cronRestartTime,
							'gh_rescan');
					return;
			}

			// Set the scan transient to when this execution will be killed by
			set_transient(static::$scanTransient, time());
			set_time_limit(0);

			if ($fullScan ||
					!($files = json_decode(get_transient(static::$filesTransient), true))) {
				static::setScanTransients('', '', __('Scanning folders',
						'gallery_hierarchy'));

				// Find all the folders and images
				$files = $me->scanFolder();
			}

			// Stats
			$files['newDirs'] = 0;
			$files['newImages'] = 0;
			$files['updatedImages'] = 0;
			$files['redoneImages'] = 0;

			static::setScanTransients( 
					__("$files[totalDirs] folders to check.", 'gallery_hierarchy'),
					__("$files[totalImages] images to check.", 'gallery_hierarchy'),
					__('Scanning folders', 'gallery_hierarchy'), $files);

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
					static::setScanTransients(
							__("Added $files[newDirs] new folders. ", 'gallery_hierarchy')
							. count($files['dirs']) . __(' to check.', 'gallery_hierarchy'),
							false, __('Scanning folders', 'gallery_hierarchy'), $files); 
				}
			}

			// Remove any deleted directories
			if (count($dirs)) {
				$files['removedDirs'] = count($dirs);
				$dirIds = array();
				foreach ($dirs as &$dir) {
					array_push($dirIds, $dir->id);
				}
				$wpdb->query('DELETE FROM ' . $me->dirTable
						. ' WHERE id IN (' . join(', ', $dirIds) . ')');
			}

			static::setScanTransients(($files['newDirs'] ?
					__("$files[newDirs] new folders, ", 'gallery_hierarchy') : '')
					. (isset($files['removedDirs']) ? __("$files[removedDirs] folders "
					. "removed.", 'gallery_hierarchy') : ''), false,
					__('Finished scanning folders', 'gallery_hierarchy'), $files);

			// Add images
			
			//Get current images
			$images = $wpdb->get_results('SELECT id,file,dir_id,updated FROM ' 
					. $me->imageTable, OBJECT_K);

			// @todo Could fail within this loop, then we loose the image.
			while(($image = array_shift($files['images'])) !== null) {
				if (static::$lp) fwrite(static::$lp, "Processing image $image\n");

				$iPath = gHpath($me->imageDir, $image);

				if (($id = $me->findImageId($images, $image)) !== false) {
					$updated = phpDate($images[$id]->updated);
					//$updated = $images[$image]->updated;
			
					// Don't bugger round with the timezones, just use utc
					$ftime = gmdate('U', filemtime($iPath));

					if (static::$lp) fwrite(static::$lp, "$iPath: $ftime > $updated?\n");

					if ($ftime > $updated) {
						if (static::$lp) fwrite(static::$lp, "Updating $iPath\n");
						$me->registerImage($image, $id);
						$files['updatedImages']++;
					} else if ($fullScan) {
						if (static::$lp) fwrite(static::$lp, "Redoing $iPath\n");
						$me->registerImage($image, $id, true);
						$files['redoneImages']++;
					} else {
					}
					
					// Remove image from previous images
					unset($images[$id]);
				} else {
					if (static::$lp) fwrite(static::$lp, "Adding $iPath\n");
					$me->registerImage($image);
					$files['newImages']++;
				}

				// Report status
				if (static::$nextSet < time()) {
					static::setScanTransients(false,
							($files['newImages'] ? __("Added $files[newImages] new images. ",
							'gallery_hierarchy') : '')
							. ($files['updatedImages'] ? __("Updated $files[updatedImages] "
							. "images. ",
							'gallery_hierarchy') : '')
							. ($files['redoneImages'] ? __("Redid $files[redoneImages] "
							. "images. ", 'gallery_hierarchy') : '')
							. count($files['images']) . __(' to check.', 'gallery_hierarchy'),
							__('Processing images', 'gallery_hierarchy'), $files);
				}
			}

			if (count($images)) {
				$files['removedImages'] = count($images);
				$imageIds = array();
				foreach ($images as &$image) {
					if (static::$lp) fwrite(static::$lp, "Scan removing "
							. $image->file . " (" . $image->id . ")\n"); // static::$lp

					array_push($imageIds, $image->id);
					// Delete cached files
					if ($path = $me->getDirectory($image->dir_id)) {
						$me->delCacheFiles(gHpath($path, $image->file));
					}
				}
				$wpdb->query('DELETE FROM ' . $me->imageTable
						. ' WHERE id IN (' . join(', ', $imageIds) . ')');
			}

			static::setScanTransients(false,
					($files['newImages'] ? __("$files[newImages] new images ",
					'gallery_hierarchy') : '')
					. ($files['updateImages'] ? __("$files[updatedImages] updated "
					. "images ", 'gallery_hierarchy') : '')
					. ($files['redoneImages'] ? __("$files[redoneImages] readded "
					. "images ", 'gallery_hierarchy') : '')
					. ($files['removedImages'] ? __("$files[removedImages] removed "
					. "images.", 'gallery_hierarchy') : ''),
					__('Finished processing images',
					'gallery_hierarchy'));

			static::setScanTransients(false, false, __('Finished scan',
					'gallery_hierarchy') . ((static::$dirScanStatus
					|| static::$imageScanStatus) ? '' : '. ' . __('No updates',
					'gallery_hierarchy')));

			delete_transient(static::$filesTransient);
			delete_transient(static::$scanTransient);
			wp_clear_scheduled_hook('gh_rescan');
		} catch (Exception $e) {
			static::setScanTransients('', '',__('Error: ',
					'gallery_hierarchy') . $e->getMessage());
			// Clear running transients
			if (static::$lp) fwrite(static::$lp, "Exception caught in scan()\n"
					. $e->getTraceAsString()); // static::$lp
			wp_clear_scheduled_hook('gh_rescan');
			delete_transient(static::$scanTransient);
			throw $e;
		}


		// Delete transient and cron job
		wp_clear_scheduled_hook('gh_rescan');
		delete_transient(static::$scanTransient);
	}

	protected function findImageId(&$images, $imagePath) {
		$file = basename($imagePath);
		$dir = dirname($imagePath);

		if (static::$lp) fwrite(static::$lp, "trying to find $dir $file: ");

		if (($dir = $this->getDirectoryId($dir))) {
			foreach ($images as $id => &$image) {
				if ($image->file == $file && $image->dir_id == $dir) {
					if (static::$lp) fwrite(static::$lp, "Found - $id\n");
					return $id;
				}
			}
		} else {
			if (static::$lp) fwrite(static::$lp, "New directory\n");
		}

		if (static::$lp) fwrite(static::$lp, "Not found\n");
		return false;
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
				static::setScanTransients(__('Scanning Folder. ',
						'gallery_hierarchy') . $files['totalDirs'] . __(' folders found, ',
						'gallery_hierarchy'), $files['totalImages'] . __(' images found.',
						'gallery_hierarchy'));
			}
		}

		return $files;
	}

	/**
	 * Registers a directory in the database. The database is used to generate
	 * directory lists in the interface.
	 * @param $dir string Directory to add to the database
	 * @returns Id of new directory
	 * @retval false If the directory is already in the database
	 */
	protected function registerDirectory($dir) {
		global $wpdb;

		// Remove trailing slash
		gHptrim($dir);

		// Check is a directory
		if (!is_dir(gHpath($this->imageDir, $dir))) {
			throw new InvalidArgumentException($dir . ' is not a valid directory');
		}

		// Check if already in database
		if (!$this->getDirectoryId($dir)) {
			if ($wpdb->insert($this->dirTable, array('dir' => $dir))) {
				$this->directories[$wpdb->insert_id] = $dir;
				return $wpdb->insert_id;
			}
		}

		return false;
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

	protected function delCacheFiles($path) {
		$name = str_replace(DIRECTORY_SEPARATOR, '_', $path);

		/// @todo Temporary
		$name = explode('.', $name);
		array_pop($name);
		$name = join('.', $name);
		

		$glob = gHPath($this->cacheDir, $name . '*');

		$files = glob($glob);
		
		if (static::$lp) fwrite(static::$lp, "Deleting cache files for $path "
				. "with glob '$glob'\n"); // static::$lp
		
		foreach($files as $file){
			if(is_file($file)) {
				unlink($file);
			}
		}
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
		/// @todo Stop removing "extension"
		if (is_string($image)) {
			$file = explode('.', $image);
			$ext = array_pop($file);
			$file = join('.', $file);
		} else {
			/// @todo Find a better way to do this
			$file = explode('.', $image->file);
			$ext = array_pop($file);
			$file = join('.', $file);

			if (($dir = $this->getDirectory($image->dir_id)) !== false) {
				$file = gHpath($dir, $file);
			}
		}

		$name = str_replace(DIRECTORY_SEPARATOR, '_', $file);

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

		if (isset($image->path)) {
			return gHurl($me->imageUrl, $image->path);
		}
		if (($dir = $me->getDirectory($image->dir_id)) !== false) {
			return gHurl($me->imageUrl, $dir, $image->file);
		}

		return null;
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
		$iName = $me->getCImagePath($image, $size);

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
		if (static::$lp) fwrite(static::$lp, "resizeImage called with newSize "
				. "wxh of " . $newSize['width'] // static::$lp
				. "x" . $newSize['height'] . "\n"); // static::$lp
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
			
		if (static::$lp) fwrite(static::$lp, "Have image wxh of " . $cw . "x"
				. $ch . "\n"); // static::$lp

		// First check if we need to do anything
		if ($cw <= $newSize['width'] && $ch <= $newSize['height']) {
			if (static::$lp) fwrite(static::$lp, "image smaller - no need to resize\n");
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

		if (static::$lp) fwrite(static::$lp, "Caculated new image wxh of " 
				. $newSize['width'] . "x" . $newSize['height'] // static::$lp
				. "\n"); // static::$lp


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
	 * @returns The id of the registered image
	 * @retval false Failed to register image
	 */
	protected function registerImage($img, $id = null, $forced = false) {
		global $wpdb;

		$iPath = gHpath($this->imageDir, $img);

		//print "Registering image $iPath\n";

		// Check we have a valid image
		if (!is_file($iPath) || !in_array(finfo_file($this->finfo,
				$iPath), $this->imageMimes)) {
			return; /// @todo Do something worse
		}

		// Create an image (for resizing, rotating and thumbnail)
		$imagick = new Imagick();
		if (!$imagick->readImage($iPath)) {

			if (static::$lp) fwrite(static::$lp, "Failed on reading image " . $iPath
					. "\n"); // static::$lp
			return false;
		}

		// Read metadata from the database
		if ($exif = exif_read_data($iPath, 0)) {
			$changed = false;

			if (!$id || ($id && !$forced)) {
				// Check the orientation
				if (static::$settings->rotate_images) {
					if (isset($exif['Orientation'])) {

						if (static::$lp) fwrite(static::$lp, "Rotating with an "
								. "orientation of '$exif[Orientation]'\n"); // static::$lp

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
						
						if (static::$lp) fwrite(static::$lp, "Flip is '$flip', "
								. "rotate is '$rotate'\n"); // static::$lp

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
		
		if (static::$lp) fwrite(static::$lp, "Got image wxh of " . $width . "x"
				. $height  . "\n"); // static::$lp

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
				. gmdate('Y-m-d H:i:s', time()) . "\n"); // static::$lp


		// Write image to database
		$data = array(
				'file' => basename($img),
				'width' => $width,
				'height' => $height,
				'updated' => gmdate('Y-m-d H:i:s', time())
		);
		
		// Build file and dir
		if (($dirId = $this->getDirectoryId(dirname($img)))) {
			$data['dir_id'] = $dirId;
		} else {
			// @todo Folder does not exist....? Could move directory registration
			// to here...
		}
		
		if ($taken) $data['taken'] = $taken;
		if ($title) $data['title'] = $title;
		if ($comment) $data['comment'] = $comment;
		if ($tags) $data['tags'] = $tags;
		//if ($) $data[''] = $;

		if ($id) {
			$wpdb->update($this->imageTable, $data, array('id' => $id));
			return $data;
		} else {
			$wpdb->insert($this->imageTable, $data);
			$data['id'] = $wpdb->insert_id;
			return $data;
		}

		return false;
	}

	/**
	 * Get the directory id of given a directory path if it is in the database
	 *
	 * @param $dir <num> Path of the directory to return the id of
	 * @retval false Directory id does not exist
	 */
	protected function getDirectoryId($dir) {
		$this->getDirectories();

		return array_search($dir, $this->directories);
	}

	/**
	 * Get the directory path of given a directory id
	 *
	 * @param $id <num> Id of the directory to return the path of
	 * @retval false Directory id does not exist
	 */
	protected function getDirectory($id) {
		$this->getDirectories();
		
		if (!isset($this->directories[$id])) {
			return false;
		} else {
			return $this->directories[$id];
		}
	}

	/**
	 * Retrieves the directories collection and stores into a cache
	 */
	protected function &getDirectories() {
		global $wpdb;
		
		if ($this->directories === false) {
			$dirObjects = $wpdb->get_results('SELECT id, dir FROM '
					. $this->dirTable, OBJECT_K);
			if ($dirObjects) {
				$this->directories = array();

				foreach ($dirObjects as &$dir) {
					$this->directories[$dir->id] = $dir->dir;
				}
			}
		}
		
		return $this->directories;
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

		// If don't have a version number, assume that it is a really old version
		if (!$current) {
			$current = 1;
		}

		if ($current < 2) {
			/**
			 * Update version 2 - changing added field to updated and adding
			 * ON UPDATE CURRENT_TIMESTAMP to updated
			 */
			// Get columns of current table
			$cols = static::getTableColumns($me->imageTable);

			// Add columns if they don't exist
			if (isset($cols['added']) && !isset($cols['updated'])) {
				$wpdb->query('ALTER TABLE ' . $me->imageTable . ' CHANGE added '
						. 'updated timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'');
			}

			static::$settings->db_version = 2;
		}

		if ($current < 3) {
			/**
			 * Update version 3 - changing image path storage method - splitting
			 * folder path from the file name and either storing the folder in
			 * the image (default) or the id of the folder. Can be changed by setting
			 * the 
			 */
			// Get columns of current table
			$cols = static::getTableColumns($me->imageTable);

			// Add columns if they don't exist
			if (!isset($cols['dir_id'])) {
				if ($wpdb->query('ALTER TABLE ' . $me->imageTable . ' ADD dir_id '
						. static::$imageTableFields['fields']['dir_id']) === false) {
		 			array_push($me->dbErrors, 'Error adding the dir_id column to the '
							. 'image table: ' . $wpdb->last_error . ' (SQL was: '
							. $wpdb->last_query . ')');
					return;
	 			}
			}

			// Redo all image file and directory fields
			if (($folders = $me->getDirectories())) {
				// Get all the images
				if ($images = $wpdb->get_results('SELECT id, file, dir_id FROM '
						. $me->imageTable, ARRAY_A)) {

					$s = DIRECTORY_SEPARATOR;
					foreach ($images as &$image) {
						$newData = array();
						// Check if we have a valid folder
						if (($ref = array_search(dirname($image['file']), $folders)) !== false) {
							$newData = array(
								'dir_id' => $ref,
								'file' => basename($image['file'])
							);
						} else {
							trigger_error('Unknown folder ' . dirname($image['file'])
									. ' for image ' . $image['file']);
						}

						$parts = array();
						foreach ($newData as $n => $v) {
							if (is_null($v)) {
								array_push($parts, $n . ' = NULL');
							} else {
								array_push($parts, $n . ' = \'' . esc_sql($v)
										. '\'');
							}
						}

						// @todo Test with using $wpdb->update - will need to check if can
						// handle NULL values
						$cmd = 'UPDATE ' . $me->imageTable . ' SET ' . join($parts, ', ')
								. ' WHERE id = \'' . $image['id'] . '\'';
						$wpdb->query($cmd);
					}
				} else if ($wpdb->last_error) {
		 			array_push($me->dbErrors, 'Error getting the current images: '
							. $wpdb->last_error . ' (SQL was: '
							. $wpdb->last_query . ')');
					return;
				}
			}

			static::$settings->db_version = 3;
		}

		if ($current < 4) {
			/**
			 * Update version 4 - added the parent field to the directory table so
			 * that the folder hierarchy can be looked up a little easier/faster
			 */
			// Get columns of current table
			$cols = static::getTableColumns($me->dirTable);

			// Add parent_id column if they don't exist
			if (!isset($cols['parent_id'])) {
				if ($wpdb->query('ALTER TABLE ' . $me->dirTable . ' ADD parent_id '
						. static::$dirTableFields['fields']['parent_id']) === false) {
		 			array_push($me->dbErrors, 'Error adding the parent_id column to the '
							. 'directory table: ' . $wpdb->last_error . ' (SQL was: '
							. $wpdb->last_query . ')');
					return;
				}
			}

			// Get folders
			if (($folders = $wpdb->get_results('SELECT dir, id FROM '
					. $me->dirTable, OBJECT_K))) {
				foreach ($folders as $f => $folder) {
					if (($pFolder = dirname($folder->dir))) {
						if (isset($folders[$pFolder]) && ($id = $folders[$pFolder]->id)) {
							$wpdb->update($me->dirTable, array('parent_id' => $id),
									array('id' => $folder->id));
						}
					}
				}
			} else if ($wpdb->last_error) {
				array_push($me->dbErrors, 'Error getting the current directories: '
						. $wpdb->last_error . ' (SQL was: '
						. $wpdb->last_query . ')');
				return;
			}

			static::$settings->db_version = 4;
		}
					
		
		// Update to current version
		static::$settings->db_version = static::$dbVersion;
	}

	static protected function getTableColumns($table) {
		global $wpdb;
		
		return $wpdb->get_results('SHOW COLUMNS from ' . $table, OBJECT_K);
	}

	static function installDatabase() {
		global $wpdb;

		$me = static::instance();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$sql = $me->buildTableSql($me->dirTable, static::$dirTableFields);
		dbDelta($sql);

		$sql = $me->buildTableSql($me->imageTable,
				static::$imageTableFields);
		dbDelta($sql);

		static::$settings->db_version = static::$dbVersion;
	}

	/**
	 * Builds an SQL statement for creating a table.
	 * @param $table string Name of the table.
	 * @param $options array Associative array containing fields and indexes
	 *        array (
	 *          'fields' => array(
	 *            'id' => 'smallint(5) NOT NULL AUTO_INCREMENT',
	 *            'file' => 'text NOT NULL',
	 *          ),
	 *          'indexes' => array(
	 *            array('type' => 'PRIMARY', 'field' => 'id'),
	 *          )
	 *        )        
	 */
	protected function buildTableSql($table, $options) {
		global $wpdb;

		/*
		 * We'll set the default character set and collation for this table.
		 * If we don't do this, some characters could end up being converted 
		 * to just ?'s when saved in our table.
		 */
		$charset_collate = '';

		if (!isset($options['fields'])) {
			return false;
		}

		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}
		$sql = 'CREATE TABLE ' . $table . " ( \n";

		foreach ($options['fields'] as $f => &$field) {
			$sql .= $f . ' ' . $field . ", \n";
		}

		if (isset($options['indexes'])) {
			$indexes = array();
			foreach ($options['indexes'] as &$index) {
				if (!isset($index['type'])) {
					$index['type'] = false;
				}
				$i = '';
				switch ($index['type']) {
					case 'PRIMARY':
						array_push($indexes, 'PRIMARY KEY  (' . $index['field'] . ")");
						break;
					case 'UNIQUE':
						$i = 'UNIQUE ';
					case false:
						array_push($indexes, $i . 'KEY ' 
								. (isset($index['name']) ? $index['name'] : $index['field'])
								. ' (' . $index['field'] . ")");
						break;
				}

			}
			
			$sql .= join($indexes, ", \n");
		}
		
		$sql .= "\n) " . $charset_collate . ';';

		if (static::$lp) fwrite(static::$lp, "buildTableSQL made SQL statement "
				. "$sql"); // static::$lp

		return $sql;
	}

	/**
	 * @see GHAlbum::printAlbum()
	 */
	static function printStyle() {
?>
/* Float left easy class */
.gh.right {
	float: right;
}

/* Float right easy class */
.gh.left {
	float: left;
}

/* Style the image caption */
.gh a span {
	display:block;
	font-size: 12px;
	font-style: italic;
	text-align: center;
}

/* Style ghimage shortcode images */
.gh.ghimage img {
	max-width: 100%;
	height: auto;
}

<?php
	}
}
