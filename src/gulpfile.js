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
var shell = require('gulp-shell');
var inlining = require('gulp-inlining-node-require');
//var concat = require('gulp-concat');
var package = require('./package.json');
//var watchify = require('gulp-watchify');
//var streamify = require('gulp-streamify');
var include = require('../../gulp-include');
var spawn = require('child_process').spawn;
var stripLine  = require('gulp-strip-line');
var tar = require('gulp-tar');
var gzip = require('gulp-gzip');
var symlink = require('gulp-symlink');

var paths = {
	dist: '../dist/',
	build: '../build/',
	ext: {},
	int: ['readme.txt', 'README.md', 'LICENSE', 'lib/GHierarchy.php', 'lib/GHAlbum.php', 'lib/utils.php'],
	albumsSrc: 'albums/*.php',
	main: 'gallery-hierarchy.php',
	//jsSrc: '{js/ghierarchy.js,albums/js/*.js}',
	jsSrc: ['js/ghierarchy.js', 'albums/js/*.js'],
	cssSrc: 'css/{jquery.plupload.queue.css,ghierarchy.less}',
	basicStylesScript: 'createBasicStyle.php',
	basicSrc: 'js/basicStyle.css'
};
//paths.albums = path.join(paths.dist, 'albums');
paths.allJsSrc = include.files(paths.jsSrc);
console.log(paths.allJsSrc);
paths.js = path.join(paths.dist, 'js');
paths.css = path.join(paths.dist, 'css');
paths.builtJsDir = path.join(paths.build, 'js');
paths.builtCssDir = path.join(paths.build, 'css');
paths.builtCss = path.join(paths.builtCssDir, '*.css');
if (paths.jsSrc instanceof Array) {
	paths.builtJs = [];
	var i;
	for (i in paths.jsSrc) {
		paths.builtJs.push(path.join(paths.build, paths.jsSrc[i]));
	}
} else {
	paths.builtJs = path.join(paths.build, paths.jsSrc);
}

// WP Settings
paths.ext.wpsettings = {
	js: 'lib/wp-settings/js/wpsettings.min.js',
	css: 'lib/wp-settings/css/wpsettings.min.css',
	lib: 'lib/wp-settings/WPSettings.php'
};

// JQuery Timepicker
paths.ext.timepicker = {
	js: 'lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.js',
	css: 'lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.css'
};

// Lightbox 2
paths.ext.lightbox = {
	js: 'lib/lightbox2/js/lightbox.min.{js,map}',
	css: 'lib/lightbox2/css/*',
	imgs: 'lib/lightbox2/img/*.{png,gif}',
};

// Fancybox
paths.ext.fancybox = {
	js: 'lib/fancybox/{jquery.fancybox-1.3.4.pack.js,jquery.mousewheel-3.0.4.pack.js}',
	css: 'lib/fancybox/{blank.gif,fancybox{,-{x,y}}.png,jquery.fancybox-1.3.4.css}',
//	imgs: 'lib/lightbox2/img/*.{png,gif}',
};

// Jquery UI
paths.ext.ui = {
	css: 'css/jquery-ui/{jquery-ui.min.css,jquery-ui.structure.min.css,jquery-ui.theme.min.css}',
	imgs: 'css/jquery-ui/images/*'
};

// Jquery Folders
paths.ext.hierarchySelect = {
	js: 'lib/jquery-hierarchy-select/dist/js/folders.js',
	css: 'lib/jquery-hierarchy-select/dist/css/folders.css'
};

// PLupload
paths.ext.plupload = {
	i18n: 'lib/plupload/js/i18n/*',
	js: 'lib/plupload/js/{moxie.min.js,plupload.full.min.js,jquery.plupload.queue/jquery.plupload.queue.min.js}',
	css: 'css/jquery.plupload.queue.css'
};

// Packery
paths.ext.plupload = {
	js: 'lib/packery/dist/packery.pkgd.min.js'
};

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
					.pipe(gulp.dest(paths.dist));
		});

gulp.task('auto-reload', function() {
	var process;

	function restart() {
		if (process) {
			process.kill();
		}

		process = spawn('gulp', ['default'], {stdio: 'inherit'});
	}

	gulp.watch('gulpfile.js', restart);
	restart();
});

