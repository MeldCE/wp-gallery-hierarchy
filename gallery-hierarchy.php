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
		// Include so we have access to is_plugin_active
		//if (!is_admin()) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		//}

		if (is_plugin_active('gallery-hierarchy/gallery-hierarchy.php')) {
			// Shortcodes
			add_shortcode('ghalbum', array('GHierarchy', 'doShortcode'));
			add_shortcode('ghthumb', array('GHierarchy', 'doShortcode'));
			add_shortcode('ghimage', array('GHierarchy', 'doShortcode'));
			
			// Include album files
			gHIncludeFiles(plugin_dir_path(__FILE__) . 'albums/');

			// Action for rescan job
			add_action('gh_rescan', array('GHierarchy', 'scan'));

			add_action('wp_enqueue_scripts', array('GHierarchy', 'enqueue'));
			add_action('admin_enqueue_scripts', array('GHierarchy', 'adminEnqueue'));

			/// @todo Add a hook for plugin deletion
			register_activation_hook(__FILE__, array('GHierarchy', 'install'));
		
			if (is_admin()) {
				// Initialise
				add_action('init', array('GHierarchy', 'adminInit'));
				
				// Handle AJAX requests (from image browser)
				add_action('wp_ajax_gHierarchy', array('GHierarchy', 'ajax'));
			}
		}

	}

	add_action('plugins_loaded', 'gHierarchySetup');
}
