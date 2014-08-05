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

	add_action('gh_rescan', array('GHierarchy', 'scan'));
	add_action('plugins_loaded', gHierarchySetup);

	register_activation_hook(__FILE__, array('GHierarchy', 'install'));
}
