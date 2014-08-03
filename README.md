wp-gallery-hierarchy
====================

By [Meld Computer Engineering](http://www.meldce.com)

[Support opensource development](https://pledgie.com/campaigns/17426)

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
- Cached thumbnails and different sized versions of an image
- AJAX-based image gallery/search
  - Easy image selection
    - Selection based on either manually selecting images or filtering
    - Images can be manually selected across multiple pages
  - One interface for browsing/inserting images
- Lightbox for larger image popup

### Auto Image Tagging
If enabled, when an image is added to the database, the name of each folder
that the image is in will be added to the tags of the image in the database.
Folders can be excluded by adding a '-' to the start of the name.

### Shortcodes
The plugin has the following shortcodes:
- `ghalbum` - for fancy albums
- `ghthumbnail` - for single or multiple thumbnails
- `ghimage` - for a single image

#### Shortcode Options
The following shortcode options are available for the included shortcodes
- `id` - list of photos (some sort of query or list) (`ghalbum` `ghthumbnail` `ghimage`)
- `group` - id for linking photos to scroll through with lightbox (`ghthumbnail` `ghimage`)
- `class` - additional classes to put on the images (`ghthumbnail` `ghimage`)
- `caption` - show caption (`ghalbum` `ghthumbnail` `ghimage`)
- `size` - size of image (`ghimage`)
- `type` - of album (`ghalbum`)

### Options
- Location of image folder
- Location of cache image folder
- Folders to image tags
- Enable resizing of images
- Number of images to display per page in the image gallery/search
- Add the title to the start of the comment
- What is displayed by default below the images (per type)

## Installation
This project uses [Lightbox](https://github.com/lokesh/lightbox2.git) and
[wp-settings](https://github.com/weldstudio/wp-settings) as a Git submodules.
To initialise them, you will need to run the command
`git submodule update --init`
from the cloned directory.

## Future Features (Todos)
https://github.com/weldstudio/wp-gallery-hierarchy/issues

## Changelog
https://github.com/weldstudio/wp-gallery-hierarchy/releases
