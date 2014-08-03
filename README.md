wp-gallery-hierarchy
====================

## Introduction

Being super tired of the short-comings of all the gallery plugins in Wordpress
that I have tried and looked at, I have decided to create my own that suit my
needs and maybe it will yours as well.

Images are stored in hierarchical folders, as you normally would. To add new
images, you can either put them directly into the folders or upload them 
through the web interface (Todo). If loaded directly into the folder, an
rescan must be initiated to find the new images.

When new images are loaded, the plugin can rotate and resize according to the
EXIF gallery. An image title, comment and tags will also be extracted from the
EXIF data. The folders can also be added to the tags of the images, useful for
if you are organising the images into locations.

Once loaded, images can be search through by dates, keywords, folders, or
search terms. You can then insert thumbnail, single images, or fancier albums
into posts and pages either using your search query, or a single or a list of
id(s).

This plugin contains no styling for the inserted images, so it is up to you or
your theme to style them how you want.

## Features
- Hierarchical storage of images
- Ability to rescan the folders for new images or upload through a web
  interface
- Automatic resizing and rotating of images on addition to the database
- Auto tagging based on folder hierarchy
- AJAX-based image gallery/search

### Auto Image Tagging
If enabled, when an image is added to the database, the name of each folder
that the image is in will be added to the tags of the image in the database.
Folders can be excluded by adding a '-' to the start of the name.

### Shortcodes
The plugin has the following shortcodes:
- `ghalbum` - for fancy albums
- `ghthumbnail` - for single or multiple thumbnails
- `ghimage` - for a single image

