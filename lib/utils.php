<?php

/**
 * Builds a file path with the appropriate directory separator.
 * @param $segments,... string Unlimited number of path segments
 * @return string Path
 * @see http://php.net/manual/en/dir.constants.php
 */
function gHpath() {
	return join(DIRECTORY_SEPARATOR, func_get_args());
}

/**
 * Removes any trailing directory separator from a path
 * @param $path string Path to remove trailing directory separators from
 * @return string Path with separators removed
 */
function gHptrim($path, $ltrim = false) {
	if ($ltrim) {
		return trim($path, DIRECTORY_SEPARATOR);
	} else {
		return rtrim($path, DIRECTORY_SEPARATOR);
	}
}

?>
