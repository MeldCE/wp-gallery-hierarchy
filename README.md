wp-gallery-hierarchy
====================

## Introduction

Being super tired of the short-comings of all the gallery plugins in Wordpress that I have tried and looked at, I have decided to create my own that suit my needs and maybe it will yours as well.

Images are stored in hierarchical folders. The plugin will scan the directory (given in an option) and add the photos, including all EXIF data to it's database and create a thumbmail (optional). It can also turn the folder names into keywords to be added to the photo (see Options below)

Once loaded, images can be search through by dates, keywords, folders, or search terms. You can then insert thumbnail, single images, or fancier albums into posts and pages either using your search query, or a single or a list of id(s).

This plugin contains no styling itself, so it is up to your or your theme to do it.

## Shortcodes

The plugin has the following shortcodes and options:
- `album` - for fancy albums
 - `type` - of album
 - `images` - list of photos (some sort of query or list)
 - `group` - id for linking photos to scroll through with lightbox
- `thumbnail` - for single or multiple thumbnails
 - `images` list of photos (some sort of query or list)
 - `class` - additional classes to put on thumbnails
 - `caption` - show caption
 - `group` - id for linking photos to scroll through with lightbox
- `picture` - for a single picture
 - `image` - which image to show
 - `size` - size of image
 - `class` - additional classes to put on thumbnails
 - `caption` - show caption
 - `group` - id for linking photos to scroll through with lightbox

## Options

The following plugin options are avaiable:
 - Folder - folder where the images are kept
 - Cache Folder - folder where the thumbnails and cached photos are kept
 - Thumbnail Size - thumbnail size
 - Crop Thumbnails - whether or not to make thumbnails the exact thumbnail size
 - Concatenate - Concatenate the image title onto the image description
