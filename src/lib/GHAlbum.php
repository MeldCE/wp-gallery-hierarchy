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
	 * Returns an associative array of attribute and descriptions of attributes
	 * specific to this album. Is used to generate options on the gallery
	 * builder. Each attribute key should have an array value containing the
	 * type, a label and a description.
	 */
	static function attributes();

	/**
	 * Generates the album for the given images and options. The options will
	 * be the options built from the options in the shortcode and the defaults
	 * set in the plugin options.
	 * @internal Used to generate the builtin.css file.
	 *
	 * @param $images array Array containing the image *objects* (resulting from
	 *                      a call to $wpdb->get_results(..., OBJECT_K). The
	 *                      order of the array should be the order in which the
	 *                      images are placed
	 * @param $options array Array containing the options built for the shortcode
	 * @return string The HTML for the album
	 */
	static function printAlbum(&$images, &$options);

	/**
	 * Prints the CSS used to style the album when the option include inbuilt
	 * style is selected. It should try and use unique classnames that should
	 * be included by printAlbum only when the option is selected.
	 * @see GHierarchy::printStyle For the generic styles that can be reused.
	 */
	static function printStyle();
}
