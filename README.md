[Gallery Hierarchy (gallery-hierarchy)](http://www.meldce.com/gallery-hierarchy)
====================
A simple image gallery where images are stored in hierarchical folders.

## Description
By [Meld Computer Engineering](http://www.meldce.com)

Support this opensource development [Paypal](http://gift.meldce.com) [Gittip](http://gittip.meldce.com)

Being super tired of the limitations of the gallery plugins in Wordpress
that I have tried and looked at, I decided to create my own that suits my
needs and maybe it will yours as well.

Images are stored in hierarchical folders, as you normally would. To add new
images, you can either put them directly into the folders or upload them 
through the web interface (Todo). If loaded directly into the folder, a
rescan must be initiated to find the new images.

When new images are loaded, the plugin can rotate and resize according to the
EXIF data. An image title, comment and tags will also be extracted from the
image's EXIF and XMP data. The folder names can also be added as tags to the
images - useful, for example, if you are organising the images into locations.

Once loaded, images can be searched by dates, tags, folders, or
search terms. You can then insert thumbnails, single images, or fancier albums
into posts and pages either using your search query, a single id or a list of
ids.

This plugin contains no styling for the inserted images, so it is up to you or
your theme to style them how you want.

## Features
- Hierarchical storage of images
- Ability to rescan the folders for new images or upload through a web
  interface
- Automatic resizing and rotating of images on addition to the database
- Auto tagging based on folder hierarchy
- Caches thumbnails and different sized versions of an image
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
- `id="<id1>,<id2>,..."` - list of images (some sort of query or list - see
  Image Selection below (`ghalbum` `ghthumbnail` `ghimage`)
<!---
- `limit="<num>" - limit the number of images to <num>
  (`ghalbum` `ghthumbnail` `ghimage`)
- `sort=""` - way the images should be sorted
--->
- `group="<group1>"` - id for linking images to scroll through with lightbox
  (`ghthumbnail` `ghimage`)
- `class="<class1> <class2> ...` - additional classes to put on the images
  (`ghthumbnail` `ghimage`)
- `caption="[<id>[,<id>[,...]]:](none|title|comment|'<caption>')[;...]"` - Type of caption
  to show on/below the image. Default set in plugin options (`ghalbum`
	`ghthumbnail` `ghimage`)
- `popup_caption="[<id>[,<id>[,...]]:](none|title|comment|'<caption>')[;...]"` - Type
  of caption to show on popup. Default set in plugin options (`ghalbum`
	`ghthumbnail` `ghimage`)
- `info="[<id>[,<id>[,...]]:](none|(title|comment|...)+)[;...]"` - information
  (image metadata) to include when image is displayed. Possible options are
	`caption`, `title`, `comment`, `date`, `tags`, or any of the custom metadata
	fields. Mulitple information can be specified by using a comma `,`. A value
	of `none` will display no information.
- `link="[<id>[,<id>[,...]]:](none|popup|[<post_id>]|'<url>')[;...]"` - URL link on image,
  by default it will be the image url and will cause a lightbox popup
- `width="<width>"` - width of image/album
- `height="<height>"` - height of image/album
- `size="[<width>][x<height>]"` - size of image/album
- `type="<type1>"` - of album (`ghalbum`)
- `include_excluded="(0|1)" - include excluded images in the query result.
  Default is to not (`ghalbum` `ghthumbnail` `ghimage`)

Examples:

`[ghalbum id="123,145" group="1" caption="comment" link="http://www.example.com"]`

Will produce an album showing two images, 123 & 145, with the images' comments below.
When the user clicks on the images, they will be taken to www.example.com.

`[ghimage id="123" class="float-left" link="none"]`

Will produce a full-sized image with the float-left class on it and no url
link.

### Image Selection

Gallery Hierarchy allows you to select individual images and/or use filters to select
the images you want to include. The image selection string (the id attribute)
should be a comma-separated list of image ids and filters.

The following filters are available:
- `folder=<id1>|<id2>` - Specifies what folders to look in by their ids,
  separated with `|`
- `rfolder=<id1>|<id2>` - Specifies what folders and their sub-directories to
  look in by their ids, separated with `|`
- `taken=<date1>|<date2>` - Specifies when the image was taken/created.
  `<date1>` specifies after what date the photo should have been taken.
  `<date1>` specifies before what date the photo should have been taken.
  Either date can be omitted. The dates must be in the format
  `YYYY-MM-DD HH:MM:SS`. The time can be omitted. If only a date is given, only
  images taken/created on that day will be selected. For example,
  `taken=|2012-03-12` will match all images taken/created before the 12th of
  March, 2012.	`taken=2012-03-12 12:00:00|` will match all images
  taken/created after midday on that day. `taken=2012-03-12|2012-03-17` will
  match all images taken between the 12th and the 17th of March, 2012.
- `tags=` - Specifies what tags the images should have (see below)
- `title=` - Specifies what words should be in the image titles (see below)
- `comment=` - Specifies what words should be in the image comments (see below)

The `tags=`, `title=` and `comment=` can contain logic and grouping of tags/words.
`|` specifies OR, `&` specifies AND, and `(` `)` can be used to group logic.
For example:

`tags=home|(travel&new zealand)` will select images that have either the `home`
tag or both the `travel` and `new zealand` tags.

### Options
- Location of images folder
- Location of cached images folder
- Enable folder names to be added to image tags
- Enable resizing of images
- Number of images to display per page in the image gallery/search
- Add the title to the start of the comment
- What is displayed by default below the images (per type)

## Installation
Installation is easy as installing and activating the plugin from
Wordpress.com.

Once the plugin is activated in Wordpress, a Gallery Hierarchy menu will
appear in the dashboard. Select the Gallery Hierarchy menu and go to options.
Ensure the options are correct for your setup, including the folder from which
to retrieve images (by default, it is `wp-content/gHImages`).

### Loading Images
Images can be loaded by going to Load Images in the Gallery Hierarchy menu.
You can either scan for photos already on your site in a specific folder, or
upload them through the upload interface.

To scan for images in the image directory (by default `wp-content/gHImages`),
click on the Rescan button to start a scan to find your images. The scan uses
AJAX requests to start the scanning, so scan status updates should start
appearing. Once the scan is complete, you will be able
to view your images and use a shortcode to insert them into posts and pages.

### Viewing Images
You can view images, by clicking on Gallery Hierarchy in the menu. This will
take you to the Image Gallery with searching and filtering. From there, you can
find images, by changing any of the filtering criteria, including which
folder you want the images from, when the images were created, what tags
the images have and more. When you have adjusted the filter as you want it,
click the Filter button. You images should appear below.

#### Shortcode Generator
The Image Gallery contains a shortcode builder to convert the filter you used
and any images you selected into a shortcode. When you click on the Enable
Shortcode Builder link, the generate shortcode will be displayed along with a
select box so you can change what type of shortcode is generated.

#### Selecting Images
If you just want to insert specific images into a post or page, you can select
them by clicking the green tick on the bottom right of the image. Once selected
you can change which order they will be displayed by using the up and down
arrows on the top right of the image. The number between the arrows is the
position the image will be displayed.

#### Excluding Images
You can set images to not be included by default in galleries by clicking on
the red X on the bottom left of the image. Be sure to click the Save button to
save any changed exclusion settings.

### Including Image in Your Posts and Pages
You can include images in your posts and pages by either manually inserting a
shortcode or by using the Insert Media dialog:
- Click on the `Add Media` button just above the main text editor. This will
  open the Insert Media dialog.
- Click on the `Gallery Hierarchy` link to in the left hand column of the
  Insert Media dialog. This will bring up the Gallery Hierarchy image browser.
- Configure any or none of the filters to select certain images, including what
  folders you want images to be displayed from, and click the `Filter` button.
	This will retrieve the images you selected.
- If you want to insert all the images you selected, simply click the `Insert
  Images` button.
- If you want to use only certain images from the ones shown, mouse over the
  images you want to use and a green circle with a plus in it will be displayed
	on the bottom right of the image. Click this to select the image for use.
- Once you have selected all the images you want to use (you can select from
  multiple pages and from multiple filters, you can preview all the images
	that are selected by clicking on the `Show shortcode options` link, above the
	`Filter` button and clicking the `Show currently selected link`.
- You can rearrange your selection by clicking on the left and right buttons
  on the top right of every selected image - the number displays what position
	the image will be displayed.
- To change how the images will be displayed, use the options displayed
  when you click on the `Show shortcode options` link.

### Custom Albums
Custom albums can be created by implementing the GHAlbum interface. The
specification for the interface can be found in the
`gallery-hierarchy/lib/GHAlbum.php` file.

## Releases
https://github.com/MeldCE/wp-gallery-hierarchy/releases

### v0.2.0
New version, lots of fixed features including:

-ability to upload photos via the interface
-reworked browser and folder selection
-lots of bug fixes
-improved user interface

The version now allows you to select between lightbox and fancybox, however
the version on the Wordpress plugins site does not include the lightbox as
it is under the CC license, so you must either download lightbox separately
and install it manually, or install this plugin from Github.

### v0.1.3
Major bugfixes done to the scanner and some improvements made. Note that this
release includes lightbox2, which is under a CC license. For Wordpress
releases see -fancybox releases.

### v0.1.2
Fixed some bugs in the gallery and added some nice to haves to make it easier
to use.

### v0.1-beta
Current capabilities are:

Scanning the image folder for new images
Browsing/searching images using the image browser
Generating a shortcode (with minimal attributes) with the image browser
Current albums are:

Thumbnail - Simple album for displaying a single or group of thumbnails.
Labelling as pre-release just there is a bug that I haven't managed to see
during my testing.

There are two flavours:

-One using lightbox2 (has a Creative Commons licence)
-One using fancybox (MIT licence - capable with Wordpress licence)

Eventually, there will be one flavour once I have sorted out a nice lightbox
that is GPL/MIT.

## Future Features (Todos)
https://github.com/MeldCE/wp-gallery-hierarchy/issues
