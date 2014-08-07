<?php
interface GHAlbum {
	/**
	 * Returns the label of the type of Album, which will be used to
	 * specify that this album should be used.
	 * The name should be simple, lowercase and contain only letters
	 * and underscores.
	 *
	 * @return string Label of album type
	 */
	static function label();

	/**
	 * Returns the name of the type of Album, used in the pluin interface.
	 * The name should be passed through the __() function for
	 * internationalisation.
	 *
	 * @return string Name of album type
	 */
	static function name();
	
	/**
	 * Returns the description of the type of Album, used in the pluin interface.
	 * The description should include things like what the album is going to look
	 * like and how the user interacts with it.
	 * The description should be passed through the __() function for
	 * internationalisation.
	 *
	 * @return string Description of album type
	 */
	static function description();

	/**
	 * Called to enqueue any scripts or stylesheets required by the album.
	 * Should use the Wordpress function ______ to enqueue the scripts.
	 */
	static function enqueue();

	/**
	 * Prints the album for the given images and options. The options will
	 * be the options built from the options in the shortcode and the defaults
	 * set in the plugin options.
	 *
	 * @param $images array Array containing the image *objects* (resulting from
	 *                      a call to $wpdb->get_results(..., OBJECT_K). The
	 *                      order of the array should be the order in which the
	 *                      images are placed
	 * @param $options array Array containing the options built for the shortcode
	 */
	 static function print();
}
