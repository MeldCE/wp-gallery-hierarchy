var gulp = require('gulp');
var path = require('path');
var rename = require('gulp-rename');

var fs = require('fs');

var uglify = require('gulp-uglify');
var cssMinify = require('gulp-mini-css');
var lessCss = require('gulp-less');
var jsValidate = require('gulp-jsvalidate');
var cssValidate = require('gulp-css-validator');
var insert = require('gulp-insert');

var package = require('./package.json');

var paths = {
	build: 'build/gallery-hierarchy',
	ext: {},
	int: ['readme.txt', 'README.md', 'LICENSE', 'lib/GHierarchy.php', 'lib/GHAlbum.php', 'lib/utils.php'],
	albumsSrc: 'albums/*.php',
	main: 'gallery-hierarchy.php',
	jsSrc: 'js/ghierarchy.js',
	cssSrc: 'css/ghierarchy.css'
};
//paths.albums = path.join(paths.build, 'albums');
paths.js = path.join(paths.build, 'js');
paths.css = path.join(paths.build, 'css');

// WP Settings
paths.ext.wpsettings = 'lib/wp-settings/{LICENSE,README.md,WPSettings.php}';

// JQuery Multiselect
paths.ext.multiselect = 'lib/jquery-ui-multiselect/{src/{jquery.multiselect.filter.min.js,jquery.multiselect.min.js},i18n/*,jquery.multiselect.css,jquery.multiselect.filter.css}';

// JQuery Timepicker
paths.ext.timepicker = 'lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.{js,css}';

// Lightbox 2
paths.ext.lightbox = 'lib/lightbox2/{img/*.{png,gif},css/*,js/lightbox.min.js}';

// Jquery UI
paths.ext.ui = 'css/jquery-ui/{images/*,jquery-ui.min.css,jquery-ui.structure.min.css,jquery-ui.theme.min.css}';


gulp.task('markupMainPhp', [], function() {
			return gulp.src(paths.main)
					.pipe(insert.prepend('<?php\n/**\n'
							+ 'Plugin Name: ' + package.title + '\n'
							+ 'Plugin URI: ' + package.homepage + '\n'
							+ 'Version: ' + package.version + '\n'
							+ 'Description: ' + package.description + '\n'
							+ 'Author: ' + package.author.name + '\n'
							+ 'Author URI: ' + package.author.url + '\n'
							+ 'Text Domain: gallery-hierarchy\n'
							+ 'Tags: ' + package.keywords.join(',') + '\n'
							+ 'Licence: ' + package.licence + '\n'
							+ '*/\n?>\n'
					))
					.pipe(gulp.dest(paths.build));
		});

gulp.task('validateJs', [], function() {
			return gulp.src(paths.jsSrc)
					.pipe(jsValidate());
		});

gulp.task('js', ['validateJs'], function() {
			return gulp.src(paths.jsSrc)
					.pipe(uglify({
						preserveComments: 'some'
					}))
					.pipe(rename(function (path) {
								path.extname = '.min.js'
							}))
					.pipe(gulp.dest(paths.js));
		});

/** Can't use as running on less files
gulp.task('validateCss', [], function() {
			return gulp.src(paths.cssFiles)
					.pipe(cssValidate());
		});
*/

gulp.task('css', [], function() {
			return gulp.src(paths.cssSrc)
					.pipe(lessCss())
					.pipe(rename(function (path) {
								path.extname = '.css'
							}))
					.pipe(gulp.dest(paths.css))
					.pipe(cssMinify())
					.pipe(rename(function (path) {
								path.extname = '.min.css'
							}))
					.pipe(gulp.dest(paths.css));
		});

gulp.task('intFiles', [], function() {
			return gulp.src(paths.int, {base: './'})
					.pipe(gulp.dest(paths.build));
		});

gulp.task('albumFiles', [], function() {
			return gulp.src(paths.albumsSrc, {base: './'})
					.pipe(gulp.dest(paths.build));
		});

gulp.task('html', [], function() {
		});

for (e in paths.ext) {
	console.log('Got ' + e + ' > ' + paths.ext[e]);
	gulp.task(e, [], (function(e) { return function() {
				return gulp.src(paths.ext[e], {base: './'})
						.pipe(gulp.dest(paths.build));
			}})(e));
}

gulp.task('watch', function() {
			gulp.watch(paths.main, ['markupMainPhp']);
			gulp.watch(paths.jsFiles, ['js']);
			gulp.watch(paths.cssFiles, ['css']);
			gulp.watch(paths.int, ['intFiles']);
			gulp.watch(paths.albumsSrc, ['albumFiles']);
			for (e in paths.ext) {
				gulp.watch(paths.ext[e], [e]);
			}
		});

gulp.task('default', ['markupMainPhp', 'css', 'js', 'intFiles', 'albumFiles', 'watch'].concat(Object.keys(paths.ext)));
