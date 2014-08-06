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

/**
 * Converts a datetime of a given format into a datetime that can be inserted
 * into MySQL, because I am yet to find anything that can do it, even though
 * you would thinnk it would be a standard thing to do!
 *
 * @param $date string String containing the datetime you want to convert
 * @param $format string Format of datetime like used by strptime
 * @return string Valid MySQL datetime format
 * @retval false You stuffed up
 */
function mysqlDate($date, $format) {
	$date = strptime($date, $format);
	$date = mktime($date['tm_hour'], $date['tm_min'], $date['tm_sec'],
			$date['tm_mon']+1, $date['tm_mday'], $date['tm_year'] + 1900);
	return gmdate('Y-m-d H:i:s', $date);
}

/**
 * Converts a MySQL into a timestamp
 *
 * @param $date string String containing datetime in MySQL formate
 * @return int Timestamp
 * @retval false You stuffed up
 */
function phpDate($date) {
	return strtotime($time);
}
?>
