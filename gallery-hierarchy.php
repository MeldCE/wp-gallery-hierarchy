<?php

/**
 * Plugin Name: Gallery Hierarchy
 * Plugin URI: http://github.com/weldstudio/wp-gallery-hierarchy
 * Description: A simple image gallery where images are stored in hierarchical folders
 * Author: Meld Computer Engineering
 * Author URI: http://www.meldce.com
 * Version: 0.1
 */

if (!function_exists('gHierarchySetup')) {
	require_once('lib/GHierarchy.php');

	function gHierarchySetup() {
		add_shortcode('ghalbum', array('GHierarchy', 'albumShortcode'));
		add_shortcode('ghthumbnail', array('GHierarchy', 'thumbnailShortcode'));
		add_shortcode('ghpicture', array('GHierarchy', 'pictureShortcode'));

		if (is_admin()) {
			// Initialise
			add_action('init', array('GHierarchy', 'init'));
			
			// Handle AJAX requests (from image browser)
			add_action('wp_ajax_gHierarchy', array('GHierarchy', 'ajax'));

		}
	}

	function testgh() {
		set_transient('gHScanTran', 'HELO', 60);
	}

	add_action('gh_rescan', array('GHierarchy', 'scan'));
	add_action('plugins_loaded', gHierarchySetup);
}
