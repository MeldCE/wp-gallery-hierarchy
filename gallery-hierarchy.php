<?php

/**
 * Plugin Name: Gallery Hierarchy
 * Plugin URI: http://github.com/weldstudio/wp-gallery-hierarchy
 * Description: A simple image gallery where images are stored in hierarchical folders
 * Author: Meld Computer Engineering
 * Author URI: http://www.meldce.com
 * Version: 0.1
 */

if (!class_exists('GHierarchy')) {
	require_once('lib/GHierarchy.php');
	require_once('lib/GHAlbum.php');

	/**
	 * Scans through a given directory and includes all php files with a given
	 * extension
	 */
	function gHIncludeFiles ($path, $extension='.php') {
		$files = scandir($path);
		$extLength = strlen($extension);

		foreach ($files as $include) {
			if (strpos($include, '.') !== 0) { // ignores dotfiles and self/parent directories
				if (is_dir($include)) { // if a directory, iterate into
					$newPath = $currentPath . $include . '/';
					includeFiles($path, $extension);
				} else {
					if (!$extLength || substr($include, -$extLength) === $extension) {
						try {
							require_once($path.$include);
						} catch (Exception $e) { // Silently fail if the import fails
						}
					}
				}
			}
		}
	}

	function gHierarchySetup() {
		add_shortcode('ghalbum', array('GHierarchy', 'albumShortcode'));
		add_shortcode('ghthumbnail', array('GHierarchy', 'thumbnailShortcode'));
		add_shortcode('ghpicture', array('GHierarchy', 'pictureShortcode'));

		if (is_admin()) {
			// Initialise
			add_action('init', array('GHierarchy', 'adminInit'));
			
			// Handle AJAX requests (from image browser)
			add_action('wp_ajax_gHierarchy', array('GHierarchy', 'ajax'));

		}
	}

	gHIncludeFiles(plugin_dir_path(__FILE__) . 'albums/');

	add_action('gh_rescan', array('GHierarchy', 'scan'));
	add_action('plugins_loaded', 'gHierarchySetup');

	/// @todo Add a hook for plugin deletion
	register_activation_hook(__FILE__, array('GHierarchy', 'install'));

	// Shortcodes
	add_shortcode('ghalbum', array('GHierarchy', 'doShortcode'));
	add_shortcode('ghthumb', array('GHierarchy', 'doShortcode'));
	add_shortcode('ghimage', array('GHierarchy', 'doShortcode'));
}
