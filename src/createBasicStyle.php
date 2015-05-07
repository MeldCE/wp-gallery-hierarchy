<?php
require_once("lib/utils.php");
require_once("lib/GHierarchy.php");
require_once("lib/GHAlbum.php");

gHIncludeFiles('albums/');

GHierarchy::printStyle();

foreach(get_declared_classes() as $class) {
	if( in_array('GHAlbum', class_implements($class)) ) {
		echo "\n/* " . $class::label() . " classes */\n";
		$class::printStyle();
	}
}

?>