gulp.task('validateJs', [], function() {
	return gulp.src(paths.allJsSrc)
			.pipe(jsValidate());
});

gulp.task('js', ['validateJs'], function() {
	return gulp.src(paths.jsSrc)
			.pipe(include())
			.pipe(gulp.dest(paths.js))
			.pipe(uglify({
				preserveComments: 'some'
			}))
			.pipe(rename(function (path) {
						path.extname = '.min.js'
					}))
			.pipe(gulp.dest(paths.js));
});

gulp.task('validateCss', ['buildCss'], function() {
			return gulp.src(paths.builtCss)
					.pipe(cssValidate());
		});

gulp.task('buildCss', [], function() {
			return gulp.src(paths.cssSrc)
					.pipe(lessCss())
					.pipe(gulp.dest(paths.builtCssDir));
		});

//gulp.task('css', ['validateCss'], function() {
gulp.task('css', ['buildCss'], function() {
			return gulp.src(paths.builtCss)
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

gulp.task('createBasicStyle', [], shell.task([
			'php ' + paths.basicStylesScript + ' > ' + paths.basicSrc
		]));

gulp.task('basicStyle', ['createBasicStyle'], function() {
			return gulp.src(paths.basicSrc)
					.pipe(cssMinify())
					.pipe(rename(function (path) {
								path.extname = '.min.css'
							}))
					.pipe(gulp.dest(paths.css));
		});

gulp.task('intFiles', [], function() {
			return gulp.src(paths.int, {base: './'})
					.pipe(gulp.dest(paths.dist));
		});

gulp.task('albumFiles', [], function() {
			return gulp.src(paths.albumsSrc, {base: './'})
					.pipe(gulp.dest(paths.dist));
		});

gulp.task('html', [], function() {
		});

gulp.task('package', ['markupMainPhp', 'css', 'js', 'intFiles', 'albumFiles', 'basicStyle'], function() {
	// Removed $lp lines
	var file = path.join(paths.dist, 'lib/GHierarchy.php');
	
	shell.task('sed -ie \'/static::$lp/d\' ' + file);

	//gulp.src(path.join(paths.dist, 'lib/GHierarchy.php'))
	//		.pipe(stripLine([/static::\$lp/]))
	//		.pipe(gulp.dest(path.join(paths.dist, 'lib')));

	//gulp.src(paths.dist)
	//		.pipe(symlink('../gallery-hierarchy'));

	console.log('cd .. && tar -czf ' + package.name + '-' + package.version
			+ '.tgz gallery-hierarchy');
	shell.task('tar -czf ' + package.name + '-' + package.version
			+ '.tgz gallery-hierarchy', {cwd: '../'});
	shell.task('zip ' + package.name + '-' + package.version
			+ '.zip gallery-hierarchy', {cwd: '../'});
	shell.task('pwd');
	shell.task('cd .. && pwd');
});

for (e in paths.ext) {
	gulp.task(e, [], (function(e) { return function() {
		var p;
		for (p in paths.ext[e]) {
			gulp.src(paths.ext[e][p], {base: './'})
					.pipe(rename({dirname: path.join('lib', p)}))
					.pipe(gulp.dest(paths.dist));
		}
	}})(e));
}

gulp.task('watch', function() {
			gulp.watch(paths.main, ['markupMainPhp']);
			gulp.watch(paths.allJsSrc, ['js']);
			gulp.watch(paths.cssSrc, ['css']);
			gulp.watch(paths.basicStylesScript, ['basicStyle']);
			gulp.watch(paths.int, ['intFiles']);
			gulp.watch(paths.albumsSrc, ['albumFiles', 'basicStyle']);
			for (e in paths.ext) {
				var f, files = [];
				for (f in paths.ext[e]) {
					files.push(paths.ext[e][f]);
				}
				gulp.watch(files, [e]);
			}
		});

gulp.task('default', ['markupMainPhp', 'css', 'js', 'intFiles', 'albumFiles', 'basicStyle', 'watch'].concat(Object.keys(paths.ext)));
