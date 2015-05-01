== Description ==

By [Meld Computer Engineering](http://www.meldce.com)

Support this opensource development [Paypal](http://gift.meldce.com) [Gittip](http://gittip.meldce.com)

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

== Features ==

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

= Auto Image Tagging =
If enabled, when an image is added to the database, the name of each folder
that the image is in will be added to the tags of the image in the database.
Folders can be excluded by adding a '-' to the start of the name.

= Shortcodes =
The plugin has the following shortcodes:

- `ghalbum` - for fancy albums
- `ghthumbnail` - for single or multiple thumbnails
  (shortcut for `ghalbum type="thumbnail"`)
- `ghimage` - for a single image

= Shortcode Attributes =

The following shortcode attributes are available for the included shortcodes

- `id="<id1>,<id2>,..."` - list of images (some sort of query or list - see
  Image Selection below (`ghalbum` `ghthumbnail` `ghimage`)
- `group="<group1>"` - id for linking images to scroll through with lightbox
  (`ghthumbnail` `ghimage`)
- `class="<class1> <class2> ...` - additional classes to put on the images
  (`ghthumbnail` `ghimage`)
- `caption="(none|title|comment)"` - Type of caption to show. Default set in
  plugin options (`ghalbum` `ghthumbnail` `ghimage`)
- `popup_caption="(none|title|comment)"` - Type of caption to show on popup.
  Default set in plugin options (`ghalbum` `ghthumbnail` `ghimage`)
- `link="(none|popup|<url>)"` - URL link on image, by default it will be the
  image url and will cause a lightbox popup
- `size="<width>x<height>"` - size of image (`ghimage`)
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

= Image Selection =

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

= Options =

- Location of images folder
- Location of cached images folder
- Enable folder names to be added to image tags
- Enable resizing of images
- Number of images to display per page in the image gallery/search
- Add the title to the start of the comment
- What is displayed by default below the images (per type)

== Installation == 

1. Activate the plugin through the 'Plugins' menu in WordPress

== Use ==
Once the plugin is activated in Wordpress, a Gallery Hierarchy menu will
appear in the dashboard. Select the Gallery Hierarchy menu and go to options.
Ensure the options are correct for your setup, including the folder from which
to retrieve images. The current version does not have the capability of
uploading images, so image will have to be manually uploaded. Don't worry,
this feature is on its way.

= Loading Images =
Images can be loaded by going to Load Images in the Gallery Hierarchy menu.
Click on the Rescan button to start a scan to find your images in the
configured directory. The scan uses wp-cron to start the scan process. You may
need to visit your site before the cron kicks off (make sure you are not
getting a cache version of your site by holding down the SHIFT key and pressing
the refresh button). Once the scan is underway, status updates will be
displayed on the Load Image page. Once the scan is complete, you will be able
to view your images and use a shortcode to insert them into posts and pages.

= Viewing Images =
You can view images, by clicking on Gallery Hierarchy in the menu. This will
take you to the Image Gallery with searching and filtering. From there, you can
find images, by changing any of the filtering criteria, including which
folder you want the images from, when the images were created, what tags
the images have and more. When you have adjusted the filter as you want it,
click the Filter button. You images should appear below.

= Shortcode Generator =
The Image Gallery contains a shortcode builder to convert the filter you used
and any images you selected into a shortcode. When you click on the Enable
Shortcode Builder link, the generate shortcode will be displayed along with a
select box so you can change what type of shortcode is generated.

= Selecting Images =
If you just want to insert specific images into a post or page, you can select
them by clicking the green tick on the bottom right of the image. Once selected
you can change which order they will be displayed by using the up and down
arrows on the top right of the image. The number between the arrows is the
position the image will be displayed.

= Excluding Images =
You can set images to not be included by default in galleries by clicking on
the red X on the bottom left of the image. Be sure to click the Save button to
save any changed exclusion settings.

= Including Image in Your Posts and Pages =
Currently, the only way to include images in your posts and pages is to either
manually generate a shortcode, or use the shortcode builder in the Image
Gallery. Don't worry, in the not too distant future, you will be able to do
this from the edit page.

== Custom Albums ==
Custom albums can be created by implementing the GHAlbum interface. The
specification for the interface can be found in the
`gallery-hierarchy/lib/GHAlbum.php` file.

== Future Features (Todos) ==
https://github.com/MeldCE/wp-gallery-hierarchy/issues

== Screenshots ==

== Frequently Asked Questions ==

== Upgrade Notice ==

== Changelog ==
https://github.com/MeldCE/wp-gallery-hierarchy/releases
