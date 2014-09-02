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
	require_once('lib/utils.php');

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

			add_action('wp_enqueue_scripts', array('GHierarchy', 'enqueue'));
			add_action('admin_enqueue_scripts', array('GHierarchy', 'adminEnqueue'));
			
			// Handle AJAX requests (from image browser)
			add_action('wp_ajax_gh_gallery', array('GHierarchy', 'ajaxGallery'));
			add_action('wp_ajax_gh_save', array('GHierarchy', 'ajaxSave'));
		
			if (is_admin()) {
				// Initialise
				add_action('init', array('GHierarchy', 'adminInit'));
			}
		}
	}

	add_action('plugins_loaded', 'gHierarchySetup');

	// Action for rescan job
	add_action('gh_rescan', array('GHierarchy', 'scan'));

	/// @todo Add a hook for plugin deletion
	register_activation_hook(__FILE__, array('GHierarchy', 'install'));
}
