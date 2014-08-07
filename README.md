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
  (shortcut for `ghalbum type="thumbnail"`)
- `ghimage` - for a single image

#### Shortcode Attributes

The following shortcode attributes are available for the included shortcodes
- `id="<id1>,<id2>,..."` - list of photos (some sort of query or list - see
  Image Selection below (`ghalbum` `ghthumbnail` `ghimage`)
- `group="<group1>"` - id for linking photos to scroll through with lightbox
  (`ghthumbnail` `ghimage`)
- `class="<class1> <class2> ...` - additional classes to put on the images
  (`ghthumbnail` `ghimage`)
- `caption="(none|title|comment)"` - Type of caption to show. Default set in
  plugin options (`ghalbum` `ghthumbnail` `ghimage`)
- `popup_caption="(none|title|comment)"` - Type of caption to show on popup.
  Default set in plugin options (`ghalbum` `ghthumbnail` `ghimage`)
- `link="(none|popup|<url>)"` - URL link on image, by default it will be the
  image url and will cause a lightbox popup
- `size="(<width>x<height>)"` - size of image (`ghimage`)
- `type="<type1>"` - of album (`ghalbum`)

Examples:

`[ghalbum id="123,145" group="1" caption="comment" link="http://www.example.com"]`

Will produce a album showing two images, 123 & 145, with the comment below.
When the user clicks on the images, they will be taken to www.example.com.

`[ghimage id="123" class="float-left" link="none"]`

Will produce a full-sized image with the float-left class on it and no url
link.

### Image Selection

Gallery Hierarchy allows you to select individual and/or use filters to select
the images you want to include. The image selection string (the id attribute)
should be a comma-separated list of image ids and filters.

The following filters are available:
- `folder:<id1>|<id2>` - Specifies what folders to look in by their ids,
  separated with `|`
- `rfolder:<id1>|<id2>` - Specifies what folders and their sub-directories to
  look in by their ids, separated with `|`
- `taken:<date1>-<date2>` - Specifies when the image was taken/created.
  `<date1>` specifies after what date the photo should have been taken.
  `<date1>` specifies before what date the photo should have been taken.
  Either date can be ommited. The dates must be in the format
  `YYYY-MM-DD HH:MM:SS`. The time can be ommited.
- `tags:` - Specifies what tags the images should have (see below)
- `title:` - Specifies what words should be in the image titles (see below)
- `comment:` - Specifies what words should be in the image comments (see below)

The `tags:`, `title:` and `date` can contain logic and grouping of tags/words.
`|` specifies OR, `&` specifies AND, and `(` `)` can be used to group logic.
For example:

`tags:home|(travel&new zealand)` will select images that have either the `home`
tag or both the `travel` and `new zealand` tags.

### Options
- Location of image folder
- Location of cache image folder
- Folders to image tags
- Enable resizing of images
- Number of images to display per page in the image gallery/search
- Add the title to the start of the comment
- What is displayed by default below the images (per type)

## Future Features (Todos)
- Upload interface
- Google Drive syncing

## Changelog


